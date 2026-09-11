import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import BaseDashboardLayout from './BaseDashboardLayout.vue';

const mountLayout = () => mount(BaseDashboardLayout, {
    global: {
        plugins: [createPinia()],
        stubs: {
            RouterView: { template: '<div />' },
            RouterLink: { template: '<a><slot /></a>' },
        },
    },
});

describe('BaseDashboardLayout', () => {
    beforeEach(() => {
        localStorage.clear();
        localStorage.setItem('auth_token', 'test-token');
    });

    it('shows the green verified badge and user navigation', () => {
        localStorage.setItem('auth_user', JSON.stringify({ id: 1, mobile: '09120000001', is_verified: true, roles: ['buyer'] }));
        const wrapper = mountLayout();

        expect(wrapper.text()).toContain('احراز هویت شده');
        expect(wrapper.text()).not.toContain('احراز هویت ناقص');
        expect(wrapper.text()).toContain('تایید هویت و مدارک');
        expect(wrapper.text()).toContain('خروج از حساب');
    });

    it('shows the warning badge for an incomplete verification', () => {
        localStorage.setItem('auth_user', JSON.stringify({ id: 1, mobile: '09120000001', is_verified: false, roles: ['buyer'] }));
        const wrapper = mountLayout();

        expect(wrapper.text()).toContain('احراز هویت ناقص');
        expect(wrapper.text()).toContain('تکمیل احراز هویت');
    });
});