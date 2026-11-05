<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import KpiCard from '@/Components/Admin/KpiCard.vue';
import InsightList from '@/Components/Admin/InsightList.vue';
import SortableTh from '@/Components/Admin/SortableTh.vue';
import AdminFilters from '@/Components/Admin/AdminFilters.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import TicketDialog from '@/Components/Admin/TicketDialog.vue';
import { useAdminTable } from '@/composables/useAdminTable';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler, BarElement, Legend } from 'chart.js';
import { Line, Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Tooltip, Filler, Legend);

const props = defineProps({
    tickets: { type: Object, default: null },
    view: { type: String, default: 'tickets' },
    filters: { type: Object, default: () => ({}) },
    sort: String,
    dir: String,
    counts: { type: Object, default: () => ({}) },
    analytics: { type: Object, default: null },
    options: { type: Object, required: true },
});

const page = usePage();
const me = computed(() => String(page.props.auth?.user?.id ?? ''));
const keys = ['category', 'priority', 'assigned', 'q'];
const { state, visit, search, sortBy, reset } = useAdminTable('/admin/tickets', {
    status: props.filters.status || 'active', category: props.filters.category || '', priority: props.filters.priority || '',
    assigned: props.filters.assigned || '', q: props.filters.q || '', range: props.filters.range || '30', sort: props.sort, dir: props.dir,
}, { defaults: { status: 'active', range: '30' } });

const tabs = [['active', 'Active'], ['open', 'Open'], ['in_progress', 'In progress'], ['resolved', 'Resolved'], ['closed', 'Closed'], ['all', 'All']];
const filtering = computed(() => keys.some(key => state[key] !== ''));
const days = computed(() => props.analytics?.range.days ?? Number(props.filters.range || 30));
function setStatus(status) { if (state.status !== status) { state.status = status; visit(); } }
function setRange(value) { state.range = String(value); router.get('/admin/tickets', { view: 'insights', range: value === 30 ? undefined : value }, { preserveState: true, preserveScroll: true, replace: true }); }
function showView(view) { if (view !== props.view) router.get('/admin/tickets', view === 'insights' && state.range !== '30' ? { view, range: state.range } : view === 'insights' ? { view } : {}); }

const selected = ref(null);
const reloadList = () => router.reload({ only: ['tickets', 'counts'], preserveScroll: true, preserveState: true });
const dateText = value => new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
const ago = iso => {
    const minutes = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
    if (minutes < 60) return `${minutes || 1}m ago`;
    if (minutes < 1440) return `${Math.round(minutes / 60)}h ago`;
    return `${Math.round(minutes / 1440)}d ago`;
};
const needsReply = ticket => !ticket.firstResponseAt && ['open', 'in_progress'].includes(ticket.status);

