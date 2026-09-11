<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AuthModal from '../components/AuthModal.vue';
import { adService } from '../services';
import { useAuthStore } from '../stores/useAuthStore';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdDetailView');
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const ad = ref(null);
const loading = ref(true);
const error = ref('');
const authModalVisible = ref(false);
const contactVisible = ref(false);
const contactMobile = ref('');
const saved = ref(false);
const feedback = ref('');

const numberFormatter = new Intl.NumberFormat('fa-IR');

function formatMoney(value) {
    return `${numberFormatter.format(Number(value || 0))} میلیون تومان`;
}

async function loadAd() {
    loading.value = true;
    error.value = '';
    logger.info('loadAd', 'Advertisement detail request started', { advertisementId: route.params.id });
    try {
        const response = await adService.getById(route.params.id);
        ad.value = response.data || response;
        const recent = JSON.parse(localStorage.getItem('recent_ads') || '[]');
        const next = [ad.value, ...recent.filter((item) => item.id !== ad.value.id)].slice(0, 8);
        localStorage.setItem('recent_ads', JSON.stringify(next));
        logger.info('loadAd', 'Advertisement detail received', { advertisementId: ad.value?.id });
    } catch (exception) {
        logger.error('loadAd', 'Advertisement detail request failed', { ...logException(exception), advertisementId: route.params.id });
        error.value = exception.response?.status === 404 ? 'این آگهی پیدا نشد یا دیگر منتشر نیست.' : 'دریافت آگهی با خطا مواجه شد.';
    } finally {
        loading.value = false;
    }
}

async function loadBookmarkState() {
    if (!authStore.isAuthenticated || !authStore.isVerified) return;
    try {
        const response = await adService.getBookmarks();
        const bookmarks = Array.isArray(response?.data) ? response.data : [];
        saved.value = bookmarks.some((bookmark) => bookmark.id === Number(route.params.id));
        logger.info('loadBookmarkState', 'Bookmark state received', { advertisementId: route.params.id, bookmarked: saved.value });
    } catch (exception) {
        logger.warn('loadBookmarkState', 'Bookmark state could not be loaded', logException(exception));
    }
}

async function shareAd() {
    const shareData = { title: ad.value?.title, text: ad.value?.title, url: window.location.href };
    try {
        if (navigator.share) await navigator.share(shareData);
        else await navigator.clipboard.writeText(window.location.href);
        feedback.value = 'لینک آگهی کپی شد.';
        logger.info('shareAd', 'Advertisement link shared', { advertisementId: ad.value?.id });
    } catch (exception) {
        if (exception.name !== 'AbortError') logger.error('shareAd', 'Advertisement sharing failed', logException(exception));
    }
}

async function toggleBookmark() {
    authStore.syncFromStorage();
    if (!authStore.isAuthenticated) {
        authModalVisible.value = true;
        return;
    }
    if (!authStore.isVerified) {
        window.dispatchEvent(new CustomEvent('auth:verification-required', { detail: { code: 'KYC_REQUIRED' } }));
        return;
    }

    try {
        const response = await adService.toggleBookmark(Number(ad.value.id));
        saved.value = response?.bookmarked === true;
        feedback.value = saved.value ? 'آگهی نشان شد.' : 'آگهی از نشان‌ها حذف شد.';
        logger.info('toggleBookmark', 'Advertisement bookmark changed', { advertisementId: ad.value.id, bookmarked: saved.value });
    } catch (exception) {
        feedback.value = 'تغییر وضعیت نشان انجام نشد.';
        logger.error('toggleBookmark', 'Advertisement bookmark change failed', logException(exception));
    }
}

function reportAd() {
    feedback.value = 'گزارش شما ثبت شد و بررسی می‌شود.';
    logger.info('reportAd', 'Advertisement report submitted', { advertisementId: ad.value?.id });
}

async function showContact() {
    logger.info('showContact', 'Contact action requested', { advertisementId: ad.value?.id, authenticated: authStore.isAuthenticated });
    authStore.syncFromStorage();
    if (!authStore.isAuthenticated) {
        authModalVisible.value = true;
        return;
    }
    if (!authStore.isVerified) {
        window.dispatchEvent(new CustomEvent('auth:verification-required', { detail: { code: 'KYC_REQUIRED' } }));
        return;
    }
    try {
        const response = await adService.getContact(Number(ad.value.id));
        contactMobile.value = response.mobile || response.data?.mobile || '';
        contactVisible.value = true;
    } catch (exception) {
        logger.error('showContact', 'Contact request failed', logException(exception));
    }
}

async function handleAuthenticated() {
    authStore.syncFromStorage();
    authModalVisible.value = false;
    if (authStore.isVerified) await showContact();
    else window.dispatchEvent(new CustomEvent('auth:verification-required', { detail: { code: 'KYC_REQUIRED' } }));
    logger.info('showContact', 'Contact revealed after authentication', { advertisementId: ad.value?.id });
}

