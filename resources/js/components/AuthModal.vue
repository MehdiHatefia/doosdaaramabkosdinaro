<script setup>
import { computed, ref, watch } from 'vue';
import { createLogger, logException } from '../utils/logger';
import { authService } from '../services';

const logger = createLogger('AuthModal');

const visible = defineModel('visible', { type: Boolean, default: false });
const emit = defineEmits(['authenticated']);
const step = ref(1);
const mobile = ref('');
const code = ref('');
const name = ref('');
const email = ref('');
const registrationRequired = ref(false);
const secondsLeft = ref(0);
const loading = ref(false);
const error = ref('');
let timer;

function normalizeDigits(value) {
    return String(value)
        .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
        .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));
}

const normalizedMobile = computed(() => normalizeDigits(mobile.value));
const mobileIsValid = computed(() => /^09[0-9]{9}$/.test(normalizedMobile.value));
const formattedTimer = computed(() => `${String(Math.floor(secondsLeft.value / 60)).padStart(2, '0')}:${String(secondsLeft.value % 60).padStart(2, '0')}`);

function startTimer() {
    logger.info('startTimer', 'OTP timer started');
    window.clearInterval(timer);
    secondsLeft.value = 120;
    timer = window.setInterval(() => {
        secondsLeft.value -= 1;
        if (secondsLeft.value <= 0) window.clearInterval(timer);
    }, 1000);
}

async function sendOtp() {
    if (!mobileIsValid.value || loading.value) {
        logger.warn('sendOtp', 'OTP request skipped', { validMobile: mobileIsValid.value, loading: loading.value });
        return;
    }
    logger.info('sendOtp', 'OTP request started');
    loading.value = true;
    error.value = '';
    try {
        await authService.sendOtp(normalizedMobile.value);
        logger.info('sendOtp', 'OTP request succeeded');
        step.value = 2;
        code.value = '';
        startTimer();
    } catch (exception) {
        logger.error('sendOtp', 'OTP request failed', logException(exception));
        error.value = exception.response?.data?.message || 'ارسال کد با خطا روبه‌رو شد.';
    } finally {
        loading.value = false;
        logger.info('sendOtp', 'OTP request state finalized', { loading: loading.value });
    }
}

async function verifyOtp() {
    if (String(code.value).length !== 5 || loading.value) {
        logger.warn('verifyOtp', 'OTP verification skipped', { codeLength: String(code.value).length, loading: loading.value });
        return;
    }
    logger.info('verifyOtp', 'OTP verification started');
    loading.value = true;
    error.value = '';
    try {
        const data = await authService.verifyOtp({ mobile: normalizedMobile.value, code: normalizeDigits(code.value) });
        logger.info('verifyOtp', 'OTP verification succeeded');
        if (data.registration_required) {
            registrationRequired.value = true;
            step.value = 3;
            return;
        }
        logger.info('verifyOtp', 'Authenticated state stored', { userId: data.user?.id });
        emit('authenticated', data.user);
        visible.value = false;
    } catch (exception) {
        logger.error('verifyOtp', 'OTP verification failed', logException(exception));
        error.value = exception.response?.data?.message || 'کد واردشده صحیح نیست.';
        code.value = '';
    } finally {
        loading.value = false;
        logger.info('verifyOtp', 'OTP verification state finalized', { loading: loading.value });
    }
}

async function completeRegistration() {
    if (!name.value.trim() || loading.value) return;

    loading.value = true;
    error.value = '';
    try {
        const data = await authService.completeRegistration({
            mobile: normalizedMobile.value,
            code: normalizeDigits(code.value),
            name: name.value.trim(),
            email: email.value.trim() || null,
        });
        logger.info('completeRegistration', 'Registration and login succeeded', { userId: data.user?.id });
        emit('authenticated', data.user);
        visible.value = false;
    } catch (exception) {
        logger.error('completeRegistration', 'Registration failed', logException(exception));
        error.value = exception.response?.data?.message || 'ثبت‌نام با خطا روبه‌رو شد.';
    } finally {
        loading.value = false;
    }
}

function editMobile() {
    logger.info('editMobile', 'Returning to mobile input');
    step.value = 1;
    code.value = '';
    error.value = '';
    window.clearInterval(timer);
}

watch(code, (value) => {
    logger.info('watchCode', 'OTP input changed', { codeLength: String(value).length });
    if (String(value).length === 5) verifyOtp();
});

watch(visible, (isVisible) => {
    logger.info('watchVisible', 'Authentication modal visibility changed', { visible: isVisible });
    if (!isVisible) {
        step.value = 1;
        code.value = '';
        name.value = '';
        email.value = '';
        registrationRequired.value = false;
        error.value = '';
        window.clearInterval(timer);
    }
});
</script>

