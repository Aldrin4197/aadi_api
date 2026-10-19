<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/MarketplaceLayout.vue';
import DiscoveryMap from '@/Components/Marketplace/DiscoveryMap.vue';
import ListingCard from '@/Components/Marketplace/ListingCard.vue';
const props=defineProps({results:Object,filters:Object,types:Object});
const form=ref({service:props.filters.service||'spaces',q:props.filters.q||'',type:props.filters.type||'',minPrice:props.filters.minPrice??'',maxPrice:props.filters.maxPrice??'',radiusKm:props.filters.radiusKm||10,lat:props.filters.lat??'',lng:props.filters.lng??''});
const selected=ref(null), loading=ref(false), locationError=ref(''), locating=ref(false), mobileView=ref('list');
const center=computed(()=>form.value.lat!==''&&form.value.lng!==''?[Number(form.value.lat),Number(form.value.lng)]:props.results.data.find(l=>l.location.lat!=null&&l.location.lng!=null)?[props.results.data.find(l=>l.location.lat!=null&&l.location.lng!=null).location.lat,props.results.data.find(l=>l.location.lat!=null&&l.location.lng!=null).location.lng]:[12.0,124.75]);
const headings={spaces:['A place to call yours.','Rooms, homes, and spaces around you.'],haul:['Your next ride is nearby.','Find the right vehicle for wherever life takes you.'],raketeer:['Good help, close to home.','Find local skills and services for your next project.']};
function search(page=1){loading.value=true;const query=Object.fromEntries(Object.entries({...form.value,page}).filter(([,v])=>v!==''&&v!==null));router.get('/explore',query,{preserveScroll:true,onFinish:()=>loading.value=false});}
function service(value){form.value.service=value;form.value.type='';search();}
function area(coords){[form.value.lat,form.value.lng]=coords;search();}
function locate(){locationError.value='';if(!navigator.geolocation){locationError.value='Location is unavailable. Search a city or move the map.';return;}locating.value=true;navigator.geolocation.getCurrentPosition(p=>{locating.value=false;area([p.coords.latitude,p.coords.longitude]);},()=>{locating.value=false;locationError.value='Location access failed. Search a city or move the map instead.';},{timeout:10000});}
function clear(){form.value={service:form.value.service,q:'',type:'',minPrice:'',maxPrice:'',radiusKm:10,lat:'',lng:''};search();}
</script>
<template><Layout><Head title="Find your nearby"/>
<section class="explore-intro"><div><p class="eyebrow">YOUR NEIGHBORHOOD, MORE POSSIBILITIES</p><h1>{{ headings[form.service][0] }}</h1><p>{{ headings[form.service][1] }}</p></div><div class="service-tabs" aria-label="Service"><button v-for="(label,key) in {spaces:'Spaces',haul:'Vehicles',raketeer:'Rakets'}" :key="key" :aria-pressed="form.service===key" :class="{active:form.service===key}" @click="service(key)">{{ label }}</button></div></section>
<form class="search-toolbar" @submit.prevent="search()">
<label class="wide">Search by title or city<input v-model="form.q" placeholder="Where are you looking?" /></label>
<label>Type<select v-model="form.type"><option value="">All types</option><option v-for="type in types[form.service]" :key="type" :value="type">{{ type.replace(/([A-Z])/g,' $1') }}</option></select></label>
<label>Minimum ₱<input v-model="form.minPrice" type="number" min="0" placeholder="Any" /></label><label>Maximum ₱<input v-model="form.maxPrice" type="number" :min="form.minPrice||0" placeholder="Any" /></label>
<label>Within<select v-model="form.radiusKm"><option v-for="km in [2,5,10,25,50,100,200]" :value="km">{{ km }} km</option></select></label><button class="button primary" :disabled="loading">{{ loading?'Searching…':'Search' }}</button><button type="button" class="button" @click="locate" :disabled="locating">{{ locating?'Locating…':'Use my location' }}</button>
</form><p v-if="locationError" role="alert" class="notice">{{ locationError }}</p>
<div class="results-bar"><p><strong>{{ results.meta.total }}</strong> listings {{ form.lat!==''?'near this area':'to explore' }} <span v-if="loading" role="status"> · Updating…</span></p><button class="text-button" @click="clear">Clear filters</button><button class="button mobile-toggle" @click="mobileView=mobileView==='list'?'map':'list'">{{ mobileView==='list'?'Show map':'Show list' }}</button></div>
<section class="explore-grid" :class="'show-'+mobileView"><div class="results-panel" :aria-busy="loading">
<div v-if="!results.data.length" class="empty-state"><span class="empty-symbol">⌖</span><h2>No matches here yet</h2><p>Try a wider radius, another location, or fewer filters.</p><button class="button" @click="clear">Reset search</button></div>
<div v-else class="cards-grid"><ListingCard v-for="item in results.data" :key="item.id" :listing="item" :selected="selected===item.id" @select="selected=$event" /></div>
<div v-if="results.meta.lastPage>1" class="pagination"><button class="button" :disabled="results.meta.currentPage<=1" @click="search(results.meta.currentPage-1)">Previous</button><span>{{ results.meta.currentPage }} / {{ results.meta.lastPage }}</span><button class="button" :disabled="results.meta.currentPage>=results.meta.lastPage" @click="search(results.meta.currentPage+1)">Next</button></div></div>
<DiscoveryMap :listings="results.data" :center="center" :selected="selected" @select="selected=$event" @search="area" />
</section></Layout></template>
