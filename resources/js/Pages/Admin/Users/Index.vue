<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UserAvatar from '@/Components/Account/UserAvatar.vue';
import RoleStatusBadge from '@/Components/Admin/RoleStatusBadge.vue';
import SortableTh from '@/Components/Admin/SortableTh.vue';
import AdminFilters from '@/Components/Admin/AdminFilters.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { useAdminTable } from '@/composables/useAdminTable';

const props = defineProps({
    users: Object,
    filters: { type: Object, default: () => ({}) },
    sort: String,
    dir: String,
    summary: { type: Object, default: () => ({}) },
});

const keys = ['q', 'role', 'roleStatus', 'listings', 'email'];
const { state, visit, search, sortBy, reset } = useAdminTable('/admin/users', {
    q: props.filters.q || '', role: props.filters.role || '', roleStatus: props.filters.roleStatus || '',
    listings: props.filters.listings || '', email: props.filters.email || '', sort: props.sort, dir: props.dir,
});
const active = computed(() => keys.some(key => state[key] !== ''));
const roles = [['landlord_status', 'spaces'], ['vehicle_owner_status', 'haul'], ['service_provider_status', 'raketeer']];
const countIcons = [
    ['listings_count', 'm3 11 9-8 9 8M5 9v12h14V9M9 21v-8h6v8', 'properties'],
    ['vehicle_listings_count', 'M3 5h12v12H3zM15 9h4l3 4v4h-7M5 17v3h3v-3M17 17v3h3v-3', 'vehicles'],
    ['service_listings_count', 'M3 7h18v14H3zM8 7V3h8v4M3 12l9 4 9-4', 'rakets'],
];
const dateText = value => new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
const listingTotal = user => (user.listings_count || 0) + (user.vehicle_listings_count || 0) + (user.service_listings_count || 0);
</script>

