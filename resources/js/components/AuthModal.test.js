import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AuthModal from './AuthModal.vue';

const { sendOtp, verifyOtp, completeRegistration } = vi.hoisted(() => ({
    sendOtp: vi.fn(),
    verifyOtp: vi.fn(),
    completeRegistration: vi.fn(),
}));
vi.mock('../services', () => ({ authService: { sendOtp, verifyOtp, completeRegistration } }));

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

    it('accepts Persian digits and sends normalized mobile numbers', async () => {
        sendOtp.mockResolvedValue({ message: 'ok' });
        const wrapper = mount(AuthModal, { props: { visible: true } });

        await wrapper.find('#auth-mobile').setValue('۰۹۱۲۳۴۵۶۷۸۹');
        expect(wrapper.find('.auth-submit').element.disabled).toBe(false);
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(sendOtp).toHaveBeenCalledWith('09123456789');
    });

    it('asks new users for details before authenticating them', async () => {
        sendOtp.mockResolvedValue({ message: 'ok' });
        verifyOtp.mockResolvedValue({ registration_required: true, mobile: '09123456789' });
        completeRegistration.mockResolvedValue({ token: 'token', user: { id: 2 } });
        const wrapper = mount(AuthModal, { props: { visible: true } });

        await wrapper.find('#auth-mobile').setValue('09123456789');
        await wrapper.find('form').trigger('submit');
        await flushPromises();
        await wrapper.find('.otp-input').setValue('12345');
        await flushPromises();

        expect(wrapper.find('#auth-name').exists()).toBe(true);
        await wrapper.find('#auth-name').setValue('کاربر جدید');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(completeRegistration).toHaveBeenCalledWith({
            mobile: '09123456789',
            code: '12345',
            name: 'کاربر جدید',
            email: null,
        });
        expect(wrapper.emitted('authenticated')).toHaveLength(1);
    });
});