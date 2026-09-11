<script setup>
const emit = defineEmits(['view']);

defineProps({
    ad: { type: Object, required: true },
});
</script>

<template>
    <article class="ad-card">
        <div>
            <div class="ad-card__header"><span class="plan-badge">{{ ad.bank_plan?.title || 'طرح نامشخص' }}</span><span class="ad-type" :class="ad.type">{{ ad.type === 'supply' ? 'عرضه' : 'تقاضا' }}</span></div>
            <h2>{{ ad.title }}</h2>
            <p class="ad-bank">{{ ad.bank }}، {{ ad.bank_plan?.title || 'طرح نامشخص' }}</p>
            <dl class="ad-details"><div><dt><i class="pi pi-wallet"></i>مبلغ کل وام</dt><dd>{{ ad.amount }} میلیون تومان</dd></div><div><dt><i class="pi pi-money-bill"></i>قیمت واگذاری</dt><dd>{{ ad.price }} میلیون تومان</dd></div></dl>
        </div>
        <footer><span><i class="pi pi-map-marker"></i>{{ ad.city }}<small v-if="ad.province">، {{ ad.province }}</small></span><span><i class="pi pi-clock"></i>{{ ad.time }}</span></footer>
        <button class="card-cta" type="button" @click="emit('view', ad)">مشاهده <i class="pi pi-arrow-left"></i></button>
    </article>
</template>

<style scoped>
.ad-card { direction: rtl; text-align: right; }
.ad-card__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 13px; }
.plan-badge { display: inline-flex; align-items: center; min-height: 30px; max-width: 70%; padding: 5px 9px; border: 1px solid #f1d8d4; border-radius: 7px; background: #fff5f2; color: #b83232; font-size: 10px; font-weight: 700; line-height: 1.5; }
.ad-type { padding: 4px 9px; border-radius: 20px; background: #edf8f5; color: #287d6f; font-size: 10px; font-weight: 600; }
.ad-type.demand { background: #fff4e8; color: #b56b2b; }
.ad-details dd { text-align: right; }
.ad-card { display: flex; flex-direction: column; justify-content: space-between; min-height: 210px; padding: 16px; border: 1px solid #e5e5e5; border-radius: 8px; background: #fff; transition: box-shadow .2s, border-color .2s; }.ad-card:hover { border-color: #d6d6d6; box-shadow: 0 2px 8px rgba(0, 0, 0, .06); }.ad-card h2 { display: -webkit-box; overflow: hidden; margin: 0 0 8px; color: var(--ink); font-size: 14px; line-height: 1.8; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }.ad-bank { margin: 0 0 18px; color: var(--muted); font-size: 11px; }.ad-details { margin: 0; }.ad-details div { display: flex; justify-content: space-between; gap: 10px; padding: 7px 0; border-top: 1px solid #f2f2f2; }.ad-details dt { color: var(--muted); font-size: 11px; }.ad-details dd { margin: 0; color: var(--ink); font-size: 11px; text-align: left; }.ad-card footer { display: flex; justify-content: space-between; gap: 8px; margin-top: 18px; color: var(--muted); font-size: 10px; }
.ad-card { min-height: 248px; padding: 18px; border: 1px solid #e8eaed; border-top-width: 3px; border-radius: 12px; box-shadow: 0 2px 5px rgba(32, 42, 53, .06), 0 8px 18px rgba(32, 42, 53, .04); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.ad-card:hover { border-color: #d8dde2; box-shadow: 0 5px 10px rgba(32, 42, 53, .08), 0 14px 28px rgba(32, 42, 53, .07); transform: translateY(-3px); }
.ad-card h2 { margin-bottom: 6px; font-size: 15px; line-height: 1.75; }
.ad-bank { margin-bottom: 20px; color: var(--muted); }
.ad-details div { padding: 9px 0; border-top-color: #edf0f2; }
.ad-details dt { color: var(--muted); }.ad-details dd { font-size: 11px; font-weight: 600; }
.ad-card footer { padding-top: 13px; border-top: 1px solid #edf0f2; color: var(--muted); }
.ad-details dt, .ad-card footer span { display: inline-flex; align-items: center; gap: 6px; }
.ad-details dt .pi, .ad-card footer .pi { color: #a62626; font-size: 11px; }
.card-cta { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; margin-top: 16px; padding: 10px 12px; border: 0; border-radius: 7px; background: #a62626; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; }.card-cta:hover { background: #861f1f; }.card-cta .pi { font-size: 11px; }
</style>
