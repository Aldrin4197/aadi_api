<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ListingImage from './ListingImage.vue';
import { compareKey, formatPrice } from './comparison.mjs';
defineProps({ items: { type: Array, default: () => [] }, distanceLabel: { type: Function, required: true } });
defineEmits(['remove']);
const dialog = ref(null);
const categories = {spaces:'Space rentals', haul:'Utility vehicles', raketeer:'Raket Finder'};
const readable = value => (value || '').replace(/([A-Z])/g, ' $1').replace(/^./, c => c.toUpperCase());
defineExpose({ open: () => dialog.value?.showModal() });
</script>
<template>
<dialog ref="dialog" class="price-comparison" aria-labelledby="comparison-title">
  <header class="comparison-top"><span>A little perspective</span><button type="button" class="comparison-close" aria-label="Close comparison" @click="dialog.close()">×</button></header>
  <div class="comparison-content">
    <h2 id="comparison-title">Find your best fit.</h2>
    <p>Compare the details that matter to your plan.</p>
    <p class="comparison-note">Rates use different billing units. Monthly rentals, daily rentals, hourly rates, and fixed jobs are not equivalent total costs. Confirm scope and additional charges with the provider.</p>
    <div v-if="items.length" class="comparison-table" tabindex="0" role="region" aria-label="Scrollable listing comparison">
      <table :style="{minWidth:(140 + items.length * 240)+'px'}"><colgroup><col style="width:140px" /><col v-for="item in items" :key="compareKey(item)" /></colgroup><caption class="sr-only">Comparison of selected listings</caption>
        <thead><tr><th scope="col">Your finds</th><th v-for="item in items" :key="compareKey(item)" scope="col">
          <ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" :type="item.type" />
          <strong>{{ item.title }}</strong><button class="compare-remove" :aria-label="'Remove '+item.title+' from comparison'" @click="$emit('remove',item)">Remove</button>
        </th></tr></thead>
        <tbody>
          <tr><th scope="row">Category</th><td v-for="item in items" :key="compareKey(item)">{{ categories[item.service] }}</td></tr>
          <tr><th scope="row">Base rate</th><td v-for="item in items" :key="compareKey(item)"><strong class="comparison-rate">{{ formatPrice(item.price) }}</strong><small>per {{ item.priceUnit }}</small></td></tr>
          <tr><th scope="row">Location</th><td v-for="item in items" :key="compareKey(item)">{{ item.location.city || item.location.address || 'Not provided' }}</td></tr>
          <tr><th scope="row">Distance</th><td v-for="item in items" :key="compareKey(item)">{{ distanceLabel(item) || 'Choose a location to calculate' }}</td></tr>
          <tr><th scope="row">Type / scope</th><td v-for="item in items" :key="compareKey(item)">{{ readable(item.type) }}<span v-if="item.comparison?.scope"> · {{ item.comparison.scope }}</span></td></tr>
          <tr><th scope="row">Listed features / skills</th><td v-for="item in items" :key="compareKey(item)">{{ item.comparison?.features?.join(', ') || 'Not specified — ask the provider' }}</td></tr>
          <tr><th scope="row">Not included</th><td v-for="item in items" :key="compareKey(item)">Confirm exclusions and extra charges with the provider.</td></tr>
          <tr><th scope="row">Listed status</th><td v-for="item in items" :key="compareKey(item)">{{ item.status === 'active' ? 'Available' : readable(item.status) }}</td></tr>
          <tr><th scope="row">Provider</th><td v-for="item in items" :key="compareKey(item)">{{ item.ownerName || 'Not provided' }}</td></tr>
          <tr><th scope="row">Next step</th><td v-for="item in items" :key="compareKey(item)"><Link class="button primary" :href="`/offers/${item.service}/${item.id}`">View listing →</Link></td></tr>
        </tbody>
      </table>
    </div>
    <p v-if="items.length < 2" role="status">Choose at least two finds from the map to compare.</p>
    <p class="comparison-footnote">On a small screen, swipe the table to see every find. Distances are straight-line estimates from your selected location. Details reflect when each listing was selected.</p>
  </div>
</dialog>
</template>
