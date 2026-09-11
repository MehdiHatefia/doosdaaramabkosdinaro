<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/useAuthStore';
import { adService } from '../services';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('Bookmarks');
const router = useRouter();
const authStore = useAuthStore();
const bookmarks = ref([]);
const loading = ref(true);
const error = ref('');
const money = (value) => `${new Intl.NumberFormat('fa-IR').format(Number(value || 0))} میلیون تومان`;

async function load() {
    authStore.syncFromStorage();
    const userId = authStore.user?.id || null;
    loading.value = true;
    error.value = '';
    logger.info('fetch', 'Bookmark fetch started', { userId, count: 0 });
    try {
        const response = await adService.getBookmarks();
        const returned = Array.isArray(response?.data) ? response.data : [];
        bookmarks.value = returned.filter((ad) => ad && ad.id);
        logger.info('fetch', 'Bookmark fetch filtered', { userId, count: bookmarks.value.length });
    } catch (exception) {
        bookmarks.value = [];
        error.value = 'دریافت نشان‌ها با خطا مواجه شد.';
        logger.error('fetch', 'Bookmark fetch failed', logException(exception));
    } finally {
        loading.value = false;
    }
}

async function remove(id) {
    try {
        await adService.removeBookmark(id);
        bookmarks.value = bookmarks.value.filter((ad) => ad.id !== id);
        logger.info('remove', 'Bookmark removed', { advertisementId: id, count: bookmarks.value.length });
    } catch (exception) {
        error.value = 'حذف نشان انجام نشد.';
        logger.error('remove', 'Bookmark removal failed', logException(exception));
    }
}

function goToAds() {
    logger.info('emptyState', 'Returning to advertisements');
    router.push({ name: 'home' });
}

onMounted(load);
</script>
<template><section class="module-view"><header class="module-header"><div><span class="module-kicker">ذخیره‌شده‌ها</span><h1>نشان‌ها و یادداشت‌ها</h1><p>آگهی‌هایی که برای بررسی بعدی ذخیره کرده‌اید.</p></div></header><div v-if="loading" class="module-loading" role="status">در حال دریافت نشان‌ها...</div><p v-else-if="error" class="module-notice" role="alert">{{ error }}</p><div v-else-if="!bookmarks.length" class="module-empty"><i class="pi pi-bookmark"></i><h2>هنوز هیچ آگهی را نشان نکرده‌اید</h2><p>آگهی‌های مورد علاقه خود را ذخیره کنید.</p><button type="button" class="primary-action" @click="goToAds"><i class="pi pi-arrow-right"></i>بازگشت به آگهی‌ها</button></div><div v-else class="saved-list"><article v-for="ad in bookmarks" :key="ad.id" class="saved-item"><i class="pi pi-building"></i><div><h2>{{ ad.title }}</h2><p>{{ ad.bank }}، {{ money(ad.amount) }}</p></div><button type="button" aria-label="حذف از نشان‌ها" @click="remove(ad.id)"><i class="pi pi-bookmark-fill"></i></button></article></div></section></template>
<style scoped>.module-view{direction:rtl}.module-header h1{margin:7px 0 5px;font-size:24px;color:#202a35}.module-header p{margin:0;color:#78848c;font-size:12px}.module-kicker{color:#a62626;font-size:11px;font-weight:700}.module-loading,.module-empty{display:grid;place-items:center;gap:8px;min-height:260px;padding:35px;border:1px dashed #dfe4e7;border-radius:10px;background:#fff;text-align:center;color:#7b8790;font-size:13px}.module-empty h2{margin:3px 0;color:#33414a;font-size:16px}.module-empty p{margin:0;font-size:12px}.module-empty>i{font-size:28px}.module-notice{padding:12px;background:#fff5f2;color:#812828;font-size:12px}.saved-list{display:grid;gap:10px}.saved-item{display:flex;align-items:center;gap:14px;padding:17px;border:1px solid #edf0f2;border-radius:10px;background:#fff}.saved-item>i{color:#69757d;font-size:21px}.saved-item div{flex:1}.saved-item h2{margin:0 0 5px;font-size:14px;color:#202a35}.saved-item p{margin:0;color:#7b8790;font-size:11px}.saved-item button{color:#a62626;cursor:pointer;background:transparent;border:0;font-size:16px}</style>
