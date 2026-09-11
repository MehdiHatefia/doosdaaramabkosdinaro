<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import AppMenu from '../components/AppMenu.vue';
import { adminMenu, userMenu } from '../config/menus';
import { canAccessAdmin, getStoredUser } from '../auth/access';
import { useAuthStore } from '../stores/useAuthStore';
import { authService } from '../services';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('DashboardLayout');
const authStore = useAuthStore();
const isAdmin = computed(() => canAccessAdmin(getStoredUser()));
const menuItems = computed(() => (isAdmin.value ? adminMenu : userMenu));
const menuTitle = computed(() => (isAdmin.value ? 'مدیریت سامانه' : 'حساب کاربری'));
const user = computed(() => authStore.user || getStoredUser() || {});
const isVerified = computed(() => Boolean(user.value.is_verified === true || user.value.verified === true));
const isLoggingOut = ref(false);
const confirmLogout = ref(false);
const avatarPreview = ref('');

async function refreshAvatar() {
    if (!user.value?.avatar) { avatarPreview.value = ''; return; }
    try {
        const nextPreview = await authService.getAvatarPreview();
        if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value);
        avatarPreview.value = nextPreview;
    } catch (exception) {
        avatarPreview.value = '';
        logger.warn('avatar', 'Sidebar avatar could not be loaded', logException(exception));
    }
}

async function requestLogout() {
    confirmLogout.value = true;
}

async function logout() {
    if (isLoggingOut.value) return;
    logger.info('logout', 'Dashboard logout started', { userId: user.value.id });
    isLoggingOut.value = true;
    try { await authService.logout(); } catch (exception) { logger.error('logout', 'Dashboard logout failed', logException(exception)); }
    finally {
        authStore.clear();
        isLoggingOut.value = false;
        confirmLogout.value = false;
        window.location.assign('/');
    }
}

watch(() => user.value?.avatar, refreshAvatar, { immediate: true });
onUnmounted(() => { if (avatarPreview.value?.startsWith('blob:')) URL.revokeObjectURL(avatarPreview.value); });
</script>

