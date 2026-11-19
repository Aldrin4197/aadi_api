<script setup>
import { computed } from 'vue';

const props = defineProps({
  role: { type: String, required: true, validator: value => ['user', 'landlord', 'vehicle-owner', 'raketeer'].includes(value) },
  verified: { type: Boolean, default: false },
  unverified: { type: Boolean, default: false },
  statusLabel: { type: String, default: '' },
  iconStyle: { type: String, default: 'outline', validator: value => ['outline', 'filled'].includes(value) },
  variant: { type: String, default: 'pill', validator: value => ['pill', 'emblem', 'icon'].includes(value) },
  size: { type: String, default: 'md', validator: value => ['sm', 'md', 'lg'].includes(value) },
});

const roles = {
  user: { label: 'User', offset: 6.5, color: '#0e7490', filled: 'M29 19a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z M13 37v-2c0-6 4-9 11-9s11 3 11 9v2Z', paths: ['M29 19a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z', 'M13 37v-2c0-6 4-9 11-9s11 3 11 9v2Z'] },
  landlord: { label: 'Landlord', offset: 7.5, filled: 'M24 10 40 23 37 26 35 24V38H13V24l-2 2-3-3Z M21 28V38H27V28Z', color: '#087f5b', paths: ['M10 23 24 12l14 11', 'M14 21v16h20V21', 'M21 37V27h6v10', 'M18 24h1m10 0h1'] },
  'vehicle-owner': { label: 'Vehicle owner', offset: 5.5, filled: 'M16 14H32L36 25V36H33V39H29V36H19V39H15V36H12V25Z M18 18 16 25H32L30 18Z M16 28V31H20V28Z M28 28V31H32V28Z', color: '#2563eb', paths: ['m13 24 3-9h16l3 9', 'M12 24h24v11H12z', 'M16 35v3m16-3v3', 'M17 29h2m10 0h2', 'M18 19h12'] },
  raketeer: { label: 'Raketeer', offset: 5.5, filled: 'M19 15H29A2 2 0 0 1 31 17V21H36V38H12V21H17V17A2 2 0 0 1 19 15Z M21 18V21H27V18Z M14 27V29H22V32H26V29H34V27H26V26H22V27Z', color: '#b45309', paths: ['M12 22h24v15H12z', 'M19 22v-6h10v6', 'M12 28c8 4 16 4 24 0', 'M24 28v5'] },
};
const data = computed(() => roles[props.role]);
const accessibleLabel = computed(() => props.unverified ? `${data.value?.label ?? ''}: ${props.statusLabel || 'not verified'}` : props.role === 'user'
  ? 'Verified user. Email verified; this is not an identity-document check.'
  : `Aadi verified ${data.value?.label.toLowerCase() ?? ''}`);
</script>

