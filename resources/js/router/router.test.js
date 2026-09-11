import { beforeEach, describe, expect, it } from 'vitest';
import { canAccessAdmin } from '../auth/access';
import { router } from './index';

describe('dashboard access guard', () => {
    beforeEach(() => { localStorage.clear(); router.push('/'); });
    it('redirects a regular user away from admin routes', async () => {
        localStorage.setItem('auth_token', 'user-token'); localStorage.setItem('auth_user', JSON.stringify({ roles: ['User'], is_verified: true }));
        await router.push('/admin/dashboard');
        expect(router.currentRoute.value.name).toBe('user.my-ads');
    });
    it('loads the nested user dashboard routes', async () => {
        localStorage.setItem('auth_token', 'user-token');
        localStorage.setItem('auth_user', JSON.stringify({ roles: ['buyer'], is_verified: true }));

        await router.push({ name: 'user.my-ads' });
        expect(router.currentRoute.value.name).toBe('user.my-ads');

        await router.push({ name: 'user.bookmarks' });
        expect(router.currentRoute.value.name).toBe('user.bookmarks');

        await router.push({ name: 'user.profile' });
        expect(router.currentRoute.value.name).toBe('user.profile');

        await router.push({ name: 'user.history' });
        expect(router.currentRoute.value.name).toBe('user.history');

        await router.push({ name: 'user.verification' });
        expect(router.currentRoute.value.name).toBe('user.verification');

        await router.push({ name: 'user.settings' });
        expect(router.currentRoute.value.name).toBe('user.settings');
    });
    it('redirects an unverified user to verification from protected routes', async () => {
        localStorage.setItem('auth_token', 'user-token');
        localStorage.setItem('auth_user', JSON.stringify({ roles: ['buyer'], is_verified: false }));

        await router.push({ name: 'user.bookmarks' });
        expect(router.currentRoute.value.name).toBe('user.verification');
    });
    it('allows supported admin roles', () => {
        expect(canAccessAdmin({ roles: ['super-admin'] })).toBe(true);
        expect(canAccessAdmin({ roles: ['admin'] })).toBe(true);
        expect(canAccessAdmin({ roles: ['operator'] })).toBe(true);
        expect(canAccessAdmin({ roles: [{ name: 'admin' }] })).toBe(true);
    });
});