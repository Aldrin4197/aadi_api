<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({listing:Object, selected:Boolean}); defineEmits(['select']);
const money = n => new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP',maximumFractionDigits:0}).format(n);
const label = s => s?.replace(/([A-Z])/g,' $1').replace(/^./,c=>c.toUpperCase());
</script>
<template><article class="listing-card" :class="{selected}" @mouseenter="$emit('select',listing.id)" @focusin="$emit('select',listing.id)">
<Link :href="`/offers/${listing.service}/${listing.id}`" class="card-image"><img v-if="listing.photos[0]" :src="listing.photos[0]" :alt="listing.title" loading="lazy" @error="$event.target.style.display='none'"/><span v-else class="photo-placeholder">{{ label(listing.type) }}</span><span class="card-tag">{{ label(listing.type) }}</span></Link>
<div class="card-body"><p class="eyebrow">{{ listing.location.city || listing.location.address || 'Location not provided' }}</p><h2><Link :href="`/offers/${listing.service}/${listing.id}`">{{ listing.title }}</Link></h2><p class="card-price">{{ money(listing.price) }} <span>/ {{ listing.priceUnit }}</span></p></div>
</article></template>
