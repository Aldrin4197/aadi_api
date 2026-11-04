<script setup>
import { computed } from 'vue';

const props = defineProps({
    service: { type: String, required: true },
    status: { type: String, default: 'none' },
    // Icon-only version for dense tables; the full name and status stay available as a tooltip and to screen readers.
    compact: { type: Boolean, default: false },
});

const roles = {
    spaces: { label: 'Property', path: 'm3 11 9-8 9 8M5 9v12h14V9M9 21v-8h6v8' },
    haul: { label: 'Vehicle', path: 'M3 5h12v12H3zM15 9h4l3 4v4h-7M5 17v3h3v-3M17 17v3h3v-3' },
    raketeer: { label: 'Raket', path: 'M3 7h18v14H3zM8 7V3h8v4M3 12l9 4 9-4' },
};
const states = {
    approved: { label: 'Approved', mark: 'M5 12.5l4.2 4.2L19 7' },
    pending: { label: 'Pending', mark: 'M12 7v5l3 2' },
    rejected: { label: 'Rejected', mark: 'M7 7l10 10M17 7 7 17' },
    draft: { label: 'Draft', mark: 'M6 12h12' },
    none: { label: 'Not applied', mark: 'M7 12h10' },
};
const role = computed(() => roles[props.service]);
const state = computed(() => states[props.status] ?? states.none);
const key = computed(() => (states[props.status] ? props.status : 'none'));
const text = computed(() => `${role.value.label} role: ${state.value.label}`);
</script>

<template>
    <span v-if="compact" class="role-icon" :class="`is-${key}`" role="img" :aria-label="text" :title="text">
        <svg class="glyph" viewBox="0 0 24 24" aria-hidden="true"><path :d="role.path" /></svg>
        <span v-if="key !== 'none'" class="dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path :d="state.mark" /></svg></span>
    </span>
    <span v-else class="role-status" :class="`is-${key}`" role="img" :aria-label="text" :title="text">
        <svg class="role-glyph" viewBox="0 0 24 24" aria-hidden="true"><path :d="role.path" /></svg>
        <span class="role-name">{{ role.label }}</span>
        <span class="role-state"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="state.mark" /></svg>{{ state.label }}</span>
    </span>
</template>

<style scoped>
.role-status, .role-icon { --fg:#8a9d99; --bg:#f1f4f3; --line:#e1e8e6; }
.is-approved { --fg:#12744a; --bg:#e1f5ec; --line:#b9e3cf; }
.is-pending { --fg:#8a5a00; --bg:#fff3d6; --line:#f2dba0; }
.is-rejected { --fg:#b42318; --bg:#fdeaea; --line:#f3c3c0; }

.role-icon { position:relative; display:inline-grid; place-items:center; width:36px; height:36px; border-radius:10px; background:var(--bg); border:1px solid var(--line); color:var(--fg); flex-shrink:0; }
.role-icon.is-none { background:#fff; border-style:dashed; }
.role-icon .glyph { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.role-icon .dot { position:absolute; right:-5px; bottom:-5px; display:grid; place-items:center; width:15px; height:15px; border-radius:50%; background:var(--fg); border:2px solid #fff; box-sizing:content-box; }
.role-icon .dot svg { width:9px; height:9px; fill:none; stroke:#fff; stroke-width:3.2; stroke-linecap:round; stroke-linejoin:round; }

.role-status { display:inline-flex; align-items:stretch; border:1px solid var(--line); border-radius:9px; overflow:hidden; font-size:12px; line-height:1; background:#fff; white-space:nowrap; }
.role-glyph { width:30px; padding:8px 7px; box-sizing:border-box; background:var(--bg); fill:none; stroke:var(--fg); stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }
.role-name { display:inline-flex; align-items:center; padding:0 9px 0 8px; font-weight:700; color:#183e3b; }
.role-state { display:inline-flex; align-items:center; gap:4px; padding:0 10px 0 8px; margin:3px; border-radius:6px; background:var(--bg); color:var(--fg); font-weight:700; }
.role-state svg { width:12px; height:12px; fill:none; stroke:currentColor; stroke-width:2.6; stroke-linecap:round; stroke-linejoin:round; }
</style>
