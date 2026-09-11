import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import { createPinia } from 'pinia';
import ProfileView from './ProfileView.vue';
import { authService, locationService } from '../services';

Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: () => ({ matches: false, addListener: vi.fn(), removeListener: vi.fn(), addEventListener: vi.fn(), removeEventListener: vi.fn() }),
});

vi.mock('../services', () => ({
    authService: {
        getProfile: vi.fn(),
        updateProfile: vi.fn(),
        uploadAvatar: vi.fn(),
        getAvatarPreview: vi.fn(),
    },
    locationService: {
        getProvinces: vi.fn(),
        getCities: vi.fn(),
    },
}));

describe('ProfileView', () => {
    beforeEach(() => {
        localStorage.clear();
        authService.getProfile.mockResolvedValue({ user: { id: 1, mobile: '09120000000', name: 'کاربر تایید شده', national_code: '0012345678', is_verified: true, show_phone_publicly: true } });
        authService.getAvatarPreview.mockResolvedValue('blob:avatar');
        locationService.getProvinces.mockResolvedValue({ data: [{ id: 1, name: 'استان تست', children: [{ id: 2, name: 'شهر تست' }] }] });
    });

    it('renders profile fields and locks verified identity fields', async () => {
        const wrapper = mount(ProfileView, { global: { plugins: [PrimeVue, ToastService, createPinia()] } });
        await vi.waitUntil(() => !wrapper.find('.module-loading').exists());

        expect(wrapper.find('input[disabled][readonly]').exists()).toBe(true);
        expect(wrapper.find('input[disabled][readonly]').element.value).toBe('09120000000');
        expect(wrapper.findAll('input[disabled]').length).toBe(3);
        expect(wrapper.text()).toContain('احراز هویت شده');
        expect(wrapper.text()).toContain('شماره شبا جهت تسویه');
        expect(wrapper.text()).toContain('نمایش شماره تماس در آگهی‌ها');
    });

    it('toggles phone visibility between on and off states', async () => {
        const wrapper = mount(ProfileView, { global: { plugins: [PrimeVue, ToastService, createPinia()] } });
        await vi.waitUntil(() => !wrapper.find('.module-loading').exists());

        expect(wrapper.find('.privacy-status').text()).toBe('روشن');
        await wrapper.find('.p-toggleswitch-input').setValue(false);

        expect(wrapper.find('.privacy-status').text()).toBe('خاموش');
        expect(wrapper.find('.p-toggleswitch').classes()).not.toContain('p-toggleswitch-checked');
    });

    it('shows the iban length and removes non-numeric characters', async () => {
        const wrapper = mount(ProfileView, { global: { plugins: [PrimeVue, ToastService, createPinia()] } });
        await vi.waitUntil(() => !wrapper.find('.module-loading').exists());
        const ibanInput = wrapper.find('input[aria-label="شماره شبا"]');

        await ibanInput.setValue('12abc0123456789012345678');

        expect(ibanInput.element.value).toBe('1201 - 2345 - 6789 - 0123 - 4567 - 8');
        expect(wrapper.find('.iban-meta').text()).toContain('۲۴ رقم');
        expect(wrapper.find('.iban-bank').exists()).toBe(false);
    });

    it('uploads an avatar and updates the rendered image and auth state', async () => {
        authService.getProfile.mockResolvedValue({ user: { id: 1, mobile: '09120000000', name: 'کاربر تست', avatar: null, is_verified: false } });
        authService.uploadAvatar.mockResolvedValue({ user: { id: 1, mobile: '09120000000', name: 'کاربر تست', avatar: 'avatars/avatar.jpg', is_verified: false } });
        authService.getAvatarPreview.mockResolvedValue('blob:avatar-new');
        const wrapper = mount(ProfileView, { global: { plugins: [PrimeVue, ToastService, createPinia()] } });
        await vi.waitUntil(() => !wrapper.find('.module-loading').exists());
        const file = new File(['avatar'], 'avatar.jpg', { type: 'image/jpeg' });
        const input = wrapper.find('input[type="file"]');

        Object.defineProperty(input.element, 'files', { value: [file], configurable: true });
        await input.trigger('change');

        expect(authService.uploadAvatar).toHaveBeenCalledWith(file);
        await vi.waitFor(() => expect(wrapper.find('.avatar img').exists()).toBe(true));
        expect(wrapper.find('.avatar img').attributes('src')).toBe('blob:avatar-new');
    });
});
