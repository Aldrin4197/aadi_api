<script setup>
import { computed, onMounted, ref } from 'vue';
import { cityLabel } from '@/Components/Marketplace/citySearch.mjs';

const props = defineProps({ modelValue: { type: String, default: '' }, id: String, required: Boolean, maxlength: { type: Number, default: 255 }, area: Boolean });
const emit = defineEmits(['update:modelValue']);
const cities = ref([]);
const loading = ref(true);
const error = ref('');
const selected = ref('');
const labels = computed(() => [...new Set(cities.value.map(cityLabel))].sort((a, b) => a.localeCompare(b)));
const hasCurrentValue = computed(() => props.modelValue && !labels.value.includes(props.modelValue));

async function loadCities() {
    loading.value = true;
    error.value = '';
    try {
        const response = await fetch('/data/ph-cities.json', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('City directory request failed');
        const data = await response.json();
        if (!Array.isArray(data.cities) || !data.cities.length) throw new Error('City directory is empty');
        cities.value = data.cities;
    } catch {
        error.value = 'The city directory could not be loaded. Enter the location manually or try again.';
    } finally {
        loading.value = false;
    }
}
function chooseArea(event) {
    selected.value = event.target.value;
    if (selected.value) emit('update:modelValue', selected.value);
}
onMounted(loadCities);
</script>

<template>
    <div class="address-city">
        <input v-if="area || error" :id="id" :value="modelValue" :required="required" :maxlength="maxlength" :autocomplete="area ? 'street-address' : 'address-level2'" :placeholder="area ? 'Address or service area' : 'City or municipality'" @input="emit('update:modelValue', $event.target.value)" />
        <select v-if="!error" :id="area ? undefined : id" :value="area ? selected : modelValue" :required="!area && required" :disabled="loading" :aria-label="area ? 'Choose a city for the service area' : undefined" @change="area ? chooseArea($event) : emit('update:modelValue', $event.target.value)">
            <option value="">{{ loading ? 'Loading cities…' : 'Select a city or municipality' }}</option>
            <option v-if="!area && hasCurrentValue" :value="modelValue">{{ modelValue }} (existing value)</option>
            <option v-for="city in labels" :key="city" :value="city">{{ city }}</option>
        </select>
        <div v-if="error" role="alert"><small>{{ error }}</small> <button type="button" class="retry-city" @click="loadCities">Try again</button></div>
        <small v-if="area && !error">Choose a city as a starting point or enter the full address or service area manually.</small>
    </div>
</template>

<style scoped>
.address-city { display:grid; gap:8px; min-width:0; }
.address-city > input,.address-city > select { width:100%; min-height:44px; padding:10px 12px; border:1px solid #cbded9; border-radius:9px; background:#fff; font-size:16px; }
.address-city small { color:#586e6c; line-height:1.5; font-size:12px; }
.retry-city { color:#137671; text-decoration:underline; min-height:44px; }
</style>
