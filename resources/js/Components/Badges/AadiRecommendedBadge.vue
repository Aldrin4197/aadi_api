<script setup>
defineProps({
  active: { type: Boolean, default: false },
  iconStyle: { type: String, default: 'filled', validator: value => ['outline', 'filled'].includes(value) },
  variant: { type: String, default: 'pill', validator: value => ['pill', 'emblem', 'icon'].includes(value) },
  size: { type: String, default: 'md', validator: value => ['sm', 'md', 'lg'].includes(value) },
});
</script>

<template>
  <span v-if="active" class="aadi-recommended" :class="[`aadi-recommended--${iconStyle}`, `aadi-recommended--${variant}`, `aadi-recommended--${size}`]" role="img" aria-label="Aadi Recommended" title="Aadi Recommended">
    <svg class="aadi-recommended__mark" viewBox="0 0 64 64" fill="none" aria-hidden="true">
      <circle class="aadi-recommended__medallion" cx="32" cy="32" r="27" stroke-width="1.6"/>
      <circle cx="32" cy="32" r="23" class="aadi-recommended__ring" stroke-width=".9"/>
      <path class="aadi-recommended__star" d="m32 17 4.6 10 10.9 1.3-8.1 7.4 2.1 10.8L32 41l-9.5 5.5 2.1-10.8-8.1-7.4L27.4 27Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span v-if="variant !== 'icon'" class="aadi-recommended__label" aria-hidden="true"><span class="aadi-recommended__brand">Aadi</span><span class="aadi-recommended__edition">Recommended</span></span>
  </span>
</template>

<style scoped>
.aadi-recommended {
  --ar-color: #a16207;
  --ar-icon: 34px;
  --ar-font: 12px;
  --ar-emblem: 96px;
  --ar-title: 17px;
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  gap: 7px;
  color: #52525b;
  font-family: inherit;
  font-size: var(--ar-font);
  line-height: 1;
  vertical-align: middle;
  box-sizing: border-box;
}
.aadi-recommended--sm { --ar-icon: 28px; --ar-font: 11px; --ar-emblem: 76px; --ar-title: 15px; }
.aadi-recommended--lg { --ar-icon: 40px; --ar-font: 14px; --ar-emblem: 120px; --ar-title: 20px; }
.aadi-recommended__mark { display: block; width: var(--ar-icon); height: var(--ar-icon); flex: 0 0 auto; }
.aadi-recommended__medallion { fill: transparent; stroke: currentColor; }
.aadi-recommended__star { fill: none; stroke: currentColor; }
.aadi-recommended__ring { stroke: currentColor; opacity: .3; }
.aadi-recommended--filled .aadi-recommended__star { fill: white; stroke: white; }
.aadi-recommended--filled .aadi-recommended__ring { stroke: white; opacity: .45; }
.aadi-recommended__label { display: inline-flex; align-items: baseline; gap: .3em; white-space: nowrap; line-height: 1.25; }
.aadi-recommended__brand { font-weight: 500; }
.aadi-recommended__edition { font-weight: 800; }
.aadi-recommended--pill { border: 1px solid #a1a1aa; border-radius: 999px; padding: 3px 13px 3px 4px; }
.aadi-recommended--filled { color: var(--ar-color); }
.aadi-recommended--filled .aadi-recommended__medallion { fill: var(--ar-color); stroke: var(--ar-color); }
.aadi-recommended--filled.aadi-recommended--pill { color: white; background: var(--ar-color); border-color: var(--ar-color); }
.aadi-recommended--filled.aadi-recommended--pill .aadi-recommended__medallion { stroke: white; stroke-opacity: .5; }
.aadi-recommended--emblem { flex-direction: column; gap: 14px; }
.aadi-recommended--emblem .aadi-recommended__mark { width: var(--ar-emblem); height: var(--ar-emblem); }
.aadi-recommended--emblem .aadi-recommended__label { flex-direction: column; align-items: center; gap: 4px; font-size: var(--ar-title); }
.aadi-recommended--emblem .aadi-recommended__brand { font-size: var(--ar-font); }
@media (forced-colors: active) {
  .aadi-recommended, .aadi-recommended--filled.aadi-recommended--pill { color: CanvasText; background: Canvas; border-color: CanvasText; }
  .aadi-recommended .aadi-recommended__medallion { fill: Canvas; stroke: CanvasText; }
  .aadi-recommended .aadi-recommended__star { stroke: CanvasText; }
  .aadi-recommended--filled .aadi-recommended__star { fill: CanvasText; }
  .aadi-recommended .aadi-recommended__ring { stroke: CanvasText; }
}
</style>
