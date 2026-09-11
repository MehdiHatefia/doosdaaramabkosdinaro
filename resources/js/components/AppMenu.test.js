import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AppMenu from './AppMenu.vue';
import { adminMenu, userMenu } from '../config/menus';

const mountMenu = (items) => mount(AppMenu, { props: { items }, global: { stubs: { RouterLink: { template: '<a><slot /></a>' } } } });
describe('AppMenu', () => {
    it('renders the full user dashboard navigation list', () => {
        expect(userMenu.map((item) => item.to.name)).toEqual([
            'user.my-ads',
            'user.bookmarks',
            'user.profile',
            'user.history',
            'user.verification',
            'user.settings',
        ]);
        const text = mountMenu(userMenu).text();
        expect(text).toContain('آگهی‌های من');
        expect(text).toContain('بازدیدهای اخیر');
        expect(text).toContain('تنظیمات حساب کاربری');
        expect(text).not.toContain('بررسی و تأیید آگهی‌ها');
    });
    it('renders only admin navigation', () => { const text = mountMenu(adminMenu).text(); expect(text).toContain('بررسی و تأیید آگهی‌ها'); expect(text).not.toContain('آگهی‌های من'); });
});