<template>
    <div class="dashboard-shell" dir="rtl">
        <aside class="dashboard-sidebar">
            <RouterLink class="dashboard-brand" to="/"><span class="dashboard-brand__mark">م</span><span>مستروام</span></RouterLink>
            <div class="profile-card"><div class="profile-avatar"><img v-if="avatarPreview" :src="avatarPreview" alt="تصویر پروفایل" /><i v-else class="pi pi-user"></i></div><strong>{{ user.mobile || 'کاربر مستروام' }}</strong><span v-if="isVerified" class="verification-badge verification-badge--success"><i class="pi pi-check-circle"></i>احراز هویت شده</span><span v-else class="verification-badge verification-badge--warning"><i class="pi pi-exclamation-circle"></i>احراز هویت ناقص</span><RouterLink v-if="!isVerified" class="verification-link" :to="{ name: 'user.verification' }">تکمیل احراز هویت</RouterLink></div>
            <AppMenu :items="menuItems" :title="menuTitle" />
            <nav v-if="!isAdmin" class="account-menu" aria-label="گزینه‌های حساب"><button class="account-menu__item account-menu__logout" type="button" :disabled="isLoggingOut" @click="requestLogout"><i class="pi pi-sign-out"></i><span>{{ isLoggingOut ? 'در حال خروج...' : 'خروج از حساب' }}</span><i class="pi pi-angle-left"></i></button></nav>
        </aside>
        <section class="dashboard-main">
            <header class="dashboard-header"><div><span class="dashboard-header__eyebrow">حساب کاربری</span><strong>{{ isAdmin ? 'پنل مدیریت' : 'پنل کاربری' }}</strong></div><RouterLink class="dashboard-home-link" to="/"><i class="pi pi-home"></i>بازگشت به سایت</RouterLink></header>
            <main class="dashboard-content"><RouterView /></main>
        </section>
    </div>
    <div v-if="confirmLogout" class="logout-confirm" role="dialog" aria-modal="true">
        <div class="logout-confirm__box">
            <h3>خروج از حساب</h3>
            <p>آیا از خروج از حساب کاربری اطمینان دارید؟</p>
            <div class="logout-confirm__actions">
                <button type="button" class="secondary-action" @click="confirmLogout = false">انصراف</button>
                <button type="button" class="danger-action" @click="logout">تأیید خروج</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dashboard-shell { min-height: 100vh; display: flex; background: #f9fafb; color: #1f2933; }.dashboard-sidebar { width: min(292px, 100%); flex: 0 0 min(292px, 100%); padding: 25px 18px; border-left: 1px solid #edf0f2; background: #fff; }.dashboard-brand { display: flex; align-items: center; gap: 10px; margin: 0 10px 24px; color: #202a35; font-size: 19px; font-weight: 800; text-decoration: none; }.dashboard-brand__mark { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 12px; background: #a62626; color: #fff; }.profile-card { display: flex; align-items: center; flex-direction: column; padding: 22px 12px; border: 1px solid #eef0f2; border-radius: 12px; background: #fff; text-align: center; }.profile-avatar { display: grid; place-items: center; width: 58px; height: 58px; margin-bottom: 12px; border-radius: 50%; background: #f1f3f5; color: #65717b; font-size: 22px; }.profile-card strong { direction: ltr; font-size: 14px; }.verification-badge { display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; padding: 5px 9px; border-radius: 16px; font-size: 10px; }.verification-badge--success { background: #ecf8f1; color: #24734b; }.verification-badge--warning { background: #fff5e7; color: #a86426; }.verification-link { margin-top: 10px; color: #a62626; font-size: 10px; text-decoration: none; }.account-menu { margin-top: 10px; padding-top: 10px; border-top: 1px solid #f0f1f2; }.account-menu__item { display: flex; align-items: center; justify-content: space-between; gap: 11px; width: 100%; min-height: 44px; padding: 11px 12px; border: 0; border-radius: 8px; background: transparent; color: #59656e; font: inherit; font-size: 12px; line-height: 1.8; text-align: right; text-decoration: none; cursor: pointer; transition: background-color .18s ease, color .18s ease; }.account-menu__item span { flex: 1; min-width: 0; overflow-wrap: anywhere; }.account-menu__item i:first-child { flex: 0 0 18px; color: #66727b; text-align: center; }.account-menu__item i:last-child { flex: 0 0 12px; color: #a6afb5; font-size: 11px; text-align: center; }.account-menu__item:hover { background: #f9fafb; color: #a62626; }.account-menu__logout { color: #b34a4a; }.account-menu__logout i:first-child { color: #b34a4a !important; }.dashboard-main { min-width: 0; flex: 1; }.dashboard-header { display: flex; justify-content: space-between; align-items: center; min-height: 76px; padding: 0 42px; border-bottom: 1px solid #edf0f2; background: #fff; }.dashboard-header div { display: flex; align-items: center; gap: 14px; }.dashboard-header__eyebrow { color: #a62626; font-size: 11px; }.dashboard-header strong { font-size: 17px; }.dashboard-home-link { display: inline-flex; align-items: center; gap: 7px; color: #68747d; font-size: 11px; text-decoration: none; }.dashboard-content { max-width: 1180px; margin: 0 auto; padding: 34px 42px; }
@media (max-width: 720px) { .dashboard-shell { display: block; }.dashboard-sidebar { width: auto; padding: 16px; border-left: 0; }.dashboard-brand { margin-bottom: 16px; }.profile-card { flex-direction: row; gap: 10px; padding: 12px; text-align: right; }.profile-avatar { flex: 0 0 42px; width: 42px; height: 42px; margin: 0; font-size: 17px; }.profile-card strong { flex: 1; }.verification-badge { margin: 0; }.verification-link { margin: 0; }.app-menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; }.app-menu__title { grid-column: 1 / -1; }.app-menu__item { padding: 10px; }.account-menu { display: grid; grid-template-columns: repeat(2, 1fr); }.dashboard-header { min-height: 62px; padding: 0 18px; }.dashboard-header div { gap: 8px; }.dashboard-header strong { font-size: 14px; }.dashboard-content { padding: 24px 18px; } }
.logout-confirm { position: fixed; inset: 0; z-index: 30; display: grid; place-items: center; background: rgba(17, 24, 39, .42); }
.logout-confirm__box { width: min(420px, calc(100% - 32px)); padding: 22px; border-radius: 12px; background: #fff; box-shadow: 0 20px 40px rgba(15, 23, 42, .15); }
.logout-confirm__box h3 { margin: 0 0 10px; color: #202a35; font-size: 20px; }
.logout-confirm__box p { margin: 0; color: #59656e; font-size: 13px; }
.logout-confirm__actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.secondary-action, .danger-action { padding: 10px 16px; border: 0; border-radius: 8px; cursor: pointer; font: inherit; }
.secondary-action { background: #edf1f3; color: #2f3a41; }
.danger-action { background: #a62626; color: #fff; }
.profile-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}
</style>