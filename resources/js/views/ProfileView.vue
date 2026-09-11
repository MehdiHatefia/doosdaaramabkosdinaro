<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Toast from 'primevue/toast';
import PersianDatePicker from '../components/PersianDatePicker.vue';
import { authService, locationService } from '../services';
import { useAuthStore } from '../stores/useAuthStore';
import { createLogger, logException } from '../utils/logger';

const logger = createLogger('Profile');
const toast = useToast();
const authStore = useAuthStore();
const fileInput = ref(null);
const loading = ref(true);
const saving = ref(false);
const avatarUploading = ref(false);
const provinces = ref([]);
const cities = ref([]);
const profile = ref({ is_verified: false, avatar: null });
const avatarPreview = ref('');
const form = reactive({
    mobile: '', name: '', nickname: '', email: '', national_code: '', birth_date: '',
    iban: '', province_id: null, city_id: null, show_phone_publicly: true,
});

const isVerified = computed(() => profile.value.is_verified === true);
const initials = computed(() => (form.name || form.nickname || 'کاربر').trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join(''));
const avatarUrl = computed(() => profile.value.avatar ? (profile.value.avatar.startsWith('http') ? profile.value.avatar : `/storage/${profile.value.avatar}`) : '');
async function refreshAvatarPreview(user) {
    if (!user?.avatar) { avatarPreview.value = ''; return; }
    try { avatarPreview.value = await authService.getAvatarPreview(); } catch (exception) { avatarPreview.value = ''; logger.warn('avatar', 'Avatar preview could not be loaded', logException(exception)); }
}
const selectedProvince = computed(() => provinces.value.find((province) => province.id === form.province_id));
const ibanDigits = computed(() => (form.iban?.replace(/^IR/i, '') || '').length);
const formattedIban = computed(() => (form.iban || '').replace(/\D/g, '').slice(0, 24).replace(/(.{4})(?=.)/g, '$1 - '));

function notify(severity, summary, detail) {
    toast.add({ severity, summary, detail, life: 3500 });
}

function applyUser(user) {
    profile.value = user || {};
    Object.assign(form, {
        mobile: user?.mobile || '', name: user?.name || '', nickname: user?.nickname || '', email: user?.email || '',
        national_code: user?.national_code || '', birth_date: user?.birth_date || '',
        iban: (user?.iban || '').replace(/^IR/i, ''), province_id: user?.province_id || user?.province?.id || null,
        city_id: user?.city_id || user?.city?.id || null, iban: (user?.iban || '').replace(/\D/g, '').slice(0, 24), show_phone_publicly: user?.show_phone_publicly !== false,
    });
}

function handleIbanInput(event) {
    form.iban = event.target.value.replace(/\D/g, '').slice(0, 24);
}

function optionName(options, value) {
    return options.find((option) => option.id === value)?.name || '';
}

async function loadCities(provinceId) {
    if (!provinceId) { cities.value = []; form.city_id = null; return; }
    const province = provinces.value.find((item) => item.id === provinceId);
    cities.value = province?.children || (await locationService.getCities(provinceId)).data || [];
    if (!cities.value.some((city) => city.id === form.city_id)) form.city_id = null;
}

async function load() {
    try {
        const [profileResponse, locationResponse] = await Promise.all([authService.getProfile(), locationService.getProvinces()]);
        provinces.value = locationResponse.data || [];
        applyUser(profileResponse.user || profileResponse);
        await refreshAvatarPreview(profile.value);
        await loadCities(form.province_id);
        authStore.user = profileResponse.user || profileResponse;
        localStorage.setItem('auth_user', JSON.stringify(authStore.user));
    } catch (exception) {
        logger.error('fetch', 'Profile loading failed', logException(exception));
        notify('error', 'خطا', 'دریافت اطلاعات پروفایل انجام نشد.');
    } finally { loading.value = false; }
}

async function save() {
    saving.value = true;
    try {
        const response = await authService.updateProfile({
            name: form.name, nickname: form.nickname, email: form.email, national_code: form.national_code,
            birth_date: form.birth_date || null,
            iban: form.iban ? `IR${form.iban}` : null, province_id: form.province_id, city_id: form.city_id,
            show_phone_publicly: form.show_phone_publicly,
        });
        applyUser(response.user);
        authStore.user = response.user;
        notify('success', 'ذخیره شد', 'اطلاعات پروفایل با موفقیت ذخیره شد.');
        logger.info('update', 'Profile updated', { userId: response.user?.id });
    } catch (exception) {
        const message = exception.response?.data?.message || Object.values(exception.response?.data?.errors || {})[0]?.[0] || 'ذخیره اطلاعات انجام نشد.';
        notify('error', 'خطا', message);
        logger.error('update', 'Profile update failed', logException(exception));
    } finally { saving.value = false; }
}

