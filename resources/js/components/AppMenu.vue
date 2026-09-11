<script setup>
defineProps({ items: { type: Array, required: true }, title: { type: String, default: '' } });
</script>

<template>
    <nav class="app-menu" aria-label="منوی داشبورد">
        <p v-if="title" class="app-menu__title">{{ title }}</p>
        <RouterLink v-for="item in items" :key="item.label" :to="item.to" class="app-menu__item">
            <i :class="item.icon" aria-hidden="true"></i><span>{{ item.label }}</span><i class="pi pi-angle-left" aria-hidden="true"></i>
        </RouterLink>
    </nav>
</template>

<style scoped>
.app-menu { display: flex; flex-direction: column; gap: 3px; width: 100%; margin-top: 26px; }
.app-menu__title { margin: 0 12px 9px; color: #8b959e; font-size: 11px; line-height: 1.8; }
.app-menu__item { display: flex; align-items: center; justify-content: space-between; gap: 11px; width: 100%; min-height: 44px; padding: 11px 12px; border-radius: 8px; background: transparent; color: #59656e; font-size: 12px; line-height: 1.8; text-align: right; text-decoration: none; transition: background-color .18s ease, color .18s ease; }
.app-menu__item > span { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.app-menu__item > i:first-child { flex: 0 0 18px; color: #66727b; font-size: 14px; text-align: center; }
.app-menu__item > i:last-child { flex: 0 0 12px; color: #a6afb5; font-size: 11px; text-align: center; }
.app-menu__item:hover { background: #f9fafb; color: #59656e; }
.app-menu__item.router-link-active { background: transparent; color: #59656e; }
.app-menu__item:hover > i:first-child, .app-menu__item.router-link-active > i:first-child { color: #66727b; }
@media (max-width: 720px) {
    .app-menu { margin-top: 20px; }
    .app-menu__item { min-height: 42px; padding: 9px 10px; }
}
</style>