<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminFilters from '@/Components/Admin/AdminFilters.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import SortableTh from '@/Components/Admin/SortableTh.vue';
import ReportDialog from '@/Components/Admin/ReportDialog.vue';
import { useAdminTable } from '@/composables/useAdminTable';

const props = defineProps({
    reports: { type: Object, required: true }, status: { type: String, required: true },
    filters: { type: Object, required: true }, counts: { type: Object, required: true }, reviewers: { type: Array, required: true },
    sort: String, dir: String,
});
const tabs = [['pending', 'Pending'], ['confirmed', 'Confirmed'], ['dismissed', 'Dismissed'], ['reversed', 'Reversed'], ['all', 'All']];
const keys = ['q', 'reporter', 'reported', 'from', 'to', 'score_min', 'score_max', 'points_min', 'points_max', 'reviewer'];
const { state, visit, sortBy, reset } = useAdminTable('/admin/reports', {
    status: props.status, ...props.filters, sort: props.sort, dir: props.dir,
}, { defaults: { status: 'pending' } });
const filtering = computed(() => keys.some(key => state[key] !== ''));
const filterError = ref('');
const selected = ref(null);
function validFilters() {
    const invalidNumber = ['score_min', 'score_max', 'points_min', 'points_max'].some(key => state[key] !== '' && (!/^\d+$/.test(String(state[key])) || Number(state[key]) > 255));
    if (invalidNumber) filterError.value = 'Scores and points must be whole numbers from 0 to 255.';
    else if (state.from && state.to && state.from > state.to) filterError.value = 'The end date must be on or after the start date.';
    else if (state.score_min !== '' && state.score_max !== '' && Number(state.score_min) > Number(state.score_max)) filterError.value = 'The minimum trust score must not exceed the maximum.';
    else if (state.points_min !== '' && state.points_max !== '' && Number(state.points_min) > Number(state.points_max)) filterError.value = 'The minimum deduction must not exceed the maximum.';
    else filterError.value = '';
    return !filterError.value;
}
function apply() { if (validFilters()) visit(); }
function setStatus(status) { if (state.status !== status && validFilters()) { state.status = status; visit(); } }
function sort(column, direction) { if (validFilters()) sortBy(column, direction); }
function resetFilters() { filterError.value = ''; reset(keys); }
const dateText = value => new Date(value.replace(' ', 'T')).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
</script>

