<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { cityLabel, searchCities } from './citySearch.mjs';
const props = defineProps({ selected: String, disabled: Boolean });
const emit = defineEmits(['select', 'clear']);
const open = ref(false), query = ref(''), cities = ref([]), loading = ref(false), error = ref(''), input = ref(null), trigger = ref(null);
const matches = computed(() => searchCities(cities.value, query.value));
async function load() {
  if (cities.value.length || loading.value) return;
  loading.value = true; error.value = '';
  try {
    const response = await fetch('/data/ph-cities.json', {headers:{Accept:'application/json'}});
    if (!response.ok) throw new Error('directory');
    const body = await response.json();
    if (!Array.isArray(body.cities) || !body.cities.length) throw new Error('directory');
    cities.value = body.cities;
  } catch { error.value = 'City search could not load. Try again, use your location, or choose a center on the map.'; }
  finally { loading.value = false; }
}
async function toggle() {
  open.value = !open.value;
  if (open.value) { query.value = ''; load(); await nextTick(); input.value?.focus(); }
}
function close() { open.value = false; trigger.value?.focus(); }
function select(city) { close(); emit('select', { ...city, label:cityLabel(city) }); }
function clear() { close(); emit('clear'); }
watch(() => props.disabled, disabled => { if(disabled) open.value=false; });
</script>
<template>
<div class="city-picker" @keydown.esc.stop.prevent="close">
  <button ref="trigger" type="button" class="city-trigger" :disabled="disabled" :aria-expanded="open" aria-controls="city-search-panel" @click="toggle"><span>{{ selected || 'Search a city or municipality' }}</span><span aria-hidden="true">⌄</span></button>
  <section v-if="open" id="city-search-panel" class="city-search-panel" aria-label="Find a Philippine city">
    <div class="city-search-heading"><strong>Where are you looking?</strong><button type="button" aria-label="Close city search" @click="close">×</button></div>
    <label for="city-query">City or municipality in the Philippines</label>
    <input id="city-query" ref="input" v-model="query" type="search" placeholder="Try Cebu, Manila, or Calbayog" maxlength="100" autocomplete="off" @keydown.enter.stop.prevent />
    <p v-if="loading" role="status">Loading cities…</p>
    <div v-else-if="error" role="alert"><p>{{ error }}</p><button type="button" class="text-button" @click="load">Try again</button></div>
    <template v-else>
      <p v-if="query.trim().length<2" class="city-help">Type at least 2 letters. Add a province to narrow your search.</p>
      <p v-else-if="!matches.length" role="status">No matching city found. Try another spelling or include the province.</p>
      <p v-else class="sr-only" role="status">{{ matches.length }} matching locations. Tab to choose a result.</p>
      <ul class="city-results"><li v-for="city in matches" :key="city.id"><button type="button" @click="select(city)"><strong>{{ city.name }}</strong><span>{{ [city.province,city.region].filter(Boolean).join(' · ') }}</span></button></li></ul>
    </template>
    <div class="city-search-footer"><button type="button" class="text-button" @click="clear">All locations</button><small>Location data: <a href="https://www.geonames.org/" target="_blank" rel="noopener noreferrer">GeoNames</a></small></div>
  </section>
</div>
</template>
<style scoped>
.city-picker{position:relative;min-width:0;flex:0 1 280px}
.city-trigger{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;min-height:44px;border:1px solid var(--line);border-radius:7px;padding:10px 12px;font-size:12px;background:#fff;text-align:left;cursor:pointer}
.city-trigger>span:first-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.city-search-panel{position:absolute;right:0;top:calc(100% + 8px);z-index:600;width:360px;max-width:calc(100vw - 48px);padding:16px;border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:0 12px 32px #0b3a3826;color:var(--aadi-dark)}
.city-search-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;font-size:14px;margin-bottom:12px}
.city-search-heading button{font-size:24px;min-width:32px;cursor:pointer}
.city-search-panel label{font-size:11px;margin-bottom:8px}
.city-search-panel p{font-size:12px;line-height:1.5;margin:10px 0}
.city-results{list-style:none;padding:0;margin:8px -6px;max-height:240px;overflow:auto;overscroll-behavior:contain}
.city-results button{display:flex;flex-direction:column;width:100%;text-align:left;padding:10px;border-radius:8px;gap:4px;cursor:pointer}
.city-results button:hover,.city-results button:focus-visible{background:var(--aadi-tint)}
.city-results strong{font-size:13px}.city-results span{font-size:11px;color:var(--muted)}
.city-search-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;border-top:1px solid var(--line);padding-top:12px;font-size:10px}
.city-search-footer a{text-decoration:underline}
@media(max-width:700px){.city-search-panel{right:auto;left:0;width:calc(100vw - 48px)}.city-results{max-height:200px}}
</style>
