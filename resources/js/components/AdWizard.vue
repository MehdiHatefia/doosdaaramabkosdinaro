<script setup>
import { computed, ref, watch } from 'vue';
import { adService, bankService } from '../services';
import { createLogger, logException } from '../utils/logger';

const props = defineProps({
    banks: { type: Array, default: () => [] },
    provinces: { type: Array, default: () => [] },
    advertisement: { type: Object, default: null },
});
const emit = defineEmits(['saved', 'cancel']);
const logger = createLogger('AdWizard');
const step = ref(1);
const loading = ref(false);
const plansLoading = ref(false);
const error = ref('');
const plans = ref([]);
const form = ref(emptyForm());

function emptyForm() {
    return { type: 'supply', bank_id: '', bank_plan_id: '', province_id: '', location_id: '', title: '', description: '', loan_amount: '', assignment_price: '', profit_rate: '', installment_count: '' };
}
function changeProvince() { form.value.location_id = ''; }
function numberValue(value) { return value === '' || value === null || value === undefined ? '' : Number(value); }
function hydrate(ad) {
    const province = props.provinces.find((item) => (item.children || []).some((city) => city.id === ad?.location_id));
    form.value = { type: ad?.type || 'supply', bank_id: ad?.bank_id || '', bank_plan_id: ad?.bank_plan_id || '', province_id: province?.id || '', location_id: ad?.location_id || '', title: ad?.title || '', description: ad?.description || '', loan_amount: numberValue(ad?.loan_amount ?? ad?.amount), assignment_price: numberValue(ad?.assignment_price ?? ad?.price), profit_rate: numberValue(ad?.profit_rate ?? ad?.fee), installment_count: numberValue(ad?.installment_count) };
}
const cities = computed(() => props.provinces.find((province) => province.id === Number(form.value.province_id))?.children || []);
const editing = computed(() => Boolean(props.advertisement?.id));
const steps = ['اطلاعات بانکی', 'ارقام و شرایط مالی', 'موقعیت و متن آگهی'];
const money = (value) => value === '' ? '' : new Intl.NumberFormat('fa-IR').format(Number(value || 0));
const parseMoney = (value) => String(value).replace(/[^0-9۰-۹]/g, '').replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit));
const titleHasPhone = computed(() => /(?:\+98|0098|09)\s?\d{9,10}/.test(form.value.title.replace(/[\u06F0-\u06F9]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))));
const validationMessages = computed(() => {
    if (step.value === 1) {
        return [
            !form.value.bank_id && 'بانک را انتخاب کنید.',
            form.value.bank_id && !form.value.bank_plan_id && 'طرح وام بانک را انتخاب کنید.',
        ].filter(Boolean);
    }
    if (step.value === 2) {
        return [
            (!form.value.loan_amount || Number(form.value.loan_amount) <= 0) && 'مبلغ کل تسهیلات باید بیشتر از صفر باشد.',
            (form.value.assignment_price === '' || Number(form.value.assignment_price) < 0) && 'قیمت واگذاری را وارد کنید.',
            (form.value.profit_rate === '' || Number(form.value.profit_rate) < 0 || Number(form.value.profit_rate) > 100) && 'سود / کارمزد باید بین صفر تا ۱۰۰ درصد باشد.',
            (!form.value.installment_count || Number(form.value.installment_count) < 1) && 'تعداد اقساط باید حداقل ۱ ماه باشد.',
        ].filter(Boolean);
    }
    const title = String(form.value.title || '').trim();
    const description = String(form.value.description || '').trim();
    return [
        !form.value.province_id && 'استان را انتخاب کنید.',
        form.value.province_id && !form.value.location_id && 'شهر را انتخاب کنید.',
        title.length < 10 && 'عنوان آگهی باید حداقل ۱۰ کاراکتر باشد.',
        titleHasPhone.value && 'عنوان آگهی نباید شامل شماره تماس باشد.',
        description.length < 20 && 'توضیحات آگهی باید حداقل ۲۰ کاراکتر باشد.',
    ].filter(Boolean);
});
const currentValid = computed(() => validationMessages.value.length === 0);