function startMessage() {
    if (!authStore.isAuthenticated) {
        authModalVisible.value = true;
        return;
    }
    if (!authStore.isVerified) {
        window.dispatchEvent(new CustomEvent('auth:verification-required', { detail: { code: 'KYC_REQUIRED' } }));
        return;
    }
    feedback.value = 'امکان ارسال پیام پس از فعال شدن گفت‌وگو فراهم می‌شود.';
}

onMounted(async () => {
    authStore.syncFromStorage();
    await loadAd();
    await loadBookmarkState();
});
</script>

<template>
    <main class="ad-detail-page" dir="rtl">
        <div class="ad-detail-shell">
            <button class="back-link" type="button" @click="router.push({ name: 'home' })"><i class="pi pi-arrow-right"></i>بازگشت به آگهی‌ها</button>
            <div v-if="loading" class="detail-state" role="status"><i class="pi pi-spin pi-spinner"></i><span>در حال دریافت آگهی...</span></div>
            <section v-else-if="error" class="detail-state detail-state--error" role="alert"><i class="pi pi-exclamation-circle"></i><h1>{{ error }}</h1><button type="button" @click="loadAd">تلاش دوباره</button></section>
            <template v-else-if="ad">
                <nav class="breadcrumb" aria-label="مسیر آگهی"><button type="button" @click="router.push({ name: 'home' })">مستروام</button><i class="pi pi-angle-left"></i><span>وام‌های بانکی</span><i class="pi pi-angle-left"></i><span>{{ ad.province || 'استان' }}</span><i class="pi pi-angle-left"></i><span>{{ ad.city || 'شهر' }}</span><i class="pi pi-angle-left"></i><strong>{{ ad.title }}</strong></nav>
                <header class="detail-header">
                    <div><div class="detail-kicker"><span class="type-badge" :class="ad.type">{{ ad.type === 'supply' ? 'عرضه' : 'تقاضا' }}</span><span>{{ ad.time }} در {{ ad.city }}</span></div><h1>{{ ad.title }}</h1><p>{{ ad.bank }}، {{ ad.bank_plan?.title || ad.plan }}</p></div>
                    <div class="detail-actions"><button type="button" aria-label="اشتراک‌گذاری آگهی" title="اشتراک‌گذاری" @click="shareAd"><i class="pi pi-share-alt"></i></button><button type="button" :aria-label="saved ? 'حذف از نشان‌ها' : 'نشان کردن آگهی'" :title="saved ? 'حذف از نشان‌ها' : 'نشان کردن'" :class="{ active: saved }" @click="toggleBookmark"><i class="pi" :class="saved ? 'pi-bookmark-fill' : 'pi-bookmark'"></i></button><button type="button" aria-label="گزارش آگهی" title="گزارش آگهی" @click="reportAd"><i class="pi pi-flag"></i></button></div>
                </header>
                <div v-if="feedback" class="feedback" role="status">{{ feedback }}</div>
                <div class="detail-layout">
                    <article class="detail-main">
                        <section class="spec-section"><h2>مشخصات آگهی</h2><dl class="spec-list"><div><dt>بانک</dt><dd>{{ ad.bank }}</dd></div><div><dt>طرح تسهیلاتی</dt><dd>{{ ad.bank_plan?.title || ad.plan || 'ثبت نشده' }}</dd></div><div><dt>نوع آگهی</dt><dd>{{ ad.type === 'supply' ? 'عرضه امتیاز وام' : 'تقاضای امتیاز وام' }}</dd></div><div><dt>مبلغ کل وام</dt><dd>{{ formatMoney(ad.amount) }}</dd></div><div><dt>قیمت واگذاری امتیاز</dt><dd>{{ formatMoney(ad.price) }}</dd></div><div><dt>کارمزد / سود بانکی</dt><dd>{{ numberFormatter.format(Number(ad.fee || ad.bank_plan?.interest_rate || 0)) }}٪</dd></div><div><dt>موقعیت</dt><dd>{{ ad.province }}، {{ ad.city }}</dd></div></dl></section>
                        <section class="description-section"><h2>توضیحات</h2><p class="description">{{ ad.description || 'توضیحی برای این آگهی ثبت نشده است.' }}</p></section>
                        <aside class="safety-box"><i class="pi pi-shield"></i><div><h2>راهنمای معامله امن</h2><p>پیش از انتقال رسمی امتیاز در شعبه بانک، هیچ مبلغی به‌عنوان بیعانه پرداخت نکنید. معامله را حضوری و پس از بررسی مدارک انجام دهید.</p></div></aside>
                    </article>
                    <aside class="contact-card"><p class="contact-label">تماس با آگهی‌دهنده</p><h2>{{ ad.type === 'demand' ? 'خریدار امتیاز وام' : 'فروشنده امتیاز وام' }}</h2><p class="contact-meta"><i class="pi pi-clock"></i>{{ ad.time }}</p><button class="contact-button" type="button" @click="showContact"><i class="pi pi-phone"></i>{{ contactVisible ? contactMobile : 'اطلاعات تماس' }}</button><button class="message-button" type="button" @click="startMessage"><i class="pi pi-comment"></i>ارسال پیام</button><small>با حفظ حریم خصوصی، اطلاعات تماس فقط برای کاربران تاییدشده نمایش داده می‌شود.</small></aside>
                </div>
            </template>
        </div>
        <AuthModal v-model:visible="authModalVisible" @authenticated="handleAuthenticated" />
    </main>