function chooseAvatar() {
    fileInput.value?.click();
}

async function uploadAvatar(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/') || file.size > 2 * 1024 * 1024) {
        notify('warn', 'تصویر نامعتبر', 'تصویر باید حداکثر ۲ مگابایت باشد.');
        event.target.value = '';
        return;
    }
    avatarUploading.value = true;
    try {
        const response = await authService.uploadAvatar(file);
        applyUser(response.user);
        await refreshAvatarPreview(response.user);
        authStore.user = response.user;
        notify('success', 'آواتار تغییر کرد', 'تصویر پروفایل با موفقیت ذخیره شد.');
    } catch (exception) {
        notify('error', 'خطا', exception.response?.data?.message || 'آپلود تصویر انجام نشد.');
        logger.error('avatar', 'Avatar upload failed', logException(exception));
    } finally {
        avatarUploading.value = false;
        event.target.value = '';
    }
}

watch(() => form.province_id, (provinceId, previousId) => { if (provinceId !== previousId) loadCities(provinceId); });
onMounted(load);
</script>

<template>
    <section class="profile-view" dir="rtl">
        <Toast class="profile-toast" position="top-center" />
        <header class="profile-header">
            <span class="module-kicker">اطلاعات حساب</span>
            <h1>پروفایل و شماره تماس</h1>
            <p>اطلاعات فردی، هویتی و حساب تسویه خود را مدیریت کنید.</p>
        </header>

        <div v-if="loading" class="module-loading" role="status">در حال دریافت اطلاعات...</div>
        <template v-else>
            <section class="profile-hero">
                <div class="profile-identity">
                    <div class="avatar">
                        <img v-if="avatarPreview || avatarUrl" :src="avatarPreview || avatarUrl" alt="تصویر پروفایل" />
                        <i v-else class="pi pi-user"></i>
                    </div>
                    <div class="identity-copy">
                        <h2>{{ form.name || form.nickname || 'کاربر مستروام' }}</h2>
                        <p dir="ltr">{{ form.mobile }}</p>
                        <span class="verification-badge" :class="{ verified: isVerified }">
                            <i :class="isVerified ? 'pi pi-check-circle' : 'pi pi-exclamation-circle'"></i>
                            {{ isVerified ? 'احراز هویت شده' : 'نیازمند احراز هویت' }}
                        </span>
                    </div>
                </div>
                <div class="avatar-action">
                    <input ref="fileInput" class="hidden" type="file" accept="image/*" @change="uploadAvatar" />
                    <button class="avatar-button" type="button" :disabled="avatarUploading" @click="chooseAvatar">
                        <i class="pi pi-camera"></i>
                        {{ avatarUploading ? 'در حال آپلود...' : 'تغییر عکس پروفایل' }}
                    </button>
                    <small>حداکثر حجم تصویر ۲ مگابایت</small>
                </div>
            </section>

            <form class="profile-form grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5" @submit.prevent="save">
                <div class="section-title col-span-full flex items-center gap-2 font-bold text-gray-800 border-b pb-2 mt-4 mb-2"><i class="pi pi-user text-gray-500"></i><h2>اطلاعات فردی و هویتی</h2></div>
                <label>شماره موبایل<div class="input-with-icon"><input v-model="form.mobile" readonly disabled dir="ltr" /><i class="pi pi-lock"></i></div></label>
                <label>نام مستعار<input v-model.trim="form.nickname" maxlength="80" /></label>
                <label>نام و نام خانوادگی رسمی<div class="input-with-icon"><input v-model.trim="form.name" required :readonly="isVerified" :disabled="isVerified" /><i v-if="isVerified" class="pi pi-lock"></i></div></label>
                <label>کد ملی<div class="input-with-icon"><input v-model.trim="form.national_code" inputmode="numeric" maxlength="10" dir="ltr" :readonly="isVerified" :disabled="isVerified" /><i v-if="isVerified" class="pi pi-lock"></i></div></label>
                <label>ایمیل<input v-model.trim="form.email" type="email" dir="ltr" /></label>
                <label>تاریخ تولد<PersianDatePicker v-model="form.birth_date" /></label>
                <label><span class="field-label">استان محل سکونت</span><Select v-model="form.province_id" class="profile-select w-full" panel-class="profile-select-overlay" :options="provinces" option-label="name" option-value="id" placeholder="انتخاب استان">
                    <template #value="slotProps"><div class="select-value"><span class="select-value-main"><i class="pi pi-map-marker"></i><span>{{ slotProps.value ? optionName(provinces, slotProps.value) : slotProps.placeholder }}</span></span><i class="pi pi-chevron-down select-chevron"></i></div></template>
                    <template #option="slotProps"><div class="select-option"><i class="pi pi-map-marker"></i><span>{{ slotProps.option.name }}</span></div></template>
                </Select></label>
                <label><span class="field-label">شهر محل سکونت</span><Select v-model="form.city_id" class="profile-select w-full" panel-class="profile-select-overlay" :options="cities" option-label="name" option-value="id" placeholder="انتخاب شهر" :disabled="!selectedProvince">
                    <template #value="slotProps"><div class="select-value"><span class="select-value-main"><i class="pi pi-building"></i><span>{{ slotProps.value ? optionName(cities, slotProps.value) : slotProps.placeholder }}</span></span><i class="pi pi-chevron-down select-chevron"></i></div></template>
                    <template #option="slotProps"><div class="select-option"><i class="pi pi-building"></i><span>{{ slotProps.option.name }}</span></div></template>
                </Select></label>

                <div class="section-title section-title--wide col-span-full flex items-center gap-2 font-bold text-gray-800 border-b pb-2 mt-4 mb-2"><i class="pi pi-wallet text-gray-500"></i><h2>اطلاعات مالی</h2></div>
                <label class="wide">شماره شبا جهت تسویه<div class="iban-input"><span>IR</span><input :value="formattedIban" inputmode="numeric" maxlength="35" dir="ltr" aria-label="شماره شبا" @input="handleIbanInput" /><div class="iban-meta"><small>{{ ibanDigits }} از ۲۴ رقم</small></div></div></label>

                <div class="privacy-card wide">
                    <div class="privacy-copy"><i class="pi pi-shield"></i><div><strong>نمایش شماره تماس در آگهی‌ها</strong><p>در صورت فعال بودن، شماره تماس شما برای کاربران نمایش داده می‌شود.</p></div></div>
                    <div class="privacy-control">
                        <span class="privacy-status">{{ form.show_phone_publicly ? 'روشن' : 'خاموش' }}</span>
                        <ToggleSwitch v-model="form.show_phone_publicly" aria-label="نمایش شماره تماس در آگهی‌ها" />
                    </div>
                </div>
                <button class="primary-action" type="submit" :disabled="saving"><i :class="saving ? 'pi pi-spin pi-spinner' : 'pi pi-save'"></i>{{ saving ? 'در حال ذخیره...' : 'ذخیره تغییرات' }}</button>
            </form>
        </template>
    </section>
