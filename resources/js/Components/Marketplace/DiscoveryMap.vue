<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import { markerPath } from './mapMarkers.mjs';
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
const listingMarkers = new Map();
function selectStyles() {
  listingMarkers.forEach((marker,id)=>{
    const active=props.selected===id;
    marker.setZIndexOffset(active?1000:0);
    const node=marker.getElement();
    node?.classList.toggle('selected',active);
    node?.setAttribute('aria-pressed',String(active));
  });
}
function draw() {
  if (!map) return;
  markers.clearLayers(); listingMarkers.clear();
  props.listings.forEach(item => {
    if (item.location.lat == null || item.location.lng == null) return;
    const glyph=document.createElementNS('http://www.w3.org/2000/svg','svg');
    glyph.setAttribute('viewBox','0 0 24 24');glyph.setAttribute('aria-hidden','true');
    const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d',markerPath(item));glyph.append(path);
    const icon=L.divIcon({html:glyph,className:'aadi-category-pin',iconSize:[44,44],iconAnchor:[22,22]});
    const title=`${item.title}, ₱${money(item.price)} per ${item.priceUnit}`;
    const marker=L.marker([item.location.lat,item.location.lng],{icon,title,alt:title,keyboard:true}).on('click',()=>emit('select',item.id)).addTo(markers);
    marker.getElement()?.setAttribute('aria-label',title);
    marker.getElement()?.addEventListener('keydown',event=>{if(event.key===' '){event.preventDefault();emit('select',item.id);}});
    listingMarkers.set(item.id,marker);
  });
  selectStyles();
}
function mapPadding() {
  const bounds=el.value.getBoundingClientRect(), parent=el.value.closest('.map-explorer');
  const controls=parent?.querySelector('.explorer-controls')?.getBoundingClientRect();
  const results=parent?.querySelector('.map-results');
  const bottom=results && getComputedStyle(results).display!=='none'?results.getBoundingClientRect():null;
  return {top:Math.min(bounds.height*.35,Math.max(24,(controls?.bottom||bounds.top)-bounds.top+16)),bottom:Math.min(bounds.height*.45,Math.max(24,bottom?bounds.bottom-bottom.top+16:40))};
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
watch(()=>props.listings,draw,{deep:true});
watch(()=>props.selected,selectStyles);
function fit() {
  if (!map || !props.fitListings) return;
  const points=props.listings.filter(item=>item.location.lat!=null && item.location.lng!=null).map(item=>[item.location.lat,item.location.lng]);
  const padding=mapPadding();
  if(points.length) map.fitBounds(L.latLngBounds(points),props.immersive ? {paddingTopLeft:[54,padding.top],paddingBottomRight:[54,padding.bottom],maxZoom:14} : {padding:[45,45],maxZoom:14});
}
watch(()=>props.listings,fit,{deep:true});
watch(()=>props.center,center=>{if(map) { drawCenter(); if(props.fitListings && !props.selected) fit(); else map.setView(center,map.getZoom()); }},{deep:true});
watch(()=>props.selected,id=>{if(!props.immersive||!map)return;const item=props.listings.find(l=>l.id===id);if(item?.location.lat!=null&&item?.location.lng!=null){const padding=mapPadding();const point=map.project([item.location.lat,item.location.lng],map.getZoom());point.y-=(padding.top-padding.bottom)/2;map.panTo(map.unproject(point,map.getZoom()),{animate:!matchMedia('(prefers-reduced-motion: reduce)').matches});}});
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
@media(max-width:700px), (max-width:950px) and (max-height:550px) { .map-explorer .aadi-map .map-reset { top:180px; right:12px; } }
</style>

<style>
.aadi-map .aadi-category-pin{display:grid;place-items:center;background:#fff;color:#137671;border:2px solid #137671;border-radius:50%;box-shadow:0 2px 6px #0003;cursor:pointer;transition:background .15s ease,color .15s ease;touch-action:manipulation;-webkit-tap-highlight-color:transparent}
.aadi-category-pin svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;pointer-events:none;transition:width .15s ease,height .15s ease}
.aadi-map .aadi-category-pin.selected{background:#137671;color:white;box-shadow:0 0 0 4px #f6ca52,0 3px 9px #0004}
.aadi-category-pin.selected svg{width:24px;height:24px}.aadi-category-pin:focus-visible{outline:3px solid #f59e42;outline-offset:5px}
@media(prefers-reduced-motion:reduce){.aadi-map .aadi-category-pin,.aadi-category-pin svg{transition:none}}
</style>
