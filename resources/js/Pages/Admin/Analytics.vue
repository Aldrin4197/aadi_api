<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import KpiCard from '@/Components/Admin/KpiCard.vue';
import InsightList from '@/Components/Admin/InsightList.vue';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler } from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Filler);

const props = defineProps({ analytics: { type: Object, required: true } });

const days = computed(() => props.analytics.range.days);
const dayLabel = date => new Date(`${date}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
const rangeText = computed(() => `${dayLabel(props.analytics.range.from)} – ${dayLabel(props.analytics.range.to)}`);
function setRange(value) {
    if (value === days.value) return;
    router.get('/admin/analytics', { range: value }, { preserveScroll: true, preserveState: true, only: ['analytics'] });
}

const metrics = [
    { key: 'views', label: 'Views', color: '#3b82f6' },
    { key: 'saves', label: 'Saves', color: '#e08a2c' },
    { key: 'reviews', label: 'Reviews', color: '#8b5cf6' },
    { key: 'viewings', label: 'Viewings', color: '#1fa6a0' },
    { key: 'users', label: 'New users', color: '#12744a' },
    { key: 'listings', label: 'New listings', color: '#b45f12' },
];
const metric = ref(metrics.find(m => (props.analytics.trend.series[m.key] || []).some(n => n > 0))?.key ?? 'views');
const active = computed(() => metrics.find(m => m.key === metric.value));
const series = computed(() => props.analytics.trend.series[metric.value] || []);
const total = computed(() => series.value.reduce((a, b) => a + b, 0));
const peakIndex = computed(() => series.value.indexOf(Math.max(...series.value, 0)));
const caption = computed(() => {
    if (!total.value) return `No ${active.value.label.toLowerCase()} recorded in this period.`;
    return `${total.value.toLocaleString()} total · ${(total.value / days.value).toFixed(1)} per day · peak on ${dayLabel(props.analytics.trend.dates[peakIndex.value])} (${series.value[peakIndex.value]})`;
});
const trendData = computed(() => ({
    labels: props.analytics.trend.dates.map(dayLabel),
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

const categoryColor = { spaces: '#1fa6a0', haul: '#e08a2c', raketeer: '#8b5cf6' };
const humanize = value => String(value).replace(/_/g, ' ').replace(/([A-Z])/g, ' $1').replace(/^./, c => c.toUpperCase()).trim();
const typeMax = computed(() => Math.max(1, ...props.analytics.types.map(t => t.count)));
const visibleTypes = computed(() => props.analytics.types.slice(0, 8));
const hiddenTypes = computed(() => Math.max(0, props.analytics.types.length - 8));
const hasFunnelData = computed(() => props.analytics.funnel.some(step => step.count > 0));
</script>

<template>
    <Head title="Analytics — Admin" />

    <AdminLayout>
        <template #header>
            <div>
                <h1 class="dash-title">Analytics</h1>
                <p class="dash-subtitle">How people use Aadi · {{ rangeText }}</p>
            </div>
        </template>

        <div class="dash">
            <div class="dash-toolbar">
                <p>What visitors do and what they respond to. Each trend compares with the previous {{ days }} days.</p>
                <div class="range" role="group" aria-label="Time range">
                    <button v-for="option in analytics.range.options" :key="option" type="button" :aria-pressed="option === days" @click="setRange(option)">{{ option }} days</button>
                </div>
            </div>

            <section class="kpis" aria-label="Key metrics">
                <KpiCard v-for="kpi in analytics.kpis" :key="kpi.key" :kpi="kpi" :days="days" />
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
                    <InsightList :insights="analytics.insights" />
                </section>
            </div>

            <div class="grid-sub">
                <section class="panel" aria-labelledby="funnel-title">
                    <header class="panel-head"><div><h2 id="funnel-title">Engagement</h2><p>What happens after someone views a property.</p></div></header>
                    <ul v-if="hasFunnelData" class="funnel">
                        <li v-for="step in analytics.funnel" :key="step.key">
                            <div class="funnel-label"><strong>{{ step.label }}</strong><span>{{ step.count.toLocaleString() }}<small v-if="step.per100 !== null"> · {{ step.per100 }} per 100 views</small></span></div>
                            <div class="bar" role="img" :aria-label="`${step.label}: ${step.count}`"><i :style="{ width: `${step.width}%` }" /></div>
                        </li>
                    </ul>
                    <p v-else class="empty">No activity in this period yet.</p>
                </section>

                <section class="panel" aria-labelledby="types-title">
                    <header class="panel-head"><div><h2 id="types-title">Listings by type</h2><p>What is available to browse, all categories.</p></div><Link href="/admin/listings" class="panel-link">Properties →</Link></header>
                    <ul v-if="visibleTypes.length" class="types">
                        <li v-for="row in visibleTypes" :key="row.category + row.type">
                            <span class="type-name"><i :style="{ background: categoryColor[row.category] }" />{{ humanize(row.type) }}<small>{{ row.categoryLabel }}</small></span>
                            <div class="bar"><i :style="{ width: `${(row.count / typeMax) * 100}%`, background: categoryColor[row.category] }" /></div>
                            <strong>{{ row.count }}</strong>
                        </li>
                    </ul>
                    <p v-else class="empty">No listings yet.</p>
                    <p v-if="hiddenTypes" class="more">+ {{ hiddenTypes }} more {{ hiddenTypes === 1 ? 'type' : 'types' }}</p>
                </section>

                <section class="panel" aria-labelledby="top-title">
                    <header class="panel-head"><div><h2 id="top-title">Most viewed properties</h2><p>Top listings in the last {{ days }} days.</p></div></header>
                    <ol v-if="analytics.topListings.length" class="top">
                        <li v-for="(item, i) in analytics.topListings" :key="item.id">
                            <span class="rank">{{ i + 1 }}</span>
                            <div><strong>{{ item.title }}</strong><small>{{ item.city || 'Location not provided' }}</small></div>
                            <div class="nums"><b>{{ item.views.toLocaleString() }}</b><small>views · {{ item.saves }} {{ item.saves === 1 ? 'save' : 'saves' }}</small></div>
                        </li>
                    </ol>
                    <p v-else class="empty">No properties were viewed in this period.</p>
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
@media (min-width:1100px) { .kpis > * { grid-column:span 4; } }
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
.empty { font-size:13px; color:#586e6c; margin:0; }
.more { margin:12px 0 0; font-size:12px; color:#7b918d; }

.funnel, .types, .top { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; }
.funnel { gap:18px; }
.funnel-label { display:flex; justify-content:space-between; align-items:baseline; gap:12px; margin-bottom:6px; font-size:13px; }
.funnel-label span { font-weight:700; font-variant-numeric:tabular-nums; }
.funnel-label small { font-weight:500; color:#7b918d; font-size:11px; }
.bar { height:10px; border-radius:6px; background:#eef2f1; overflow:hidden; }
.bar i { display:block; height:100%; border-radius:6px; background:#1fa6a0; }
.funnel li:nth-child(1) .bar i { background:#3b82f6; }
.funnel li:nth-child(2) .bar i { background:#e08a2c; }
.funnel li:nth-child(3) .bar i { background:#1fa6a0; }
.funnel li:nth-child(4) .bar i { background:#8b5cf6; }

.types { gap:14px; }
.types li { display:grid; grid-template-columns:minmax(110px, 1.1fr) minmax(0, 1.2fr) 28px; gap:12px; align-items:center; font-size:13px; }
.type-name { display:flex; flex-wrap:wrap; align-items:center; gap:2px 8px; font-weight:600; min-width:0; }
.type-name i { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.type-name small { flex-basis:100%; margin-left:16px; font-size:10px; font-weight:500; color:#7b918d; }
.types strong { text-align:right; font-variant-numeric:tabular-nums; }

.top { gap:0; }
.top li { display:grid; grid-template-columns:auto minmax(0, 1fr) auto; gap:12px; align-items:center; padding:11px 0; border-top:1px solid #eef2f1; }
.top li:first-child { border-top:0; padding-top:0; }
.rank { display:grid; place-items:center; width:26px; height:26px; border-radius:50%; background:#e3f6f4; color:#137671; font-size:12px; font-weight:700; }
.top strong { display:block; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.top small { display:block; font-size:11px; color:#637b76; margin-top:2px; }
.nums { text-align:right; }
.nums b { font-size:15px; font-variant-numeric:tabular-nums; }

@media (max-width:1200px) { .grid-main { grid-template-columns:1fr; } .grid-sub { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
@media (max-width:760px) { .grid-sub { grid-template-columns:1fr; } .tabs { overflow-x:auto; max-width:100%; } .chart { height:240px; } .dash-toolbar p { display:none; } }
</style>
