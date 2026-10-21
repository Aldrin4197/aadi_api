<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
const props = defineProps({ listings: { type: Array, default: () => [] }, center: { type: Array, default: () => [12.0, 124.75] }, selected: String, pick: Boolean, searchable: { type: Boolean, default: true }, fitListings: Boolean, immersive: Boolean, preview: { type: Boolean, default: true }, scrollWheelZoom: { type: Boolean, default: true } });
const selectedListing = computed(() => props.listings.find(item => item.id === props.selected));
const emit = defineEmits(['select','search','pick']);
const el = ref(null); const tileError = ref(false); let map, markers, centerMarker, resize;
const mapboxToken = import.meta.env.VITE_MAPBOX_TOKEN;
const customTiles = import.meta.env.VITE_MAP_TILE_URL;
const osmAttribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const tileUrl = customTiles || (mapboxToken
  ? `https://api.mapbox.com/styles/v1/mapbox/light-v11/tiles/512/{z}/{x}/{y}@2x?access_token=${encodeURIComponent(mapboxToken)}`
  : 'https://tile.openstreetmap.org/{z}/{x}/{y}.png');
function drawCenter() {
  if (!map) return;
  if (centerMarker) centerMarker.setLatLng(props.center);
  else centerMarker = L.circleMarker(props.center, {radius:7,color:'#fff',weight:3,fillColor:'#1fa6a0',fillOpacity:1,interactive:false}).addTo(map);
}
function resetView() {
  if(props.fitListings && props.listings.some(item => item.location.lat != null && item.location.lng != null)) fit();
  else map.setView(props.center,12);
}
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
  L.tileLayer(tileUrl, {
    maxZoom:19,
    ...(mapboxToken && !customTiles ? {tileSize:512,zoomOffset:-1} : {}),
    className: !customTiles && !mapboxToken ? 'aadi-muted-basemap' : '',
    attribution: import.meta.env.VITE_MAP_ATTRIBUTION || (mapboxToken && !customTiles ? '&copy; <a href="https://www.mapbox.com/about/maps/">Mapbox</a> · ' : '') + osmAttribution
  }).on('tileerror',()=>tileError.value=true).on('tileload',()=>tileError.value=false).addTo(map);
  drawCenter();
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
watch(()=>props.center,center=>{if(map) { drawCenter(); if(props.fitListings && !props.selected) fit(); else map.setView(center,map.getZoom()); }},{deep:true});
watch(()=>props.selected,id=>{if(!props.immersive||!map)return;const item=props.listings.find(l=>l.id===id);if(item?.location.lat!=null&&item?.location.lng!=null)map.panTo([item.location.lat,item.location.lng]);});
onBeforeUnmount(()=>{resize?.disconnect();map?.remove();});
function search(){const c=map.getCenter();emit('search',[c.lat,c.lng]);}
</script>
<template><div class="map-wrap aadi-map"><div ref="el" class="discovery-map" aria-label="Nearby listings map" />
<button type="button" class="map-reset" aria-label="Reset map view" title="Reset map view" @click="resetView"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="6"/><path d="M12 2v4m0 12v4M2 12h4m12 0h4"/></svg></button>
<button v-if="!pick && searchable" class="button map-search" @click="search">Search near map center</button>
<a v-if="preview && selectedListing && !pick" :href="`/offers/${selectedListing.service}/${selectedListing.id}`" class="map-selection"><strong>{{ selectedListing.title }}</strong><span>View listing →</span></a>
<div v-if="pick" class="map-hint">Click the map to set the listing location</div>
<div v-if="tileError" class="map-hint" role="status">Map tiles unavailable. You can still browse the results.</div>
</div></template>

<style>
.aadi-map .aadi-muted-basemap { filter: saturate(.15) contrast(.92) brightness(1.04); }
.aadi-map .leaflet-container { font-family:inherit; background:#eef2f1; }
.aadi-map .leaflet-bar { border:1px solid var(--line); border-radius:12px; overflow:hidden; box-shadow:0 3px 12px #0b3a3814; }
.aadi-map .leaflet-bar a { width:40px; height:40px; line-height:40px; color:var(--aadi-dark,#0b3a38); background:#fff; border-bottom-color:var(--line); }
.aadi-map .leaflet-bar a:hover { background:var(--aadi-tint,#e3f6f4); }
.aadi-map .leaflet-bar a.leaflet-disabled { color:#8c9b99; }
.aadi-map .price-pin { border:1px solid var(--teal,#1fa6a0); color:var(--aadi-dark,#0b3a38); box-shadow:0 3px 10px #0b3a3826; }
.aadi-map .price-pin.selected { background:var(--teal,#1fa6a0); color:#fff; border-color:var(--teal,#1fa6a0); }
.aadi-map .map-reset { position:absolute; z-index:500; top:104px; left:10px; display:grid; place-items:center; width:42px; height:42px; border:1px solid var(--line); border-radius:12px; background:#fff; color:var(--aadi-dark,#0b3a38); box-shadow:0 3px 12px #0b3a3814; cursor:pointer; }
.map-explorer .aadi-map .map-reset { left:auto; right:10px; top:154px; }
.aadi-map .map-search { border-radius:999px; border-color:var(--line); box-shadow:0 3px 12px #0b3a3814; }
.aadi-map .leaflet-control-attribution { background:#fffffff0; color:#536661; font-size:10px; }
@media(max-width:700px) { .map-explorer .aadi-map .map-reset { top:406px; right:12px; } }
</style>