</template>

<style scoped>
.ad-detail-page { min-height: 100vh; background: #f7f8f9; color: #202a35; }.ad-detail-shell { width: min(1080px, calc(100% - 32px)); margin: auto; padding: 28px 0 64px; }.back-link, .breadcrumb button { border: 0; background: transparent; color: #a62626; cursor: pointer; font: inherit; }.back-link { display: inline-flex; gap: 8px; margin-bottom: 25px; font-size: 12px; }.breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; color: #7a8791; font-size: 11px; }.breadcrumb strong { overflow: hidden; max-width: 280px; color: #202a35; text-overflow: ellipsis; white-space: nowrap; }.breadcrumb .pi { font-size: 9px; }.detail-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding-bottom: 26px; border-bottom: 1px solid #e4e7e9; }.detail-kicker { display: flex; align-items: center; gap: 12px; color: #7a8791; font-size: 12px; }.type-badge { padding: 5px 10px; border-radius: 16px; background: #edf8f5; color: #287d6f; font-size: 11px; }.type-badge.demand { background: #fff4e8; color: #a86426; }.detail-header h1 { margin: 15px 0 7px; font-size: clamp(22px, 3vw, 32px); line-height: 1.6; }.detail-header p { margin: 0; color: #7a8791; font-size: 13px; }.detail-actions { display: flex; gap: 8px; }.detail-actions button { width: 38px; height: 38px; border: 1px solid #dfe4e8; border-radius: 8px; background: #fff; color: #63717b; cursor: pointer; }.detail-actions button:hover, .detail-actions button.active { border-color: #e3b3ad; background: #fff5f2; color: #a62626; }.feedback { margin-top: 16px; padding: 10px 14px; border-right: 3px solid #a62626; background: #fff5f2; color: #7e2929; font-size: 12px; }.detail-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 28px; margin-top: 28px; }.detail-main { min-width: 0; }.spec-section, .description-section { padding: 0 0 30px; }.detail-main h2 { margin: 0 0 18px; font-size: 17px; }.spec-list { margin: 0; }.spec-list div { display: flex; justify-content: space-between; gap: 24px; padding: 14px 0; border-bottom: 1px dashed #dfe4e8; }.spec-list dt { color: #7a8791; font-size: 13px; }.spec-list dd { margin: 0; color: #202a35; font-size: 13px; font-weight: 700; }.description { margin: 0; color: #4d5b65; font-size: 14px; line-height: 2.3; white-space: pre-line; }.safety-box { display: flex; gap: 14px; padding: 18px; border: 1px solid #eadfc5; border-radius: 8px; background: #fffaf0; color: #66542b; }.safety-box > .pi { color: #b9872d; font-size: 22px; }.safety-box h2 { margin-bottom: 7px; color: #5d4b25; font-size: 14px; }.safety-box p { margin: 0; font-size: 12px; line-height: 2; }.contact-card { align-self: start; padding: 22px; border: 1px solid #e4e7e9; border-radius: 10px; background: #fff; box-shadow: 0 8px 24px rgba(32,42,53,.06); }.contact-label, .contact-meta, .contact-card small { color: #7a8791; font-size: 11px; }.contact-card h2 { margin: 8px 0; font-size: 16px; }.contact-meta { display: flex; gap: 6px; margin: 0 0 20px; }.contact-button, .message-button { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; border-radius: 7px; cursor: pointer; font: inherit; font-size: 12px; }.contact-button { border: 0; background: #a62626; color: #fff; }.message-button { margin-top: 9px; border: 1px solid #d9dfe3; background: #fff; color: #42515b; }.contact-card small { display: block; margin-top: 16px; line-height: 1.9; }.detail-state { display: grid; place-items: center; gap: 12px; min-height: 420px; color: #7a8791; font-size: 14px; }.detail-state--error h1 { margin: 0; color: #202a35; font-size: 18px; }.detail-state--error button { padding: 9px 16px; border: 0; border-radius: 6px; background: #a62626; color: #fff; cursor: pointer; }
@media (max-width: 720px) { .ad-detail-shell { width: min(100% - 24px, 560px); padding-top: 18px; }.detail-header { flex-direction: column; }.detail-actions { order: -1; align-self: flex-start; }.detail-layout { grid-template-columns: 1fr; }.contact-card { order: -1; }.breadcrumb strong { max-width: 180px; }.spec-list div { align-items: flex-start; }.spec-list dd { text-align: left; } }
</style>
