import { createRouter, createWebHistory } from 'vue-router';
import { canAccessAdmin, getStoredUser, isAuthenticated, userRoles } from '../auth/access';
import HomeView from '../components/HomeView.vue';
import BaseDashboardLayout from '../layouts/BaseDashboardLayout.vue';
import UserDashboardView from '../views/UserDashboardView.vue';
import AdminDashboardView from '../views/AdminDashboardView.vue';
import AdDetailView from '../views/AdDetailView.vue';
import MyAdsView from '../views/MyAdsView.vue';
import BookmarksView from '../views/BookmarksView.vue';
import ProfileView from '../views/ProfileView.vue';
import HistoryView from '../views/HistoryView.vue';
import VerificationView from '../views/VerificationView.vue';
import VerificationReviewView from '../views/VerificationReviewView.vue';
import SettingsView from '../views/SettingsView.vue';
import { createLogger } from '../utils/logger';
import { useAuthStore } from '../stores/useAuthStore';
import { getActivePinia } from 'pinia';

const logger = createLogger('AuthGuard');

const routes = [
    { path: '/', name: 'home', component: HomeView },
    { path: '/advertisements/:id', name: 'advertisement.detail', component: AdDetailView, props: true },
    { path: '/login/user/authentication', redirect: { name: 'user.my-ads' } },
    { path: '/user', component: BaseDashboardLayout, meta: { requiresAuth: true, area: 'user' }, children: [
        { path: '', redirect: { name: 'user.my-ads' } },
        { path: 'my-ads', name: 'user.my-ads', component: MyAdsView, meta: { requiresVerification: true } },
        { path: 'bookmarks', name: 'user.bookmarks', component: BookmarksView, meta: { requiresVerification: true } },
        { path: 'profile', name: 'user.profile', component: ProfileView },
        { path: 'history', name: 'user.history', component: HistoryView, meta: { requiresVerification: true } },
        { path: 'verification', name: 'user.verification', component: VerificationView },
        { path: 'settings', name: 'user.settings', component: SettingsView, meta: { requiresVerification: true } },
    ] },
    { path: '/user/dashboard', redirect: { name: 'user.my-ads' } },
    { path: '/admin', component: BaseDashboardLayout, meta: { requiresAuth: true, requiresAdmin: true, area: 'admin' }, children: [
        { path: 'dashboard', name: 'admin.dashboard', component: AdminDashboardView },
        { path: 'verifications', name: 'admin.verifications', component: VerificationReviewView },
    ] },
];

export const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(async (to) => {
    logger.info('checkRole', 'Route navigation checked', { name: to.name, path: to.path });
    if (to.meta.requiresAuth && !isAuthenticated()) {
        logger.warn('checkRole', 'Unauthenticated navigation redirected', { name: to.name });
        return { name: 'home' };
    }
    const user = isAuthenticated() && to.meta.requiresVerification && getActivePinia()
        ? await useAuthStore().refreshUser()
        : getStoredUser();
    const verified = user?.is_verified === true || user?.verified === true;
    if (to.meta.requiresVerification && !verified) {
        logger.warn('checkVerification', 'Unverified navigation blocked', { name: to.name });
        window.dispatchEvent(new CustomEvent('auth:verification-required', { detail: { code: 'KYC_REQUIRED' } }));
        return { name: 'user.verification' };
    }
    if (to.meta.requiresAdmin && !canAccessAdmin()) {
        logger.warn('checkRole', 'Unauthorized admin navigation redirected', { name: to.name });
        return { name: 'user.my-ads' };
    }
    if ((to.name === 'user.dashboard' || to.name?.startsWith('user.')) && canAccessAdmin()) {
        logger.info('checkRole', 'Manager redirected to admin dashboard', { path: to.path, roles: userRoles() });
        return { name: 'admin.dashboard' };
    }
    logger.info('checkRole', 'Route navigation allowed', { name: to.name });
    return true;
});

export default router;