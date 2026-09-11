<script setup>
const props = defineProps({
    form: { type: Object, required: true },
    banks: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    currentStep: { type: Number, default: 1 },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const emit = defineEmits(['next-step', 'previous-step', 'submit']);
</script>

<template>
    <div class="form-grid" dir="rtl">
        <template v-if="currentStep === 1">
            <label>
                بانک
                <select v-model="form.bank_id" required @change="$emit('next-step')">
                    <option value="">انتخاب بانک</option>
                    <option v-for="bank in banks" :key="bank.id" :value="bank.id">{{ bank.name }}</option>
                </select>
            </label>
            <label>
                طرح وام
                <select v-model="form.bank_plan_id" required :disabled="!form.bank_id">
                    <option value="">انتخاب طرح</option>
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.title }}</option>
                </select>
            </label>
        </template>

        <template v-else-if="currentStep === 2">
            <label>
                نوع آگهی
                <select v-model="form.type" required>
                    <option value="supply">عرضه</option>
                    <option value="demand">تقاضا</option>
                </select>
            </label>
            <label>
                مبلغ وام (تومان)
                <input v-model.number="form.loan_amount" type="number" min="1" required />
            </label>
            <label>
                قیمت واگذاری امتیاز
                <input v-model.number="form.transfer_price" type="number" min="0" required />
            </label>
            <label>
                کارمزد / سود
                <input v-model.number="form.interest_rate" type="number" min="0" step="0.01" />
            </label>
        </template>

        <template v-else-if="currentStep === 3">
            <label class="wide">
                استان و شهر
                <select v-model="form.location_id" required>
                    <option value="">انتخاب شهر</option>
                    <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                </select>
            </label>
        </template>

        <template v-else>
            <label class="wide">
                عنوان آگهی
                <input v-model.trim="form.title" maxlength="255" required placeholder="مثلاً واگذاری امتیاز وام رسالت" />
            </label>
            <label class="wide">
                توضیحات کامل
                <textarea v-model.trim="form.description" rows="5" maxlength="5000" placeholder="جزئیات معامله، شرایط و توضیحات لازم..." />
            </label>
        </template>

        <small v-if="error" class="create-error" role="alert">{{ error }}</small>
    </div>
</template>

<style scoped>
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.form-grid label {
    display: grid;
    gap: 7px;
    color: #4d5a62;
    font-size: 11px;
    font-weight: 600;
}
.form-grid .wide {
    grid-column: 1 / -1;
}
.form-grid input,
.form-grid select,
.form-grid textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 10px;
    border: 1px solid #dfe4e8;
    border-radius: 7px;
    outline: 0;
    color: #202a35;
    font: inherit;
}
.form-grid textarea {
    min-height: 120px;
    resize: vertical;
}
.form-grid input:focus,
.form-grid select:focus,
.form-grid textarea:focus {
    border-color: #b83232;
    box-shadow: 0 0 0 3px #f8e7e5;
}
.create-error {
    grid-column: 1 / -1;
    color: #b83232;
    font-size: 11px;
}
@media (max-width: 560px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
