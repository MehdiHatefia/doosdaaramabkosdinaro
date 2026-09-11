import { flushPromises, mount } from '@vue/test-utils';
import PrimeVue from 'primevue/config';
import { createPinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import HomeView from './HomeView.vue';

const ads = [
    { id: 1, type: 'supply', bank: 'بانک رسالت', bank_id: 2, bank_plan: { id: 1, title: 'طرح مرآت' }, title: 'امتیاز وام ۳۰۰ میلیونی رسالت', amount: 300, price: 18, city: 'پونک', province: 'تهران', time: '۱۲ دقیقه پیش', fee: 2, description: 'توضیحات آگهی رسالت' },
    { id: 2, type: 'demand', bank: 'بانک ملی', bank_id: 1, bank_plan: { id: 2, title: 'طرح اعتبار ملی' }, title: 'خریدار امتیاز وام فوری', amount: 500, price: 28, city: 'مشهد', province: 'خراسان رضوی', time: '۳۵ دقیقه پیش', fee: 4, description: 'توضیحات آگهی ملی' },
    { id: 3, type: 'supply', bank: 'مهر ایران', bank_id: 3, bank_plan: { id: 3, title: 'وام مهربانی' }, title: 'واگذاری امتیاز وام مهربانی', amount: 200, price: 12, city: 'مرکز شهر', province: 'اصفهان', time: '۱ ساعت پیش', fee: 0, description: 'توضیحات آگهی مهر' },
    { id: 4, type: 'supply', bank: 'بانک کشاورزی', bank_id: 4, bank_plan: { id: 4, title: 'طرح نوآور' }, title: 'امتیاز وام ۷۰۰ میلیونی', amount: 700, price: 42, city: 'معالی‌آباد', province: 'فارس', time: '۲ ساعت پیش', fee: 8, description: 'توضیحات آگهی کشاورزی' },
    { id: 5, type: 'demand', bank: 'بانک صادرات', bank_id: 5, bank_plan: { id: 5, title: 'طرح سپاس' }, title: 'تقاضای امتیاز وام کم‌کارمزد', amount: 400, price: 20, city: 'عظیمیه', province: 'البرز', time: '۳ ساعت پیش', fee: 12, description: 'توضیحات آگهی صادرات' },
    { id: 6, type: 'supply', bank: 'بانک رسالت', bank_id: 2, bank_plan: { id: 1, title: 'طرح مرآت' }, title: 'امتیاز وام رسالت با انتقال سریع', amount: 900, price: 55, city: 'سعادت‌آباد', province: 'تهران', time: '۵ ساعت پیش', fee: 4, description: 'توضیحات آگهی رسالت دوم' },
];

const { getAllAds, getAllBanks, getProvinces, getPlans } = vi.hoisted(() => ({ getAllAds: vi.fn(), getAllBanks: vi.fn(), getProvinces: vi.fn(), getPlans: vi.fn() }));
vi.mock('../services', () => ({ adService: { getAll: getAllAds }, bankService: { getAll: getAllBanks, getPlans }, locationService: { getProvinces }, authService: {} }));

function responseFor(params = {}) {
    const cityByLocationId = { 1: 'پونک', 2: 'سجاد', 3: 'مرکز شهر', 4: 'معالی‌آباد', 5: 'عظیمیه', 6: 'سعادت‌آباد', 7: 'مشهد' };
    return {
        data: ads.filter((ad) => (!params.type || ad.type === params.type)
            && (!params.location_id || ad.city === cityByLocationId[params.location_id])
            && (!params.location_ids || params.location_ids.some((locationId) => ad.city === cityByLocationId[locationId]))
            && (!params.search || `${ad.title} ${ad.bank_plan.title}`.includes(params.search))),
    };
}

describe('HomeView', () => {
    const mountHome = () => mount(HomeView, { global: { plugins: [PrimeVue, createPinia()] } });

    beforeEach(() => {
        localStorage.clear();
        window.history.replaceState({}, '', '/');
        window.matchMedia = window.matchMedia || (() => ({ matches: false, addListener: () => {}, removeListener: () => {}, addEventListener: () => {}, removeEventListener: () => {} }));
        getAllAds.mockImplementation(responseFor);
        getAllBanks.mockResolvedValue({ data: [
            { id: 1, name: 'بانک ملی' }, { id: 2, name: 'بانک رسالت' }, { id: 3, name: 'مهر ایران' },
            { id: 4, name: 'بانک کشاورزی' }, { id: 5, name: 'بانک صادرات' },
        ] });
        getProvinces.mockResolvedValue({ data: [
            { id: 1, name: 'تهران', children: [{ id: 1, name: 'پونک' }, { id: 6, name: 'سعادت‌آباد' }] },
            { id: 2, name: 'خراسان رضوی', children: [{ id: 7, name: 'مشهد' }, { id: 2, name: 'سجاد' }] },
        ] });
        getPlans.mockResolvedValue({ data: [] });
    });

    it('opens the OTP modal when a guest clicks ثبت آگهی', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.post-button').trigger('click');

        expect(wrapper.find('.auth-overlay').exists()).toBe(true);
    });

    it('shows login and hides the dashboard menu for guests', async () => {
        const wrapper = mountHome();
        await flushPromises();

        expect(wrapper.find('.auth-header-link').text()).toContain('ورود / ثبت‌نام');
        expect(wrapper.find('.user-menu').exists()).toBe(false);
    });

    it('shows the user dashboard menu and hides login for authenticated users', async () => {
        localStorage.setItem('auth_token', 'test-token');
        localStorage.setItem('auth_user', JSON.stringify({ id: 1, name: 'کاربر تست', roles: ['buyer', 'seller'] }));
        const wrapper = mountHome();
        await flushPromises();

        expect(wrapper.find('.auth-header-link').exists()).toBe(false);
        expect(wrapper.find('.user-menu').exists()).toBe(true);
        expect(wrapper.find('.user-menu__trigger').text()).toContain('کاربر تست');
    });

    it('opens the verification guidance dialog for an unverified user', async () => {
        localStorage.setItem('auth_token', 'test-token');
        localStorage.setItem('auth_user', JSON.stringify({ id: 1, is_verified: false }));
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.post-button').trigger('click');
        await flushPromises();

        expect(document.body.textContent).toContain('دسترسی شما محدود است');
        expect(document.body.textContent).toContain('تکمیل احراز هویت');
    });

    it('opens the create advertisement form for a verified user', async () => {
        localStorage.setItem('auth_token', 'test-token');
        localStorage.setItem('auth_user', JSON.stringify({ id: 1, is_verified: true }));
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.post-button').trigger('click');

        expect(wrapper.find('.create-overlay').exists()).toBe(true);
        expect(wrapper.text()).toContain('ثبت آگهی امتیاز وام');
    });

    it('filters listings by offer type', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('input[type="radio"][value="supply"]').setValue(true);
        await flushPromises();

        expect(wrapper.findAll('.ad-card')).toHaveLength(4);
    });

    it('resets search and all listing filters', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('input[type="search"]').setValue('رسالت');
        await wrapper.find('input[type="radio"][value="demand"]').setValue(true);
        const resetButton = wrapper.findAll('button').find((button) => button.text() === 'حذف همه');
        await resetButton.trigger('click');
        await flushPromises();

        expect(wrapper.findAll('.ad-card')).toHaveLength(6);
        expect(wrapper.find('input[type="search"]').element.value).toBe('');
    });

    it('opens the city modal and filters listings after selection', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.location-button').trigger('click');
        expect(wrapper.find('.city-modal').exists()).toBe(true);
        await wrapper.findAll('.province-list button').find((button) => button.text().includes('خراسان رضوی')).trigger('click');
        await wrapper.findAll('.city-list button').find((button) => button.text().includes('مشهد')).trigger('click');
        await wrapper.find('.city-modal__confirm').trigger('click');
        await flushPromises();

        expect(wrapper.find('.location-button span').text()).toBe('مشهد');
        expect(wrapper.find('.city-modal').exists()).toBe(false);
        expect(wrapper.findAll('.ad-card')).toHaveLength(1);
    });

    it('marks and applies multiple selected cities', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.location-button').trigger('click');
        await wrapper.findAll('.province-list button').find((button) => button.text().includes('تهران')).trigger('click');
        await wrapper.findAll('.city-list button').find((button) => button.text().includes('پونک')).trigger('click');
        await wrapper.findAll('.province-list button').find((button) => button.text().includes('خراسان رضوی')).trigger('click');
        await wrapper.findAll('.city-list button').find((button) => button.text().includes('مشهد')).trigger('click');

        expect(wrapper.findAll('.selected-city-chip')).toHaveLength(2);
        expect(wrapper.findAll('.city-list button.active')).toHaveLength(1);
        await wrapper.find('.city-modal__confirm').trigger('click');
        await flushPromises();

        expect(wrapper.find('.location-button span').text()).toBe('2 شهر انتخاب شده');
        expect(wrapper.findAll('.ad-card')).toHaveLength(2);
    });

    it('selects all Iran and removes the location filter', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.location-button').trigger('click');
        await wrapper.find('.province-list button').trigger('click');

        expect(wrapper.find('.province-list button').classes()).toContain('active');
        expect(wrapper.findAll('.city-list button.active')).toHaveLength(4);
        await wrapper.find('.city-modal__confirm').trigger('click');
        await flushPromises();

        expect(wrapper.find('.location-button span').text()).toBe('کل ایران');
        expect(wrapper.findAll('.ad-card')).toHaveLength(6);
        expect(getAllAds).toHaveBeenLastCalledWith(expect.not.objectContaining({ location_id: expect.anything(), location_ids: expect.anything() }));
    });

    it('opens listing details from the card CTA and returns to listings', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.card-cta').trigger('click');

        expect(wrapper.find('.detail-page').exists()).toBe(true);
        expect(wrapper.find('.detail-card h1').text()).toContain('امتیاز وام');

        await wrapper.find('.back-button').trigger('click');
        expect(wrapper.find('.detail-page').exists()).toBe(false);
        expect(wrapper.findAll('.ad-card')).toHaveLength(6);
    });

    it('shows media, description, location, and related ads in details', async () => {
        const wrapper = mountHome();
        await flushPromises();

        await wrapper.find('.card-cta').trigger('click');

        expect(wrapper.find('.detail-media').exists()).toBe(true);
        expect(wrapper.find('.detail-description').text()).toContain('توضیحات آگهی');
        expect(wrapper.find('.location-map').exists()).toBe(true);
        expect(wrapper.findAll('.related-grid .ad-card')).toHaveLength(3);
    });

    it('reloads advertisements with the selected sort option and syncs the URL', async () => {
        const wrapper = mountHome();
        await flushPromises();
        getAllAds.mockClear();

        await wrapper.find('.sort-controls__dropdown').trigger('click');
        const amountSort = document.body.querySelectorAll('.p-select-option');
        const amountOption = Array.from(amountSort).find((option) => option.textContent.includes('بیشترین مبلغ'));
        amountOption.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        await flushPromises();

        expect(window.location.search).toBe('?sort=amount_desc');
        expect(getAllAds).toHaveBeenCalledWith(expect.objectContaining({ sort: 'amount_desc' }));
    });
});