</template>

<style scoped>
.profile-view{direction:rtl}.profile-header{margin-bottom:20px}.profile-header h1{margin:7px 0 5px;color:#202a35;font-size:24px}.profile-header p{margin:0;color:#78848c;font-size:12px}.module-kicker{color:#a62626;font-size:11px;font-weight:700}.module-loading{display:grid;place-items:center;min-height:260px;color:#7b8790}.profile-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:16px;margin-bottom:20px;border:1px solid #f0f1f2;border-radius:12px;background:#f9fafb}.profile-identity{display:flex;align-items:center;gap:14px;min-width:0}.avatar{display:grid;place-items:center;width:64px;height:64px;flex:0 0 64px;overflow:hidden;border:2px solid #fff;border-radius:50%;background:#e7eaec;box-shadow:0 2px 7px rgba(32,42,53,.12);color:#7a8791;font-size:24px}.avatar img{width:100%;height:100%;object-fit:cover}.identity-copy{min-width:0}.identity-copy h2{overflow:hidden;margin:0 0 5px;color:#202a35;font-size:16px;text-overflow:ellipsis;white-space:nowrap}.identity-copy p{margin:0 0 8px;color:#687680;font-size:12px}.verification-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:14px;background:#fff4dd;color:#98651e;font-size:10px}.verification-badge.verified{background:#eaf8f0;color:#287b50}.avatar-action{display:flex;align-items:flex-end;gap:10px;flex-direction:column}.avatar-action small{color:#8a959b;font-size:10px}.hidden{display:none!important}.avatar-button{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border:1px solid #dfe4e8;border-radius:7px;background:#fff;color:#52616b;cursor:pointer;font:inherit;font-size:11px}.avatar-button:hover{border-color:#b8c2c8;background:#f5f7f8}.avatar-button:disabled{opacity:.55;cursor:wait}.profile-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px;padding:22px;border:1px solid #edf0f2;border-radius:10px;background:#fff}.profile-form label{display:block;color:#5c6972;font-size:11px;font-weight:600}.profile-form label>input,.input-with-icon,.iban-input,.profile-form label>:deep(.p-datepicker),.profile-form label>:deep(.p-select){display:block;width:100%;margin-top:7px}.profile-form input{width:100%;height:42px;padding:0 11px;border:1px solid #dfe4e8;border-radius:7px;background:#fff;color:#26343c;outline:0;font:inherit;font-size:12px}.profile-form input:focus,.profile-form :deep(.p-select:focus-within),.profile-form :deep(.p-datepicker:focus-within){border-color:#aeb9bf;box-shadow:0 0 0 3px #f1f3f4}.profile-form input:disabled{background:#f1f3f4;color:#7b8790;cursor:not-allowed}.input-with-icon{position:relative}.input-with-icon input{padding-left:36px}.input-with-icon i{position:absolute;top:14px;left:12px;color:#8c979d;font-size:12px}.profile-form :deep(.p-select),.profile-form :deep(.p-datepicker){min-height:42px;border:1px solid #dfe4e8;border-radius:7px}.profile-form :deep(.p-select-label),.profile-form :deep(.p-datepicker-input){min-height:40px;padding:10px 11px;border:0;font:inherit;font-size:12px}.profile-form :deep(.p-datepicker-input){width:100%}.profile-form :deep(.p-datepicker-dropdown){border:0;background:transparent;color:#7a8791}.wide,.section-title--wide{grid-column:1/-1}.section-title{display:flex;align-items:center;gap:8px;margin:2px 0 -2px;padding-bottom:10px;border-bottom:1px solid #e7eaec;color:#3f4b53}.section-title i{color:#7f8b92;font-size:14px}.section-title h2{margin:0;font-size:15px;font-weight:700}.iban-input{position:relative}.iban-input>span{position:absolute;top:12px;left:12px;color:#a62626;font-size:12px;font-weight:700}.iban-input input{padding-left:35px}.iban-input small{position:absolute;top:14px;right:12px;color:#7c8990;font-size:10px}.privacy-card{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 14px;border:1px solid #e3e7e9;border-radius:10px;background:#fff}.privacy-copy{display:flex;align-items:flex-start;gap:10px}.privacy-copy>i{margin-top:2px;color:#7d898f;font-size:17px}.privacy-copy strong{display:block;color:#3e4b53;font-size:12px}.privacy-copy p{margin:4px 0 0;color:#859097;font-size:10px}.primary-action{grid-column:1/-1;display:inline-flex;align-items:center;justify-content:center;gap:8px;justify-self:start;padding:10px 24px;border:0;border-radius:8px;background:#a62626;color:#fff;cursor:pointer;font:inherit;font-size:12px;font-weight:700}.primary-action:hover{background:#8e2020}.primary-action:disabled{opacity:.6;cursor:wait}@media(max-width:680px){.profile-hero{align-items:flex-start;flex-direction:column}.avatar-action{align-items:flex-start}.profile-form{grid-template-columns:1fr}.wide,.section-title--wide{grid-column:auto}.primary-action{width:100%}}
 .field-label{display:block}.profile-form :deep(.profile-select){height:44px;min-height:44px;background:#fbfcfd}.profile-form :deep(.profile-select .p-select-label){display:flex;align-items:center;height:42px;min-height:0;padding:0 12px}.profile-form :deep(.profile-select .p-select-dropdown){display:none}.profile-form :deep(.profile-select.p-disabled){background:#f1f3f4;color:#7b8790}.select-value,.select-option{display:flex;align-items:center;gap:9px}.select-value{justify-content:space-between;width:100%;color:#2f3d45;font-size:12px}.select-value-main{display:flex;align-items:center;gap:9px;min-width:0}.select-value-main>span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.select-value i,.select-option i{display:grid;place-items:center;width:25px;height:25px;border-radius:7px;background:#fbeeed;color:#a62626;font-size:11px}.select-value .select-chevron{width:auto;height:auto;margin-right:8px;background:transparent;color:#74828b;font-size:10px}.select-option{width:100%;padding:6px 8px;color:#37464f;font-size:12px}.select-option i{background:#f1f4f5;color:#64737d}.profile-select :deep(.p-placeholder) .select-value{color:#7b8890}
.privacy-control{display:flex;align-items:center;gap:10px;flex-shrink:0}.privacy-status{min-width:36px;color:#687680;font-size:11px;font-weight:700;text-align:center}.privacy-control :deep(.p-toggleswitch){position:relative;display:inline-block;width:48px;height:26px;flex:0 0 48px}.privacy-control :deep(.p-toggleswitch-input){position:absolute;width:100%;height:100%;margin:0;opacity:0;cursor:pointer;z-index:1}.privacy-control :deep(.p-toggleswitch-slider){position:absolute;inset:0;border-radius:999px;background:#cbd3d7;cursor:pointer;transition:background-color .2s ease}.privacy-control :deep(.p-toggleswitch-handle){position:absolute;top:3px;right:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(32,42,53,.28);transition:transform .2s ease}.privacy-control :deep(.p-toggleswitch-checked .p-toggleswitch-slider){background:#a62626}.privacy-control :deep(.p-toggleswitch-checked .p-toggleswitch-handle){transform:translateX(-22px)}.privacy-control :deep(.p-toggleswitch-input:focus-visible + .p-toggleswitch-slider){outline:2px solid #a62626;outline-offset:3px}
.iban-input{position:relative;padding-bottom:22px}.iban-input input{padding-left:35px!important}.iban-input input::placeholder{color:#9aa5ab;font-size:11px}.iban-meta{display:flex;align-items:center;justify-content:flex-end;margin-top:4px;color:#89959c;font-size:10px;font-weight:400}
 .profile-header{position:relative;overflow:hidden;padding:18px 22px;border:1px solid #edf0f2;border-radius:12px;background:#fff;box-shadow:0 6px 18px rgba(32,42,53,.04)}.profile-header::before{position:absolute;top:0;right:0;bottom:0;width:4px;content:'';background:#a62626}.profile-header h1{margin:7px 0 5px;font-size:23px;line-height:1.5}.profile-header p{line-height:1.9}.module-kicker{display:inline-flex;align-items:center;gap:6px}.module-kicker::before{width:6px;height:6px;border-radius:50%;content:'';background:#ef8354}@media(max-width:680px){.profile-header{padding:16px 18px}.profile-header h1{font-size:20px}}
 .profile-hero{position:relative;gap:28px;padding:18px 20px;overflow:hidden;border-color:#e9edef;border-radius:14px;background:linear-gradient(105deg,#fff 0%,#fff 62%,#fff8f4 100%);box-shadow:0 8px 20px rgba(32,42,53,.05)}.profile-hero::before{position:absolute;top:0;right:0;bottom:0;width:4px;content:'';background:#a62626}.profile-identity{gap:15px}.avatar{width:68px;height:68px;flex-basis:68px;border-width:3px;background:#eef1f2;box-shadow:0 3px 12px rgba(32,42,53,.14);font-size:25px}.identity-copy h2{font-size:17px;font-weight:700}.verification-badge{padding:5px 10px;border:1px solid #f6e5bd;background:#fff8e9}.avatar-action{gap:8px;flex-shrink:0}.avatar-button{padding:10px 14px;border-radius:8px;border-color:#d6dde0;color:#3f4f58;box-shadow:0 2px 5px rgba(32,42,53,.04);transition:border-color .2s ease,background-color .2s ease,box-shadow .2s ease}.avatar-button:hover{background:#fffaf7;box-shadow:0 4px 10px rgba(32,42,53,.08)}@media(max-width:680px){.profile-hero{align-items:stretch;gap:18px;padding:16px;flex-direction:column}.profile-identity{align-self:flex-start;width:100%}.avatar-action{align-items:stretch;width:100%;padding-top:14px;border-top:1px solid #edf0f2}.avatar-button{justify-content:center}.avatar-action small{text-align:center}}
</style>
