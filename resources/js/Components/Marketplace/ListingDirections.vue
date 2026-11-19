<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, ref } from 'vue';
import SearchIcon from './SearchIcon.vue';

const props = defineProps({ listing: { type: Object, required: true } });
const emit = defineEmits(['route', 'origin', 'pick', 'close']);
const busy = ref(false);
const picking = ref(false);
const error = ref('');
const route = ref(null);
const origin = ref(null);
const mode = ref('driving');
const latitude = ref('');
const longitude = ref('');
const duration = computed(() => {
    const minutes = Math.max(1, Math.round(route.value.duration / 60));
    const hours = Math.floor(minutes / 60);
    const remainder = minutes % 60;
    return hours ? `${hours} hr${hours === 1 ? '' : 's'}${remainder ? ` ${remainder} min` : ''}` : `${minutes} min`;
});
let sequence = 0;
let controller;

function cancelPending() {
    sequence++;
    controller?.abort();
    controller = undefined;
    busy.value = false;
}

function clear() {
    cancelPending();
    picking.value = false;
    emit('pick', false);
    route.value = null;
    origin.value = null;
    latitude.value = '';
    longitude.value = '';
    error.value = '';
    emit('route', null);
    emit('origin', null);
}

async function calculate(start) {
    cancelPending();
    const requestId = sequence;
    controller = new AbortController();
    busy.value = true;
    error.value = '';
    route.value = null;
    emit('route', null);
    origin.value = start;
    emit('origin', start);
    picking.value = false;
    emit('pick', false);
    try {
        const { data } = await axios.post(`/offers/${props.listing.service}/${props.listing.id}/directions`, {
            latitude: start.coordinates[0], longitude: start.coordinates[1], mode: mode.value,
        }, { signal: controller.signal, headers: { Accept: 'application/json' } });
        if (requestId !== sequence) return;
        route.value = data;
        emit('route', data);
    } catch (failure) {
        if (requestId !== sequence || axios.isCancel(failure)) return;
        error.value = failure.response?.data?.message || 'Could not load directions. Check your connection and try again.';
    } finally {
        if (requestId === sequence) busy.value = false;
    }
}

function useLocation() {
    cancelPending();
    const requestId = sequence;
    error.value = '';
    picking.value = false;
    emit('pick', false);
    if (!navigator.geolocation) {
        error.value = 'Location is unavailable. Choose a starting point instead.';
        return;
    }
    busy.value = true;
    navigator.geolocation.getCurrentPosition(position => {
        if (requestId !== sequence) return;
        calculate({ coordinates: [position.coords.latitude, position.coords.longitude], source: 'location' });
    }, () => {
        if (requestId !== sequence) return;
        busy.value = false;
        error.value = 'Could not access your location. Choose a starting point instead.';
    }, { timeout: 10000 });
}

function chooseOnMap() {
    cancelPending();
    error.value = '';
    picking.value = true;
    emit('pick', true);
}

function choosePoint(point) {
    if (!picking.value) return;
    latitude.value = String(point[0]);
    longitude.value = String(point[1]);
    calculate({ coordinates: point, source: 'map' });
}

function useCoordinates() {
    calculate({ coordinates: [Number(latitude.value), Number(longitude.value)], source: 'coordinates' });
}

function selectMode(value) {
    if (mode.value === value) return;
    mode.value = value;
    if (origin.value) calculate(origin.value);
}

function close() {
    clear();
    emit('close');
}

defineExpose({ choosePoint });
onBeforeUnmount(cancelPending);
</script>

