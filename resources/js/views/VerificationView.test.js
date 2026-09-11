import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import PrimeVue from 'primevue/config';
import VerificationView from './VerificationView.vue';
import { authService } from '../services';

vi.mock('../services', () => ({
    authService: {
        getKycStatus: vi.fn(),
        getKycHistory: vi.fn(),
        getKycDetails: vi.fn(),
        getKycDocument: vi.fn(),
        submitKyc: vi.fn(),
    },
}));

describe('VerificationView', () => {
    beforeEach(() => {
        authService.getKycStatus.mockResolvedValue({ status: 'not_submitted', data: null });
        authService.getKycHistory.mockResolvedValue({ data: [] });
        authService.getKycDetails.mockResolvedValue({ data: null });
        authService.getKycDocument.mockResolvedValue('blob:document');
    });

    it('shows the complete submission form for a user without a verification record', async () => {
        const wrapper = mount(VerificationView, { global: { plugins: [PrimeVue] } });
        await vi.waitUntil(() => !wrapper.find('.verification-loading').exists());

        expect(wrapper.find('.verification-form').exists()).toBe(true);
        expect(wrapper.find('.verification-status--pending').exists()).toBe(false);
        expect(wrapper.text()).toContain('شماره سریال پشت کارت ملی');
        expect(wrapper.text()).toContain('ادامه');
    });

    it('renders history statuses and enables resubmission only for the latest rejection', async () => {
        authService.getKycStatus.mockResolvedValue({ status: 'rejected', data: { status: 'rejected', rejection_reason: 'مدرک ناخوانا' } });
        authService.getKycHistory.mockResolvedValue({ data: [
            { id: 2, status: 'rejected', submitted_at: '1405/06/20 10:00', rejection_reason: 'مدرک ناخوانا' },
            { id: 1, status: 'pending', submitted_at: '1405/06/19 10:00', rejection_reason: null },
        ] });
        const wrapper = mount(VerificationView, { global: { plugins: [PrimeVue] } });
        await vi.waitUntil(() => !wrapper.find('.verification-loading').exists());

        expect(wrapper.find('.p-tag-danger').exists()).toBe(true);
        expect(wrapper.find('.p-tag-warn').exists()).toBe(true);
        expect(wrapper.find('.history-action').exists()).toBe(true);
        expect(wrapper.text()).toContain('مدرک ناخوانا');
    });

    it('shows document details action and opens the preview dialog for pending rows', async () => {
        authService.getKycStatus.mockResolvedValue({ status: 'pending', data: { status: 'pending' } });
        authService.getKycHistory.mockResolvedValue({ data: [{ id: 7, status: 'pending', submitted_at: '1405/06/20 10:00' }] });
        authService.getKycDetails.mockResolvedValue({ data: {
            id: 7, status: 'pending', submitted_at: '1405/06/20 10:00', national_code: '0012345678', home_phone: '02112345678', postal_address: 'تهران، خیابان نمونه', iban: 'IR123456789012345678901234',
            documents: { national_card_front: 'blob:front', national_card_back: null },
        } });
        const wrapper = mount(VerificationView, { global: { plugins: [PrimeVue] } });
        await vi.waitUntil(() => !wrapper.find('.verification-loading').exists());

        const viewButton = wrapper.find('.history-action--secondary');
        expect(viewButton.exists()).toBe(true);
        expect(viewButton.find('.pi-eye').exists()).toBe(true);
        await viewButton.trigger('click');
        await vi.waitFor(() => expect(authService.getKycDetails).toHaveBeenCalledWith(7));
        expect(document.body.textContent).toContain('کد ملی');
        expect(document.body.textContent).toContain('جزئیات مدارک ارسالی');
        expect(document.body.querySelector('.kyc-dialog-mask')).not.toBeNull();
        expect(document.body.querySelector('.kyc-details-dialog__root')).not.toBeNull();
        await document.body.querySelector('.details-close').click();
        await vi.waitFor(() => expect(wrapper.vm.detailsVisible).toBe(false));
    });
});
