<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminListingsTable from '@/Components/Admin/AdminListingsTable.vue';
defineProps({listings:Object});
</script>
<template><Head title="Rakets & services — Admin"/><AdminLayout><template #header><div class="listing-page-heading"><h1>Rakets & services</h1><span>{{ listings.total }} total</span></div></template><AdminListingsTable :listings="listings"/></AdminLayout></template>
<style scoped>.listing-page-heading{display:flex;align-items:center;flex-wrap:wrap;gap:12px;justify-content:space-between}.listing-page-heading h1{font-size:24px;font-weight:700}.listing-page-heading span{font-size:12px;color:#637b76;background:#eff5f2;padding:6px 10px;border-radius:6px}</style>
