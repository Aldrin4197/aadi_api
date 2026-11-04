<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import KpiCard from '@/Components/Admin/KpiCard.vue';
import InsightList from '@/Components/Admin/InsightList.vue';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Tooltip, Filler } from 'chart.js';
import { Line, Doughnut } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Tooltip, Filler);

const props = defineProps({ overview: { type: Object, required: true } });

const days = computed(() => props.overview.range.days);
const dayLabel = date => new Date(`${date}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
const rangeText = computed(() => `${dayLabel(props.overview.range.from)} – ${dayLabel(props.overview.range.to)}`);
function setRange(value) {
    if (value === days.value) return;
    router.get('/admin/dashboard', { range: value }, { preserveScroll: true, preserveState: true, only: ['overview'] });
}

const metrics = [
    { key: 'users', label: 'New users', color: '#1fa6a0' },
    { key: 'listings', label: 'New listings', color: '#e08a2c' },
    { key: 'views', label: 'Listing views', color: '#3b82f6' },
    { key: 'engagement', label: 'Engagement', color: '#8b5cf6' },
];
const metric = ref(metrics.find(m => (props.overview.trend.series[m.key] || []).some(n => n > 0))?.key ?? 'users');
const active = computed(() => metrics.find(m => m.key === metric.value));
const series = computed(() => props.overview.trend.series[metric.value] || []);
const total = computed(() => series.value.reduce((a, b) => a + b, 0));
const peakIndex = computed(() => series.value.indexOf(Math.max(...series.value, 0)));
const caption = computed(() => {
    if (!total.value) return `No ${active.value.label.toLowerCase()} recorded in this period.`;
    const average = (total.value / days.value).toFixed(1);
    return `${total.value.toLocaleString()} total · ${average} per day · peak on ${dayLabel(props.overview.trend.dates[peakIndex.value])} (${series.value[peakIndex.value]})`;
});
const trendData = computed(() => ({
    labels: props.overview.trend.dates.map(dayLabel),
    datasets: [{ label: active.value.label, data: series.value, borderColor: active.value.color, backgroundColor: `${active.value.color}22`, fill: true, tension: .35, cubicInterpolationMode: 'monotone', pointRadius: days.value <= 30 ? 2 : 0, pointHoverRadius: 5, borderWidth: 2 }],
}));
const trendOptions = {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { display: false }, tooltip: { padding: 10, displayColors: false } },
    scales: {
        x: { grid: { display: false }, ticks: { maxTicksLimit: 8, color: '#7b918d', font: { size: 11 } } },
        y: { beginAtZero: true, ticks: { precision: 0, color: '#7b918d', font: { size: 11 } }, grid: { color: '#eef2f1' } },
    },
};

const supplyTotal = computed(() => props.overview.supply.reduce((n, s) => n + s.live, 0));
const supplyColors = ['#1fa6a0', '#e08a2c', '#8b5cf6'];
const supplyData = computed(() => ({
    labels: props.overview.supply.map(s => s.label),
    datasets: [{ data: props.overview.supply.map(s => s.live), backgroundColor: supplyColors, borderWidth: 2, borderColor: '#fff' }],
}));
const supplyOptions = { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } };

const pipelineMax = computed(() => Math.max(1, ...props.overview.pipeline.map(p => p.approved + p.pending + p.rejected)));
const pct = (n, p) => `${(n / pipelineMax.value) * 100}%`;

const activityIcons = {
    user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0M4 21v-2a8 8 0 0116 0v2',
    spaces: 'M3 10l9-7 9 7M5 9v12h14V9M9 21v-8h6v8',
    haul: 'M3 5h12v12H3zM15 9h4l3 4v4h-7M5 17v3h3v-3M17 17v3h3v-3',
    raketeer: 'M3 7h18v14H3zM8 7V3h8v4M3 12l9 4 9-4',
    application: 'm5 12 4 4L19 6M12 3l9 4v6c0 4-9 8-9 8S3 17 3 13V7z',
};
function ago(iso) {
    const minutes = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
    if (minutes < 60) return `${minutes || 1}m ago`;
    if (minutes < 1440) return `${Math.round(minutes / 60)}h ago`;
    if (minutes < 43200) return `${Math.round(minutes / 1440)}d ago`;
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>
            <div>
                <h1 class="dash-title">Dashboard</h1>
                <p class="dash-subtitle">Marketplace overview · {{ rangeText }}</p>
            </div>
        </template>

        <div class="dash">
            <div class="dash-toolbar">
                <p>How is Aadi doing? Totals show the current state; trends compare against the previous {{ days }} days.</p>
                <div class="range" role="group" aria-label="Time range">
                    <button v-for="option in overview.range.options" :key="option" type="button" :aria-pressed="option === days" @click="setRange(option)">{{ option }} days</button>
                </div>
            </div>

            <section class="kpis" aria-label="Key metrics">
                <KpiCard v-for="kpi in overview.kpis" :key="kpi.key" :kpi="kpi" :days="days" />
            </section>

            <div class="grid-main">
                <section class="panel" aria-labelledby="trend-title">
                    <header class="panel-head">
                        <div><h2 id="trend-title">Activity over time</h2><p>{{ caption }}</p></div>
                        <div class="tabs" role="group" aria-label="Metric">
                            <button v-for="m in metrics" :key="m.key" type="button" :aria-pressed="m.key === metric" @click="metric = m.key"><span class="dot" :style="{ background: m.color }" />{{ m.label }}</button>
                        </div>
                    </header>
                    <div class="chart" role="img" :aria-label="`${active.label} per day. ${caption}`"><Line :data="trendData" :options="trendOptions" /></div>
                </section>

                <section class="panel insights" aria-labelledby="insights-title">
                    <header class="panel-head"><div><h2 id="insights-title">What to know</h2><p>Plain-language read of the numbers.</p></div></header>
                    <InsightList :insights="overview.insights" />
                </section>
            </div>

            <div class="grid-sub">
                <section class="panel" aria-labelledby="supply-title">
                    <header class="panel-head"><div><h2 id="supply-title">Live supply</h2><p>Listings visible to customers, by category.</p></div></header>
                    <div class="supply">
                        <div class="donut" role="img" :aria-label="`${supplyTotal} live listings`">
                            <Doughnut :data="supplyData" :options="supplyOptions" />
                            <div class="donut-center"><strong>{{ supplyTotal }}</strong><span>live</span></div>
                        </div>
                        <ul class="legend">
                            <li v-for="(row, i) in overview.supply" :key="row.key">
                                <span class="dot" :style="{ background: supplyColors[i] }" /><span class="legend-name">{{ row.label }}</span>
                                <strong>{{ row.live }}</strong><small>{{ row.other ? `+${row.other} not live` : 'all live' }}</small>
                            </li>
                        </ul>
                    </div>
                </section>

                <section class="panel" aria-labelledby="pipeline-title">
                    <header class="panel-head"><div><h2 id="pipeline-title">Provider pipeline</h2><p>Who can publish, by role.</p></div><Link href="/admin/approvals" class="panel-link">Approvals →</Link></header>
                    <ul class="pipeline">
                        <li v-for="row in overview.pipeline" :key="row.key">
                            <div class="pipeline-label"><strong>{{ row.label }}</strong><span>{{ row.approved }} approved · {{ row.pending }} pending · {{ row.rejected }} rejected</span></div>
                            <div class="bar" role="img" :aria-label="`${row.label}: ${row.approved} approved, ${row.pending} pending, ${row.rejected} rejected`">
                                <span class="seg approved" :style="{ width: pct(row.approved) }" /><span class="seg pending" :style="{ width: pct(row.pending) }" /><span class="seg rejected" :style="{ width: pct(row.rejected) }" />
                            </div>
                        </li>
                    </ul>
                    <p class="key"><span class="dot approved" />Approved <span class="dot pending" />Pending <span class="dot rejected" />Rejected</p>
                </section>

                <section class="panel" aria-labelledby="activity-title">
                    <header class="panel-head"><div><h2 id="activity-title">Recent activity</h2><p>Latest sign-ups, listings and applications.</p></div></header>
                    <ul v-if="overview.activity.length" class="activity">
                        <li v-for="(item, i) in overview.activity" :key="i">
                            <span class="activity-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="activityIcons[item.type]" /></svg></span>
                            <Link :href="item.href"><strong>{{ item.title }}</strong><span>{{ item.meta }}</span></Link>
                            <time :datetime="item.at">{{ ago(item.at) }}</time>
                        </li>
                    </ul>
                    <p v-else class="empty">No activity yet.</p>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.dash-title { font-size:24px; font-weight:700; color:#0f2f2c; margin:0; }
.dash-subtitle { font-size:13px; color:#586e6c; margin:2px 0 0; }
.dash { display:flex; flex-direction:column; gap:20px; color:#183e3b; }
.dash-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.dash-toolbar p { margin:0; font-size:13px; color:#586e6c; max-width:62ch; }
.range, .tabs { display:flex; gap:4px; padding:3px; background:#fff; border:1px solid #dce7e4; border-radius:11px; }
.range button, .tabs button { min-height:36px; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; color:#42665e; cursor:pointer; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
.range button[aria-pressed=true], .tabs button[aria-pressed=true] { background:#103f3b; color:#fff; }
.range button:focus-visible, .tabs button:focus-visible, .dash a:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.kpis { display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:16px; }
.kpis > * { grid-column:span 6; }
@media (min-width:1100px) { .kpis > :nth-child(-n+4) { grid-column:span 3; } .kpis > :nth-child(n+5) { grid-column:span 4; } }
@media (max-width:520px) { .kpis > * { grid-column:1 / -1; } }
.grid-main { display:grid; grid-template-columns:minmax(0, 2fr) minmax(300px, 1fr); gap:16px; }
.grid-sub { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px; }
.panel { background:#fff; border:1px solid #dce7e4; border-radius:14px; padding:20px; min-width:0; display:flex; flex-direction:column; }
.panel-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
.panel-head h2 { font-size:16px; font-weight:700; margin:0 0 4px; color:#0f2f2c; }
.panel-head p { font-size:12px; color:#586e6c; margin:0; line-height:1.5; }
.panel-link { font-size:12px; font-weight:700; color:#137671; white-space:nowrap; }
.dot { display:inline-block; width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.chart { position:relative; height:290px; }
.supply { display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
.donut { position:relative; width:150px; height:150px; flex-shrink:0; }
.donut-center { position:absolute; inset:0; display:grid; place-content:center; text-align:center; pointer-events:none; }
.donut-center strong { font-size:28px; line-height:1; }
.donut-center span { font-size:11px; color:#586e6c; }
.legend { list-style:none; margin:0; padding:0; flex:1; min-width:150px; display:flex; flex-direction:column; gap:12px; }
.legend li { display:grid; grid-template-columns:auto 1fr auto; align-items:center; column-gap:8px; font-size:13px; }
.legend strong { font-size:15px; }
.legend small { grid-column:2 / 4; font-size:11px; color:#7b918d; }
.pipeline { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:18px; }
.pipeline-label { display:flex; flex-direction:column; gap:2px; margin-bottom:6px; }
.pipeline-label strong { font-size:13px; }
.pipeline-label span { font-size:11px; color:#586e6c; }
.bar { display:flex; height:10px; border-radius:6px; background:#eef2f1; overflow:hidden; }
.seg.approved, .dot.approved { background:#1fa6a0; }
.seg.pending, .dot.pending { background:#f5b56f; }
.seg.rejected, .dot.rejected { background:#e5736b; }
.key { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin:auto 0 0; padding-top:16px; font-size:11px; color:#586e6c; }
.key .dot { margin-left:8px; }
.key .dot:first-child { margin-left:0; }
.activity { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; }
.activity li { display:grid; grid-template-columns:auto minmax(0, 1fr) auto; gap:12px; align-items:center; padding:10px 0; border-top:1px solid #eef2f1; }
.activity li:first-child { border-top:0; padding-top:0; }
.activity-icon { display:grid; place-items:center; width:32px; height:32px; border-radius:50%; background:#e3f6f4; color:#137671; }
.activity-icon svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.activity a { display:flex; flex-direction:column; min-width:0; }
.activity a strong { font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.activity a span { font-size:11px; color:#586e6c; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.activity a:hover strong { text-decoration:underline; }
.activity time { font-size:11px; color:#7b918d; white-space:nowrap; }
.empty { font-size:13px; color:#586e6c; }
@media (max-width:1200px) { .grid-main { grid-template-columns:1fr; } .grid-sub { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
@media (max-width:760px) { .grid-sub { grid-template-columns:1fr; } .tabs { overflow-x:auto; max-width:100%; } .chart { height:240px; } .dash-toolbar p { display:none; } }
</style>