<template>
  <span v-if="(verified || unverified) && data" class="aadi-badge" :class="[`aadi-badge--${variant}`, `aadi-badge--${size}`, `aadi-badge--${unverified ? 'outline' : iconStyle}`, { 'aadi-badge--unverified': unverified }]"
    :style="{ '--ab-role': data.color }"
    role="img" :aria-label="accessibleLabel" :title="accessibleLabel">
    <svg class="aadi-badge__seal" viewBox="0 0 64 64" fill="none" aria-hidden="true">
      <path class="aadi-badge__crest" d="M32.00,4.10 L32.49,4.14 L32.97,4.25 L33.45,4.42 L33.91,4.66 L34.37,4.95 L34.81,5.28 L35.24,5.64 L35.65,6.01 L36.06,6.38 L36.45,6.74 L36.85,7.07 L37.24,7.36 L37.63,7.61 L38.03,7.81 L38.44,7.95 L38.87,8.03 L39.32,8.06 L39.78,8.05 L40.27,7.99 L40.77,7.90 L41.29,7.79 L41.83,7.67 L42.38,7.55 L42.93,7.46 L43.47,7.39 L44.01,7.37 L44.54,7.40 L45.04,7.48 L45.51,7.63 L45.95,7.84 L46.35,8.11 L46.72,8.45 L47.04,8.84 L47.32,9.28 L47.57,9.76 L47.79,10.27 L47.98,10.79 L48.16,11.32 L48.32,11.84 L48.49,12.35 L48.66,12.83 L48.85,13.28 L49.07,13.69 L49.32,14.06 L49.61,14.39 L49.94,14.68 L50.31,14.93 L50.72,15.15 L51.17,15.34 L51.65,15.51 L52.16,15.68 L52.68,15.84 L53.21,16.02 L53.73,16.21 L54.24,16.43 L54.72,16.68 L55.16,16.96 L55.55,17.28 L55.89,17.65 L56.16,18.05 L56.37,18.49 L56.52,18.96 L56.60,19.46 L56.63,19.99 L56.61,20.53 L56.54,21.07 L56.45,21.62 L56.33,22.17 L56.21,22.71 L56.10,23.23 L56.01,23.73 L55.95,24.22 L55.94,24.68 L55.97,25.13 L56.05,25.56 L56.19,25.97 L56.39,26.37 L56.64,26.76 L56.93,27.15 L57.26,27.55 L57.62,27.94 L57.99,28.35 L58.36,28.76 L58.72,29.19 L59.05,29.63 L59.34,30.09 L59.58,30.55 L59.75,31.03 L59.86,31.51 L59.90,32.00 L59.86,32.49 L59.75,32.97 L59.58,33.45 L59.34,33.91 L59.05,34.37 L58.72,34.81 L58.36,35.24 L57.99,35.65 L57.62,36.06 L57.26,36.45 L56.93,36.85 L56.64,37.24 L56.39,37.63 L56.19,38.03 L56.05,38.44 L55.97,38.87 L55.94,39.32 L55.95,39.78 L56.01,40.27 L56.10,40.77 L56.21,41.29 L56.33,41.83 L56.45,42.38 L56.54,42.93 L56.61,43.47 L56.63,44.01 L56.60,44.54 L56.52,45.04 L56.37,45.51 L56.16,45.95 L55.89,46.35 L55.55,46.72 L55.16,47.04 L54.72,47.32 L54.24,47.57 L53.73,47.79 L53.21,47.98 L52.68,48.16 L52.16,48.32 L51.65,48.49 L51.17,48.66 L50.72,48.85 L50.31,49.07 L49.94,49.32 L49.61,49.61 L49.32,49.94 L49.07,50.31 L48.85,50.72 L48.66,51.17 L48.49,51.65 L48.32,52.16 L48.16,52.68 L47.98,53.21 L47.79,53.73 L47.57,54.24 L47.32,54.72 L47.04,55.16 L46.72,55.55 L46.35,55.89 L45.95,56.16 L45.51,56.37 L45.04,56.52 L44.54,56.60 L44.01,56.63 L43.47,56.61 L42.93,56.54 L42.38,56.45 L41.83,56.33 L41.29,56.21 L40.77,56.10 L40.27,56.01 L39.78,55.95 L39.32,55.94 L38.87,55.97 L38.44,56.05 L38.03,56.19 L37.63,56.39 L37.24,56.64 L36.85,56.93 L36.45,57.26 L36.06,57.62 L35.65,57.99 L35.24,58.36 L34.81,58.72 L34.37,59.05 L33.91,59.34 L33.45,59.58 L32.97,59.75 L32.49,59.86 L32.00,59.90 L31.51,59.86 L31.03,59.75 L30.55,59.58 L30.09,59.34 L29.63,59.05 L29.19,58.72 L28.76,58.36 L28.35,57.99 L27.94,57.62 L27.55,57.26 L27.15,56.93 L26.76,56.64 L26.37,56.39 L25.97,56.19 L25.56,56.05 L25.13,55.97 L24.68,55.94 L24.22,55.95 L23.73,56.01 L23.23,56.10 L22.71,56.21 L22.17,56.33 L21.62,56.45 L21.07,56.54 L20.53,56.61 L19.99,56.63 L19.46,56.60 L18.96,56.52 L18.49,56.37 L18.05,56.16 L17.65,55.89 L17.28,55.55 L16.96,55.16 L16.68,54.72 L16.43,54.24 L16.21,53.73 L16.02,53.21 L15.84,52.68 L15.68,52.16 L15.51,51.65 L15.34,51.17 L15.15,50.72 L14.93,50.31 L14.68,49.94 L14.39,49.61 L14.06,49.32 L13.69,49.07 L13.28,48.85 L12.83,48.66 L12.35,48.49 L11.84,48.32 L11.32,48.16 L10.79,47.98 L10.27,47.79 L9.76,47.57 L9.28,47.32 L8.84,47.04 L8.45,46.72 L8.11,46.35 L7.84,45.95 L7.63,45.51 L7.48,45.04 L7.40,44.54 L7.37,44.01 L7.39,43.47 L7.46,42.93 L7.55,42.38 L7.67,41.83 L7.79,41.29 L7.90,40.77 L7.99,40.27 L8.05,39.78 L8.06,39.32 L8.03,38.87 L7.95,38.44 L7.81,38.03 L7.61,37.63 L7.36,37.24 L7.07,36.85 L6.74,36.45 L6.38,36.06 L6.01,35.65 L5.64,35.24 L5.28,34.81 L4.95,34.37 L4.66,33.91 L4.42,33.45 L4.25,32.97 L4.14,32.49 L4.10,32.00 L4.14,31.51 L4.25,31.03 L4.42,30.55 L4.66,30.09 L4.95,29.63 L5.28,29.19 L5.64,28.76 L6.01,28.35 L6.38,27.94 L6.74,27.55 L7.07,27.15 L7.36,26.76 L7.61,26.37 L7.81,25.97 L7.95,25.56 L8.03,25.13 L8.06,24.68 L8.05,24.22 L7.99,23.73 L7.90,23.23 L7.79,22.71 L7.67,22.17 L7.55,21.62 L7.46,21.07 L7.39,20.53 L7.37,19.99 L7.40,19.46 L7.48,18.96 L7.63,18.49 L7.84,18.05 L8.11,17.65 L8.45,17.28 L8.84,16.96 L9.28,16.68 L9.76,16.43 L10.27,16.21 L10.79,16.02 L11.32,15.84 L11.84,15.68 L12.35,15.51 L12.83,15.34 L13.28,15.15 L13.69,14.93 L14.06,14.68 L14.39,14.39 L14.68,14.06 L14.93,13.69 L15.15,13.28 L15.34,12.83 L15.51,12.35 L15.68,11.84 L15.84,11.32 L16.02,10.79 L16.21,10.27 L16.43,9.76 L16.68,9.28 L16.96,8.84 L17.28,8.45 L17.65,8.11 L18.05,7.84 L18.49,7.63 L18.96,7.48 L19.46,7.40 L19.99,7.37 L20.53,7.39 L21.07,7.46 L21.62,7.55 L22.17,7.67 L22.71,7.79 L23.23,7.90 L23.73,7.99 L24.22,8.05 L24.68,8.06 L25.13,8.03 L25.56,7.95 L25.97,7.81 L26.37,7.61 L26.76,7.36 L27.15,7.07 L27.55,6.74 L27.94,6.38 L28.35,6.01 L28.76,5.64 L29.19,5.28 L29.63,4.95 L30.09,4.66 L30.55,4.42 L31.03,4.25 L31.51,4.14 L32.00,4.10Z" />
      <circle cx="32" cy="32" r="21.5" stroke="currentColor" stroke-opacity=".22" />
      <g v-if="unverified || iconStyle === 'outline'" :transform="`translate(8 ${data.offset})`" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
        <path v-for="path in data.paths" :key="path" :d="path" />
      </g>
      <path v-else :d="data.filled" :transform="`translate(8 ${data.offset})`" fill="currentColor" fill-rule="evenodd"/>
      <circle v-if="!unverified" class="aadi-badge__check-disc" cx="51" cy="51" r="11" stroke="white" stroke-width="3" />
      <path v-if="!unverified" class="aadi-badge__check-mark" d="m46.5 51 3 3L56 47.5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span v-if="variant !== 'icon'" class="aadi-badge__copy" aria-hidden="true">
      <span class="aadi-badge__verified">{{ unverified ? statusLabel : variant === 'emblem' ? 'Aadi verified' : 'Verified' }}</span>
      <span class="aadi-badge__role">{{ data.label }}</span>
    </span>
  </span>
