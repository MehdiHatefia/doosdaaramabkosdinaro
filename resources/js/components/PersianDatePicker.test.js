import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import PersianDatePicker from './PersianDatePicker.vue';

describe('PersianDatePicker', () => {
    it('emits the selected Jalali date string', async () => {
        const wrapper = mount(PersianDatePicker, { props: { modelValue: '1370/01/01' } });
        await wrapper.find('.persian-picker__input').trigger('click');
        const dayButton = wrapper.findAll('.persian-picker__days button').find((button) => button.text() === '2');

        await dayButton.trigger('click');

        expect(wrapper.emitted('update:modelValue')[0]).toEqual(['1370/01/02']);
    });
});
