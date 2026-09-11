<script setup>
import { computed, ref } from 'vue';

const props = defineProps({ modelValue: { type: String, default: '' } });
const emit = defineEmits(['update:modelValue']);
const months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
const open = ref(false);
const today = jalaliToday();
const parsed = parseJalali(props.modelValue) || today;
const year = ref(parsed.year);
const month = ref(parsed.month);
const selectedDay = computed(() => parseJalali(props.modelValue)?.day || null);
const years = computed(() => Array.from({ length: 121 }, (_, index) => today.year - 120 + index));
const daysInMonth = computed(() => month.value <= 6 ? 31 : month.value <= 11 ? 30 : isLeapJalali(year.value) ? 30 : 29);
const firstWeekday = computed(() => {
    const gregorian = jalaliToGregorian(year.value, month.value, 1);
    return (new Date(Date.UTC(gregorian.year, gregorian.month - 1, gregorian.day)).getUTCDay() + 1) % 7;
});
const calendarDays = computed(() => [...Array(firstWeekday.value).fill(null), ...Array.from({ length: daysInMonth.value }, (_, index) => index + 1)]);

function parseJalali(value) {
    const match = String(value || '').match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/);
    return match ? { year: Number(match[1]), month: Number(match[2]), day: Number(match[3]) } : null;
}

function jalaliToday() {
    const date = new Date();
    const converted = gregorianToJalali(date.getFullYear(), date.getMonth() + 1, date.getDate());
    return converted;
}

function isLeapJalali(jalaliYear) {
    const current = jalaliToGregorian(jalaliYear, 1, 1);
    const next = jalaliToGregorian(jalaliYear + 1, 1, 1);
    return new Date(Date.UTC(next.year, next.month - 1, next.day)).getTime() - new Date(Date.UTC(current.year, current.month - 1, current.day)).getTime() > 365 * 86400000;
}

function jalaliToGregorian(jy, jm, jd) {
    jy += 1595;
    let days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd;
    days += jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186;
    let gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    let gd = days + 1;
    const monthDays = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let gm = 1;
    while (gm <= 12 && gd > monthDays[gm]) gd -= monthDays[gm++];
    return { year: gy, month: gm, day: gd };
}

function gregorianToJalali(gy, gm, gd) {
    const gYear = gy - 1600;
    let dayNo = 365 * gYear + Math.floor((gYear + 3) / 4) - Math.floor((gYear + 99) / 100) + Math.floor((gYear + 399) / 400) - 1;
    const monthDays = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    for (let index = 1; index < gm; index++) dayNo += monthDays[index];
    dayNo += gd;
    let jalaliDayNo = dayNo - 79;
    const cycle = Math.floor(jalaliDayNo / 12053);
    jalaliDayNo %= 12053;
    let jy = 979 + 33 * cycle + 4 * Math.floor(jalaliDayNo / 1461);
    jalaliDayNo %= 1461;
    if (jalaliDayNo >= 366) { jy += Math.floor((jalaliDayNo - 1) / 365); jalaliDayNo = (jalaliDayNo - 1) % 365; }
    const jm = jalaliDayNo < 186 ? 1 + Math.floor(jalaliDayNo / 31) : 7 + Math.floor((jalaliDayNo - 186) / 30);
    const jd = 1 + (jalaliDayNo < 186 ? jalaliDayNo % 31 : (jalaliDayNo - 186) % 30);
    return { year: jy, month: jm, day: jd };
}

function selectDay(day) {
    if (!day) return;
    emit('update:modelValue', `${year.value}/${String(month.value).padStart(2, '0')}/${String(day).padStart(2, '0')}`);
    open.value = false;
}

function selectToday() {
    year.value = today.year;
    month.value = today.month;
    selectDay(today.day);
}
</script>

<template>
    <div class="persian-picker" dir="rtl">
        <button type="button" class="persian-picker__input" :aria-expanded="open" @click="open = !open">
            <span :class="{ placeholder: !modelValue }">{{ modelValue || 'انتخاب تاریخ شمسی' }}</span>
            <i class="pi pi-calendar"></i>
        </button>
        <div v-if="open" class="persian-picker__panel">
            <div class="persian-picker__toolbar">
                <select v-model.number="month" aria-label="ماه"><option v-for="(monthName, index) in months" :key="monthName" :value="index + 1">{{ monthName }}</option></select>
                <select v-model.number="year" aria-label="سال"><option v-for="item in years" :key="item" :value="item">{{ item }}</option></select>
            </div>
            <div class="persian-picker__weekdays"><span v-for="dayName in ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه']" :key="dayName">{{ dayName.slice(0, 1) }}</span></div>
            <div class="persian-picker__days"><button v-for="(day, index) in calendarDays" :key="`${year}-${month}-${index}`" type="button" :class="{ selected: day === selectedDay }" :disabled="!day" @click="selectDay(day)">{{ day || '' }}</button></div>
            <button type="button" class="persian-picker__today" @click="selectToday"><i class="pi pi-calendar"></i>امروز</button>
        </div>
    </div>
</template>

<style scoped>
.persian-picker{position:relative;width:100%}.persian-picker__input{display:flex;align-items:center;justify-content:space-between;width:100%;min-height:42px;padding:0 11px;border:1px solid #dfe4e8;border-radius:7px;background:#fff;color:#26343c;cursor:pointer;font:inherit;font-size:12px;text-align:right}.persian-picker__input .placeholder{color:#8a959b}.persian-picker__input i{color:#7a8791}.persian-picker__panel{position:absolute;top:calc(100% + 7px);right:0;z-index:20;width:min(310px,calc(100vw - 40px));padding:14px;border:1px solid #dfe4e8;border-radius:10px;background:#fff;box-shadow:0 10px 28px rgba(32,42,53,.14)}.persian-picker__toolbar{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:13px}.persian-picker select{min-height:34px;padding:0 7px;border:1px solid #e1e6e8;border-radius:6px;background:#fff;color:#3c4a52;font:inherit;font-size:11px}.persian-picker__weekdays,.persian-picker__days{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center}.persian-picker__weekdays{margin-bottom:5px;color:#89949a;font-size:10px}.persian-picker__days button{min-height:30px;border:0;border-radius:6px;background:transparent;color:#44525b;cursor:pointer;font:inherit;font-size:11px}.persian-picker__days button:hover:not(:disabled){background:#f5e9e7;color:#a62626}.persian-picker__days button.selected{background:#a62626;color:#fff}.persian-picker__days button:disabled{cursor:default}.persian-picker__today{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;margin-top:12px;padding:8px;border:0;border-radius:6px;background:#fff5f2;color:#a62626;cursor:pointer;font:inherit;font-size:11px}
</style>
