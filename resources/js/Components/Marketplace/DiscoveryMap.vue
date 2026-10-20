<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
const props = defineProps({ listings: { type: Array, default: () => [] }, center: { type: Array, default: () => [12.0, 124.75] }, selected: String, pick: Boolean, searchable: { type: Boolean, default: true }, fitListings: Boolean, immersive: Boolean, preview: { type: Boolean, default: true }, scrollWheelZoom: { type: Boolean, default: true } });
const selectedListing = computed(() => props.listings.find(item => item.id === props.selected));
const emit = defineEmits(['select','search','pick']);
const el = ref(null); const tileError = ref(false); let map, markers, resize;
const money = n => new Intl.NumberFormat('en-PH', {maximumFractionDigits:0}).format(n);
function draw() {
  if (!map) return;
  markers.clearLayers();
  props.listings.forEach(item => {
    if (item.location.lat == null || item.location.lng == null) return;
    const label = document.createElement('span'); label.textContent = '₱' + money(item.price) + ' / ' + item.priceUnit;
    const icon = L.divIcon({html:label, className:'price-pin' + (props.selected === item.id ? ' selected' : ''), iconSize:[128,32],iconAnchor:[64,16]});
    L.marker([item.location.lat,item.location.lng],{icon, title:`${item.title}, ₱${money(item.price)} per ${item.priceUnit}`, keyboard:true}).on('click',()=>emit('select',item.id)).addTo(markers);
  });
}
onMounted(() => {
  map=L.map(el.value,{scrollWheelZoom:props.scrollWheelZoom,zoomControl:false}).setView(props.center,12);
  L.control.zoom({position:props.immersive?'topright':'topleft'}).addTo(map);
  L.tileLayer(import.meta.env.VITE_MAP_TILE_URL || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',{
    maxZoom:19, attribution: import.meta.env.VITE_MAP_ATTRIBUTION || '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).on('tileerror',()=>tileError.value=true).on('tileload',()=>tileError.value=false).addTo(map);
  markers=L.layerGroup().addTo(map); draw(); fit();
  map.on('click', e=>{ if(props.pick) emit('pick',[e.latlng.lat,e.latlng.lng]); });
  resize = new ResizeObserver(()=>map.invalidateSize()); resize.observe(el.value);
});
watch(()=>props.scrollWheelZoom,enabled=>{if(map) { if(enabled) map.scrollWheelZoom.enable(); else map.scrollWheelZoom.disable(); }});
watch(()=>[props.listings,props.selected],draw,{deep:true});
function fit() {
  if (!map || !props.fitListings) return;
  const points=props.listings.filter(item=>item.location.lat!=null && item.location.lng!=null).map(item=>[item.location.lat,item.location.lng]);
  if(points.length) map.fitBounds(L.latLngBounds(points),props.immersive ? {paddingTopLeft:[35,230],paddingBottomRight:[35,250],maxZoom:14} : {padding:[45,45],maxZoom:14});
}
watch(()=>props.listings,fit,{deep:true});
watch(()=>props.center,center=>{if(map) { if(props.fitListings && !props.selected) fit(); else map.setView(center,map.getZoom()); }},{deep:true});
watch(()=>props.selected,id=>{if(!props.immersive||!map)return;const item=props.listings.find(l=>l.id===id);if(item?.location.lat!=null&&item?.location.lng!=null)map.panTo([item.location.lat,item.location.lng]);});
onBeforeUnmount(()=>{resize?.disconnect();map?.remove();});
function search(){const c=map.getCenter();emit('search',[c.lat,c.lng]);}
</script>
<template><div class="map-wrap"><div ref="el" class="discovery-map" aria-label="Nearby listings map" />
<button v-if="!pick && searchable" class="button map-search" @click="search">Search near map center</button>
<a v-if="preview && selectedListing && !pick" :href="`/offers/${selectedListing.service}/${selectedListing.id}`" class="map-selection"><strong>{{ selectedListing.title }}</strong><span>View listing →</span></a>
<div v-if="pick" class="map-hint">Click the map to set the listing location</div>
<div v-if="tileError" class="map-hint" role="status">Map tiles unavailable. You can still browse the results.</div>
</div></template>
