<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Paginator from 'primevue/paginator';
import Skeleton from 'primevue/skeleton';
import AdCard from './AdCard.vue';
import AuthModal from './AuthModal.vue';
import CreateAdModal from './CreateAdModal.vue';
import SortControls from './SortControls.vue';
import router from '../router';
import { adService, bankService, locationService } from '../services';
import { createLogger, logException } from '../utils/logger';
import { useAuthStore } from '../stores/useAuthStore';
import { canAccessAdmin } from '../auth/access';
import { authService } from '../services';

const logger = createLogger('HomeView');
const authStore = useAuthStore();

const query = ref('');
const activeType = ref('all');
const selectedBank = ref('');
const selectedPlan = ref('');
const selectedProvince = ref('');
const selectedCity = ref('');
const isCityMenuOpen = ref(false);
const citySearch = ref('');
const selectedCities = ref([]);
const selectedAllIran = ref(false);
const draftProvinces = ref([]);
const draftCities = ref([]);
const draftAllIran = ref(false);
const activeProvinceId = ref(null);
const amountMin = ref(0);
const amountMax = ref(1000);
const priceMin = ref(0);
const priceMax = ref(100);
const validSorts = new Set(['latest', 'amount_desc', 'amount_asc', 'price_asc', 'price_desc', 'rate_asc']);
const requestedSort = new URLSearchParams(window.location.search).get('sort');
const sort = ref(validSorts.has(requestedSort) ? requestedSort : 'latest');
const currentPage = ref(Math.max(1, Number(new URLSearchParams(window.location.search).get('page')) || 1));
const totalRecords = ref(0);
const rowsPerPage = 10;
const selectedAd = ref(null);
const ads = ref([]);
const banks = ref([]);
const publishedAdvertisementsCount = ref(0);
const plans = ref([]);
const provinces = ref([]);
const isLoading = ref(false);
const serverError = ref('');
const isAuthModalOpen = ref(false);
const isContactDialogOpen = ref(false);
const isAccessErrorOpen = ref(false);
const authAccessError = ref('');
const isCreateAdModalOpen = ref(false);
const isUserMenuOpen = ref(false);
const isLoggingOut = ref(false);
const displayUser = computed(() => authStore.user?.name || authStore.user?.mobile || 'کاربر');

const cities = computed(() => provinces.value.flatMap((province) => province.children || []));
const selectedProvinceCities = computed(() => provinces.value.find((province) => province.id === Number(selectedProvince.value))?.children || []);
const draftCitiesForSelection = computed(() => {
    if (draftAllIran.value) return cities.value;
    return provinces.value.find((province) => province.id === activeProvinceId.value)?.children || [];
});
const filteredLocationOptions = computed(() => {
    const term = citySearch.value.trim();
    return draftCitiesForSelection.value.filter((city) => !term || city.name.includes(term));
});
const filters = computed(() => ({
    ...(activeType.value !== 'all' && { type: activeType.value }),
    ...(selectedBank.value && { bank_id: selectedBank.value }),
    ...(selectedPlan.value && { bank_plan_id: selectedPlan.value }),
    ...(!selectedAllIran.value && selectedCities.value.length === 1 && { location_id: selectedCities.value[0] }),
    ...(!selectedAllIran.value && selectedCities.value.length > 1 && { location_ids: selectedCities.value }),
    ...(!selectedAllIran.value && !selectedCities.value.length && selectedCity.value && { location_id: selectedCity.value }),
    ...(query.value && { search: query.value }),
    ...(amountMin.value > 0 && { min_amount: amountMin.value }),
    ...(amountMax.value < 1000 && { max_amount: amountMax.value }),
    ...(priceMin.value > 0 && { min_price: priceMin.value }),
    ...(priceMax.value < 100 && { max_price: priceMax.value }),
    sort: sort.value,
}));

