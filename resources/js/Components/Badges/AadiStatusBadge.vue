<script setup>
import { computed } from 'vue';
import { statusBadges } from './statusBadges.js';
const props = defineProps({
  type: { type: String, required: true, validator: value => Object.hasOwn(statusBadges, value) },
  active: { type: Boolean, default: false },
  iconStyle: { type: String, default: 'filled', validator: value => ['outline', 'filled'].includes(value) },
  variant: { type: String, default: 'pill', validator: value => ['pill', 'emblem', 'icon'].includes(value) },
  size: { type: String, default: 'md', validator: value => ['sm', 'md', 'lg'].includes(value) },
});
const badge = computed(() => Object.hasOwn(statusBadges, props.type) ? statusBadges[props.type] : null);
</script>

<template>
  <span v-if="active && badge" class="aadi-status" :class="[`aadi-status--${iconStyle}`, `aadi-status--${variant}`, `aadi-status--${size}`]" :style="{ '--as-color': badge.color }" role="img" :aria-label="badge.label" :title="badge.label">
    <svg class="aadi-status__mark" viewBox="0 0 64 64" fill="none" aria-hidden="true">
      <rect class="aadi-status__tile" x="6" y="6" width="52" height="52" rx="15" stroke-width="1.5"/>
      <g class="aadi-status__glyph" transform="translate(14 14) scale(1.5)" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path v-for="path in badge.paths" :key="path" :d="path"/>
      </g>
    </svg>
    <span v-if="variant !== 'icon'" class="aadi-status__label" aria-hidden="true">{{ badge.label }}</span>
  </span>
</template>

<style scoped>
.aadi-status {
  --as-icon: 34px; --as-font: 12px; --as-emblem: 96px; --as-title: 17px;
  display: inline-flex; flex: 0 0 auto; align-items: center; justify-content: center; gap: 7px;
  color: #52525b; font-family: inherit; font-size: var(--as-font); line-height: 1;
  vertical-align: middle; box-sizing: border-box;
}
.aadi-status--sm { --as-icon: 28px; --as-font: 11px; --as-emblem: 76px; --as-title: 15px; }
.aadi-status--lg { --as-icon: 40px; --as-font: 14px; --as-emblem: 120px; --as-title: 20px; }
.aadi-status__mark { display: block; width: var(--as-icon); height: var(--as-icon); flex: 0 0 auto; }
.aadi-status__tile { fill: transparent; stroke: currentColor; }
.aadi-status__glyph { stroke: currentColor; }
.aadi-status__label { white-space: nowrap; font-weight: 700; line-height: 1.25; }
.aadi-status--pill { border: 1px solid #a1a1aa; border-radius: 999px; padding: 3px 13px 3px 4px; }
.aadi-status--filled { color: var(--as-color); }
.aadi-status--filled .aadi-status__tile { fill: var(--as-color); stroke: var(--as-color); }
.aadi-status--filled .aadi-status__glyph { stroke: white; }
.aadi-status--filled.aadi-status--pill { color: white; background: var(--as-color); border-color: var(--as-color); }
.aadi-status--filled.aadi-status--pill .aadi-status__tile { stroke: white; stroke-opacity: .5; }
.aadi-status--emblem { flex-direction: column; gap: 14px; }
.aadi-status--emblem .aadi-status__mark { width: var(--as-emblem); height: var(--as-emblem); }
.aadi-status--emblem .aadi-status__label { font-size: var(--as-title); }
@media (forced-colors: active) {
  .aadi-status, .aadi-status--filled.aadi-status--pill { color: CanvasText; background: Canvas; border-color: CanvasText; }
  .aadi-status .aadi-status__tile { fill: Canvas; stroke: CanvasText; }
  .aadi-status .aadi-status__glyph { stroke: CanvasText; }
}
</style>
