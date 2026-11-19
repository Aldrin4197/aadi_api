<script setup>
import { computed } from 'vue';
import AadiVerifiedBadge from '@/Components/Badges/AadiVerifiedBadge.vue';

const props = defineProps({
    service: { type: String, required: true },
    status: { type: String, default: 'none' },
    // Icon-only version for dense tables; the full name and status stay available as a tooltip and to screen readers.
    compact: { type: Boolean, default: false },
});

const roles = {
    spaces: { label: 'Property', path: 'M10 23 24 12l14 11M14 21v16h20V21M21 37V27h6v10M18 24h1m10 0h1' },
    haul: { label: 'Vehicle', path: 'm13 24 3-9h16l3 9M12 24h24v11H12zM16 35v3m16-3v3M17 29h2m10 0h2M18 19h12' },
    raketeer: { label: 'Raket', path: 'M12 22h24v15H12zM19 22v-6h10v6M12 28c8 4 16 4 24 0M24 28v5' },
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
const badgeRole = { spaces: 'landlord', haul: 'vehicle-owner', raketeer: 'raketeer' };
const text = computed(() => `${role.value.label} role: ${state.value.label}`);
</script>

<template>
    <AadiVerifiedBadge v-if="key === 'approved'" :role="badgeRole[service]" verified icon-style="filled" :variant="compact ? 'icon' : 'pill'" :size="compact ? 'md' : 'sm'" />
    <span v-else class="role-wrap" :class="`is-${key}`">
        <AadiVerifiedBadge :role="badgeRole[service]" unverified :status-label="state.label" :variant="compact ? 'icon' : 'pill'" :size="compact ? 'md' : 'sm'" />
        <span v-if="compact && key !== 'none'" class="dot" aria-hidden="true"><svg viewBox="0 0 24 24"><path :d="state.mark" /></svg></span>
    </span>
</template>

<style scoped>
.role-wrap { --fg:#8a9d99; position:relative; display:inline-flex; vertical-align:middle; }
.is-pending { --fg:#8a5a00; }
.is-rejected { --fg:#b42318; }
.dot { position:absolute; right:-3px; bottom:-3px; display:grid; place-items:center; width:14px; height:14px; border-radius:50%; background:var(--fg); border:2px solid #fff; box-sizing:content-box; }
.dot svg { width:9px; height:9px; fill:none; stroke:#fff; stroke-width:3.2; stroke-linecap:round; stroke-linejoin:round; }
</style>