async function loadPlans() {
    form.value.bank_plan_id = '';
    plans.value = [];
    if (!form.value.bank_id) return;
    plansLoading.value = true;
    try {
        const response = await bankService.getPlans(form.value.bank_id);
        plans.value = response.data || response;
        logger.info('loadPlans', 'Plans loaded', { bankId: form.value.bank_id, count: plans.value.length });
    } catch (exception) {
        logger.error('loadPlans', 'Plans request failed', logException(exception));
        error.value = 'دریافت طرح‌های بانک با خطا مواجه شد.';
    } finally { plansLoading.value = false; }
}
function updateMoney(field, event) { form.value[field] = parseMoney(event.target.value); }
function showValidationError() { error.value = validationMessages.value.join(' '); }
function next() { if (currentValid.value && step.value < 3) { step.value += 1; error.value = ''; } else if (!currentValid.value) showValidationError(); }
function previous() { if (step.value > 1) step.value -= 1; }
async function submit() {
    if (!currentValid.value || loading.value) { showValidationError(); return; }
    loading.value = true; error.value = '';
    const payload = { ...form.value, bank_id: Number(form.value.bank_id), bank_plan_id: Number(form.value.bank_plan_id), location_id: Number(form.value.location_id), loan_amount: Number(form.value.loan_amount), assignment_price: Number(form.value.assignment_price), profit_rate: Number(form.value.profit_rate), installment_count: Number(form.value.installment_count) };
    delete payload.province_id;
    try {
        const response = editing.value ? await adService.update(props.advertisement.id, payload) : await adService.create(payload);
        window.dispatchEvent(new CustomEvent('app:toast', { detail: { severity: 'success', summary: 'موفق', detail: editing.value ? 'آگهی برای بررسی مجدد ارسال شد.' : 'آگهی شما ثبت شد و پس از بررسی تیم مستروام منتشر خواهد شد', life: 5000 } }));
        logger.info('submit', 'Advertisement saved', { id: response.data?.id, editing: editing.value });
        emit('saved', response.data);
        form.value = emptyForm(); step.value = 1;
    } catch (exception) {
        logger.error('submit', 'Advertisement save failed', logException(exception));
        error.value = exception.response?.data?.message || Object.values(exception.response?.data?.errors || {})?.[0]?.[0] || 'ثبت آگهی با خطا مواجه شد.';
    } finally { loading.value = false; }
}
watch(() => props.advertisement, (ad) => { hydrate(ad); step.value = 1; if (ad?.bank_id) loadPlans(); }, { immediate: true });
watch(() => form.value.province_id, () => { form.value.location_id = ''; });
</script>

<template>
    <form class="ad-wizard" dir="rtl" @submit.prevent="submit">
        <div class="wizard-steps"><span v-for="(label, index) in steps" :key="label" :class="{ active: step === index + 1, done: step > index + 1 }"><b>{{ index + 1 }}</b>{{ label }}</span></div>
        <p v-if="error" class="wizard-error" role="alert">{{ error }}</p>
        <section v-if="step === 1" class="wizard-grid">
            <fieldset class="wide"><legend>نوع آگهی</legend><div class="type-cards"><label :class="{ selected: form.type === 'supply' }"><input v-model="form.type" type="radio" value="supply"><strong>واگذاری امتیاز وام</strong><small>امتیاز وام خود را عرضه می‌کنم</small></label><label :class="{ selected: form.type === 'demand' }"><input v-model="form.type" type="radio" value="demand"><strong>درخواست خرید وام</strong><small>به دنبال خرید امتیاز وام هستم</small></label></div></fieldset>
            <label>بانک<select v-model="form.bank_id" required @change="loadPlans"><option value="">انتخاب بانک</option><option v-for="bank in banks" :key="bank.id" :value="bank.id">{{ bank.name }}</option></select></label>
            <label>طرح وام<select v-model="form.bank_plan_id" required :disabled="!form.bank_id || plansLoading"><option value="">{{ plansLoading ? 'در حال دریافت...' : 'انتخاب طرح' }}</option><option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.title }}</option></select></label>
        </section>
        <section v-else-if="step === 2" class="wizard-grid">
            <label>مبلغ کل تسهیلات (تومان)<div class="money-input"><input :value="money(form.loan_amount)" inputmode="numeric" required @input="updateMoney('loan_amount', $event)"><span>تومان</span></div></label>
            <label>قیمت واگذاری امتیاز (تومان)<div class="money-input"><input :value="money(form.assignment_price)" inputmode="numeric" required @input="updateMoney('assignment_price', $event)"><span>تومان</span></div></label>
            <label>سود / کارمزد بانکی (%)<input v-model.number="form.profit_rate" type="number" min="0" max="100" step="0.01" required></label>
            <label>تعداد اقساط (ماه)<input v-model.number="form.installment_count" type="number" min="1" max="360" required></label>
        </section>
        <section v-else class="wizard-grid">
            <label>استان<select v-model="form.province_id" required @change="changeProvince"><option value="">انتخاب استان</option><option v-for="province in provinces" :key="province.id" :value="province.id">{{ province.name }}</option></select></label>
            <label>شهر<select v-model="form.location_id" required :disabled="!form.province_id"><option value="">انتخاب شهر</option><option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option></select></label>
            <label class="wide">عنوان آگهی <small>حداقل ۱۰ کاراکتر، بدون شماره تماس</small><input v-model.trim="form.title" minlength="10" maxlength="255" required></label>
            <label class="wide">توضیحات<textarea v-model.trim="form.description" minlength="20" maxlength="5000" rows="5" required></textarea></label>
        </section>
        <footer class="wizard-actions"><button v-if="step > 1" type="button" class="secondary-action" @click="previous">قبلی</button><button v-if="step < 3" type="button" class="primary-action" @click="next">ادامه</button><button v-else type="submit" class="primary-action" :disabled="loading">{{ loading ? 'در حال ارسال...' : editing ? 'ارسال برای بررسی مجدد' : 'ثبت آگهی' }}</button><button type="button" class="cancel-action" @click="emit('cancel')">انصراف</button></footer>
    </form>
