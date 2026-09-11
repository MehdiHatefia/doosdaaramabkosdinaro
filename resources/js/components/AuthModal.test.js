import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AuthModal from './AuthModal.vue';

const { sendOtp, verifyOtp, completeRegistration } = vi.hoisted(() => ({
    sendOtp: vi.fn(),
    verifyOtp: vi.fn(),
    completeRegistration: vi.fn(),
}));
vi.mock('../services', () => ({ authService: { sendOtp, verifyOtp, completeRegistration } }));
vi.mock('../stores/useAuthStore', () => ({ useAuthStore: () => ({ setSession: vi.fn() }) }));

describe('AuthModal', () => {
    beforeEach(() => vi.clearAllMocks());

    it('moves to the code step after sending a valid mobile number', async () => {
        sendOtp.mockResolvedValue({ message: 'ok' });
        const wrapper = mount(AuthModal, { props: { visible: true } });

        await wrapper.find('#auth-mobile').setValue('09123456789');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(sendOtp).toHaveBeenCalledWith('09123456789');
        expect(wrapper.text()).toContain('کد پنج رقمی');
        expect(wrapper.find('.otp-input').exists()).toBe(true);
    });

    it('starts the two-minute countdown on the code step', async () => {
        sendOtp.mockResolvedValue({ message: 'ok' });
        const wrapper = mount(AuthModal, { props: { visible: true } });

        await wrapper.find('#auth-mobile').setValue('09123456789');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(wrapper.find('.resend-row').text()).toContain('02:00');
        expect(wrapper.find('.resend-row button').element.disabled).toBe(true);
    });

    it('accepts Persian digits and sends normalized mobile numbers', async () => {
        sendOtp.mockResolvedValue({ message: 'ok' });
        const wrapper = mount(AuthModal, { props: { visible: true } });

        await wrapper.find('#auth-mobile').setValue('۰۹۱۲۳۴۵۶۷۸۹');
        expect(wrapper.find('.auth-submit').element.disabled).toBe(false);
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(sendOtp).toHaveBeenCalledWith('09123456789');
    });

    it('authenticates a new user and exposes the new-user flag', async () => {
        sendOtp.mockResolvedValue({ message: 'ok' });
        verifyOtp.mockResolvedValue({ is_new_user: true, token: 'token', user: { id: 2, mobile: '09123456789' } });
        const wrapper = mount(AuthModal, { props: { visible: true } });

        await wrapper.find('#auth-mobile').setValue('09123456789');
        await wrapper.find('form').trigger('submit');
        await flushPromises();
        await wrapper.find('.otp-input').setValue('12345');
        await flushPromises();

        expect(wrapper.find('#auth-name').exists()).toBe(false);
        expect(wrapper.emitted('authenticated')).toHaveLength(1);
        expect(wrapper.emitted('authenticated')[0][0]).toEqual({ user: { id: 2, mobile: '09123456789' }, is_new_user: true });
    });
});