import { flushPromises, mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import UserDashboardView from './UserDashboardView.vue';

const { fetchAds } = vi.hoisted(() => ({ fetchAds: vi.fn() }));
vi.mock('../stores/useUserAds', () => ({ useUserAds: () => ({ ads: [
    { id: 1, status: 'approved', type: 'supply', title: 'امتیاز وام رسالت', bank: 'بانک رسالت', bank_plan: { title: 'طرح مرآت' }, amount: 300, price: 18, city: 'تهران', province: 'تهران' },
    { id: 2, status: 'pending', type: 'demand', title: 'تقاضای امتیاز وام', bank: 'بانک ملی', amount: 500, price: 28, city: 'مشهد', province: 'خراسان رضوی' },
], loading: false, fetchAds }) }));
vi.mock('../stores/useAuthStore', () => ({ useAuthStore: () => ({ user: { id: 1, name: 'کاربر تست', mobile: '09120000000', is_verified: true }, syncFromStorage: vi.fn() }) }));
vi.mock('vue-router', () => ({ useRouter: () => ({ push: vi.fn() }) }));

describe('UserDashboardView', () => {
    beforeEach(() => { fetchAds.mockResolvedValue(); });

    it('renders user navigation and verified badge', async () => {
        const wrapper = mount(UserDashboardView, { global: { plugins: [createPinia()] } });
        await flushPromises();

        expect(wrapper.text()).toContain('حساب کاربری من');
        expect(wrapper.text()).toContain('مدیریت آگهی‌ها، نشان‌ها و اطلاعات فردی');
        expect(wrapper.text()).toContain('همه');
        expect(wrapper.text()).toContain('منتشر شده');
        expect(wrapper.text()).toContain('امتیاز وام رسالت');
    });

    it('switches advertisement status tabs', async () => {
        const wrapper = mount(UserDashboardView, { global: { plugins: [createPinia()] } });
        await flushPromises();

        await wrapper.findAll('[role="tab"]')[2].trigger('click');
        expect(wrapper.text()).toContain('تقاضای امتیاز وام');
        expect(wrapper.text()).not.toContain('امتیاز وام رسالت');
    });
});