</template>

<style scoped>
.ad-wizard{padding:20px 22px}.wizard-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:20px}.wizard-steps span{display:flex;align-items:center;gap:7px;padding-bottom:10px;border-bottom:2px solid #e8ecee;color:#879198;font-size:11px}.wizard-steps b{display:grid;place-items:center;width:24px;height:24px;border-radius:50%;background:#f0f3f4;font-size:11px}.wizard-steps .active,.wizard-steps .done{border-color:#a62626;color:#a62626}.wizard-steps .active b,.wizard-steps .done b{background:#fbeeed;color:#a62626}.wizard-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.wizard-grid label,.wizard-grid legend{display:grid;gap:7px;color:#4d5a62;font-size:11px;font-weight:600}.wizard-grid small{font-size:10px;font-weight:400;color:#8a959b}.wizard-grid fieldset{border:0}.wide{grid-column:1/-1}.type-cards{display:grid;grid-template-columns:1fr 1fr;gap:10px}.type-cards label{position:relative;padding:14px;border:1px solid #dfe4e8;border-radius:9px;background:#fff;cursor:pointer}.type-cards label.selected{border-color:#a62626;background:#fff8f6;box-shadow:0 0 0 2px #f8e7e5}.type-cards input{position:absolute;opacity:0}.type-cards strong,.type-cards small{display:block}.wizard-grid input,.wizard-grid select,.wizard-grid textarea{width:100%;padding:10px;border:1px solid #dfe4e8;border-radius:7px;outline:0;color:#202a35;font:inherit;font-size:12px}.wizard-grid textarea{resize:vertical}.wizard-grid input:focus,.wizard-grid select:focus,.wizard-grid textarea:focus{border-color:#a62626;box-shadow:0 0 0 3px #f8e7e5}.money-input{position:relative}.money-input input{padding-left:58px}.money-input span{position:absolute;left:10px;top:11px;color:#879198;font-size:10px}.wizard-error{margin:0 0 14px;padding:10px;border-right:3px solid #b83232;background:#fff1ef;color:#a12d2d;font-size:11px}.wizard-actions{display:flex;align-items:center;gap:8px;margin-top:22px}.primary-action,.secondary-action,.cancel-action{padding:10px 16px;border-radius:7px;cursor:pointer;font:inherit;font-size:11px}.primary-action{border:0;background:#a62626;color:#fff}.primary-action:disabled{opacity:.5;cursor:not-allowed}.secondary-action{border:1px solid #dfe4e8;background:#fff;color:#52616b}.cancel-action{margin-right:auto;border:0;background:transparent;color:#78848c}@media(max-width:560px){.wizard-grid,.type-cards{grid-template-columns:1fr}.wide{grid-column:auto}.wizard-steps span{font-size:10px}.wizard-steps span:not(.active):not(.done){font-size:0}.wizard-steps span:not(.active):not(.done) b{font-size:11px}}
</style>
