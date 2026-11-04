<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Review from '@/Components/Admin/ProviderApplicationReview.vue';
import RoleStatusBadge from '@/Components/Admin/RoleStatusBadge.vue';
import SortableTh from '@/Components/Admin/SortableTh.vue';
import AdminFilters from '@/Components/Admin/AdminFilters.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { useAdminTable } from '@/composables/useAdminTable';

const props = defineProps({
    applications: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    sort: String,
    dir: String,
    requirements: { type: Object, default: () => ({}) },
});

const { state, visit, search, sortBy, reset } = useAdminTable('/admin/approvals', {
    status: props.filters.status || 'pending', service: props.filters.service || '', q: props.filters.q || '', sort: props.sort, dir: props.dir,
}, { defaults: { status: 'pending' } });
const active = ref(null);
const titles = { spaces: 'Aadi Properties', haul: 'Aadi UV', raketeer: 'Aadi Raket' };
const tabs = [['pending', 'Pending'], ['approved', 'Approved'], ['rejected', 'Rejected'], ['all', 'All']];
const filtering = computed(() => state.service !== '' || state.q !== '');
function setStatus(status) {
    if (state.status === status) return;
    state.status = status;
    state.sort = status === 'pending' ? 'submitted' : 'decided';
    state.dir = status === 'pending' ? 'asc' : 'desc';
    visit();
}
function clear() { state.service = ''; state.q = ''; visit(); }
const date = value => (value ? new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—');
const waiting = value => {
    if (!value) return '';
    const days = Math.floor((Date.now() - new Date(value).getTime()) / 86400000);
    return days < 1 ? 'today' : `${days}d ago`;
};
const reviewLabel = item => (item.status === 'pending' ? 'Review' : 'View');
const emptyText = computed(() => (filtering.value || state.status !== 'pending'
    ? 'No applications match these filters.'
    : 'All caught up. New provider applications will appear here for review.'));
</script>

<template>
    <Head title="Role approvals — Admin" />
    <AdminLayout>
        <template #header><div><h1 class="page-title">Role approvals</h1><p class="page-sub">Review provider applications and their private documents.</p></div></template>

        <div class="tabs" role="tablist" aria-label="Application status">
            <button v-for="[key, label] in tabs" :key="key" type="button" role="tab" :aria-selected="state.status === key" :class="{ attention: key === 'pending' && counts.pending }" @click="setStatus(key)">
                {{ label }}<span>{{ counts[key] ?? 0 }}</span>
            </button>
        </div>

        <AdminFilters :active="filtering" :summary="`${applications.total} ${applications.total === 1 ? 'application' : 'applications'}`" @reset="clear">
            <label class="grow">Search<input v-model="state.q" type="search" placeholder="Applicant name or email" maxlength="150" @input="search" /></label>
            <label>Provider role
                <select v-model="state.service" @change="visit()"><option value="">All roles</option><option v-for="(title, key) in titles" :key="key" :value="key">{{ title }}</option></select>
            </label>
        </AdminFilters>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <SortableTh column="name" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Applicant</SortableTh>
                        <SortableTh column="service" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Role &amp; status</SortableTh>
                        <SortableTh column="documents" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Documents</SortableTh>
                        <SortableTh column="submitted" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Submitted</SortableTh>
                        <SortableTh v-if="state.status !== 'pending'" column="decided" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Decided</SortableTh>
                        <th scope="col"><span class="sr-only">Review</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in applications.data" :key="item.userId + item.service">
                        <td><strong>{{ item.application?.displayName || item.name }}</strong><small>{{ item.name }} · {{ item.email }}</small></td>
                        <td><RoleStatusBadge :service="item.service" :status="item.status" /></td>
                        <td><span class="tag" :class="{ warn: !item.application }">{{ item.application ? `${item.application.documents.length} uploaded` : 'Needs application' }}</span></td>
                        <td class="nowrap">{{ date(item.submittedAt) }}<small v-if="item.status === 'pending' && item.submittedAt">{{ waiting(item.submittedAt) }}</small></td>
                        <td v-if="state.status !== 'pending'" class="nowrap">{{ date(item.decidedAt) }}<small v-if="item.reviewer">{{ item.reviewer }}</small></td>
                        <td><button type="button" class="open" :class="{ primary: item.status === 'pending' }" :aria-label="`${reviewLabel(item)} ${titles[item.service]} application from ${item.name}`" aria-haspopup="dialog" @click="active = item">{{ reviewLabel(item) }} application →</button></td>
                    </tr>
                    <tr v-if="!applications.data.length"><td :colspan="state.status === 'pending' ? 5 : 6" class="empty">{{ emptyText }}</td></tr>
                </tbody>
            </table>
        </div>
        <AdminPagination :paginator="applications" label="Application pages" />

        <Review v-if="active" :key="active.userId + active.service" :item="active" :requirements="requirements[active.service]" @close="active = null" />
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size:24px; font-weight:700; margin:0; }
.page-sub { font-size:13px; color:#637b76; margin:2px 0 0; }
.tabs { display:flex; gap:6px; margin-bottom:16px; padding:4px; background:#fff; border:1px solid #dce7e4; border-radius:12px; width:fit-content; max-width:100%; overflow-x:auto; }
.tabs button { display:inline-flex; align-items:center; gap:8px; min-height:40px; padding:0 14px; border-radius:9px; font-size:13px; font-weight:700; color:#42665e; cursor:pointer; white-space:nowrap; }
.tabs button span { min-width:22px; padding:2px 7px; border-radius:20px; background:#eef2f1; font-size:11px; text-align:center; }
.tabs button[aria-selected=true] { background:#103f3b; color:#fff; }
.tabs button[aria-selected=true] span { background:#ffffff26; }
.tabs button.attention:not([aria-selected=true]) span { background:#f5b56f; color:#4a2c00; }
.tabs button:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.table-wrap { overflow:auto; border:1px solid #dce7e4; border-radius:14px; background:#fff; }
table { width:100%; border-collapse:collapse; min-width:760px; text-align:left; font-size:13px; }
th { background:#f7faf8; padding:10px 16px; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#42665e; white-space:nowrap; }
td { padding:15px 16px; border-top:1px solid #e7efec; vertical-align:middle; }
tbody tr:hover { background:#f8fbfa; }
td strong, td small { display:block; overflow-wrap:anywhere; max-width:300px; }
td small { margin-top:4px; color:#637b76; font-size:11px; }
.nowrap { white-space:nowrap; color:#586e6c; }
.tag { display:inline-block; background:#eef2f1; color:#42665e; padding:5px 9px; border-radius:6px; font-size:12px; white-space:nowrap; }
.tag.warn { background:#fff3d6; color:#8a5a00; }
.status { display:inline-block; padding:4px 9px; border-radius:20px; font-size:11px; font-weight:700; text-transform:capitalize; }
.status.is-pending { background:#fff3d6; color:#8a5a00; }
.status.is-approved { background:#e1f5ec; color:#12744a; }
.status.is-rejected { background:#fdeaea; color:#b42318; }
.open { min-height:40px; padding:0 14px; border:1px solid #b7d8d0; border-radius:9px; font-weight:700; color:#12675f; white-space:nowrap; cursor:pointer; background:#fff; }
.open.primary { background:#137671; border-color:#137671; color:#fff; }
.open:focus-visible { outline:3px solid #e6bb51; outline-offset:2px; }
.empty { text-align:center; padding:44px 16px !important; color:#637b76; }
</style>
