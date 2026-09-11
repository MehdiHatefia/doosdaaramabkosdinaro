import { flushPromises, mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AdDetailView from './AdDetailView.vue';

const { getById } = vi.hoisted(() => ({ getById: vi.fn() }));
vi.mock('../services', () => ({ adService: { getById } }));
vi.mock('../components/AuthModal.vue', () => ({
    default: { template: '<div data-testid="auth-modal" />', props: ['visible'] },
}));
vi.mock('vue-router', () => ({
    useRoute: () => ({ params: { id: '7' } }),
    useRouter: () => ({ push: vi.fn() }),
}));

const advertisement = {
    id: 7,
    type: 'supply',
    title: 'امتیاز وام ۳۰۰ میلیونی رسالت',
    bank: 'بانک رسالت',
    bank_plan: { title: 'طرح مرآت', interest_rate: 2 },
    amount: 300,
    price: 18,
    fee: 2,
    province: 'تهران',
    city: 'پونک',
    description: 'توضیح خط اول\nتوضیح خط دوم',
    advertiser_mobile: '09123456789',
    time: '۲ ساعت پیش',
};

const mountView = () => mount(AdDetailView, { global: { plugins: [createPinia()] } });

describe('AdDetailView', () => {
    beforeEach(() => {
        localStorage.clear();
        getById.mockReset();
    });

    it('renders advertisement specs and formats money values', async () => {
        getById.mockResolvedValue(advertisement);
        const wrapper = mountView();
        await flushPromises();

        expect(getById).toHaveBeenCalledWith('7');
        expect(wrapper.text()).toContain('امتیاز وام ۳۰۰ میلیونی رسالت');
        expect(wrapper.text()).toContain('بانک رسالت');
        expect(wrapper.text()).toContain('۳۰۰ میلیون تومان');
        expect(wrapper.text()).toContain('۱۸ میلیون تومان');
        expect(wrapper.find('.description').text()).toContain('توضیح خط اول\nتوضیح خط دوم');
        expect(wrapper.find('.safety-box').exists()).toBe(true);
    });

    it('shows a loading state while the request is pending', async () => {
        let resolveRequest;
        getById.mockReturnValue(new Promise((resolve) => { resolveRequest = resolve; }));
        const wrapper = mountView();

        expect(wrapper.find('[role="status"]').text()).toContain('در حال دریافت آگهی');
        resolveRequest(advertisement);
        await flushPromises();
        expect(wrapper.find('.detail-header').exists()).toBe(true);
    });

    it('shows a friendly 404 error state', async () => {
        getById.mockRejectedValue({ response: { status: 404 } });
        const wrapper = mountView();
        await flushPromises();

        expect(wrapper.find('[role="alert"]').text()).toContain('این آگهی پیدا نشد');
        expect(wrapper.find('.detail-header').exists()).toBe(false);
    });
});
