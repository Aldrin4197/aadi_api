<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
import AdminListingPreview from './AdminListingPreview.vue';
import SortableTh from './SortableTh.vue';
import AdminFilters from './AdminFilters.vue';
import AdminPagination from './AdminPagination.vue';
import { useAdminTable } from '@/composables/useAdminTable';

const props = defineProps({
    listings: Object,
    service: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
    sort: String,
    dir: String,
    options: { type: Object, default: () => ({ statuses: [], types: [] }) },
    totals: { type: Object, default: () => ({}) },
});

const paths = { spaces: '/admin/listings', haul: '/admin/vehicles', raketeer: '/admin/services' };
const keys = ['q', 'status', 'type', 'minPrice', 'maxPrice', 'provider'];
const { state, visit, search, sortBy, reset } = useAdminTable(paths[props.service], {
    q: props.filters.q || '', status: props.filters.status || '', type: props.filters.type || '',
    minPrice: props.filters.minPrice ?? '', maxPrice: props.filters.maxPrice ?? '', provider: props.filters.provider || '',
    sort: props.sort, dir: props.dir,
});
const active = computed(() => keys.some(key => state[key] !== ''));
const label = value => String(value).replace(/([A-Z])/g, ' $1').replace(/^./, c => c.toUpperCase());
const live = new Set(['active', 'available']);

const selected = ref(null);
watch(() => props.listings, () => (selected.value = null));
function rowClick(event, item) { if (event.target.closest('a,button,input,select,textarea')) return; selected.value = item; }
const money = item => new Intl.NumberFormat('en-PH', { style: 'currency', currency: item.currency || 'PHP' }).format(item.price);
const dateText = value => new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
</script>

<template>
    <p class="table-hint">{{ (totals.live ?? 0).toLocaleString() }} live of {{ (totals.all ?? 0).toLocaleString() }} listings. Select a listing to inspect its photos, details, owner and status.</p>

    <AdminFilters :active="active" :summary="`${listings.total.toLocaleString()} ${listings.total === 1 ? 'listing' : 'listings'} found`" @reset="reset(keys)">
        <label class="grow">Search<input v-model="state.q" type="search" placeholder="Title, location, owner name or email" maxlength="150" @input="search" /></label>
        <label>Status
            <select v-model="state.status" @change="visit()"><option value="">Any status</option><option v-for="status in options.statuses" :key="status" :value="status">{{ label(status) }}</option></select>
        </label>
        <label>Type
            <select v-model="state.type" @change="visit()"><option value="">Any type</option><option v-for="type in options.types" :key="type" :value="type">{{ label(type) }}</option></select>
        </label>
        <label>Owner role
            <select v-model="state.provider" @change="visit()"><option value="">Any</option><option value="approved">Approved provider</option><option value="unapproved">Not approved</option></select>
        </label>
        <label class="narrow">Min price<input v-model="state.minPrice" type="number" min="0" inputmode="decimal" placeholder="₱0" @input="search" /></label>
        <label class="narrow">Max price<input v-model="state.maxPrice" type="number" min="0" inputmode="decimal" placeholder="Any" @input="search" /></label>
    </AdminFilters>

    <div class="admin-listings-table">
        <table>
            <thead>
                <tr>
                    <SortableTh column="title" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Listing</SortableTh>
                    <SortableTh column="type" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Type</SortableTh>
                    <SortableTh column="price" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Price</SortableTh>
                    <SortableTh column="owner" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Owner / provider</SortableTh>
                    <SortableTh column="status" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Status</SortableTh>
                    <SortableTh column="created" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Listed</SortableTh>
                    <th scope="col"><span class="sr-only">Preview</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in listings.data" :key="item.id" @click="rowClick($event, item)">
                    <td>
                        <div class="table-listing">
                            <button type="button" class="table-photo" :aria-label="`Preview photos for ${item.title}`" aria-haspopup="dialog" @click="selected = item"><ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" :type="item.type" /></button>
                            <div>
                                <button type="button" class="table-title" aria-haspopup="dialog" @click="selected = item">{{ item.title }}</button>
                                <small>{{ item.location.city || item.location.address || 'Location not provided' }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="cap">{{ label(item.type) }}</td>
                    <td class="table-price">{{ money(item) }}<small>/ {{ item.priceUnit }}</small></td>
                    <td>
                        <Link v-if="item.adminOwner.id" :href="`/admin/users/${item.adminOwner.id}/listings?service=${item.service}`">{{ item.adminOwner.name }}</Link><span v-else>{{ item.adminOwner.name }}</span>
                        <small v-if="item.adminOwner.email">{{ item.adminOwner.email }}</small>
                        <small :class="item.providerApproved ? 'ok' : 'warn'">{{ item.providerApproved ? 'Approved provider' : 'Role not approved' }}</small>
                    </td>
                    <td><span class="table-status" :class="{ 'is-active': live.has(item.sourceStatus) }">{{ item.sourceStatus }}</span></td>
                    <td class="nowrap">{{ dateText(item.createdAt) }}</td>
                    <td><button type="button" class="table-inspect" :aria-label="`Inspect ${item.title}`" aria-haspopup="dialog" @click="selected = item">Inspect ↗</button></td>
                </tr>
                <tr v-if="!listings.data.length"><td colspan="7" class="table-empty">No listings match these filters.</td></tr>
            </tbody>
        </table>
    </div>
    <AdminPagination :paginator="listings" label="Listing pages" />
    <AdminListingPreview v-if="selected" :item="selected" :owner="selected.adminOwner" @close="selected = null" />
</template>

<style scoped>
.table-hint { font-size:13px; color:#637b76; margin-bottom:16px; }
.admin-listings-table { overflow-x:auto; border:1px solid #dce7e4; border-radius:12px; background:white; }
.admin-listings-table table { width:100%; text-align:left; font-size:13px; border-collapse:collapse; }
.admin-listings-table th { padding:10px 18px; background:#f7faf8; font-size:11px; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; color:#42665e; }
.admin-listings-table td { padding:16px 18px; border-top:1px solid #e5edeb; vertical-align:middle; }
.admin-listings-table tbody tr { cursor:pointer; }
.admin-listings-table tbody tr:hover { background:#f4faf7; }
.table-listing { display:flex; align-items:center; gap:12px; min-width:260px; max-width:420px; }
.table-photo { width:72px; height:58px; flex-shrink:0; border-radius:8px; overflow:hidden; }
.table-title { font-weight:700; text-align:left; line-height:1.5; color:#12675f; overflow-wrap:anywhere; }
.admin-listings-table small { display:block; color:#637b76; font-size:11px; margin-top:5px; }
.admin-listings-table small.ok { color:#12744a; font-weight:700; }
.admin-listings-table small.warn { color:#b45f12; font-weight:700; }
.cap { text-transform:capitalize; }
.nowrap { white-space:nowrap; color:#586e6c; }
.table-price { white-space:nowrap; font-variant-numeric:tabular-nums; }
.table-status { display:inline-block; background:#eff3f1; padding:5px 8px; border-radius:6px; text-transform:capitalize; font-size:11px; }
.table-status.is-active { background:#e0f4eb; color:#12675f; }
.table-inspect { border:1px solid #b7d8d0; border-radius:8px; min-height:44px; padding:10px 12px; font-weight:600; color:#12675f; white-space:nowrap; }
.table-empty { text-align:center; padding:40px !important; color:#637b76; }
.admin-listings-table :is(a, button):focus-visible { outline:2px solid #137671; outline-offset:3px; }
</style>