</template>

<style scoped>
.aadi-badge {
  --ab-ink: #52525b;
  --ab-border: #a1a1aa;
  --ab-surface: transparent;
  --ab-glyph: #52525b;
  --ab-check-bg: #52525b;
  --ab-check-ink: #fff;
  --ab-icon: 34px;
  --ab-font: 12px;
  --ab-seal: 96px;
  --ab-title: 17px;
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  gap: 7px;
  box-sizing: border-box;
  color: var(--ab-ink);
  font-family: inherit;
  font-size: var(--ab-font);
  line-height: 1;
  vertical-align: middle;
}
.aadi-badge--filled {
  --ab-ink: var(--ab-role);
  --ab-border: var(--ab-role);
  --ab-surface: var(--ab-role);
  --ab-glyph: #fff;
  --ab-check-bg: #fff;
  --ab-check-ink: var(--ab-role);
}
.aadi-badge--filled.aadi-badge--pill { color: #fff; }
.aadi-badge--unverified { opacity:.85; }
.aadi-badge__check-disc { fill: var(--ab-check-bg); }
.aadi-badge__check-mark { stroke: var(--ab-check-ink); }
.aadi-badge--sm { --ab-icon: 28px; --ab-font: 11px; --ab-seal: 76px; --ab-title: 15px; }
.aadi-badge--lg { --ab-icon: 40px; --ab-font: 14px; --ab-seal: 120px; --ab-title: 20px; }
.aadi-badge__seal { color: var(--ab-glyph); display: block; width: var(--ab-icon); height: var(--ab-icon); flex: 0 0 auto; overflow: visible; }
.aadi-badge__crest { fill: var(--ab-surface); stroke: currentColor; stroke-width: 1.2; }
.aadi-badge__copy { display: inline-flex; align-items: baseline; justify-content: center; gap: .32em; white-space: nowrap; }
.aadi-badge__verified, .aadi-badge__role { font-size: inherit; line-height: 1.25; }
.aadi-badge__verified { font-weight: 500; }
.aadi-badge__role { font-weight: 700; }
.aadi-badge--pill { padding: 3px 13px 3px 4px; border: 1px solid var(--ab-border); background: var(--ab-surface); border-radius: 999px; }
.aadi-badge--emblem { flex-direction: column; gap: 14px; text-align: center; }
.aadi-badge--emblem .aadi-badge__seal { width: var(--ab-seal); height: var(--ab-seal); }
.aadi-badge--emblem .aadi-badge__copy { flex-direction: column; align-items: center; gap: 5px; }
.aadi-badge--emblem .aadi-badge__verified { font-size: var(--ab-font); }
.aadi-badge--emblem .aadi-badge__role { font-size: var(--ab-title); letter-spacing: -.02em; }
@media (forced-colors: active) {
  .aadi-badge { color: CanvasText; border-color: CanvasText; }
  .aadi-badge__seal { color: CanvasText; }
  .aadi-badge__crest { fill: Canvas; stroke: CanvasText; }
  .aadi-badge--filled.aadi-badge--pill { color: CanvasText; background: Canvas; }
  .aadi-badge__check-disc { fill: CanvasText; stroke: Canvas; }
  .aadi-badge__check-mark { stroke: Canvas; }
}
</style>
