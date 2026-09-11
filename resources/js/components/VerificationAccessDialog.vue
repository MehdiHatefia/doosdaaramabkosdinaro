<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Dialog from 'primevue/dialog';
import { createLogger } from '../utils/logger';

const router = useRouter();
const logger = createLogger('VerificationAccessDialog');
const visible = ref(false);
const message = ref('شما به این بخش دسترسی ندارید. جهت استفاده از این امکان، ابتدا باید احراز هویت خود را تکمیل کنید');

function open(event) {
    message.value = event.detail?.message || message.value;
    visible.value = true;
    logger.warn('open', 'Verification access dialog opened', { code: event.detail?.code });
}
function goToVerification() {
    visible.value = false;
    router.push({ name: 'user.verification' });
}
onMounted(() => window.addEventListener('auth:verification-required', open));
onUnmounted(() => window.removeEventListener('auth:verification-required', open));
</script>

<template>
    <Dialog v-model:visible="visible" modal="true" :draggable="false" :closable="true" :style="{ width: '90vw', maxWidth: '440px' }" :pt="{ root: { class: 'verification-access-dialog' }, content: { class: 'verification-access-dialog__content' }, header: { class: 'verification-access-dialog__header' }, headerIcons: { class: 'verification-access-dialog__header-icons' }, mask: { class: 'verification-access-dialog__mask' } }" dir="rtl">
        <div class="verification-access-content">
            <div class="verification-access-icon"><i class="pi pi-lock"></i></div>
            <h3>نیاز به تکمیل احراز هویت</h3>
            <p>شما به این بخش دسترسی ندارید. جهت استفاده از امکانات سامانه مستروام، لطفاً ابتدا فرایند احراز هویت خود را تکمیل کنید.</p>
            <button type="button" class="primary-action" @click="goToVerification"><i class="pi pi-arrow-left"></i>تکمیل احراز هویت</button>
        </div>
    </Dialog>
</template>

<style scoped>
.verification-access-content{display:flex;flex-direction:column;align-items:center;padding:24px;text-align:center}.verification-access-icon{display:flex;align-items:center;justify-content:center;width:64px;height:64px;margin-bottom:16px;border:1px solid #fef3c7;border-radius:999px;background:#fffbeb;color:#d97706;box-shadow:0 1px 3px rgba(15,23,42,.08);font-size:24px}.verification-access-content h3{margin:0 0 8px;color:#111827;font-size:18px;font-weight:700;line-height:1.7}.verification-access-content p{max-width:320px;margin:0 0 24px;color:#4b5563;font-size:14px;line-height:1.9}.primary-action{display:flex;width:100%;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border:0;border-radius:12px;background:#a62626;color:#fff;cursor:pointer;font:inherit;font-size:13px;font-weight:700;box-shadow:0 4px 10px rgba(166,38,38,.2);transition:background-color .18s ease,transform .18s ease}.primary-action:hover{background:#8e2020}.primary-action:active{transform:translateY(1px)}
</style>
