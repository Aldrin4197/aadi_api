<script setup>
import ListingPreviewLink from './ListingPreviewLink.vue';
defineProps({ item: { type:Object, required:true } });
</script>
<template><ListingPreviewLink :item="item" class="listing-view-button" :aria-label="`View listing: ${item.title}`">View listing <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></ListingPreviewLink></template>
<style scoped>
.listing-view-button { display:inline-flex!important; align-items:center; justify-content:center; gap:8px; min-height:44px; padding:10px 14px!important; border:1px solid #137671!important; border-radius:12px!important; background:#137671!important; color:#fff!important; font-size:13px!important; font-weight:700!important; line-height:1.3; text-decoration:none; }
.listing-view-button:hover { background:#0b3a38!important; }
.listing-view-button svg { width:18px; height:18px; flex:none; }
</style>
