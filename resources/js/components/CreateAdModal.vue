<script setup>
import AdWizard from './AdWizard.vue';

defineProps({
    banks: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
});
const visible = defineModel('visible', { type: Boolean, default: false });
const emit = defineEmits(['created']);
</script>

<template>
    <div v-if="visible" class="create-overlay" dir="rtl" @click.self="visible = false">
        <section class="create-dialog" role="dialog" aria-modal="true" aria-labelledby="create-ad-title">
            <header class="create-header"><h2 id="create-ad-title">ثبت آگهی امتیاز وام</h2><button type="button" aria-label="بستن" @click="visible = false"><i class="pi pi-times"></i></button></header>
            <AdWizard :banks="banks" :provinces="provinces" @saved="(ad) => { emit('created', ad); visible = false; }" @cancel="visible = false" />
        </section>
    </div>
</template>

<style scoped>
.create-overlay{position:fixed;inset:0;z-index:110;display:grid;place-items:center;padding:16px;background:rgba(32,42,53,.48)}.create-dialog{width:min(720px,100%);max-height:calc(100vh - 32px);overflow:auto;border-radius:12px;background:#fff;box-shadow:0 18px 50px rgba(32,42,53,.2)}.create-header{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid #f0f2f4}.create-header h2{margin:0;color:#202a35;font-size:17px}.create-header button{border:0;background:transparent;color:#71808c;cursor:pointer}
</style>
