<script setup>
import { computed } from 'vue';
import Select from 'primevue/select';
import { createLogger } from '../utils/logger';

const logger = createLogger('SortComponent');

const props = defineProps({
    modelValue: { type: String, default: 'latest' },
});

const emit = defineEmits(['update:modelValue', 'change-sort']);

const sortOptions = [
    { label: 'جدیدترین', value: 'latest', icon: 'pi pi-clock' },
    { label: 'بیشترین مبلغ وام', value: 'amount_desc', icon: 'pi pi-arrow-up' },
    { label: 'کمترین مبلغ وام', value: 'amount_asc', icon: 'pi pi-arrow-down' },
    { label: 'ارزان‌ترین واگذاری', value: 'price_asc', icon: 'pi pi-tag' },
    { label: 'گران‌ترین واگذاری', value: 'price_desc', icon: 'pi pi-dollar' },
    { label: 'کمترین سود / کارمزد', value: 'rate_asc', icon: 'pi pi-percentage' },
];

const selectedSort = computed({
    get: () => props.modelValue,
    set: (value) => changeSort(value),
});

function changeSort(value) {
    const nextSort = sortOptions.some((option) => option.value === value) ? value : 'latest';
    const url = new URL(window.location.href);
    url.searchParams.set('sort', nextSort);
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);

    logger.info('changeSort', 'Advertisement sort changed', { sort: nextSort });
    emit('update:modelValue', nextSort);
    emit('change-sort', nextSort);
}
</script>

<template>
    <div class="sort-controls" aria-label="مرتب‌سازی آگهی‌ها">
        <span class="sort-controls__label"><i class="pi pi-sort-alt"></i>مرتب‌سازی:</span>
        <Select v-model="selectedSort" class="sort-controls__dropdown" :options="sortOptions" option-label="label" option-value="value" overlay-class="sort-options-overlay" aria-label="مرتب‌سازی آگهی‌ها">
            <template #option="{ option, selected }">
                <span class="sort-option"><i :class="option.icon"></i><span>{{ option.label }}</span><i v-if="selected" class="pi pi-check sort-option__check"></i></span>
            </template>
        </Select>
    </div>
</template>

<style scoped>
.sort-controls { display: inline-flex; align-items: center; gap: 5px; min-height: 42px; padding: 3px 5px 3px 12px; border: 1px solid #e4e6e8; border-radius: 8px; background: #fff; box-shadow: 0 2px 8px rgba(32, 42, 53, .04); transition: border-color .2s, box-shadow .2s; }
.sort-controls:hover, .sort-controls:focus-within { border-color: #d7aaa3; box-shadow: 0 4px 12px rgba(166, 38, 38, .1); }
.sort-controls__label { display: inline-flex; align-items: center; gap: 5px; color: #68747c; font-size: 11px; white-space: nowrap; }
.sort-controls__label .pi { color: #a62626; }
.sort-controls__dropdown { width: 138px; border: 0; background: transparent; box-shadow: none; }
.sort-controls__dropdown :deep(.p-select-label) { padding: 8px 4px; color: #30383d; font-size: 11px; font-weight: 600; }
.sort-controls__dropdown :deep(.p-select-dropdown) { width: 26px; color: #a62626; }
.sort-option { display: flex; align-items: center; gap: 10px; width: 100%; direction: rtl; font-family: inherit; font-size: 12px; line-height: 1.4; }
.sort-option > .pi:first-child { width: 18px; color: #a62626; font-size: 13px; text-align: center; }
.sort-option__check { margin-right: auto; color: #a62626; font-size: 12px; }
:global(.sort-options-overlay), :global(.sort-options-overlay.p-select-overlay) { min-width: 238px; padding: 6px; overflow: hidden; border: 1px solid #eadfdd; border-radius: 10px; background: #fff !important; opacity: 1; box-shadow: 0 14px 30px rgba(32, 42, 53, .16); font-family: inherit; }
:global(.sort-options-overlay .p-select-list) { gap: 2px; background: #fff !important; }
:global(.sort-options-overlay .p-select-option) { min-height: 40px; margin: 2px 0; padding: 9px 10px; border-radius: 7px; color: #4d5960; font-family: inherit; }
:global(.sort-options-overlay .p-select-option:hover) { background: #fff5f2; color: #a62626; }
:global(.sort-options-overlay .p-select-option.p-select-option-selected) { background: #fff0ed; color: #a62626; font-weight: 700; }
@media (max-width: 700px) { .sort-controls { min-height: 40px; padding-left: 8px; }.sort-controls__dropdown { width: 128px; } }
</style>