<template>
    <Head title="Users — Admin" />
    <AdminLayout>
        <template #header><h1 class="users-title">Users & listings</h1></template>

        <div class="users-summary">
            <div><strong>{{ (summary.total ?? 0).toLocaleString() }}</strong><span>Accounts</span></div>
            <div><strong>{{ (summary.providers ?? 0).toLocaleString() }}</strong><span>With an approved role</span></div>
            <Link href="/admin/approvals" class="is-attention" :class="{ quiet: !summary.pending }"><strong>{{ (summary.pending ?? 0).toLocaleString() }}</strong><span>Awaiting approval →</span></Link>
        </div>

        <AdminFilters :active="active" :summary="`${users.total.toLocaleString()} ${users.total === 1 ? 'account' : 'accounts'} found`" @reset="reset(keys)">
            <label class="grow">Search<input v-model="state.q" type="search" placeholder="Name or email" maxlength="150" @input="search" /></label>
            <label>Provider role
                <select v-model="state.role" @change="visit()"><option value="">Any role</option><option value="spaces">Property</option><option value="haul">Vehicle</option><option value="raketeer">Raket</option></select>
            </label>
            <label>Role status
                <select v-model="state.roleStatus" @change="visit()"><option value="">Any status</option><option value="approved">Approved</option><option value="pending">Pending</option><option value="rejected">Rejected</option><option value="none">Not applied</option></select>
            </label>
            <label>Listings
                <select v-model="state.listings" @change="visit()"><option value="">Any</option><option value="with">Has listings</option><option value="without">No listings</option></select>
            </label>
            <label>Email
                <select v-model="state.email" @change="visit()"><option value="">Any</option><option value="verified">Verified</option><option value="unverified">Unverified</option></select>
            </label>
        </AdminFilters>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <SortableTh column="name" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">User</SortableTh>
                        <SortableTh column="roles" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Provider roles</SortableTh>
                        <SortableTh column="listings" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Listings</SortableTh>
                        <SortableTh column="joined" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Joined</SortableTh>
                        <th scope="col"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users.data" :key="user.id">
                        <td>
                            <div class="user-identity">
                                <UserAvatar :user="user" />
                                <div>
                                    <strong>{{ user.name }}<span v-if="user.is_admin" class="tag admin">Admin</span></strong>
                                    <span class="email">{{ user.email }}
                                        <svg v-if="user.email_verified_at" class="mail-state ok" viewBox="0 0 24 24" role="img" aria-label="Email verified"><title>Email verified</title><path d="M5 12.5l4.2 4.2L19 7" /></svg>
                                        <svg v-else class="mail-state warn" viewBox="0 0 24 24" role="img" aria-label="Email not verified"><title>Email not verified</title><path d="M12 8v5m0 3.5h.01" /></svg>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td><div class="user-roles"><RoleStatusBadge v-for="[field, service] in roles" :key="field" compact :service="service" :status="user[field] || 'none'" /></div></td>
                        <td>
                            <div class="counts" :title="`${user.listings_count || 0} properties, ${user.vehicle_listings_count || 0} vehicles, ${user.service_listings_count || 0} rakets`">
                                <strong>{{ listingTotal(user) }}</strong>
                                <span v-for="[key, path, label] in countIcons" :key="key" class="count" :class="{ zero: !user[key] }">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="path" /></svg>{{ user[key] || 0 }}<span class="sr-only"> {{ label }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="nowrap">{{ dateText(user.created_at) }}</td>
                        <td class="actions">
                            <Link :href="`/admin/users/${user.id}/listings`" class="icon-action" :aria-label="`View listings for ${user.name}`" :title="`View ${user.name}'s listings`">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></svg>
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!users.data.length"><td colspan="5" class="empty">No users match these filters.</td></tr>
                </tbody>
            </table>
        </div>
        <AdminPagination :paginator="users" label="User pages" />
    </AdminLayout>
</template>

<style scoped>
.users-title { font-size:24px; font-weight:700; }
.users-summary { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin-bottom:16px; }
.users-summary > * { display:flex; flex-direction:column; gap:2px; padding:14px 16px; background:#fff; border:1px solid #dce7e4; border-radius:12px; }
.users-summary strong { font-size:24px; line-height:1.1; letter-spacing:-.02em; }
.users-summary span { font-size:12px; color:#637b76; }
.users-summary .is-attention { border-color:#f2dba0; background:#fffaf0; }
.users-summary .is-attention.quiet { border-color:#dce7e4; background:#fff; }
.users-summary .is-attention span { color:#8a5a00; font-weight:700; }
.users-summary .is-attention.quiet span { color:#637b76; }
.users-table { overflow:auto; border:1px solid #dce7e4; border-radius:12px; background:#fff; }
.users-table table { width:100%; text-align:left; font-size:13px; border-collapse:collapse; }
.users-table th { background:#f8faf9; padding:10px 18px; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#42665e; white-space:nowrap; }
.users-table td { padding:12px 18px; border-top:1px solid #e5edeb; vertical-align:middle; }
.users-table tbody tr:hover { background:#f8fbfa; }
.user-identity { display:flex; align-items:center; gap:12px; min-width:230px; }
.user-identity strong { display:flex; align-items:center; gap:8px; font-weight:700; }
.user-identity .email { display:flex; align-items:center; gap:6px; color:#637b76; font-size:12px; margin-top:2px; overflow-wrap:anywhere; }
.mail-state { width:14px; height:14px; flex-shrink:0; fill:none; stroke-width:3; stroke-linecap:round; stroke-linejoin:round; }
.mail-state.ok { stroke:#12744a; }
.mail-state.warn { stroke:#b45f12; }
.tag { display:inline-block; padding:2px 7px; border-radius:5px; font-size:10px; font-weight:700; background:#eff3f1; color:#586e6c; }
.tag.admin { background:#e8e4fb; color:#4b3fa8; }
.user-roles { display:flex; align-items:center; gap:12px; padding-right:6px; }
.counts { display:flex; align-items:center; gap:12px; }
.counts strong { min-width:18px; font-size:15px; }
.count { display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#42665e; font-variant-numeric:tabular-nums; }
.count svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.count.zero { color:#b0bfbc; }
.nowrap { white-space:nowrap; color:#586e6c; }
.actions { width:1%; text-align:right; }
.icon-action { display:inline-grid; place-items:center; width:40px; height:40px; border:1px solid #cbded8; border-radius:10px; color:#12675f; background:#fff; }
.icon-action:hover { background:#e3f6f4; border-color:#9fd0c6; }
.icon-action svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
.icon-action:focus-visible { outline:2px solid #137671; outline-offset:3px; }
.empty { text-align:center; padding:40px !important; color:#637b76; }
@media (max-width:700px) { .users-summary { grid-template-columns:1fr; } }
</style>
