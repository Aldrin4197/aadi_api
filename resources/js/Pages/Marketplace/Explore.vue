<script setup>
import ListingPreviewLink from '@/Components/Marketplace/ListingPreviewLink.vue';
import { computed, ref, watch, nextTick } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Layout from '@/Layouts/MarketplaceLayout.vue';
import DiscoveryMap from '@/Components/Marketplace/DiscoveryMap.vue';
import HomeLanding from '@/Components/Marketplace/HomeLanding.vue';
import HomeHero from '@/Components/Marketplace/HomeHero.vue';
import SearchIcon from '@/Components/Marketplace/SearchIcon.vue';
import CityPicker from '@/Components/Marketplace/CityPicker.vue';
import CompareListings from '@/Components/Marketplace/CompareListings.vue';
import ListingViewButton from '@/Components/Marketplace/ListingViewButton.vue';
import ListingCarousel from '@/Components/Marketplace/ListingCarousel.vue';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
import { distanceKm, formatPrice, compareKey } from '@/Components/Marketplace/comparison.mjs';
const props=defineProps({results:Object,filters:Object,types:Object,landing:{type:Object,default:()=>({})}});
const form=ref({service:props.filters.service||'spaces',locationName:props.filters.locationName||'',q:props.filters.q||'',type:props.filters.type||'',minPrice:props.filters.minPrice??'',maxPrice:props.filters.maxPrice??'',radiusKm:props.filters.radiusKm||10,lat:props.filters.lat??'',lng:props.filters.lng??''});
const page = usePage();
const hasSearched = ref(page.url.includes('?'));
const expanded = ref(!hasSearched.value);
const compactTrigger = ref(null);
const serviceLabels = {spaces:'Spaces',haul:'Vehicles',raketeer:'Rakets'};
async function editSearch() { expanded.value=true; await nextTick(); document.getElementById('explorer-query')?.focus({preventScroll:true}); }
async function collapseSearch() { expanded.value=false; filtersOpen.value=false; await nextTick(); compactTrigger.value?.focus({preventScroll:true}); }
const isHome = computed(() => page.url.split(/[?#]/)[0] === '/');
function exploreCategory(value) {
  form.value.service = value;
  form.value.type = '';
  search(1, () => {
    document.getElementById('explore-map')?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    compactTrigger.value?.focus({ preventScroll: true });
  });
}
const selected=ref(null), loading=ref(false), locationError=ref(''), locating=ref(false), filtersOpen=ref(false), cardsOpen=ref(true), comparisons=ref([]), compareDialog=ref(null), compareError=ref('');
const center=computed(()=>props.filters.lat!=null&&props.filters.lng!=null?[Number(props.filters.lat),Number(props.filters.lng)]:props.results.data.find(l=>l.location.lat!=null&&l.location.lng!=null)?[props.results.data.find(l=>l.location.lat!=null&&l.location.lng!=null).location.lat,props.results.data.find(l=>l.location.lat!=null&&l.location.lng!=null).location.lng]:[12.0,124.75]);
const headings={spaces:['A place to call yours.','Rooms, homes, and spaces around you.'],haul:['Your next ride is nearby.','Find the right vehicle for wherever life takes you.'],raketeer:['Good help, close to home.','Find local skills and services for your next project.']};
function search(page=1,afterSearch=null){locationError.value='';loading.value=true;const query=Object.fromEntries(Object.entries({...form.value,page}).filter(([,v])=>v!==''&&v!==null));router.get(isHome.value ? '/' : '/explore',query,{preserveScroll:true,preserveState:true,onSuccess:async()=>{hasSearched.value=true;await collapseSearch();afterSearch?.();},onFinish:()=>loading.value=false,onError:()=>locationError.value='Search could not be completed. Check your filters and try again.'});}
function selectListing(id){selected.value=id;cardsOpen.value=true;}
const isCompared=item=>comparisons.value.some(l=>compareKey(l)===compareKey(item));
function toggleCompare(item){const key=compareKey(item);compareError.value='';if(comparisons.value.some(l=>compareKey(l)===key)) comparisons.value=comparisons.value.filter(l=>compareKey(l)!==key);else if(comparisons.value.length<3) comparisons.value.push(item);else compareError.value='Choose up to three listings. Remove one to add another.';}
const distanceLabel=item=>{const km=distanceKm(props.filters.lat,props.filters.lng,item.location.lat,item.location.lng);return km===null?'':`${km.toFixed(1)} km away`;};
const missingCoordinates=computed(()=>props.results.data.filter(l=>l.location.lat==null||l.location.lng==null).length);
watch(()=>props.results,()=>{selected.value=null;});
watch(()=>props.filters,filters=>{form.value={service:filters.service||'spaces',locationName:filters.locationName||'',q:filters.q||'',type:filters.type||'',minPrice:filters.minPrice??'',maxPrice:filters.maxPrice??'',radiusKm:filters.radiusKm||10,lat:filters.lat??'',lng:filters.lng??''};});
function service(value){form.value.service=value;form.value.type='';search();}
function area(coords,name='Map search center'){locationError.value='';form.value.locationName=name;[form.value.lat,form.value.lng]=coords;search();}
function chooseCity(city){area([city.lat,city.lng],city.label);}
function clearLocation(){form.value.lat='';form.value.lng='';form.value.locationName='';search();}
function locate(){locationError.value='';if(!navigator.geolocation){locationError.value='Location is unavailable. Search a city or move the map.';return;}locating.value=true;navigator.geolocation.getCurrentPosition(p=>{locating.value=false;area([p.coords.latitude,p.coords.longitude],'Your current location');},()=>{locating.value=false;locationError.value='Location access failed. Search a city or move the map instead.';},{timeout:10000});}
function clear(){form.value={service:form.value.service,locationName:'',q:'',type:'',minPrice:'',maxPrice:'',radiusKm:10,lat:'',lng:''};search();}
</script>
<template><Layout><Head title="Find properties, vehicles & rakets nearby"><meta name="description" content="Find rental spaces, vehicles, and local services near you with Aadi. Browse the map, compare listed rates, and apply to offer your own space, vehicle, or skills." /></Head>
<main id="main-content">
<HomeHero v-if="isHome" />
<section id="explore-map" :class="{'has-comparison':comparisons.length}" class="map-explorer" aria-label="Explore nearby listings" tabindex="-1">
<DiscoveryMap :scroll-wheel-zoom="!isHome" :listings="results.data" :center="center" :selected="selected" :fit-listings="filters.lat==null" immersive :preview="false" @select="selectListing" @search="area" />
<section class="explorer-controls" :class="{'is-initial':!hasSearched,'is-compact':!expanded}" aria-label="Search and filter">
<div v-if="!expanded" class="compact-search-bar">
<button ref="compactTrigger" type="button" class="compact-search-summary" aria-label="Edit search and filters" aria-expanded="false" @click="editSearch"><SearchIcon :name="filters.service || 'spaces'"/><span><strong>{{ serviceLabels[filters.service || 'spaces'] }}</strong><small>{{ [filters.q, filters.locationName || 'All locations', filters.type, filters.minPrice != null || filters.maxPrice != null ? 'Price filtered' : ''].filter(Boolean).join(' · ') }}</small></span><SearchIcon name="filters"/><span class="compact-edit-label">Edit</span></button>
<button type="button" class="compact-icon-button" title="Use my location" aria-label="Use my location" :disabled="locating||loading" @click="locate"><SearchIcon name="location"/></button>
<button type="button" class="compact-icon-button" :title="cardsOpen?'Hide listings':'Show listings'" :aria-label="cardsOpen?'Hide listings':'Show listings'" :aria-expanded="cardsOpen" aria-controls="map-results" @click="cardsOpen=!cardsOpen"><SearchIcon name="list"/></button>
</div>
<div v-show="expanded" @keydown.esc="hasSearched && collapseSearch()">
<button v-if="hasSearched" type="button" class="search-collapse" aria-label="Collapse search" @click="collapseSearch"><SearchIcon name="close"/></button>
<div class="explorer-heading"><component :is="isHome ? 'h2' : 'h1'" class="explorer-title">Find your nearby.</component><span>{{ headings[form.service][1] }}</span></div>
<div class="service-tabs" aria-label="Service"><button v-for="(label,key) in {spaces:'Spaces',haul:'Vehicles',raketeer:'Rakets'}" :key="key" :aria-pressed="form.service===key" :class="{active:form.service===key}" :disabled="loading" @click="service(key)"><SearchIcon :name="key"/>{{ label }}</button></div>
<form @submit.prevent="search()"><div class="explorer-search"><label><span class="sr-only">Search listings</span><input id="explorer-query" v-model="form.q" placeholder="Search rooms, vehicles, or services" maxlength="150" /></label><CityPicker :selected="filters.locationName" :disabled="loading" @select="chooseCity" @clear="clearLocation" /><button class="button primary" :disabled="loading"><SearchIcon name="search"/>{{ loading?'Searching…':'Search' }}</button><button type="button" class="button" :aria-expanded="filtersOpen" aria-controls="explorer-filters" @click="filtersOpen=!filtersOpen"><SearchIcon name="filters"/>Filters</button></div>
<div v-show="filtersOpen" id="explorer-filters" class="explorer-filters"><label>Type<select v-model="form.type"><option value="">All types</option><option v-for="type in types[form.service]" :key="type" :value="type">{{ type.replace(/([A-Z])/g,' $1') }}</option></select></label><label>Minimum rate ₱<input v-model="form.minPrice" type="number" min="0" placeholder="Any" /></label><label>Maximum rate ₱<input v-model="form.maxPrice" type="number" :min="form.minPrice||0" placeholder="Any" /></label><label>Search radius<select v-model="form.radiusKm"><option v-for="km in [2,5,10,25,50,100,200]" :key="km" :value="km">{{ km }} km</option></select></label><p>Radius applies around your location or the map search center. Rates are per month, day, hour, or job.</p><button class="button primary" :disabled="loading">Apply filters</button><button type="button" class="text-button" @click="clear">Reset</button></div></form>
<div class="explorer-tools"><button class="text-button" :disabled="locating||loading" @click="locate"><SearchIcon name="location"/>{{ locating?'Finding you…':'Use my location' }}</button><button class="text-button" @click="clear" :disabled="loading"><SearchIcon name="reset"/>Clear search</button><button class="text-button" :aria-expanded="cardsOpen" aria-controls="map-results" @click="cardsOpen=!cardsOpen"><SearchIcon name="list"/>{{ cardsOpen?'Hide listings':'Show listings' }}</button></div>
</div>
<p v-if="expanded && filters.lat!=null" class="explorer-area" role="status">Within {{ filters.radiusKm || 10 }} km of {{ filters.locationName || 'map search center' }}</p>
<p v-if="locationError" role="alert" class="explorer-message">{{ locationError }}</p>
<p v-if="expanded && !page.props.auth?.user" class="explorer-hint">Browse, preview, and compare freely. Log in for full listing details.</p>
</section>
<section v-show="cardsOpen" id="map-results" class="map-results" :aria-busy="loading" aria-label="Matching listings">
<div class="map-results-heading"><span role="status"><strong>{{ results.meta.total }}</strong> matches · showing {{ results.data.length }} on this page<span v-if="form.lat!==''"> · nearest first</span><span v-if="missingCoordinates"> · {{ missingCoordinates }} without a map location</span></span><div v-if="results.meta.lastPage>1" class="map-pages"><button :disabled="loading||results.meta.currentPage<=1" @click="search(results.meta.currentPage-1)">← Previous</button><span>{{ results.meta.currentPage }}/{{ results.meta.lastPage }}</span><button :disabled="loading||results.meta.currentPage>=results.meta.lastPage" @click="search(results.meta.currentPage+1)">Next →</button></div></div>
<div v-if="!results.data.length" class="map-empty"><h2>No matches nearby yet</h2><p>{{ filters.locationName ? 'No listings match your filters near '+filters.locationName+'. Try a wider radius or fewer filters.' : 'Try another city or fewer filters.' }}</p><button class="button" @click="clear">Reset search</button></div>
<ListingCarousel v-else :selected-id="selected" :reset-key="results.data.map(item=>item.service+item.id).join(',')" :visible="cardsOpen"><article v-for="item in results.data" :key="item.id" :id="'map-card-'+item.id" class="map-listing" :class="{selected:selected===item.id}">
<button class="map-listing-photo" :aria-label="'Locate '+item.title+' on map'" :disabled="item.location.lat==null||item.location.lng==null" @click="selectListing(item.id)"><ListingImage :src="item.photos[0]" :alt="item.title" :service="item.service" :type="item.type" /></button>
<div class="map-listing-body"><h2><ListingPreviewLink :item="item">{{ item.title }}</ListingPreviewLink></h2><p>{{ item.location.city||item.location.address||'Location not provided' }}<span v-if="distanceLabel(item)"> · {{ distanceLabel(item) }}</span></p><strong class="map-rate">{{ formatPrice(item.price) }} <small>/ {{ item.priceUnit }}</small></strong><div class="map-card-actions"><button class="text-button" :disabled="item.location.lat==null||item.location.lng==null" @click="selectListing(item.id)">On map</button><label class="compare-checkbox" :title="!isCompared(item) && comparisons.length>=3 ? 'Remove a selected listing to choose another (maximum 3).' : 'Select for comparison'"><input type="checkbox" :checked="isCompared(item)" :disabled="!isCompared(item) && comparisons.length>=3" :aria-label="'Select '+item.title+' for comparison'" @change="toggleCompare(item)" /><span>{{ isCompared(item) ? 'Selected' : 'Select' }}</span></label><ListingViewButton :item="item"/></div></div></article></ListingCarousel>
</section>
<div v-if="comparisons.length" class="map-compare-bar" aria-label="Selected listings for comparison">
  <div class="compare-thumbnails" aria-hidden="true"><ListingImage v-for="item in comparisons" :key="compareKey(item)" :src="item.photos?.[0]" alt="" :service="item.service" :type="item.type" /></div>
  <div class="compare-count"><strong>{{ comparisons.length }} {{ comparisons.length === 1 ? 'find' : 'finds' }} selected</strong><small>Choose up to 3 listings</small></div>
  <button class="compare-clear" @click="comparisons=[];compareError=''">Clear</button><button class="button primary" :disabled="comparisons.length<2" @click="compareDialog.open()">Compare <span aria-hidden="true">→</span></button>
  <span v-if="compareError" role="status" class="compare-limit">{{ compareError }}</span>
</div>
<CompareListings ref="compareDialog" :items="comparisons" :distance-label="distanceLabel" @remove="toggleCompare" />
</section>
<template v-if="isHome">
  <div class="explorer-scroll-cue"><a href="#featured-listings">Find more than a listing <span aria-hidden="true">↓</span></a><p>Scroll to discover Aadi. Use the map’s + / − buttons to zoom.</p></div>
  <HomeLanding :content="landing" :loading="loading" @explore="exploreCategory" />
</template>
</main>
<template v-if="isHome" #footer>
  <footer class="landing-footer">
    <div><Link href="/" aria-label="Aadi home"><img src="/images/logos/Aadi_Color.png" alt="Aadi" width="92" height="42" loading="lazy" /></Link><p>Good things. Nearby.</p></div>
    <nav aria-label="Footer navigation"><a href="#explore-map">Explore the map</a><a href="#featured-listings">Featured finds</a><a v-if="landing?.reviews?.length" href="#community-reviews">Reviews</a><a href="#download-aadi">Get the app</a><Link href="/become-provider">Become a provider</Link><Link v-if="!page.props.auth?.user" href="/register">Create an account</Link><Link v-else href="/profile">Your account</Link></nav>
  </footer>
</template>
</Layout></template>