<template>
    <div v-if="visible" class="auth-overlay" dir="rtl" @click.self="visible = false">
        <section class="auth-dialog" role="dialog" aria-modal="true" aria-labelledby="auth-title">
            <div class="auth-dialog__header"><h2 id="auth-title">ورود / ثبت‌نام</h2><button type="button" aria-label="بستن" @click="visible = false"><i class="pi pi-times"></i></button></div>
        <div class="auth-content">
            <p class="auth-intro">برای ادامه، شماره موبایل خود را وارد کنید.</p>
            <form v-if="step === 1" @submit.prevent="sendOtp">
                <label for="auth-mobile">شماره موبایل</label>
                <input id="auth-mobile" v-model.trim="mobile" inputmode="numeric" maxlength="11" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" />
                <small v-if="mobile && !mobileIsValid" class="auth-error">شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقمی باشد.</small>
                <button class="auth-submit" type="submit" :disabled="!mobileIsValid || loading">{{ loading ? 'در حال ارسال...' : 'ادامه' }}<i class="pi pi-arrow-left"></i></button>
            </form>
            <form v-else-if="step === 2" @submit.prevent="verifyOtp">
                <p class="sent-to">کد پنج رقمی به <strong dir="ltr">{{ mobile }}</strong> ارسال شد.</p>
                <button class="edit-mobile" type="button" @click="editMobile"><i class="pi pi-pencil"></i>ویرایش شماره</button>
                <input v-model="code" class="otp-input" type="text" inputmode="numeric" maxlength="5" autocomplete="one-time-code" aria-label="کد پنج رقمی" autofocus />
                <small v-if="error" class="auth-error">{{ error }}</small>
                <div class="resend-row"><span>ارسال مجدد کد</span><button type="button" :disabled="secondsLeft > 0 || loading" @click="sendOtp">{{ secondsLeft > 0 ? formattedTimer : 'ارسال مجدد' }}</button></div>
            </form>
            <form v-else @submit.prevent="completeRegistration">
                <p class="sent-to">شماره <strong dir="ltr">{{ mobile }}</strong> تایید شد. اطلاعات خود را وارد کنید.</p>
                <label for="auth-name">نام و نام خانوادگی</label>
                <input id="auth-name" v-model.trim="name" type="text" autocomplete="name" required />
                <label for="auth-email">ایمیل (اختیاری)</label>
                <input id="auth-email" v-model.trim="email" type="email" autocomplete="email" dir="ltr" />
                <small v-if="error" class="auth-error">{{ error }}</small>
                <button class="auth-submit" type="submit" :disabled="!name.trim() || loading">{{ loading ? 'در حال ثبت‌نام...' : 'تکمیل ثبت‌نام و ورود' }}<i class="pi pi-arrow-left"></i></button>
            </form>
            <small v-if="step === 1 && error" class="auth-error">{{ error }}</small>
            <p class="auth-footnote">با ورود، قوانین استفاده از مستروام را می‌پذیرید.</p>
        </div>
        </section>
    </div>
</template>

<style scoped>
.auth-overlay { position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; padding: 16px; background: rgba(32, 42, 53, .42); }.auth-dialog { width: min(420px, 100%); border-radius: 12px; background: #fff; box-shadow: 0 18px 50px rgba(32, 42, 53, .2); }.auth-dialog__header { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid #f0f2f4; }.auth-dialog__header h2 { margin: 0; color: #202a35; font-size: 16px; }.auth-dialog__header button { border: 0; background: transparent; color: #71808c; cursor: pointer; font-size: 14px; }.auth-content { min-width: min(360px, 72vw); padding: 2px 20px 20px; text-align: right; }.auth-intro, .sent-to, .auth-footnote { color: #71808c; font-size: 12px; line-height: 2; }.auth-content label { display: block; margin: 20px 0 8px; color: #202a35; font-size: 12px; font-weight: 600; }.auth-content input:not(.otp-input) { width: 100%; height: 46px; padding: 0 14px; border: 1px solid #dfe4e8; border-radius: 7px; outline: 0; font-family: inherit; }.auth-content input:focus { border-color: #b83232; box-shadow: 0 0 0 3px #f8e7e5; }.auth-submit { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; margin-top: 20px; padding: 12px; border: 0; border-radius: 7px; background: #b83232; color: #fff; cursor: pointer; font-size: 13px; }.auth-submit:disabled { cursor: not-allowed; opacity: .5; }.auth-error { display: block; margin-top: 8px; color: #b83232; font-size: 11px; }.sent-to { margin-bottom: 2px; }.sent-to strong { color: #202a35; }.edit-mobile { padding: 0; border: 0; background: transparent; color: #b83232; cursor: pointer; font-size: 11px; }.otp-input { display: block; width: 100%; height: 46px; margin: 24px 0 12px; border: 1px solid #dfe4e8; border-radius: 7px; outline: 0; text-align: center; direction: ltr; letter-spacing: 8px; }.otp-input:focus { border-color: #b83232; box-shadow: 0 0 0 3px #f8e7e5; }.resend-row { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; color: #89949d; font-size: 11px; }.resend-row button { padding: 0; border: 0; background: transparent; color: #b83232; cursor: pointer; font-size: 11px; }.resend-row button:disabled { color: #aeb6bc; cursor: not-allowed; }.auth-footnote { margin: 24px 0 0; padding-top: 14px; border-top: 1px solid #f0f2f4; font-size: 10px; }
</style>