function syncAdFromHash() {
    const match = window.location.hash.match(/^#ad-(\d+)$/);
    if (match) {
        router.replace({ name: 'advertisement.detail', params: { id: match[1] } });
        return;
    }
    selectedAd.value = null;
}

async function loadAds() {
    isLoading.value = true;
    serverError.value = '';

    try {
        const response = await adService.getAll({ ...filters.value, page: currentPage.value, per_page: rowsPerPage });
        ads.value = response.data || response;
        totalRecords.value = response.meta?.total || ads.value.length;
        logger.info('onMounted', 'Advertisements received', { count: ads.value.length });
        if (!ads.value.length) logger.warn('onMounted', 'No advertisements received');
        syncAdFromHash();
    } catch (exception) {
        logger.error('onMounted', 'Advertisements request failed', logException(exception));
        serverError.value = 'دریافت آگهی‌ها با خطا مواجه شد. لطفاً دوباره تلاش کنید.';
        ads.value = [];
    } finally {
        isLoading.value = false;
    }
}

async function loadBanks() {
    try {
        const response = await bankService.getAll();
        banks.value = response.data || [];
        publishedAdvertisementsCount.value = response.meta?.published_advertisements_count || 0;
        logger.info('onMounted', 'Banks received', { count: banks.value.length });
        if (!banks.value.length) logger.warn('onMounted', 'No banks received');
    } catch (exception) {
        logger.error('onMounted', 'Banks request failed', logException(exception));
        serverError.value = 'دریافت اطلاعات بانک‌ها با خطا مواجه شد. لطفاً دوباره تلاش کنید.';
    }
}

async function loadLocations() {
    try {
        const response = await locationService.getProvinces();
        provinces.value = response.data || [];
        logger.info('onMounted', 'Locations received', { count: provinces.value.length });
        if (!provinces.value.length) logger.warn('onMounted', 'No locations received');
    } catch (exception) {
        logger.error('onMounted', 'Locations request failed', logException(exception));
        serverError.value = 'دریافت اطلاعات مناطق با خطا مواجه شد.';
    }
}

async function loadPlans() {
    if (!selectedBank.value) {
        plans.value = [];
        selectedPlan.value = '';
        return;
    }
    try {
        const response = await bankService.getPlans(selectedBank.value);
        plans.value = response.data || [];
        logger.info('onMounted', 'Bank plans received', { bankId: selectedBank.value, count: plans.value.length });
    } catch (exception) {
        logger.error('onMounted', 'Bank plans request failed', logException(exception));
    }
}

onMounted(async () => {
    syncAdFromHash();
    await Promise.all([loadAds(), loadBanks(), loadLocations()]);
    window.addEventListener('popstate', syncAdFromHash);
    window.addEventListener('click', (event) => {
        if (event.target.closest('.detail-cta')) contactAdvertiser();
    });
});

const filteredAds = computed(() => ads.value);
const relatedAds = computed(() => selectedAd.value ? ads.value.filter((ad) => ad.id !== selectedAd.value.id && (ad.bank === selectedAd.value.bank || ad.type === selectedAd.value.type)).slice(0, 3) : []);
const selectedDescription = computed(() => selectedAd.value?.description || '');

watch(filters, () => {
    currentPage.value = 1;
    syncPageQuery(1);
    loadAds();
}, { deep: true });
watch(selectedBank, () => {
    selectedPlan.value = '';
    loadPlans();
});
watch(selectedProvince, () => { selectedCity.value = ''; });

function resetFilters() {
    query.value = '';
    activeType.value = 'all';
    selectedBank.value = '';
    selectedPlan.value = '';
    selectedProvince.value = '';
    selectedCity.value = '';
    selectedCities.value = [];
    selectedAllIran.value = false;
    amountMin.value = 0;
    amountMax.value = 1000;
    priceMin.value = 0;
    priceMax.value = 100;
    sort.value = 'latest';
    currentPage.value = 1;
    syncPageQuery(1);
}

function openCityModal() {
    draftAllIran.value = selectedAllIran.value;
    draftProvinces.value = selectedCities.value.length
        ? [...new Set(selectedCities.value.map((cityId) => provinces.value.find((province) => (province.children || []).some((city) => city.id === cityId))?.id).filter(Boolean))]
        : (selectedProvince.value ? [Number(selectedProvince.value)] : []);
    activeProvinceId.value = draftProvinces.value[0] || provinces.value[0]?.id || null;
    draftCities.value = selectedCities.value.length ? [...selectedCities.value] : (selectedCity.value ? [Number(selectedCity.value)] : []);
    citySearch.value = '';
    isCityMenuOpen.value = true;
    logger.info('openCityModal', 'City selection modal opened');
}

function selectProvince(provinceId) {
    if (!provinceId) {
        draftAllIran.value = true;
        draftProvinces.value = provinces.value.map((province) => province.id);
        draftCities.value = cities.value.map((city) => city.id);
        activeProvinceId.value = null;
    } else if (draftProvinces.value.includes(provinceId)) {
        draftAllIran.value = false;
        activeProvinceId.value = provinceId;
    } else {
        draftAllIran.value = false;
        draftProvinces.value.push(provinceId);
        activeProvinceId.value = provinceId;
    }
    citySearch.value = '';
}

function toggleCity(cityId) {
    draftAllIran.value = false;
    if (draftCities.value.includes(cityId)) {
        draftCities.value = draftCities.value.filter((id) => id !== cityId);
    } else {
        draftCities.value.push(cityId);
    }
}

function clearCitySelection() {
    draftAllIran.value = false;
    draftProvinces.value = [];
    draftCities.value = [];
    activeProvinceId.value = null;
    citySearch.value = '';
}

async function confirmCitySelection() {
    const confirmedCities = [...draftCities.value];
    selectedAllIran.value = draftAllIran.value;
    selectedCities.value = confirmedCities;
    selectedProvince.value = '';
    await nextTick();
    selectedCity.value = confirmedCities.length === 1 ? confirmedCities[0] : '';
    isCityMenuOpen.value = false;
    logger.info('confirmCitySelection', 'City selection confirmed', { provinceIds: draftProvinces.value, cityIds: confirmedCities });
}

function cancelCitySelection() {
    isCityMenuOpen.value = false;
    logger.info('cancelCitySelection', 'City selection cancelled');
}

function changeSort(nextSort) {
    sort.value = nextSort;
}

function syncPageQuery(page) {
    const params = new URLSearchParams(window.location.search);
    if (page > 1) params.set('page', page); else params.delete('page');
    const queryString = params.toString();
    window.history.replaceState({}, '', `${window.location.pathname}${queryString ? `?${queryString}` : ''}${window.location.hash}`);
}

async function onPageChange(event) {
    currentPage.value = event.page + 1;
    syncPageQuery(currentPage.value);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    await loadAds();
}

function openAd(ad) {
    router.push({ name: 'advertisement.detail', params: { id: ad.id } });
}

function closeAd() {
    router.push({ name: 'home' });
}

function contactAdvertiser() {
    authStore.syncFromStorage();

    if (!authStore.isAuthenticated) {
        isAuthModalOpen.value = true;
        return;
    }

    if (!authStore.isVerified) {
        authAccessError.value = 'شما دسترسی لازم را ندارید. باید مراحل احراز هویت را تکمیل کنید.';
        isAccessErrorOpen.value = true;
        return;
    }

    isContactDialogOpen.value = true;
}

function handleNewAdClick() {
    logger.info('handleNewAdClick', 'Checking access state');
    authStore.syncFromStorage();

    if (!authStore.isAuthenticated) {
        isAuthModalOpen.value = true;
        return;
    }

    if (!authStore.isVerified) {
        authAccessError.value = 'دسترسی شما محدود است. جهت ثبت آگهی در سامانه، ابتدا باید فرایند احراز هویت خود را تکمیل کنید';
        isAccessErrorOpen.value = true;
        return;
    }

    isCreateAdModalOpen.value = true;
}

function handleAuthenticated() {
    authStore.syncFromStorage();
    if (selectedAd.value) {
        isContactDialogOpen.value = true;
    } else if (authStore.isVerified) {
        isCreateAdModalOpen.value = true;
    } else {
        authAccessError.value = 'دسترسی شما محدود است. جهت ثبت آگهی در سامانه، ابتدا باید فرایند احراز هویت خود را تکمیل کنید';
        isAccessErrorOpen.value = true;
    }
}

function navigateDashboard() {
    logger.info('navigateDashboard', 'Dashboard navigation requested', { userId: authStore.user?.id, admin: canAccessAdmin(authStore.user) });
    isUserMenuOpen.value = false;
    router.push({ name: canAccessAdmin(authStore.user) ? 'admin.dashboard' : 'user.my-ads' });
}

function navigateMyAds() {
    logger.info('navigateDashboard', 'My advertisements navigation requested', { userId: authStore.user?.id });
    isUserMenuOpen.value = false;
    router.push({ name: 'user.my-ads' });
}

async function logout() {
    if (isLoggingOut.value) return;
    logger.info('logout', 'Logout started', { userId: authStore.user?.id });
    isLoggingOut.value = true;
    try {
        await authService.logout();
    } catch (exception) {
        logger.error('logout', 'Logout request failed', logException(exception));
    } finally {
        authStore.clear();
        isUserMenuOpen.value = false;
        isLoggingOut.value = false;
        logger.info('logout', 'Logout completed');
    }
}

function handleUnverifiedAccess(event) {
    authAccessError.value = event.detail?.message || 'دسترسی شما محدود است. ابتدا فرایند احراز هویت خود را تکمیل کنید.';
    isAccessErrorOpen.value = true;
}

function goToVerification() {
    isAccessErrorOpen.value = false;
    router.push({ name: 'user.verification' });
}

onMounted(() => window.addEventListener('auth:unverified', handleUnverifiedAccess));
onUnmounted(() => window.removeEventListener('auth:unverified', handleUnverifiedAccess));
</script>

<template>
    <div class="page-shell">
        <header class="site-header">
            <div class="header-inner">
                <div class="header-main">
                    <div class="brand flex items-center gap-2 cursor-pointer select-none" @click="$router.push('/')"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACYAAAAmCAYAAACoPemuAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAA7DAAAOwwHNb7hkAAAAAWJLR0QA/wUsnwAAAAd0SU1FB+oJBAQ6EZ2s3+gAAAG/SURBVFjHxZfRScNAEIa/m7S1UGvFghp3tG7h3MEV3KGl2MGB4hZu4AZu4AaK9eAOLUqL1qQ/h4VcbkISz8L3QQg3yX2/7r673eX862S+4TngFTj1r0+Z5x7hW1/gY973P6xN2zXn1u8H+n3YVl9wAkyAg9Z23X/gA/jEwXJ4X8qgC9wBtwp6y2Bw60w+C+f6h8+xGvL8A17w2F1oHkO0576QnZ/l12nQo1385+n64sH4x18D272l0Vw3h/i0a02qgGj4Fp6f9y1qfC9/P+fW79d34346q14rVf56sRauuI4nUfVaqfLXo+q1UuWvR9VrpUr131j0Wqnyr0fVa6VK9d9Y9FqpUr13vVaqVP+NRa+VKtV712ulSvXfWPRaqVK9d71WqlT/jUWvlSrVe9drpUr131j0WqlSvXe9VqpU/41Fr5Uq1XvXa6VK9d9Y9FqpUr13vVaqVP+NRa+VKtV712ulSvXfWPRaqVK9d71WqlT/jUWvlSrVe9drpUr131j0WqlSvXe9VqpU/41Fr5Uq1XvXa6VK9d9Y9FqpUr13vVaqVP+NRa+VKtV712ulSvXfWPRaqVK9d71WqlT/jUWvlSrVe9drpUr131j0WqlSvXe9VqpU/41Fr5Uq1XvXa6VK9d9Y9FqpUr13vVaqVP+NRa+VKtV712ulSvXfWPRaqVK9d71WqlT+V0Yn0v9w9vP00+y5v+G5i9V/gS++c9mZ5B8AAAAASUVORK5CYII=" alt="مستروام" class="brand-logo rounded-lg shrink-0" style="width: 38px; height: 38px; object-fit: cover;" /><span class="brand-text font-black text-xl text-[#A62626] tracking-tight">مستروام</span></div>
                    <div class="location-picker"><button class="location-button" type="button" aria-haspopup="dialog" :aria-expanded="isCityMenuOpen" @click="openCityModal"><i class="pi pi-map-marker"></i><span>{{ selectedAllIran ? 'کل ایران' : (selectedCities.length === 1 ? cities.find((city) => city.id === selectedCities[0])?.name : (selectedCities.length > 1 ? `${selectedCities.length} شهر انتخاب شده` : (selectedCity ? cities.find((city) => city.id === Number(selectedCity))?.name : 'همه شهرها'))) }}</span><i class="pi pi-chevron-down"></i></button></div>
                    <label class="header-search"><i class="pi pi-search"></i><input v-model="query" type="search" placeholder="جستجو در همه آگهی‌ها..." aria-label="جستجو در همه آگهی‌ها" /></label>
                    <div class="header-actions"><button type="button" class="header-link"><i class="pi pi-comments"></i><span>پیام‌ها</span></button><button v-if="!authStore.isAuthenticated" type="button" class="header-link auth-header-link" @click="isAuthModalOpen = true"><i class="pi pi-user"></i><span>ورود / ثبت‌نام</span></button><div v-else class="user-menu"><button type="button" class="user-menu__trigger" aria-haspopup="menu" :aria-expanded="isUserMenuOpen" @click="isUserMenuOpen = !isUserMenuOpen"><i class="pi pi-user"></i><span>{{ displayUser }}</span><i class="pi pi-chevron-down"></i></button><div v-if="isUserMenuOpen" class="user-menu__dropdown" role="menu"><button type="button" role="menuitem" @click="navigateDashboard"><i class="pi pi-th-large"></i>پنل کاربری</button><button type="button" role="menuitem" @click="navigateMyAds"><i class="pi pi-list"></i>آگهی‌های من</button><button type="button" role="menuitem" :disabled="isLoggingOut" @click="logout"><i class="pi pi-sign-out"></i>{{ isLoggingOut ? 'در حال خروج...' : 'خروج از حساب' }}</button></div></div><button class="post-button" type="button" @click="handleNewAdClick"><i class="pi pi-plus"></i>ثبت آگهی</button></div>
                </div>
            </div>
        </header>

        <div v-if="isCityMenuOpen" class="city-modal-backdrop" role="presentation" @click.self="cancelCitySelection">
            <section class="city-modal" role="dialog" aria-modal="true" aria-labelledby="city-modal-title" dir="rtl">
                <header class="city-modal__header"><h2 id="city-modal-title">انتخاب شهر</h2><button type="button" class="city-modal__clear" @click="clearCitySelection">حذف همه</button></header>
                <div class="city-modal__body">
                    <label class="city-search"><i class="pi pi-search"></i><input v-model.trim="citySearch" type="search" placeholder="جستجو در شهرها" aria-label="جستجو در شهرها" /><i class="pi pi-search"></i></label>
                    <div v-if="draftCities.length" class="selected-city-chips"><span v-for="cityId in draftCities" :key="cityId" class="selected-city-chip">{{ cities.find((city) => city.id === cityId)?.name }}<button type="button" :aria-label="`حذف ${cities.find((city) => city.id === cityId)?.name}`" @click="toggleCity(cityId)"><i class="pi pi-times"></i></button></span></div>
                    <div class="city-modal__content">
                        <div class="province-list" role="listbox" aria-label="استان‌ها"><button type="button" :class="{ active: draftAllIran }" @click="selectProvince('')"><i :class="draftAllIran ? 'pi pi-check' : 'pi pi-minus-square'"></i>کل ایران</button><button v-for="province in provinces" :key="province.id" type="button" :class="{ active: !draftAllIran && draftProvinces.includes(province.id) }" @click="selectProvince(province.id)">{{ province.name }}<i :class="!draftAllIran && draftProvinces.includes(province.id) ? 'pi pi-check' : 'pi pi-angle-left'"></i></button></div>
                        <div class="city-list" role="listbox" aria-label="شهرها"><p>{{ draftAllIran ? 'کل ایران' : (activeProvinceId ? provinces.find((province) => province.id === activeProvinceId)?.name : 'انتخاب استان') }}</p><button v-for="city in filteredLocationOptions" :key="city.id" type="button" :class="{ active: draftCities.includes(city.id) }" @click="toggleCity(city.id)">{{ city.name }}<i v-if="draftCities.includes(city.id)" class="pi pi-check"></i></button><span v-if="!draftAllIran && activeProvinceId && !filteredLocationOptions.length" class="city-empty">شهری پیدا نشد</span></div>
                    </div>
                </div>
                <footer class="city-modal__footer"><button type="button" class="city-modal__cancel" @click="cancelCitySelection">انصراف</button><button type="button" class="city-modal__confirm" :disabled="!draftCities.length" @click="confirmCitySelection">تأیید</button></footer>
            </section>
        </div>

        <main v-if="!selectedAd" id="listings" class="main-content">
            <div class="page-heading"><div><span class="breadcrumb">خانه / آگهی‌های وام</span><h1>آگهی‌های امتیاز وام</h1><p>{{ totalRecords }} آگهی در مستروام</p></div><SortControls v-model="sort" @change-sort="changeSort" /></div>
            <div class="listing-layout">
                <aside class="filters-panel" aria-label="فیلتر آگهی‌ها">
                    <div class="filter-title"><h2><i class="pi pi-filter"></i>فیلتر آگهی‌ها</h2><button type="button" @click="resetFilters">حذف همه</button></div>
                    <div class="filter-block"><label for="province"><i class="pi pi-map-marker"></i>استان</label><select id="province" v-model="selectedProvince"><option value="">همه استان‌ها</option><option v-for="province in provinces" :key="province.id" :value="province.id">{{ province.name }}</option></select><label for="city">شهر</label><select id="city" v-model="selectedCity" :disabled="!selectedProvince"><option value="">همه شهرها</option><option v-for="city in selectedProvinceCities" :key="city.id" :value="city.id">{{ city.name }}</option></select></div>
                    <div class="filter-block"><label><i class="pi pi-building"></i>دسته‌بندی بانک</label><button type="button" class="bank-option" :class="{ active: !selectedBank }" @click="selectedBank = ''"><span>همه بانک‌ها</span><span class="bank-option-count">{{ publishedAdvertisementsCount }}</span><i :class="!selectedBank ? 'pi pi-check-circle' : 'pi pi-angle-left'"></i></button><button v-for="bank in banks" :key="bank.id" type="button" class="bank-option" :class="{ active: selectedBank === bank.id }" @click="selectedBank = bank.id"><span>{{ bank.name }}</span><span class="bank-option-count">{{ bank.advertisements_count || 0 }}</span><i :class="selectedBank === bank.id ? 'pi pi-check-circle' : 'pi pi-angle-left'"></i></button><select v-if="selectedBank" v-model="selectedPlan"><option value="">همه طرح‌ها</option><option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.title }}</option></select></div>
                    <div class="filter-block"><span class="filter-label">نوع آگهی</span><label class="check-row"><input v-model="activeType" type="radio" value="all" name="type" />همه آگهی‌ها</label><label class="check-row"><input v-model="activeType" type="radio" value="supply" name="type" />فروش امتیاز وام (عرضه)</label><label class="check-row"><input v-model="activeType" type="radio" value="demand" name="type" />خریدار وام (تقاضا)</label></div>
                    <div class="filter-block"><span class="filter-label">مبلغ وام <small>میلیون تومان</small></span><div class="compact-fields"><input v-model.number="amountMin" type="number" min="0" max="1000" aria-label="حداقل مبلغ وام" /><span>تا</span><input v-model.number="amountMax" type="number" min="0" max="1000" aria-label="حداکثر مبلغ وام" /></div></div>
                    <div class="filter-block"><span class="filter-label">قیمت واگذاری <small>میلیون تومان</small></span><div class="compact-fields"><input v-model.number="priceMin" type="number" min="0" max="100" aria-label="حداقل قیمت واگذاری" /><span>تا</span><input v-model.number="priceMax" type="number" min="0" max="100" aria-label="حداکثر قیمت واگذاری" /></div></div>
                </aside>
                <section class="results-area"><div v-if="isLoading" class="ads-grid" role="status"><Skeleton v-for="index in 6" :key="index" height="248px" /></div><div v-else-if="serverError" class="empty-state" role="alert"><i class="pi pi-exclamation-triangle"></i><p>{{ serverError }}</p></div><template v-else><div class="results-toolbar"><span><i class="pi pi-list"></i>نتیجه جستجو</span><span>{{ filteredAds.length }} مورد</span></div><div class="ads-grid"><AdCard v-for="ad in filteredAds" :key="ad.id" :ad="ad" @view="openAd" /><div v-if="!filteredAds.length" class="empty-state"><i class="pi pi-search"></i><h3>آگهی‌ای پیدا نشد</h3><p>فیلترها یا عبارت جستجو را تغییر بده.</p></div></div><Paginator v-if="totalRecords > rowsPerPage" class="ads-paginator" :rows="rowsPerPage" :totalRecords="totalRecords" :first="(currentPage - 1) * rowsPerPage" @page="onPageChange"><template #firstpagelinkicon><i class="pi pi-angle-double-right"></i></template><template #prevpagelinkicon><i class="pi pi-chevron-right"></i></template><template #nextpagelinkicon><i class="pi pi-chevron-left"></i></template><template #lastpagelinkicon><i class="pi pi-angle-double-left"></i></template></Paginator></template></section>
            </div>
        </main>
        <main v-else class="detail-page main-content">
            <button class="back-button" type="button" @click="closeAd"><i class="pi pi-arrow-right"></i>بازگشت به آگهی‌ها</button>
            <div class="detail-layout"><article class="detail-card"><div class="detail-media"><i class="pi pi-image"></i><span>جای تصویر آگهی</span><small>تصاویر واقعی آگهی اینجا نمایش داده می‌شوند</small></div><div class="detail-card__top"><span class="bank-avatar">{{ selectedAd.bank.replace('بانک ', '').slice(0, 1) }}</span><span class="ad-type" :class="selectedAd.type">{{ selectedAd.type === 'supply' ? 'عرضه امتیاز' : 'تقاضای امتیاز' }}</span></div><h1>{{ selectedAd.title }}</h1><p class="detail-subtitle">{{ selectedAd.bank }}، {{ selectedAd.bank_plan?.title }}</p><div class="detail-meta"><span><i class="pi pi-map-marker"></i>{{ selectedAd.city }}<small v-if="selectedAd.province">، {{ selectedAd.province }}</small></span><span><i class="pi pi-clock"></i>{{ selectedAd.time }}</span></div><section class="detail-description"><h2><i class="pi pi-align-right"></i>توضیحات آگهی</h2><p>{{ selectedDescription }}</p></section><dl class="detail-values"><div><dt>مبلغ کل وام</dt><dd>{{ selectedAd.amount }} میلیون تومان</dd></div><div><dt>قیمت واگذاری امتیاز</dt><dd>{{ selectedAd.price }} میلیون تومان</dd></div><div><dt>نرخ سود / کارمزد</dt><dd>{{ selectedAd.fee }} درصد</dd></div></dl><button class="detail-cta" type="button"><i class="pi pi-phone"></i>تماس با آگهی‌دهنده</button></article><aside class="detail-side"><i class="pi pi-shield"></i><h2>قبل از معامله</h2><p>شرایط انتقال امتیاز و مدارک لازم را پیش از هماهنگی بررسی کن.</p><div class="location-map"><span class="map-road map-road-one"></span><span class="map-road map-road-two"></span><i class="pi pi-map-marker"></i></div><strong><i class="pi pi-map-marker"></i>محدوده آگهی</strong><p>{{ selectedAd.city }}<small v-if="selectedAd.province">، {{ selectedAd.province }}</small></p></aside></div>
            <section class="related-section"><div class="related-heading"><h2><i class="pi pi-th-large"></i>آگهی‌های مشابه</h2><span>{{ relatedAds.length }} مورد</span></div><div class="related-grid"><AdCard v-for="ad in relatedAds" :key="ad.id" :ad="ad" @view="openAd" /></div></section>
        </main>
        <footer id="post" class="site-footer"><div class="footer-inner"><div class="footer-brand"><a class="brand" href="#listings"><span class="brand-mark">م</span><span><b>مستروام</b><small>MRVaM</small></span></a><p>بازار آنلاین تبادل امتیاز وام، با تجربه‌ای شفاف و مطمئن.</p></div><div class="footer-column"><h2>مستروام</h2><a href="#listings">آگهی‌های وام</a><a href="#post">ثبت آگهی</a><a href="#listings">راهنمای استفاده</a></div><div class="footer-column"><h2>پشتیبانی</h2><a href="#post">تماس با ما</a><a href="#post">قوانین و مقررات</a><a href="#post">حریم خصوصی</a></div><div class="footer-trust"><span><i class="pi pi-shield"></i></span><div><strong>معامله مطمئن‌تر</strong><small>اطلاعات کاربران نزد ما محفوظ است</small></div></div></div><div class="footer-bottom"><span>© ۱۴۰۴ مستروام، همه حقوق محفوظ است.</span><span>ساخته‌شده برای انتخابی آگاهانه</span></div></footer>
        <AuthModal v-model:visible="isAuthModalOpen" @authenticated="handleAuthenticated" />
        <CreateAdModal v-model:visible="isCreateAdModalOpen" :banks="banks" :provinces="provinces" @created="loadAds" />
        <Dialog v-model:visible="isContactDialogOpen" modal header="اطلاعات آگهی‌دهنده" :draggable="false" class="contact-dialog" dir="rtl">
            <div class="contact-profile"><div class="contact-avatar"><i class="pi pi-user"></i></div><h2>{{ selectedAd?.type === 'demand' ? 'خریدار امتیاز وام' : 'فروشنده امتیاز وام' }}</h2><p class="contact-status"><i class="pi pi-check-circle"></i>احراز هویت شده</p><dl><div><dt>شماره تماس</dt><dd dir="ltr">{{ selectedAd?.advertiser_mobile }}</dd></div><div><dt>موقعیت</dt><dd>{{ selectedAd?.city }}</dd></div><div><dt>بانک مرتبط</dt><dd>{{ selectedAd?.bank }}</dd></div></dl><a class="contact-call" :href="`tel:${selectedAd?.advertiser_mobile}`"><i class="pi pi-phone"></i>تماس با آگهی‌دهنده</a></div>
        </Dialog>
        <Dialog v-model:visible="isAccessErrorOpen" modal header="دسترسی محدود است" :draggable="false" class="contact-dialog" dir="rtl">
            <div class="access-error"><i class="pi pi-lock"></i><p>{{ authAccessError }}</p><button type="button" @click="goToVerification"><i class="pi pi-arrow-left"></i>تکمیل احراز هویت</button></div>
        </Dialog>
    </div>
