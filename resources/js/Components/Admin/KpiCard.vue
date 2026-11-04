<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ kpi: { type: Object, required: true }, days: { type: Number, default: 30 } });

const icons = {
    users: 'M16 7a4 4 0 11-8 0 4 4 0 018 0M4 21v-2a8 8 0 0116 0v2',
    listings: 'M3 10l9-7 9 7M5 9v12h14V9M9 21v-8h6v8',
    views: 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zM12 15a3 3 0 100-6 3 3 0 000 6z',
    engagement: 'M12 21s-8-4.8-8-11a4.5 4.5 0 018-2.8A4.5 4.5 0 0120 10c0 6.2-8 11-8 11z',
    approvals: 'm5 12 4 4L19 6M12 3l9 4v6c0 4-9 8-9 8S3 17 3 13V7z',
    verified: 'M4 6h16v12H4zM4 7l8 6 8-6',
    saves: 'M6 3h12v18l-6-4-6 4z',
    reviews: 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
    viewings: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
    tickets: 'M3 4h18v13H8l-5 4z',
    backlog: 'M4 6h16M4 12h16M4 18h10',
    response: 'M12 7v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    resolution: 'M5 12.5l4.2 4.2L19 7',
};
const windowed = computed(() => ['views', 'engagement'].includes(props.kpi.key));
const scope = computed(() => props.kpi.scope ?? (windowed.value ? `Last ${props.days} days` : props.kpi.key === 'approvals' ? 'Right now' : 'All time'));
const value = computed(() => (props.kpi.value === null || props.kpi.value === undefined ? '—' : `${Number(props.kpi.value).toLocaleString()}${props.kpi.suffix || ''}`));
const good = computed(() => (props.kpi.invert ? props.kpi.delta <= 0 : props.kpi.delta >= 0));
const badge = computed(() => {
    if (props.kpi.delta !== null && props.kpi.delta !== undefined) return `${props.kpi.delta > 0 ? '+' : ''}${props.kpi.delta}%`;
    return props.kpi.newInRange > 0 && props.kpi.series?.length ? 'New' : null;
});
const spark = computed(() => {
    const series = props.kpi.series || [];
    if (series.length < 2 || !series.some(n => n > 0)) return null;
    const max = Math.max(...series);
    const points = series.map((n, i) => [(i / (series.length - 1)) * 100, 26 - (n / max) * 22]);
    const line = points.map(([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`).join(' ');
    return { line, area: `0,28 ${line} 100,28` };
});
const component = computed(() => props.kpi.href ? Link : 'div');
</script>

<template>
    <component :is="component" :href="kpi.href" class="kpi" :class="[`tone-${kpi.tone}`, { 'is-link': kpi.href }]">
        <div class="kpi-top">
            <span class="kpi-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="icons[kpi.icon]" /></svg></span>
            <span class="kpi-label">{{ kpi.label }}</span>
            <span v-if="badge" class="kpi-badge" :class="`badge-${kpi.delta === null ? 'neutral' : good ? 'up' : 'down'}`">
                <svg v-if="kpi.delta !== null" viewBox="0 0 12 12" aria-hidden="true"><path :d="kpi.delta >= 0 ? 'M6 2l4 5H2z' : 'M6 10L2 5h8z'" /></svg>{{ badge }}
            </span>
        </div>
        <strong class="kpi-value">{{ value }}</strong>
        <p class="kpi-hint">{{ kpi.hint || '\u00A0' }}</p>
        <svg v-if="spark" class="kpi-spark" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
            <polygon :points="spark.area" class="spark-area" /><polyline :points="spark.line" class="spark-line" />
        </svg>
        <span v-else class="kpi-spark-empty" aria-hidden="true" />
        <p class="kpi-scope">{{ scope }}<template v-if="kpi.delta !== null && kpi.delta !== undefined"> · vs previous {{ days }} days</template></p>
    </component>
</template>

<style scoped>
.kpi { display:flex; flex-direction:column; gap:4px; padding:18px 18px 14px; background:#fff; border:1px solid #dce7e4; border-radius:14px; min-width:0; color:#183e3b; --accent:#1fa6a0; --accent-soft:#e3f6f4; }
.kpi.is-link { transition:border-color .15s ease, box-shadow .15s ease; }
.kpi.is-link:hover { border-color:var(--accent); box-shadow:0 6px 20px #0b3a3814; }
.kpi.is-link:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.tone-warning { --accent:#e08a2c; --accent-soft:#fdf0de; }
.kpi-top { display:flex; align-items:center; gap:10px; }
.kpi-icon { display:grid; place-items:center; width:34px; height:34px; border-radius:10px; background:var(--accent-soft); color:var(--accent); flex-shrink:0; }
.kpi-icon svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.7; stroke-linecap:round; stroke-linejoin:round; }
.kpi-label { font-size:12px; font-weight:700; color:#586e6c; letter-spacing:.02em; flex:1; min-width:0; }
.kpi-badge { display:inline-flex; align-items:center; gap:3px; padding:3px 8px; border-radius:20px; font-size:11px; font-weight:700; background:#eef2f1; color:#586e6c; }
.kpi-badge svg { width:9px; height:9px; fill:currentColor; }
.badge-up { background:#e1f5ec; color:#12744a; }
.badge-down { background:#fdeadb; color:#b45f12; }
.kpi-value { font-size:32px; line-height:1.1; font-weight:700; letter-spacing:-.03em; margin-top:6px; font-variant-numeric:tabular-nums; }
.kpi-hint { font-size:12px; line-height:1.4; color:#586e6c; margin:0; min-height:17px; }
.kpi-spark, .kpi-spark-empty { display:block; width:100%; height:28px; margin-top:8px; }
.kpi-spark-empty { border-bottom:1px dashed #dce7e4; height:24px; }
.spark-line { fill:none; stroke:var(--accent); stroke-width:1.6; vector-effect:non-scaling-stroke; stroke-linejoin:round; }
.spark-area { fill:var(--accent); opacity:.12; }
.kpi-scope { font-size:11px; color:#7b918d; margin:6px 0 0; }
</style>
