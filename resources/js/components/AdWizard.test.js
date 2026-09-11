import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AdWizard from './AdWizard.vue';

const { getPlans, create } = vi.hoisted(() => ({ getPlans: vi.fn(), create: vi.fn() }));
vi.mock('../services', () => ({
    adService: { create, update: vi.fn() },
    bankService: { getPlans },
}));

describe('AdWizard', () => {
    const props = {
        banks: [{ id: 1, name: 'بانک تست' }],
        provinces: [{ id: 10, name: 'استان تست', children: [{ id: 11, name: 'شهر تست' }] }],
    };

    beforeEach(() => {
        getPlans.mockReset();
        create.mockReset();
        getPlans.mockResolvedValue({ data: [{ id: 5, title: 'طرح تست' }] });
    });

    it('loads plans after changing the selected bank', async () => {
        const wrapper = mount(AdWizard, { props });
        await wrapper.find('select').setValue('1');
        await flushPromises();

        expect(getPlans).toHaveBeenCalledWith(1);
        expect(wrapper.findAll('select')[1].text()).toContain('طرح تست');
    });

    it('blocks submission when financial values are invalid', async () => {
        const wrapper = mount(AdWizard, { props });
        const selects = wrapper.findAll('select');
        await selects[0].setValue('1');
        await flushPromises();
        await selects[1].setValue('5');
        await wrapper.find('button.primary-action').trigger('click');

        const financialInputs = wrapper.findAll('input');
        await financialInputs[0].setValue('0');
        await financialInputs[1].setValue('100');
        await financialInputs[2].setValue('2');
        await financialInputs[3].setValue('12');
        await wrapper.find('form').trigger('submit');

        expect(wrapper.text()).toContain('مبلغ کل تسهیلات باید بیشتر از صفر باشد.');
        expect(create).not.toHaveBeenCalled();
    });
});