</template>

<style scoped>
.page-shell { min-height: 100vh; background: #fff; color: var(--ink); }
.site-header { position: sticky; top: 0; z-index: 50; background: #fff; border-bottom: 1px solid #eee; }
.header-inner, .main-content, .site-footer { max-width: 1280px; margin: 0 auto; padding-left: 24px; padding-right: 24px; }
.header-main { min-height: 74px; display: flex; align-items: center; gap: 22px; }
.brand { display: flex; align-items: center; gap: 9px; flex-shrink: 0; color: var(--ink); text-decoration: none; }.brand b { display: block; font-size: 17px; }.brand small { display: block; color: var(--muted); direction: ltr; font-size: 8px; letter-spacing: 2px; }.brand-mark { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 8px; background: #a62626; color: #fff; font-size: 20px; font-weight: 800; }
.location-button, .header-link { border: 0; background: transparent; color: var(--muted); cursor: pointer; }.location-button { display: flex; gap: 7px; align-items: center; padding: 12px 18px; border-right: 1px solid #eee; white-space: nowrap; }.location-button .pi:last-child { font-size: 10px; }
.location-picker { position: relative; flex-shrink: 0; }.city-menu { position: absolute; top: calc(100% + 10px); right: 0; z-index: 60; display: flex; flex-direction: column; min-width: 150px; padding: 6px; border: 1px solid #e5e9ee; border-radius: 8px; background: #fff; box-shadow: 0 10px 24px rgba(32, 42, 53, .14); }.city-menu button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 10px; border: 0; border-radius: 5px; background: transparent; color: #555; cursor: pointer; font-size: 12px; text-align: right; }.city-menu button:hover, .city-menu button[aria-selected='true'] { background: #fff1ee; color: #a62626; }.city-menu button .pi { font-size: 12px; }
.city-modal-backdrop { position: fixed; inset: 0; z-index: 120; display: grid; place-items: center; padding: 10px; background: rgba(25, 31, 36, .48); }.city-modal { display: flex; flex-direction: column; width: min(490px, 100%); max-height: min(650px, calc(100vh - 20px)); overflow: hidden; border-radius: 4px; background: #fff; box-shadow: 0 18px 45px rgba(20, 30, 40, .28); }.city-modal__header { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 26px 32px 20px; }.city-modal__header h2 { margin: 0; color: #172638; font-size: 18px; }.city-modal__clear { border: 0; background: transparent; color: #d7353b; cursor: pointer; font: inherit; font-size: 11px; }.city-modal__body { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; overflow: hidden; padding: 0 32px; }.city-search { position: relative; display: block; flex: 0 0 auto; }.city-search input { width: 100%; box-sizing: border-box; height: 40px; padding: 0 42px; border: 1px solid #aeb5bf; border-radius: 4px; outline: 0; color: #27384b; font: inherit; font-size: 12px; text-align: right; }.city-search input:focus { border-color: #d7353b; box-shadow: 0 0 0 2px rgba(215, 53, 59, .1); }.city-search .pi-search:first-child { position: absolute; top: 12px; right: 14px; z-index: 1; color: #8fa0b1; pointer-events: none; }.city-search .pi-search:last-child { display: none; }.selected-city-chips { display: flex; flex: 0 0 auto; flex-wrap: wrap; gap: 8px; margin: 16px 0; }.selected-city-chip { display: inline-flex; align-items: center; gap: 8px; margin: 0; padding: 7px 12px; border: 1px solid #d7353b; border-radius: 18px; color: #d7353b; font-size: 12px; }.selected-city-chip button { border: 0; background: transparent; color: #d7353b; cursor: pointer; }.city-modal__content { display: grid; flex: 1 1 auto; grid-template-columns: 1fr 1fr; grid-template-rows: minmax(0, 1fr); min-height: 0; overflow: hidden; margin: 0 -32px; border-top: 1px solid #e2e5e8; }.province-list, .city-list { min-height: 0; overflow-y: auto; padding: 10px 16px 14px; }.province-list { border-left: 1px solid #e2e5e8; }.province-list button, .city-list button { display: flex; align-items: center; justify-content: space-between; width: 100%; min-height: 42px; padding: 8px 10px; border: 0; border-bottom: 1px solid #e1e4e7; background: #fff; color: #192b3e; cursor: pointer; font: inherit; font-size: 12px; text-align: right; }.province-list button .pi { color: #9aabba; }.province-list button:first-child .pi { color: #d7353b; }.province-list button.active, .province-list button:hover, .city-list button.active, .city-list button:hover { background: #fff5f3; color: #c32e35; }.city-list p { margin: 6px 10px 10px; color: #6d7d8c; font-size: 11px; }.city-list button { color: #435568; }.city-list button .pi { color: #d7353b; }.city-empty { display: block; padding: 28px 10px; color: #9aa5ad; font-size: 11px; }.city-modal__footer { flex: 0 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 13px 16px 10px; border-top: 1px solid #e2e5e8; }.city-modal__footer button { height: 40px; border: 1px solid #aeb5bf; border-radius: 4px; background: #fff; color: #27384b; cursor: pointer; font: inherit; font-size: 12px; }.city-modal__confirm { border-color: #d7353b !important; background: #d7353b !important; color: #fff !important; }.city-modal__confirm:disabled { border-color: #c7c9ce !important; background: #c7c9ce !important; color: #70747c !important; cursor: not-allowed; }
.header-search { position: relative; flex: 1; max-width: 500px; }.header-search .pi { position: absolute; right: 14px; top: 12px; color: var(--muted); }.header-search input { width: 100%; height: 42px; padding: 0 42px 0 14px; border: 0; border-radius: 7px; background: #f5f5f5; color: var(--ink); outline: 0; }.header-search input:focus { box-shadow: 0 0 0 2px #e7b3b3; background: #fff; }
.header-actions { display: flex; align-items: center; gap: 5px; margin-right: auto; }.header-link { display: flex; align-items: center; gap: 6px; padding: 10px 12px; font-size: 12px; }.header-link:hover { color: #a62626; }.post-button { display: inline-flex; align-items: center; gap: 7px; padding: 11px 17px; border-radius: 7px; background: #a62626; color: #fff; font-size: 12px; text-decoration: none; }.post-button:hover { background: #861f1f; }
.user-menu { position: relative; }.user-menu__trigger { display: flex; align-items: center; gap: 7px; max-width: 150px; padding: 10px 12px; border: 0; background: transparent; color: var(--muted); cursor: pointer; font: inherit; font-size: 12px; }.user-menu__trigger span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }.user-menu__trigger:hover { color: #a62626; }.user-menu__trigger .pi:last-child { font-size: 9px; }.user-menu__dropdown { position: absolute; top: calc(100% + 8px); left: 0; z-index: 70; min-width: 170px; padding: 6px; border: 1px solid #e5e9ee; border-radius: 8px; background: #fff; box-shadow: 0 10px 24px rgba(32, 42, 53, .14); }.user-menu__dropdown button { display: flex; align-items: center; gap: 9px; width: 100%; padding: 10px; border: 0; border-radius: 5px; background: transparent; color: #56636d; cursor: pointer; font: inherit; font-size: 12px; text-align: right; }.user-menu__dropdown button:hover { background: #fff1ee; color: #a62626; }.user-menu__dropdown button:disabled { cursor: wait; opacity: .55; }.user-menu__dropdown .pi { width: 15px; color: #a62626; }
.main-content { padding-top: 34px; padding-bottom: 60px; }.page-heading { display: flex; justify-content: space-between; align-items: end; padding-bottom: 24px; border-bottom: 1px solid #eee; }.breadcrumb { color: var(--muted); font-size: 11px; }.page-heading h1 { margin: 9px 0 4px; font-size: 25px; font-weight: 700; }.page-heading p, .results-toolbar { margin: 0; color: var(--muted); font-size: 12px; }.sort-button { border: 1px solid #ddd; border-radius: 6px; background: #fff; color: var(--muted); padding: 10px 13px; font-size: 11px; cursor: pointer; }
.listing-layout { display: grid; grid-template-columns: minmax(0, 1fr) 260px; gap: 32px; padding-top: 28px; direction: ltr; }.filters-panel, .results-area { direction: rtl; grid-row: 1; }.filters-panel { grid-column: 2; height: max-content; padding: 22px 24px 24px 18px; border-right: 1px solid #eee; }.filter-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 26px; }.filter-title h2 { margin: 0; font-size: 16px; }.filter-title button { border: 0; background: transparent; color: #a62626; cursor: pointer; font-size: 11px; }.filter-block { padding: 0 0 22px; margin-bottom: 22px; border-bottom: 1px solid #f0f0f0; }.filter-block > label, .filter-label { display: block; margin-bottom: 10px; color: #444; font-size: 12px; font-weight: 600; }.filter-label small { float: left; color: #999; font-size: 10px; font-weight: 400; }.filter-block select, .compact-fields input { height: 38px; width: 100%; border: 1px solid #ddd; border-radius: 6px; background: #fff; color: #444; padding: 0 9px; font-size: 12px; outline: 0; }.filter-block select:focus, .compact-fields input:focus { border-color: #a62626; }.bank-option { display: flex; justify-content: space-between; width: 100%; padding: 9px 0; border: 0; background: transparent; color: #666; cursor: pointer; font-size: 12px; text-align: right; }.bank-option:hover, .bank-option.active { color: #a62626; }.bank-option .pi { order: 0; }.check-row { display: flex; align-items: center; gap: 8px; margin: 11px 0; color: #666; font-size: 11px; font-weight: 400 !important; cursor: pointer; }.check-row input { accent-color: #a62626; }.compact-fields { display: grid; grid-template-columns: minmax(0, 1fr) 24px minmax(0, 1fr); align-items: center; gap: 7px; }.compact-fields input { min-width: 0; text-align: center; }.compact-fields span { color: #999; font-size: 11px; text-align: center; }
.results-area { grid-column: 1; min-width: 0; }.results-toolbar { display: flex; justify-content: space-between; padding-bottom: 14px; }.ads-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }.empty-state { grid-column: 1 / -1; padding: 60px 20px; color: #999; text-align: center; }.empty-state h3 { color: #555; font-size: 15px; }.empty-state p { font-size: 12px; }.site-footer { display: flex; align-items: center; gap: 10px; min-height: 78px; border-top: 1px solid #eee; color: #555; font-size: 13px; }.site-footer .brand-mark { width: 30px; height: 30px; font-size: 15px; }.site-footer small { margin-right: 10px; color: #999; font-size: 11px; }
.site-header { border-top: 3px solid #ef8354; box-shadow: 0 3px 16px rgba(32, 42, 53, .08); }.brand-mark { background: linear-gradient(145deg, #c74646, #a62626); box-shadow: 0 5px 12px rgba(166, 38, 38, .22); }.page-heading h1 { color: #202a35; letter-spacing: -.2px; }.page-heading p { color: #71808c; }.sort-button:hover { border-color: #b83232; color: #b83232; }.filters-panel { background: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(32, 42, 53, .05); }.filter-title { padding-bottom: 14px; border-bottom: 2px solid #ef8354; }.filter-block:last-child { border-bottom: 0; }.bank-option.active { font-weight: 700; }.results-toolbar span:last-child { color: #b83232; font-weight: 700; }
@media (max-width: 1000px) { .header-main { gap: 12px; }.header-link span { display: none; }.ads-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 700px) { .header-inner, .main-content, .site-footer { padding-left: 16px; padding-right: 16px; }.header-main { min-height: 64px; flex-wrap: wrap; padding: 10px 0; }.header-search { order: 5; flex-basis: 100%; max-width: none; }.location-button { margin-right: auto; border: 0; padding: 8px 4px; }.header-actions { gap: 0; }.header-link { padding: 8px; }.post-button { padding: 10px; font-size: 0; }.post-button .pi { font-size: 14px; }.main-content { padding-top: 24px; }.page-heading { align-items: flex-start; }.page-heading h1 { font-size: 21px; }.sort-button { margin-top: 18px; font-size: 0; }.sort-button .pi { font-size: 14px; }.listing-layout { display: block; }.filters-panel { margin-bottom: 28px; padding: 22px 16px; border-right: 0; border-top: 1px solid #eee; }.ads-grid { grid-template-columns: 1fr; }.site-footer { min-height: 64px; } }
.header-search input, .page-heading, .results-toolbar, .filters-panel, .results-area, .site-footer { text-align: right; }
.compact-fields input { text-align: right; }
.filter-title h2 { display: flex; align-items: center; gap: 7px; }.filter-title h2 .pi, .filter-block > label .pi, .results-toolbar .pi { color: #a62626; font-size: 13px; }.results-toolbar span { display: inline-flex; align-items: center; gap: 7px; }
.bank-option { align-items: center; gap: 8px; }.bank-option > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }.bank-option-count { margin-right: auto; padding: 2px 8px; border-radius: 999px; background: #f1f3f4; color: #69757d; font-size: 10px; font-weight: 500; line-height: 1.5; }.bank-option:hover .bank-option-count,.bank-option.active .bank-option-count { background: #fff0ed; color: #a62626; }
.detail-page { min-height: calc(100vh - 74px); }.back-button { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px; border: 0; background: transparent; color: #a62626; cursor: pointer; font-size: 12px; }.detail-layout { display: grid; grid-template-columns: minmax(0, 680px) 260px; gap: 24px; justify-content: center; direction: ltr; }.detail-card, .detail-side { direction: rtl; }.detail-card { padding: 28px; border: 1px solid #e5e9ee; border-radius: 12px; background: #fff; box-shadow: 0 4px 16px rgba(32, 42, 53, .07); }.detail-card__top { display: flex; align-items: center; justify-content: space-between; }.detail-card h1 { margin: 22px 0 8px; color: #202a35; font-size: 25px; }.detail-subtitle { margin: 0; color: #71808c; font-size: 12px; }.detail-meta { display: flex; gap: 22px; margin-top: 22px; padding: 14px 0; border-top: 1px solid #edf0f2; border-bottom: 1px solid #edf0f2; color: #71808c; font-size: 11px; }.detail-meta span { display: inline-flex; align-items: center; gap: 6px; }.detail-meta .pi { color: #a62626; }.detail-values { margin: 16px 0 22px; }.detail-values div { display: flex; justify-content: space-between; padding: 13px 0; border-bottom: 1px solid #f0f2f4; }.detail-values dt { color: #71808c; font-size: 12px; }.detail-values dd { margin: 0; color: #202a35; font-size: 13px; font-weight: 700; }.detail-cta { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 13px; border: 0; border-radius: 7px; background: #a62626; color: #fff; cursor: pointer; font-size: 13px; font-weight: 600; }.detail-cta:hover { background: #861f1f; }.detail-side { align-self: start; padding: 22px; border: 1px solid #f1ddd8; border-radius: 10px; background: #fff8f6; color: #71808c; }.detail-side .pi { color: #a62626; font-size: 22px; }.detail-side h2 { margin: 12px 0 7px; color: #202a35; font-size: 14px; }.detail-side p { margin: 0; font-size: 11px; line-height: 2; }
@media (max-width: 700px) { .detail-layout { display: block; }.detail-card { padding: 20px 16px; }.detail-card h1 { font-size: 21px; }.detail-side { margin-top: 16px; }.detail-meta { gap: 12px; } }
.detail-layout { grid-template-columns: 300px minmax(0, 680px); direction: rtl; align-items: start; }.detail-card { grid-column: 2; display: grid; grid-template-columns: minmax(210px, .9fr) minmax(0, 1.1fr); gap: 0 28px; direction: ltr; overflow: hidden; }.detail-card > *:not(.detail-media) { direction: rtl; }.detail-media { grid-column: 1; grid-row: 1 / span 6; height: 100%; min-height: 420px; margin: -28px 0 -28px -28px; border-bottom: 0; border-left: 1px solid #e5e9ee; }.detail-card__top, .detail-card > h1, .detail-card > .detail-subtitle, .detail-card > .detail-meta, .detail-card > .detail-description, .detail-card > .detail-values, .detail-card > .detail-cta { grid-column: 2; }.detail-side { grid-column: 1; grid-row: 1; width: 100%; }.related-section { grid-column: 1 / -1; }.related-heading { border-bottom: 1px solid #edf0f2; padding-bottom: 12px; }
@media (max-width: 700px) { .detail-layout { display: block; }.detail-card { display: block; overflow: hidden; }.detail-media { height: 170px; min-height: 0; margin: -20px -16px 20px; border-left: 0; border-bottom: 1px solid #e5e9ee; }.detail-side { margin-top: 16px; }.related-section { margin-top: 34px; } }
.detail-media { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 420px; margin: -28px 0 -28px -28px; border-left: 1px solid #e5e9ee; background: linear-gradient(135deg, #f5f7f8, #eaf0f2); color: #9aa8b0; text-align: center; }.detail-media .pi { margin-bottom: 10px; color: #a62626; font-size: 34px; }.detail-media span { color: #5d6c75; font-size: 14px; font-weight: 600; }.detail-media small { margin-top: 5px; font-size: 10px; }.detail-description { margin: 22px 0 8px; padding: 16px 0; border-top: 1px solid #edf0f2; border-bottom: 1px solid #edf0f2; }.detail-description h2, .related-heading h2 { display: flex; align-items: center; gap: 8px; margin: 0 0 9px; color: #202a35; font-size: 14px; }.detail-description h2 .pi, .related-heading h2 .pi { color: #a62626; font-size: 13px; }.detail-description p { margin: 0; color: #5e6d75; font-size: 12px; line-height: 2.1; }.location-map { position: relative; overflow: hidden; height: 130px; margin: 18px -6px 14px; border: 1px solid #d9e2e5; border-radius: 8px; background-color: #e8f0ed; background-image: linear-gradient(32deg, transparent 47%, #fff 48%, #fff 51%, transparent 52%), linear-gradient(118deg, transparent 46%, #fff 47%, #fff 50%, transparent 51%), linear-gradient(90deg, transparent 48%, #d0dfd9 49%, #d0dfd9 51%, transparent 52%); background-size: 100% 100%, 100% 100%, 54px 54px; }.location-map .map-road { position: absolute; display: block; height: 7px; border-radius: 8px; background: rgba(255, 255, 255, .95); transform: rotate(-25deg); }.map-road-one { top: 28%; left: -8%; width: 120%; }.map-road-two { top: 65%; left: -15%; width: 130%; transform: rotate(18deg) !important; }.location-map > .pi { position: absolute; top: 43%; left: 48%; color: #a62626; font-size: 26px; filter: drop-shadow(0 2px 2px rgba(0,0,0,.2)); }.detail-side > strong { display: flex; align-items: center; gap: 6px; color: #3f5159; font-size: 11px; }.detail-side > strong .pi { color: #a62626; font-size: 11px; }.detail-side > p:last-child { margin-top: 4px; color: #71808c; }.related-section { grid-column: 1 / -1; margin-top: 44px; }.related-heading { display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #edf0f2; margin-bottom: 16px; }.related-heading h2 { margin: 0; font-size: 19px; }.related-heading > span { color: #89969d; font-size: 11px; }.related-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
@media (max-width: 700px) { .detail-media { height: 170px; min-height: 0; margin: -20px -16px 20px; border-left: 0; border-bottom: 1px solid #e5e9ee; }.related-section { margin-top: 34px; }.related-grid { grid-template-columns: 1fr; } }
.detail-layout { grid-template-columns: 340px minmax(0, 760px); direction: ltr; }
.detail-card { grid-template-columns: minmax(300px, 1fr) minmax(0, 1.15fr); }
.detail-media { height: 380px; min-height: 0; align-self: start; }
@media (max-width: 700px) { .detail-layout { display: block; }.detail-card { display: block; }.detail-media { height: 170px; min-height: 0; } }
.contact-profile { min-width: min(360px, 72vw); text-align: center; }.contact-avatar { display: grid; place-items: center; width: 58px; height: 58px; margin: 4px auto 12px; border-radius: 50%; background: #fff1ee; color: #a62626; font-size: 22px; }.contact-profile h2 { margin: 0; color: #202a35; font-size: 16px; }.contact-status { display: inline-flex; align-items: center; gap: 5px; margin: 7px 0 18px; color: #2f8b61; font-size: 11px; }.contact-profile dl { margin: 0 0 18px; border-top: 1px solid #edf0f2; text-align: right; }.contact-profile dl div { display: flex; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid #edf0f2; font-size: 12px; }.contact-profile dt { color: #71808c; }.contact-profile dd { margin: 0; color: #202a35; font-weight: 600; }.contact-call, .access-error button { display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; padding: 12px; border-radius: 7px; background: #a62626; color: #fff; text-decoration: none; font-size: 12px; }.access-error { min-width: min(320px, 72vw); padding: 24px; border-radius: 10px; background: #fff; text-align: center; }.access-error > i { color: #a62626; font-size: 26px; }.access-error p { margin: 12px 0 18px; color: #5e6d75; font-size: 12px; }.access-error button { border: 0; cursor: pointer; font-family: inherit; }
.site-footer { display: block; max-width: none; margin-top: 56px; padding: 0 24px; border-top: 1px solid #e7e7e7; background: #fff; color: #555; }.footer-inner, .footer-bottom { max-width: 1280px; margin: 0 auto; }.footer-inner { display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 48px; padding: 42px 0 34px; }.footer-brand .brand { width: max-content; color: #333; }.footer-brand .brand-mark { background: #a62626; box-shadow: none; }.footer-brand p { max-width: 255px; margin: 16px 0 0; color: #777; font-size: 11px; line-height: 2; }.footer-column h2 { margin: 4px 0 16px; color: #333; font-size: 13px; }.footer-column a { display: block; width: max-content; margin: 10px 0; color: #666; font-size: 11px; text-decoration: none; }.footer-column a:hover { color: #a62626; }.footer-trust { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #e8e8e8; border-radius: 8px; background: #fafafa; }.footer-trust > span { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 6px; background: #fff1ee; color: #a62626; }.footer-trust strong, .footer-trust small { display: block; }.footer-trust strong { color: #333; font-size: 12px; }.footer-trust small { margin-top: 6px; color: #777; font-size: 10px; line-height: 1.8; }.footer-bottom { display: flex; justify-content: space-between; padding: 16px 0; border-top: 1px solid #e7e7e7; color: #888; font-size: 10px; }
@media (max-width: 700px) { .site-footer { padding: 0 16px; }.footer-inner { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px 20px; padding: 32px 0 26px; }.footer-brand, .footer-trust { grid-column: 1 / -1; }.footer-trust { max-width: 320px; }.footer-bottom { display: block; line-height: 2; }.footer-bottom span { display: block; }.footer-bottom span + span { margin-top: 3px; } }
.filter-title { border-bottom-color: #a62626; }
.ads-paginator { justify-content: center; margin-top: 24px; direction: rtl; }
.ads-paginator :deep(.p-paginator-page.p-highlight) { background: #a62626; border-color: #a62626; color: #fff; }
.ads-paginator :deep(.p-paginator-page), .ads-paginator :deep(.p-paginator-prev), .ads-paginator :deep(.p-paginator-next) { min-width: 34px; height: 34px; color: #59666f; }
.ads-grid { grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 20px; }
.ads-paginator { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 32px 0; direction: rtl; }
.ads-paginator :deep(.p-paginator-page), .ads-paginator :deep(.p-paginator-prev), .ads-paginator :deep(.p-paginator-next), .ads-paginator :deep(.p-paginator-first), .ads-paginator :deep(.p-paginator-last) { display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; color: #374151; font-size: 14px; font-weight: 700; transition: background .2s ease, box-shadow .2s ease, color .2s ease; }
.ads-paginator :deep(.p-paginator-page.p-highlight) { background: #a62626; border-color: #a62626; box-shadow: 0 4px 10px rgba(127, 29, 29, .1); color: #fff; }
.ads-paginator :deep(.p-paginator-page:not(.p-highlight):hover), .ads-paginator :deep(.p-paginator-prev:not(.p-disabled):hover), .ads-paginator :deep(.p-paginator-next:not(.p-disabled):hover), .ads-paginator :deep(.p-paginator-first:not(.p-disabled):hover), .ads-paginator :deep(.p-paginator-last:not(.p-disabled):hover) { background: #f9fafb; }
.ads-paginator :deep(.p-disabled) { cursor: not-allowed; opacity: .4; }
@media (min-width: 768px) { .ads-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 1280px) { .ads-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
:global(.p-dialog.contact-dialog),:global(.contact-dialog .p-dialog-content),:global(.contact-dialog .p-dialog-header),:global(.contact-dialog .p-dialog-footer){background-color:#fff!important;color:#1f2937!important}:global(.p-dialog.contact-dialog){border-radius:1.25rem!important;border:1px solid #f3f4f6!important;box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 10px 10px -5px rgba(0,0,0,.04)!important;overflow:hidden!important}:global(.contact-dialog .p-dialog-header){border-bottom:1px solid #f3f4f6!important}:global(.contact-dialog .p-dialog-content){padding:0 1.5rem 1.5rem!important}:global(.contact-dialog .p-dialog-footer){border-top:1px solid #f3f4f6!important}
</style>
