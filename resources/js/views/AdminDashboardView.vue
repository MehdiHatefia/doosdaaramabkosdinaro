<script setup>
import { onMounted, ref } from 'vue';
import Skeleton from 'primevue/skeleton';
import { adService } from '../services';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('AdminDashboard');
const ads = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');

async function loadAds() {
	isLoading.value = true;
	try {
		const response = await adService.getAll();
		ads.value = response.data || [];
		logger.info('onMounted', 'Admin advertisements received', { count: ads.value.length });
		if (!ads.value.length) logger.warn('onMounted', 'No admin advertisements received');
	} catch (exception) {
		errorMessage.value = 'دریافت آگهی‌ها با خطا مواجه شد.';
		logger.error('onMounted', 'Admin advertisements request failed', logException(exception));
	} finally {
		isLoading.value = false;
	}
}

onMounted(loadAds);
</script>

<template><section><span class="dashboard-kicker">مدیریت امن</span><h1>داشبورد ادمین</h1><p>صف بررسی آگهی‌ها و شاخص‌های مدیریتی سامانه.</p><Skeleton v-if="isLoading" height="90px" /><p v-else-if="errorMessage" role="alert">{{ errorMessage }}</p><div v-else class="dashboard-stats"><div><strong>{{ ads.filter((ad) => ad.status === 'pending').length }}</strong><span>در انتظار بررسی</span></div><div><strong>{{ ads.length }}</strong><span>آگهی دریافت‌شده</span></div><div><strong>{{ new Set(ads.map((ad) => ad.user_id)).size }}</strong><span>کاربر فعال</span></div></div></section></template>