<template>
    <Head title="Account reports — Admin" />
    <AdminLayout>
        <template #header><div><h1 class="page-title">Account reports</h1><p class="page-sub">Review reports submitted by members of the Aadi community.</p></div></template>
        <p class="lead">Reports affect trust scores only when confirmed. A deduction cannot reduce a score below zero.</p>
        <div class="tabs" role="group" aria-label="Report status">
            <button v-for="[key, label] in tabs" :key="key" type="button" :aria-pressed="state.status === key" @click="setStatus(key)">{{ label }}<span>{{ counts[key] ?? 0 }}</span></button>
        </div>
        <AdminFilters :active="filtering" :summary="`${reports.total} ${reports.total === 1 ? 'report' : 'reports'}`" @reset="resetFilters">
            <label class="grow">Search<input v-model="state.q" type="search" placeholder="Reporter, reported account or report" maxlength="150" @keyup.enter="apply" /></label>
            <label>Reporter<input v-model="state.reporter" type="search" placeholder="Name or email" maxlength="150" @keyup.enter="apply" /></label>
            <label>Reported account<input v-model="state.reported" type="search" placeholder="Name or email" maxlength="150" @keyup.enter="apply" /></label>
            <label>Submitted from<input v-model="state.from" type="date" /></label>
            <label>Submitted through<input v-model="state.to" type="date" /></label>
            <label class="narrow">Trust score min<input v-model="state.score_min" type="number" min="0" max="255" step="1" /></label>
            <label class="narrow">Trust score max<input v-model="state.score_max" type="number" min="0" max="255" step="1" /></label>
            <label class="narrow">Deducted points min<input v-model="state.points_min" type="number" min="0" max="255" step="1" /></label>
            <label class="narrow">Deducted points max<input v-model="state.points_max" type="number" min="0" max="255" step="1" /></label>
            <label>Last reviewed by<select v-model="state.reviewer"><option value="">Anyone</option><option v-for="reviewer in reviewers" :key="reviewer.id" :value="reviewer.id">{{ reviewer.name }}</option></select></label>
            <button type="button" class="apply-filters" @click="apply">Apply filters</button>
            <p v-if="filterError" class="filter-error" role="alert">{{ filterError }}</p>
            <p class="filter-note">Trust score is current. Deduction filters include confirmed and reversed reports.</p>
        </AdminFilters>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Report</th><th>Reporter</th><th>Reported account</th>
                    <SortableTh column="status" :sort="state.sort" :dir="state.dir" @sort="sort($event)">Status</SortableTh>
                    <SortableTh column="submitted" :sort="state.sort" :dir="state.dir" @sort="sort($event, 'desc')">Submitted</SortableTh>
                </tr></thead>
                <tbody>
                    <tr v-for="report in reports.data" :key="report.id" tabindex="0" @click="selected = report" @keydown.enter="selected = report">
                        <td><button type="button" class="open" aria-haspopup="dialog" @click.stop="selected = report"><span class="ref">Account report</span>Review report</button><small class="preview">{{ report.reason }}</small></td>
                        <td><strong>{{ report.reporter }}</strong></td>
                        <td><strong>{{ report.reported }}</strong><small>Trust score: {{ report.trustScore }}</small></td>
                        <td><span class="pill" :class="`s-${report.status}`">{{ report.status }}</span><small v-if="report.status !== 'pending'">{{ report.points }} points applied</small></td>
                        <td class="nowrap">{{ dateText(report.createdAt) }}</td>
                    </tr>
                    <tr v-if="!reports.data.length"><td colspan="5" class="empty">{{ counts.all ? 'No reports match these filters.' : 'No reports yet. Reports submitted by members will appear here.' }}</td></tr>
                </tbody>
            </table>
        </div>
        <AdminPagination :paginator="reports" :sizes="[10, 15, 20, 25, 50]" label="Report pages" />
        <ReportDialog v-if="selected" :key="selected.id" :report="selected" @close="selected = null" />
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size:24px; font-weight:700; margin:0; }
.page-sub { font-size:13px; color:#637b76; margin:2px 0 0; }
.lead { margin:0 0 16px; font-size:13px; color:#586e6c; }
.apply-filters { min-height:40px; padding:0 16px; border-radius:9px; background:#103f3b; color:#fff; font-size:13px; font-weight:700; cursor:pointer; }
.apply-filters:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.filter-error, .filter-note { flex-basis:100%; margin:0; font-size:12px; }
.filter-error { color:#a12626; }
.filter-note { color:#586e6c; }
.tabs { display:flex; gap:6px; margin-bottom:16px; padding:4px; background:#fff; border:1px solid #dce7e4; border-radius:12px; width:fit-content; max-width:100%; overflow-x:auto; }
.tabs button { display:inline-flex; align-items:center; gap:8px; min-height:40px; padding:0 14px; border-radius:9px; font-size:13px; font-weight:700; color:#42665e; cursor:pointer; white-space:nowrap; }
.tabs button span { min-width:22px; padding:2px 7px; border-radius:20px; background:#eef2f1; font-size:11px; text-align:center; }
.tabs button[aria-pressed=true] { background:#103f3b; color:#fff; }
.tabs button[aria-pressed=true] span { background:#ffffff26; }
.tabs button:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.table-wrap { overflow:auto; border:1px solid #dce7e4; border-radius:14px; background:#fff; }
table { width:100%; border-collapse:collapse; min-width:900px; text-align:left; font-size:13px; }
th { background:#f7faf8; padding:10px 16px; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#42665e; white-space:nowrap; }
td { padding:14px 16px; border-top:1px solid #e7efec; vertical-align:top; }
tbody tr { cursor:pointer; }
tbody tr:hover, tbody tr:focus-visible { background:#f8fbfa; outline:none; }
td small { display:block; margin-top:4px; color:#637b76; font-size:11px; overflow-wrap:anywhere; max-width:340px; }
td strong { display:block; }
.preview { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; white-space:pre-wrap; }
.nowrap { white-space:nowrap; color:#586e6c; }
.open { display:block; text-align:left; font-weight:700; color:#12675f; line-height:1.4; cursor:pointer; }
.open .ref { display:block; font-size:10px; letter-spacing:.06em; color:#7b918d; text-transform:uppercase; margin-bottom:2px; }
.open:focus-visible { outline:2px solid #137671; outline-offset:2px; border-radius:4px; }
.pill { display:inline-block; padding:4px 9px; border-radius:20px; font-size:11px; font-weight:700; background:#eef2f1; color:#42665e; text-transform:capitalize; white-space:nowrap; }
.s-pending { background:#fff3d6; color:#8a5a00; }
.s-confirmed { background:#e1f5ec; color:#12744a; }
.s-dismissed { background:#e3ecfb; color:#2a56b4; }
.empty { text-align:center; padding:44px 16px !important; color:#637b76; cursor:default; }
</style>
