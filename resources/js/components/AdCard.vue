<script setup>
const emit = defineEmits(['view']);

const formatMoney = (value) => `${new Intl.NumberFormat('fa-IR').format(Number(value || 0))} میلیون تومان`;

defineProps({
    ad: { type: Object, required: true },
});
</script>

<template>
    <article class="ad-card">
        <div>
            <header class="ad-card__header"><span class="ad-type" :class="ad.type">{{ ad.type === 'supply' ? 'واگذاری' : 'تقاضا' }}</span><span class="bank-label"><i class="pi pi-building"></i>{{ ad.bank || 'بانک نامشخص' }}<small>{{ ad.bank_plan?.title || 'طرح نامشخص' }}</small></span></header>
            <h2>{{ ad.title }}</h2>
            <dl class="ad-details"><div><dt>مبلغ کل وام</dt><dd>{{ formatMoney(ad.amount ?? ad.loan_amount) }}</dd></div><div><dt>قیمت واگذاری امتیاز</dt><dd>{{ formatMoney(ad.price ?? ad.assignment_price) }}</dd></div></dl>
        </div>
        <footer><span><i class="pi pi-map-marker"></i>{{ ad.city || 'شهر نامشخص' }}<small v-if="ad.province">، {{ ad.province }}</small></span><span><i class="pi pi-clock"></i>{{ ad.time || 'زمان نامشخص' }}</span></footer>
        <button class="card-cta" type="button" @click="emit('view', ad)">مشاهده آگهی <i class="pi pi-arrow-left"></i></button>
    </article>
</template>

<style scoped>
.ad-card{direction:rtl;text-align:right;display:flex;flex-direction:column;justify-content:space-between;height:100%;min-height:286px;padding:16px;border:1px solid #edf0f2;border-radius:16px;background:#fff;box-shadow:0 2px 8px rgba(32,42,53,.04);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}.ad-card:hover{border-color:#e0d2d0;box-shadow:0 12px 28px rgba(32,42,53,.1);transform:translateY(-2px)}.ad-card__header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;min-height:45px;margin-bottom:14px}.ad-type{padding:6px 10px;border-radius:999px;background:#eaf8f0;color:#287b50;font-size:10px;font-weight:700;white-space:nowrap}.ad-type.demand{background:#edf4ff;color:#4265a0}.bank-label{display:flex;align-items:center;gap:6px;max-width:70%;padding:7px 9px;border-radius:10px;background:#f5f6f7;color:#46545c;font-size:11px;font-weight:700}.bank-label .pi{color:#a62626;font-size:12px}.bank-label small{overflow:hidden;color:#879299;font-size:9px;font-weight:400;text-overflow:ellipsis;white-space:nowrap}.ad-card h2{display:-webkit-box;min-height:44px;overflow:hidden;margin:0;color:#29343c;font-size:15px;font-weight:700;line-height:1.75;-webkit-box-orient:vertical;-webkit-line-clamp:2}.ad-details{margin:14px 0;padding:10px;border:1px solid #f0f1f2;border-radius:12px;background:rgba(249,250,251,.8)}.ad-details div{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:6px 0}.ad-details div+div{border-top:1px solid #edf0f2}.ad-details dt{color:#7c8991;font-size:10px}.ad-details dd{margin:0;color:#27353d;font-size:12px;font-weight:800;text-align:left;white-space:nowrap}.ad-card footer{display:flex;justify-content:space-between;gap:8px;padding-top:12px;border-top:1px solid #f0f1f2;color:#8a969c;font-size:10px}.ad-card footer span{display:inline-flex;align-items:center;gap:5px;min-width:0}.ad-card footer span:first-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ad-card footer .pi{color:#a62626;font-size:11px}.ad-card footer small{font-size:inherit}.card-cta{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;margin-top:14px;padding:11px 12px;border:0;border-radius:12px;background:#a62626;color:#fff;cursor:pointer;font-size:12px;font-weight:700;transition:background .2s ease,transform .2s ease}.card-cta:hover{background:#861f1f;transform:translateY(-1px)}.card-cta .pi{font-size:11px}
</style>