const dayLabel = date => new Date(`${date}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
const trendData = computed(() => ({
    labels: props.analytics.trend.dates.map(dayLabel),
    datasets: [
        { label: 'Opened', data: props.analytics.trend.created, borderColor: '#e08a2c', backgroundColor: '#e08a2c22', fill: true, tension: .35, cubicInterpolationMode: 'monotone', borderWidth: 2, pointRadius: days.value <= 30 ? 2 : 0 },
        { label: 'Resolved', data: props.analytics.trend.resolved, borderColor: '#1fa6a0', backgroundColor: '#1fa6a022', fill: true, tension: .35, cubicInterpolationMode: 'monotone', borderWidth: 2, pointRadius: days.value <= 30 ? 2 : 0 },
    ],
}));
const trendOptions = {
    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
    plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } }, tooltip: { padding: 10 } },
    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8, color: '#7b918d', font: { size: 11 } } }, y: { beginAtZero: true, ticks: { precision: 0, color: '#7b918d', font: { size: 11 } }, grid: { color: '#eef2f1' } } },
};
const categoryData = computed(() => ({
    labels: props.analytics.categories.map(c => c.label),
    datasets: [{ data: props.analytics.categories.map(c => c.count), backgroundColor: '#1fa6a0', borderRadius: 4, barThickness: 14 }],
}));
const categoryOptions = {
    responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { precision: 0, color: '#7b918d', font: { size: 11 } }, grid: { color: '#eef2f1' } }, y: { grid: { display: false }, ticks: { color: '#42665e', font: { size: 11 } } } },
};
const ageMax = computed(() => Math.max(1, ...(props.analytics?.ages ?? []).map(a => a.count)));
const hasData = computed(() => (props.analytics?.totals.all ?? 0) > 0);
</script>

<template>
    <Head title="Support tickets — Admin" />
    <AdminLayout>
        <template #header><div><h1 class="page-title">Support tickets</h1><p class="page-sub">Messages sent from the Support form on the home page.</p></div></template>

        <div class="views" role="tablist" aria-label="Support desk sections">
            <button type="button" role="tab" :aria-selected="view === 'tickets'" @click="showView('tickets')">Tickets<span>{{ counts.active ?? 0 }}</span></button>
            <button type="button" role="tab" :aria-selected="view === 'insights'" @click="showView('insights')">Insights</button>
        </div>

        <template v-if="view === 'insights'">
        <div class="top">
            <p>{{ counts.all ?? 0 }} tickets in total. Insights cover the last {{ days }} days.</p>
            <div class="range" role="group" aria-label="Insights range"><button v-for="option in analytics.range.options" :key="option" type="button" :aria-pressed="option === days" @click="setRange(option)">{{ option }} days</button></div>
        </div>

        <section class="kpis" aria-label="Support insights"><KpiCard v-for="kpi in analytics.kpis" :key="kpi.key" :kpi="kpi" :days="days" /></section>

        <div v-if="!hasData" class="panel nodata"><h2>No insights yet</h2><p>Charts and trends appear once the first support ticket arrives from the home page.</p></div>
        <div v-if="hasData" class="analytics">
            <section class="panel wide" aria-labelledby="flow-title">
                <header><h2 id="flow-title">Ticket flow</h2><p>Opened versus resolved each day.</p></header>
                <div class="chart" role="img" aria-label="Tickets opened and resolved per day"><Line :data="trendData" :options="trendOptions" /></div>
            </section>
            <section class="panel" aria-labelledby="topics-title">
                <header><h2 id="topics-title">What people write about</h2><p>Tickets by topic, last {{ days }} days.</p></header>
                <div class="chart small" role="img" aria-label="Tickets by category"><Bar :data="categoryData" :options="categoryOptions" /></div>
            </section>
            <section class="panel" aria-labelledby="age-title">
                <header><h2 id="age-title">Backlog age</h2><p>How long active tickets have waited.</p></header>
                <ul class="ages"><li v-for="bucket in analytics.ages" :key="bucket.label"><span>{{ bucket.label }}</span><div class="bar"><i :style="{ width: `${(bucket.count / ageMax) * 100}%` }" /></div><strong>{{ bucket.count }}</strong></li></ul>
            </section>
            <section class="panel" aria-labelledby="know-title">
                <header><h2 id="know-title">What to know</h2><p>Plain-language read of the desk.</p></header>
                <InsightList :insights="analytics.insights" />
            </section>
        </div>
        </template>

        <template v-else>
        <p class="lead">{{ counts.active ?? 0 }} active · {{ counts.all ?? 0 }} total. Open a ticket to reply, assign it or change its status.</p>
        <div class="tabs" role="tablist" aria-label="Ticket status">
            <button v-for="[key, label] in tabs" :key="key" type="button" role="tab" :aria-selected="state.status === key" @click="setStatus(key)">{{ label }}<span>{{ counts[key] ?? 0 }}</span></button>
        </div>

        <AdminFilters :active="filtering" :summary="`${tickets.total} ${tickets.total === 1 ? 'ticket' : 'tickets'}`" @reset="reset(keys)">
            <label class="grow">Search<input v-model="state.q" type="search" placeholder="Reference, subject, name, email or message" maxlength="150" @input="search" /></label>
            <label>Topic<select v-model="state.category" @change="visit()"><option value="">Any topic</option><option v-for="(label, key) in options.categories" :key="key" :value="key">{{ label }}</option></select></label>
            <label>Priority<select v-model="state.priority" @change="visit()"><option value="">Any priority</option><option v-for="(label, key) in options.priorities" :key="key" :value="key">{{ label }}</option></select></label>
            <label>Assigned<select v-model="state.assigned" @change="visit()"><option value="">Anyone</option><option value="me">Assigned to me</option><option value="unassigned">Unassigned</option><option v-for="admin in options.staff.filter(a => a.id !== me)" :key="admin.id" :value="admin.id">{{ admin.name }}</option></select></label>
        </AdminFilters>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <SortableTh column="subject" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Ticket</SortableTh>
                        <SortableTh column="requester" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Requester</SortableTh>
                        <SortableTh column="category" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Topic</SortableTh>
                        <SortableTh column="priority" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Priority</SortableTh>
                        <SortableTh column="status" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Status</SortableTh>
                        <SortableTh column="assignee" :sort="state.sort" :dir="state.dir" @sort="sortBy($event)">Assignee</SortableTh>
                        <SortableTh column="created" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Opened</SortableTh>
                        <SortableTh column="activity" :sort="state.sort" :dir="state.dir" @sort="sortBy($event, 'desc')">Activity</SortableTh>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="ticket in tickets.data" :key="ticket.id" tabindex="0" @click="selected = ticket" @keydown.enter="selected = ticket">
                        <td><button type="button" class="open" aria-haspopup="dialog" @click.stop="selected = ticket"><span class="ref">{{ ticket.reference }}</span>{{ ticket.subject }}</button><small>{{ ticket.preview }}</small></td>
                        <td><strong>{{ ticket.name }}</strong><small>{{ ticket.email }}</small></td>
                        <td class="nowrap">{{ options.categories[ticket.category] }}</td>
                        <td><span class="pill" :class="`p-${ticket.priority}`">{{ options.priorities[ticket.priority] }}</span></td>
                        <td>
                            <span class="pill" :class="`s-${ticket.status}`">{{ options.statuses[ticket.status] }}</span>
                            <small v-if="needsReply(ticket)" class="warn">No reply yet</small>
                        </td>
                        <td class="nowrap"><span v-if="ticket.assignee" class="assignee"><i aria-hidden="true">{{ ticket.assignee.slice(0, 1).toUpperCase() }}</i>{{ ticket.assignedToId === me ? 'You' : ticket.assignee }}</span><span v-else class="unassigned">Unassigned</span></td>
                        <td class="nowrap">{{ dateText(ticket.createdAt) }}</td>
                        <td class="nowrap">{{ ticket.lastActivityAt ? ago(ticket.lastActivityAt) : '—' }}<small v-if="ticket.repliesCount">{{ ticket.repliesCount }} {{ ticket.repliesCount === 1 ? 'reply' : 'replies' }}</small></td>
                    </tr>
                    <tr v-if="!tickets.data.length">
                        <td colspan="8" class="empty">{{ counts.all ? 'No tickets match these filters.' : 'No tickets yet. Messages sent from the Support form on the home page will appear here.' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <AdminPagination :paginator="tickets" label="Ticket pages" />
        </template>

        <TicketDialog v-if="selected" :key="selected.id" :ticket-id="selected.id" :options="options" :me="me" @close="selected = null" @changed="reloadList" />
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size:24px; font-weight:700; margin:0; }
.page-sub { font-size:13px; color:#637b76; margin:2px 0 0; }
.top { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
.views { display:flex; gap:6px; margin-bottom:20px; border-bottom:1px solid #dce7e4; }
.views button { display:inline-flex; align-items:center; gap:8px; min-height:44px; padding:0 18px; margin-bottom:-1px; border-bottom:3px solid transparent; font-size:14px; font-weight:700; color:#586e6c; cursor:pointer; }
.views button span { min-width:22px; padding:2px 7px; border-radius:20px; background:#eef2f1; font-size:11px; text-align:center; }
.views button[aria-selected=true] { color:#103f3b; border-bottom-color:#137671; }
.views button:focus-visible { outline:2px solid #137671; outline-offset:-2px; border-radius:6px; }
.lead { margin:0 0 16px; font-size:13px; color:#586e6c; }
.nodata { text-align:center; padding:44px 20px; margin-bottom:20px; }
.nodata p { margin:0; color:#586e6c; font-size:13px; }
.assignee { display:inline-flex; align-items:center; gap:8px; font-weight:600; color:#103f3b; }
.assignee i { display:grid; place-items:center; width:24px; height:24px; border-radius:50%; background:#d9efeb; color:#12675f; font-size:11px; font-style:normal; font-weight:700; }
.unassigned { font-size:12px; color:#8a9e99; }
.top p { margin:0; font-size:13px; color:#586e6c; }
.range { display:flex; gap:4px; padding:3px; background:#fff; border:1px solid #dce7e4; border-radius:11px; }
.range button { min-height:36px; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; color:#42665e; cursor:pointer; }
.range button[aria-pressed=true] { background:#103f3b; color:#fff; }
.kpis { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:16px; margin-bottom:16px; }
.analytics { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; margin-bottom:20px; }
.panel { background:#fff; border:1px solid #dce7e4; border-radius:14px; padding:20px; min-width:0; }
.panel.wide { grid-column:1 / -1; }
.panel header { margin-bottom:14px; }
.panel h2 { font-size:16px; font-weight:700; margin:0 0 4px; color:#0f2f2c; }
.panel header p { margin:0; font-size:12px; color:#586e6c; }
.chart { position:relative; height:230px; }
.chart.small { height:240px; }
.ages { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:16px; }
.ages li { display:grid; grid-template-columns:100px minmax(0, 1fr) 32px; gap:12px; align-items:center; font-size:13px; }
.ages .bar { height:10px; border-radius:6px; background:#eef2f1; overflow:hidden; }
.ages .bar i { display:block; height:100%; background:#f5b56f; border-radius:6px; }
.ages strong { text-align:right; }
.tabs { display:flex; gap:6px; margin-bottom:16px; padding:4px; background:#fff; border:1px solid #dce7e4; border-radius:12px; width:fit-content; max-width:100%; overflow-x:auto; }
.tabs button { display:inline-flex; align-items:center; gap:8px; min-height:40px; padding:0 14px; border-radius:9px; font-size:13px; font-weight:700; color:#42665e; cursor:pointer; white-space:nowrap; }
.tabs button span { min-width:22px; padding:2px 7px; border-radius:20px; background:#eef2f1; font-size:11px; text-align:center; }
.tabs button[aria-selected=true] { background:#103f3b; color:#fff; }
.tabs button[aria-selected=true] span { background:#ffffff26; }
.tabs button:focus-visible, .range button:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.table-wrap { overflow:auto; border:1px solid #dce7e4; border-radius:14px; background:#fff; }
table { width:100%; border-collapse:collapse; min-width:900px; text-align:left; font-size:13px; }
th { background:#f7faf8; padding:10px 16px; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#42665e; white-space:nowrap; }
td { padding:14px 16px; border-top:1px solid #e7efec; vertical-align:top; }
tbody tr { cursor:pointer; }
tbody tr:hover, tbody tr:focus-visible { background:#f8fbfa; outline:none; }
td small { display:block; margin-top:4px; color:#637b76; font-size:11px; overflow-wrap:anywhere; max-width:340px; }
td small.warn { color:#b45f12; font-weight:700; }
td strong { display:block; }
.nowrap { white-space:nowrap; color:#586e6c; }
.open { display:block; text-align:left; font-weight:700; color:#12675f; line-height:1.4; max-width:340px; cursor:pointer; }
.open .ref { display:block; font-size:10px; letter-spacing:.06em; color:#7b918d; font-weight:700; margin-bottom:2px; }
.open:focus-visible { outline:2px solid #137671; outline-offset:2px; border-radius:4px; }
.pill { display:inline-block; padding:4px 9px; border-radius:20px; font-size:11px; font-weight:700; background:#eef2f1; color:#42665e; white-space:nowrap; }
.p-high { background:#fff3d6; color:#8a5a00; }
.p-urgent { background:#fdeaea; color:#b42318; }
.s-open { background:#e3ecfb; color:#2a56b4; }
.s-in_progress { background:#fff3d6; color:#8a5a00; }
.s-resolved { background:#e1f5ec; color:#12744a; }
.empty { text-align:center; padding:44px 16px !important; color:#637b76; cursor:default; }
@media (max-width:900px) { .analytics { grid-template-columns:1fr; } }
</style>
