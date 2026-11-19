<script setup>
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import { computed, nextTick, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Layouts/ProviderLayout.vue';
import DiscoveryMap from '@/Components/Marketplace/DiscoveryMap.vue';
const props = defineProps({ listings: Array, statuses: Object });
const services = { spaces: { label: 'Properties', role: 'Landlord', singular: 'property' }, haul: { label: 'Vehicles', role: 'Vehicle owner', singular: 'vehicle' }, raketeer: { label: 'Services & rakets', role: 'Freelance service provider', singular: 'service' } };
const active = ref(Object.keys(services).find(key => props.statuses[key] === 'approved') || props.listings[0]?.service || 'spaces');
const query = ref(''), status = ref('all'), sort = ref('newest'), mapVisible = ref(true), mobileView = ref('list'), selected = ref(null);
const deleting = ref(null), busy = ref(false), dialog = ref(null), deleteError = ref('');
const availabilityBusy = ref(null), availabilityError = ref('');
const serviceListings = computed(() => props.listings.filter(item => item.service === active.value));
const filtered = computed(() => serviceListings.value.filter(item => (status.value === 'all' || item.status === status.value) && `${item.title} ${item.location.address || ''} ${item.location.city || ''}`.toLowerCase().includes(query.value.trim().toLowerCase())).sort((a,b) => sort.value === 'price' ? a.price-b.price : sort.value === 'title' ? a.title.localeCompare(b.title) : new Date(b.createdAt)-new Date(a.createdAt)));
const mappable = computed(() => filtered.value.filter(item => item.location.lat != null && item.location.lng != null));
const center = computed(() => { const item = mappable.value.find(item => item.id === selected.value) || mappable.value[0]; return item ? [item.location.lat,item.location.lng] : [12.0,124.75]; });
const currentStatus = computed(() => props.statuses[active.value] || 'none');
const statusLabel = value => ({active:'Published',draft:'Draft',reserved:active.value==='haul'?'Maintenance':active.value==='raketeer'?'Busy':'Reserved',rented:'Rented',archived:'Archived'}[value] || value);
const money = value => new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP',maximumFractionDigits:0}).format(value);
function changeService(key) { active.value=key; status.value='all'; selected.value=null; query.value=''; }
function select(id) { selected.value=id; nextTick(() => document.getElementById('offer-'+id)?.scrollIntoView({block:'nearest',behavior:'instant'})); }
watch(filtered, items => { if (!items.some(item => item.id===selected.value)) selected.value=null; });
watch(deleting, async item => { await nextTick(); if(item) dialog.value?.showModal(); else dialog.value?.close(); });
function remove() { busy.value=true; deleteError.value=''; router.delete(`/my-listings/${deleting.value.service}/${deleting.value.id}`, {preserveScroll:true,onSuccess:()=>deleting.value=null,onError:()=>deleteError.value='The listing could not be deleted. Please try again.',onFinish:()=>busy.value=false}); }
function setAvailability(item) {
  availabilityBusy.value = `${item.service}:${item.id}`;
  availabilityError.value = '';
  router.patch(`/my-listings/${item.service}/${item.id}/availability`, { paused: !item.availabilityPaused }, {
    preserveScroll: true,
    onError: () => { availabilityError.value = 'Availability could not be updated. Please try again.'; },
    onFinish: () => { availabilityBusy.value = null; },
  });
}
</script>

<template>
  <Layout><Head title="My listings · Provider workspace" />
    <main id="provider-main" class="workspace-main">
      <AccountPageHeader title="My listings" description="Manage what you offer, how it appears, and where people find it."><Link v-if="currentStatus==='approved'" :href="`/my-listings/${active}/new`" class="button primary">+ Add {{ services[active].singular }}</Link><Link v-else :href="`/provider-applications/${active}`" class="button primary">{{ currentStatus==='pending'?'View application':'Apply for this role' }}</Link></AccountPageHeader>
      <div class="workspace-services workspace-tabs" aria-label="Listing category"><button v-for="(service,key) in services" :key="key" :aria-pressed="active===key" :class="{active:active===key}" @click="changeService(key)">{{ service.label }}<span>{{ listings.filter(item=>item.service===key).length }}</span></button><Link href="/become-provider" class="roles-link">Manage provider roles ↗</Link></div>
      <div v-if="currentStatus!=='approved'" class="role-notice"><strong>{{ currentStatus==='pending'?'Your application is under review.':currentStatus==='rejected'?'Your application needs an update.':`Become a ${services[active].role.toLowerCase()}.` }}</strong><span>{{ currentStatus==='pending'?'You can keep exploring Aadi while we review this role.':'Apply for this role to create and manage these listings. Your existing provider roles stay active.' }}</span><Link :href="`/provider-applications/${active}`">{{ currentStatus==='pending'?'Check status':'Complete application' }} →</Link></div>
      <form class="workspace-filters" @submit.prevent><label class="listing-search">Search your listings<input v-model="query" type="search" placeholder="Title, address, or city" /></label><label>Status<select v-model="status"><option value="all">All statuses</option><option v-for="value in [...new Set(serviceListings.map(item=>item.status))]" :key="value" :value="value">{{ statusLabel(value) }}</option></select></label><label>Sort by<select v-model="sort"><option value="newest">Newest first</option><option value="title">Listing name</option><option value="price">Price: low to high</option></select></label><button type="button" class="button desktop-map-toggle" :aria-pressed="mapVisible" @click="mapVisible=!mapVisible">{{ mapVisible?'Hide map':'Show map' }}</button><button type="button" class="button mobile-map-toggle" @click="mobileView=mobileView==='list'?'map':'list'">{{ mobileView==='list'?'Show map':'Show list' }}</button></form>
      <div class="workspace-results-heading"><p><strong>{{ filtered.length }}</strong> {{ filtered.length===1?'listing':'listings' }}<span v-if="query || status!=='all'"> matching your filters</span></p><span v-if="mappable.length!==filtered.length">{{ filtered.length-mappable.length }} without a map location</span><span v-else class="muted">Only published listings appear in public search</span></div>
      <p class="muted">Detail views count each visitor at most once per 30 minutes, excluding your own and admin views. Saves show the current total. Vehicle and service view tracking starts with this feature.</p>
      <p v-if="Object.values(statuses).includes('approved')"><Link href="/provider/records" class="text-button">Manage customers, rentals and jobs →</Link></p>
      <p v-if="availabilityError" class="notice error" role="alert">{{ availabilityError }}</p>
      <div class="workspace-results" :class="{'list-only':!mapVisible,'mobile-map':mobileView==='map'}">
        <section class="provider-list" aria-label="Your listings">
          <article v-for="item in filtered" :id="'offer-'+item.id" :key="item.id" class="provider-listing" :class="{selected:selected===item.id}" @focusin="selected=item.id">
            <Link :href="`/offers/${item.service}/${item.id}`" class="provider-listing-photo"><img v-if="item.photos[0]" :src="item.photos[0]" :alt="item.title" loading="lazy"/><span v-else>{{ services[active].singular }}<small>No photo added</small></span></Link>
            <div class="provider-listing-info"><div class="listing-meta"><span class="availability-tag" :class="item.status">{{ statusLabel(item.status) }}</span><span v-if="item.availabilityPaused" class="availability-tag">{{ item.service === 'spaces' ? 'Occupied / unavailable' : 'Unavailable' }}</span><span>{{ item.type?.replace(/([A-Z])/g,' $1') }}</span></div><h2><Link :href="`/offers/${item.service}/${item.id}`">{{ item.title }}</Link></h2><p class="listing-address">{{ item.location.address || item.location.city || 'Location not set' }}</p><button v-if="item.location.lat!=null && item.location.lng!=null" class="text-button" @click="selected=item.id;mapVisible=true;mobileView='map'">Show on map</button><p v-if="item.analytics" class="listing-analytics">{{ item.analytics.views7Days }} detail views in the last 7 days · {{ item.analytics.currentSaves }} current saves</p><p v-else class="listing-analytics">Views and saves are an Aadi Plus perk.</p><p class="provider-price">{{ money(item.price) }} <span>/ {{ item.priceUnit }}</span></p><div class="provider-listing-actions"><button v-if="currentStatus==='approved' && item.status==='active'" type="button" class="button" :disabled="availabilityBusy !== null" @click="setAvailability(item)">{{ item.availabilityPaused ? 'Mark available' : item.service === 'spaces' ? 'Mark occupied / unavailable' : 'Make unavailable' }}</button><Link v-if="currentStatus==='approved'" :href="`/my-listings/${item.service}/${item.id}/edit`" class="button">Manage listing</Link><Link v-if="currentStatus==='approved'" :href="`/provider/records?service=${item.service}&listing=${item.id}`" class="text-button">Customers & payments</Link><Link v-if="currentStatus!=='approved'" :href="`/provider-applications/${active}`" class="button">Complete application</Link><Link :href="`/offers/${item.service}/${item.id}`" class="text-button">Preview</Link><button class="text-button danger" @click="deleting=item">Delete</button></div></div>
          </article>
          <div v-if="!filtered.length" class="workspace-empty"><p class="workspace-kicker">{{ serviceListings.length?'Try another search':'Your next listing starts here' }}</p><h2>{{ serviceListings.length?'No matching listings.':`No ${services[active].label.toLowerCase()} listed yet.` }}</h2><p>{{ serviceListings.length?'Search a different title or clear your status filter.':currentStatus==='approved'?'Add photos, set your price, and choose the location people will see on the map.':'Complete your provider application first. Once approved, you can add your first listing here.' }}</p><button v-if="serviceListings.length" class="button" @click="query='';status='all'">Clear filters</button><Link v-else-if="currentStatus==='approved'" :href="`/my-listings/${active}/new`" class="button primary">Add {{ services[active].singular }}</Link></div>
        </section>
        <aside v-if="mapVisible || mobileView==='map'" class="provider-map-panel" aria-label="Your listing locations"><div class="provider-map-title"><strong>Your listings on the map</strong><span>{{ mappable.length }} located</span></div><DiscoveryMap :listings="mappable" :center="center" :selected="selected" :searchable="false" fit-listings @select="select"/><p class="provider-map-note">Select a price marker to find its listing. Private drafts are visible here only to you.</p></aside>
      </div>
    </main>
    <dialog ref="dialog" class="confirm-dialog" @cancel.prevent="!busy && (deleting=null)"><h2>Delete this listing?</h2><p>{{ deleting?.title }} will be removed. This cannot be undone here.</p><p v-if="deleteError" role="alert" class="error">{{ deleteError }}</p><div class="actions"><button class="button" :disabled="busy" @click="deleting=null">Keep listing</button><button class="button danger" :disabled="busy" @click="remove">{{ busy?'Deleting…':'Delete listing' }}</button></div></dialog>
  </Layout>
</template>
