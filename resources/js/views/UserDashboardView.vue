<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/useAuthStore';
import { useUserAds } from '../stores/useUserAds';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('UserDashboard');
const router = useRouter();
const authStore = useAuthStore();
const adsStore = useUserAds();
const activeTab = ref('all');
const notice = ref('');
const tabs = [
    { key: 'all', label: 'همه' },
    { key: 'approved', label: 'منتشر شده' },
    { key: 'pending', label: 'در انتظار بررسی' },
    { key: 'rejected', label: 'رد شده / منقضی' },
];
const filteredAds = computed(() => activeTab.value === 'all' ? adsStore.ads : adsStore.ads.filter((ad) => activeTab.value === 'rejected' ? ['rejected', 'expired'].includes(ad.status) : ad.status === activeTab.value));

function formatMoney(value) { return `${new Intl.NumberFormat('fa-IR').format(Number(value || 0))} میلیون تومان`; }
function statusLabel(status) { return { approved: 'منتشر شده', pending: 'در انتظار بررسی', rejected: 'رد شده', expired: 'منقضی شده' }[status] || 'وضعیت نامشخص'; }
function changeTab(tab) { activeTab.value = tab; logger.info('changeTab', 'User advertisement tab changed', { tab }); }
function openCreateAd() { logger.info('createAd', 'Create advertisement requested', { userId: authStore.user?.id }); router.push({ name: 'home' }); }
function viewAd(ad) { logger.info('viewAd', 'User advertisement viewed', { advertisementId: ad.id }); router.push({ name: 'advertisement.detail', params: { id: ad.id } }); }
function editAd(ad) { logger.info('editAd', 'Advertisement edit requested', { advertisementId: ad.id }); notice.value = 'ویرایش آگهی به‌زودی فعال می‌شود.'; }
function deleteAd(ad) { logger.info('deleteAd', 'Advertisement deletion requested', { advertisementId: ad.id }); notice.value = 'حذف آگهی به‌زودی فعال می‌شود.'; }

onMounted(async () => {
    authStore.syncFromStorage();
    logger.info('UIUpdate', 'User dashboard interface initialized', { userId: authStore.user?.id });
    try { await adsStore.fetchAds(); }
    catch (exception) { notice.value = 'دریافت آگهی‌های شما با خطا مواجه شد.'; logger.error('loadAds', 'User advertisements request failed', logException(exception)); }
});
</script>

<template>
    <section class="user-dashboard" aria-labelledby="dashboard-title">
        <div class="dashboard-intro"><div><span class="dashboard-kicker">حساب من</span><h1 id="dashboard-title">حساب کاربری من</h1><p>مدیریت آگهی‌ها، نشان‌ها و اطلاعات فردی</p></div><button class="primary-action" type="button" @click="openCreateAd"><i class="pi pi-plus"></i>ثبت آگهی جدید</button></div>
        <div v-if="notice" class="dashboard-notice" role="status">{{ notice }}</div>
        <div class="content-tabs" role="tablist" aria-label="فیلتر وضعیت آگهی‌ها"><button v-for="tab in tabs" :key="tab.key" type="button" role="tab" :aria-selected="activeTab === tab.key" :class="{ active: activeTab === tab.key }" @click="changeTab(tab.key)">{{ tab.label }}<span>{{ tab.key === 'all' ? adsStore.ads.length : adsStore.ads.filter((ad) => tab.key === 'rejected' ? ['rejected', 'expired'].includes(ad.status) : ad.status === tab.key).length }}</span></button></div>
        <div v-if="adsStore.loading" class="ads-list" role="status"><div v-for="index in 3" :key="index" class="ad-skeleton"></div></div>
        <div v-else-if="!filteredAds.length" class="empty-state"><div class="empty-icon"><i class="pi pi-inbox"></i></div><h2>هنوز آگهی‌ای در این بخش ندارید</h2><p>آگهی خود را ثبت کنید تا در بازار مستروام نمایش داده شود.</p><button class="primary-action" type="button" @click="openCreateAd"><i class="pi pi-plus"></i>ثبت آگهی جدید</button></div>
        <div v-else class="ads-list"><article v-for="ad in filteredAds" :key="ad.id" class="user-ad-card"><div class="bank-logo"><i class="pi pi-building"></i></div><div class="ad-content"><div class="ad-card-heading"><span class="ad-type">{{ ad.type === 'supply' ? 'عرضه' : 'تقاضا' }}</span><span class="ad-status" :class="`status-${ad.status || 'unknown'}`"><i class="pi pi-circle-fill"></i>{{ statusLabel(ad.status) }}</span></div><h2>{{ ad.title }}</h2><p class="ad-bank">{{ ad.bank }}<span v-if="ad.bank_plan?.title || ad.plan">، {{ ad.bank_plan?.title || ad.plan }}</span></p><div class="ad-facts"><span><small>مبلغ وام</small><strong>{{ formatMoney(ad.amount) }}</strong></span><span><small>قیمت واگذاری</small><strong>{{ formatMoney(ad.price) }}</strong></span><span><small>موقعیت</small><strong>{{ ad.city }}<small v-if="ad.province">، {{ ad.province }}</small></strong></span></div></div><div class="ad-actions"><button type="button" title="ویرایش آگهی" aria-label="ویرایش آگهی" @click="editAd(ad)"><i class="pi pi-pencil"></i></button><button type="button" title="حذف آگهی" aria-label="حذف آگهی" @click="deleteAd(ad)"><i class="pi pi-trash"></i></button><button type="button" title="مشاهده در سایت" aria-label="مشاهده در سایت" @click="viewAd(ad)"><i class="pi pi-external-link"></i></button></div></article></div>
    </section>