<template>
    <section class="directions-panel" aria-label="Directions">
        <header class="directions-header">
            <div><h2>Directions</h2><p>To {{ listing.title }}</p></div>
            <button type="button" class="directions-close" aria-label="Close directions" @click="close"><SearchIcon name="close" /></button>
        </header>
        <div class="directions-modes" role="group" aria-label="Travel mode">
            <button type="button" :class="{ active: mode === 'driving' }" :aria-pressed="mode === 'driving'" :disabled="busy" @click="selectMode('driving')"><SearchIcon name="haul" />Driving</button>
            <button type="button" :class="{ active: mode === 'walking' }" :aria-pressed="mode === 'walking'" :disabled="busy" @click="selectMode('walking')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="13" cy="4" r="2" /><path d="m7 21 3-6 2-4 3 3 1 7m-6-6 4 2m-5-7 3-3 3 2 2 3 3 1" /></svg>
                Walking
            </button>
        </div>
        <p class="directions-privacy">Your starting point and the listing location are sent to openrouteservice.</p>
        <div class="directions-actions">
            <button type="button" class="button primary" :disabled="busy" @click="useLocation"><SearchIcon name="location" />Use my location</button>
            <button type="button" class="button" :disabled="busy" :aria-pressed="picking" @click="chooseOnMap"><SearchIcon name="pin" />Choose on map</button>
        </div>
        <p v-if="picking" class="directions-hint" role="status">Select your starting point on the map, or enter coordinates below.</p>
        <details>
            <summary>Enter coordinates</summary>
            <form class="directions-coordinates" @submit.prevent="useCoordinates">
                <label>Latitude <input v-model="latitude" type="number" step="any" min="-90" max="90" required /></label>
                <label>Longitude <input v-model="longitude" type="number" step="any" min="-180" max="180" required /></label>
                <button class="button" :disabled="busy">Get route</button>
            </form>
        </details>
        <div v-if="origin" class="directions-origin-row">
            <p class="directions-hint">{{ origin.source === 'location' ? 'Your location is marked. This is not live tracking.' : 'Starting point marked on the map.' }}</p>
            <button type="button" class="directions-clear" aria-label="Clear starting point and route" title="Clear starting point and route" @click="clear"><SearchIcon name="reset" /></button>
        </div>
        <p v-if="busy" class="directions-hint" role="status">Finding your route…</p>
        <p v-if="error" class="directions-error" role="alert">{{ error }}</p>
        <div v-if="route" class="directions-result" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
            <span><strong>{{ duration }}</strong> estimated {{ mode === 'driving' ? 'driving' : 'walking' }} time<br /><small>{{ route.distance < 1000 ? `${Math.round(route.distance)} m` : `${(route.distance / 1000).toFixed(1)} km` }}</small></span>
        </div>
        <p class="directions-note">Estimates to the saved listing location; no live traffic.</p>
    </section>
</template>

<style scoped>
.directions-panel { padding:14px; border:1px solid #dce8e3; border-radius:12px; background:#fff; color:#183e3c; box-shadow:0 6px 24px rgb(18 43 40 / .12); }
.directions-header { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
.directions-header h2 { margin:0; font-size:16px; line-height:1.3; }
.directions-header p { margin:4px 0 0; color:#586e6c; font-size:12px; overflow-wrap:anywhere; }
.directions-close { display:grid; place-items:center; width:40px; height:40px; flex:none; margin:-8px -8px 0 0; border:0; border-radius:8px; background:transparent; color:inherit; cursor:pointer; }
.directions-modes { display:flex; gap:4px; margin-top:8px; padding:4px; border-radius:10px; background:#edf5f2; }
.directions-modes button { display:flex; align-items:center; justify-content:center; gap:6px; flex:1; min-height:40px; border:0; border-radius:7px; background:transparent; color:#486360; font:inherit; font-size:13px; font-weight:700; cursor:pointer; }
.directions-modes svg,.directions-actions svg,.directions-result svg { width:18px; height:18px; flex:none; }
.directions-modes button.active { background:#fff; color:#0e6964; box-shadow:0 1px 4px rgb(18 43 40 / .12); }
.directions-modes button:disabled { cursor:wait; opacity:.7; }
.directions-privacy,.directions-note,.directions-hint,.directions-error { margin:8px 0; font-size:12px; line-height:1.4; }
.directions-privacy,.directions-note,.directions-hint { color:#586e6c; }
.directions-error { color:#9c261d; }
.directions-actions { display:flex; flex-wrap:wrap; gap:8px; }
.directions-actions .button { display:inline-flex; align-items:center; justify-content:center; gap:6px; }
.directions-actions .button,.directions-coordinates .button { min-height:40px; padding:8px 10px; font-size:12px; }
details { margin-top:8px; font-size:12px; }
summary { color:#137671; font-weight:700; cursor:pointer; }
.directions-coordinates { display:flex; align-items:end; flex-wrap:wrap; gap:8px; margin-top:8px; }
.directions-coordinates label { display:grid; gap:4px; flex:1 1 100px; color:#586e6c; }
.directions-coordinates input { width:100%; min-width:0; min-height:40px; border:1px solid #c9dbd8; border-radius:8px; padding:8px; font-size:16px; }
.directions-origin-row { display:flex; align-items:center; gap:8px; }
.directions-origin-row .directions-hint { flex:1; }
.directions-result { display:flex; align-items:center; gap:10px; margin-top:8px; padding:8px 10px; border-radius:8px; background:#e9f5f1; font-size:13px; }
.directions-result svg { color:#137671; }
.directions-clear { display:grid; place-items:center; width:40px; height:40px; flex:none; border:0; border-radius:8px; background:transparent; color:#137671; cursor:pointer; }
.directions-panel :is(button,input,summary):focus-visible { outline:2px solid #137671; outline-offset:2px; }
</style>