</template>

<style scoped>
.user-dashboard { direction: rtl; }.dashboard-intro { display: flex; align-items: flex-end; justify-content: space-between; gap: 22px; margin-bottom: 30px; }.dashboard-kicker { color: #a62626; font-size: 11px; font-weight: 700; }.dashboard-intro h1 { margin: 8px 0 5px; color: #202a35; font-size: 25px; }.dashboard-intro p { margin: 0; color: #7b8790; font-size: 12px; }.primary-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 16px; border: 0; border-radius: 7px; background: #a62626; color: #fff; cursor: pointer; font: inherit; font-size: 12px; font-weight: 700; }.primary-action:hover { background: #861f1f; }.dashboard-notice { margin-bottom: 18px; padding: 11px 14px; border-right: 3px solid #a62626; background: #fff5f2; color: #812828; font-size: 12px; }.content-tabs { display: flex; gap: 24px; margin-bottom: 20px; border-bottom: 1px solid #e9ecef; }.content-tabs button { display: inline-flex; align-items: center; gap: 7px; padding: 12px 3px; border: 0; border-bottom: 2px solid transparent; background: transparent; color: #77838c; cursor: pointer; font: inherit; font-size: 12px; }.content-tabs button span { min-width: 20px; padding: 2px 5px; border-radius: 10px; background: #f0f2f3; color: #77838c; font-size: 10px; text-align: center; }.content-tabs button.active { border-bottom-color: #a62626; color: #a62626; font-weight: 700; }.content-tabs button.active span { background: #fff0ed; color: #a62626; }.ads-list { display: grid; gap: 12px; }.user-ad-card { display: grid; grid-template-columns: 54px minmax(0, 1fr) auto; align-items: center; gap: 16px; padding: 18px; border: 1px solid #edf0f2; border-radius: 10px; background: #fff; }.bank-logo { display: grid; place-items: center; width: 54px; height: 54px; border-radius: 10px; background: #f5f6f7; color: #68747c; font-size: 21px; }.ad-content { min-width: 0; }.ad-card-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; }.ad-type { color: #7d8991; font-size: 10px; }.ad-status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 14px; font-size: 10px; }.ad-status .pi { font-size: 6px; }.status-approved { background: #ecf8f1; color: #287b50; }.status-pending { background: #fff6e6; color: #a56824; }.status-rejected, .status-expired { background: #fff0ef; color: #ae4747; }.user-ad-card h2 { overflow: hidden; margin: 7px 0 4px; color: #202a35; font-size: 14px; text-overflow: ellipsis; white-space: nowrap; }.ad-bank { margin: 0; color: #7b8790; font-size: 11px; }.ad-facts { display: flex; flex-wrap: wrap; gap: 26px; margin-top: 15px; }.ad-facts span { display: flex; flex-direction: column; gap: 4px; }.ad-facts small { color: #8c969d; font-size: 10px; }.ad-facts strong { color: #36434c; font-size: 11px; }.ad-actions { display: flex; gap: 4px; }.ad-actions button { width: 32px; height: 32px; border: 1px solid #e7eaec; border-radius: 6px; background: #fff; color: #68747c; cursor: pointer; }.ad-actions button:hover { border-color: #e0b5ae; background: #fff5f2; color: #a62626; }.ad-skeleton { height: 150px; border-radius: 10px; background: linear-gradient(90deg, #f1f3f4 25%, #fafafa 50%, #f1f3f4 75%); background-size: 200% 100%; animation: pulse 1.4s infinite; }.empty-state { display: grid; place-items: center; padding: 72px 24px; border: 1px dashed #dfe4e7; border-radius: 10px; background: #fff; text-align: center; }.empty-icon { display: grid; place-items: center; width: 62px; height: 62px; margin-bottom: 14px; border-radius: 50%; background: #f3f5f6; color: #849099; font-size: 25px; }.empty-state h2 { margin: 0 0 7px; font-size: 16px; }.empty-state p { margin: 0 0 20px; color: #7b8790; font-size: 12px; }.empty-state .primary-action { margin: auto; }@keyframes pulse { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
@media (max-width: 720px) { .dashboard-intro { align-items: flex-start; flex-direction: column; }.dashboard-intro h1 { font-size: 21px; }.primary-action { width: 100%; }.content-tabs { gap: 12px; overflow-x: auto; }.content-tabs button { flex: 0 0 auto; }.user-ad-card { grid-template-columns: 42px minmax(0, 1fr); align-items: start; padding: 14px; }.bank-logo { width: 42px; height: 42px; font-size: 17px; }.ad-content { grid-column: 2; }.ad-actions { grid-column: 1 / -1; justify-content: flex-start; }.user-ad-card h2 { white-space: normal; }.ad-facts { gap: 13px; } }
</style>
