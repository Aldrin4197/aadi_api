<script setup>
defineProps({
  active: { type: Boolean, default: false },
  iconStyle: { type: String, default: 'filled', validator: value => ['outline', 'filled'].includes(value) },
  variant: { type: String, default: 'pill', validator: value => ['pill', 'emblem', 'icon'].includes(value) },
  size: { type: String, default: 'md', validator: value => ['sm', 'md', 'lg'].includes(value) },
});
</script>

<template>
  <span v-if="active" class="aadi-plus" :class="[`aadi-plus--${iconStyle}`, `aadi-plus--${variant}`, `aadi-plus--${size}`]" role="img" aria-label="Aadi Plus" title="Aadi Plus">
    <svg class="aadi-plus__mark" viewBox="0 0 64 64" fill="none" aria-hidden="true">
      <rect class="aadi-plus__diamond" x="12" y="12" width="40" height="40" rx="9" transform="rotate(45 32 32)" stroke-width="1.6"/>
      <path v-if="iconStyle === 'outline'" d="M32 22v20M22 32h20" class="aadi-plus__glyph" stroke-width="3" stroke-linecap="round"/>
      <path v-else d="M29 22a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v7h7a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-7v7a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-7h-7a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h7Z" fill="white"/>
    </svg>
    <span v-if="variant !== 'icon'" class="aadi-plus__label" aria-hidden="true"><span class="aadi-plus__brand">Aadi</span><span class="aadi-plus__edition">Plus</span></span>
  </span>
</template>

<style scoped>
.aadi-plus {
  --ap-color: #6d28d9;
  --ap-icon: 34px;
  --ap-font: 12px;
  --ap-emblem: 96px;
  --ap-title: 17px;
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  gap: 7px;
  color: #52525b;
  font-family: inherit;
  font-size: var(--ap-font);
  line-height: 1;
  vertical-align: middle;
  box-sizing: border-box;
}
.aadi-plus--sm { --ap-icon: 28px; --ap-font: 11px; --ap-emblem: 76px; --ap-title: 15px; }
.aadi-plus--lg { --ap-icon: 40px; --ap-font: 14px; --ap-emblem: 120px; --ap-title: 20px; }
.aadi-plus__mark { display: block; width: var(--ap-icon); height: var(--ap-icon); flex: 0 0 auto; }
.aadi-plus__diamond { fill: transparent; stroke: currentColor; }
.aadi-plus__glyph { stroke: currentColor; }
.aadi-plus__label { display: inline-flex; align-items: baseline; gap: .3em; white-space: nowrap; line-height: 1.25; }
.aadi-plus__brand { font-weight: 500; }
.aadi-plus__edition { font-weight: 800; }
.aadi-plus--pill { border: 1px solid #a1a1aa; border-radius: 999px; padding: 3px 13px 3px 4px; }
.aadi-plus--filled { color: var(--ap-color); }
.aadi-plus--filled .aadi-plus__diamond { fill: var(--ap-color); stroke: var(--ap-color); }
.aadi-plus--filled.aadi-plus--pill { color: white; background: var(--ap-color); border-color: var(--ap-color); }
.aadi-plus--filled.aadi-plus--pill .aadi-plus__diamond { stroke: white; stroke-opacity: .5; }
.aadi-plus--emblem { flex-direction: column; gap: 14px; }
.aadi-plus--emblem .aadi-plus__mark { width: var(--ap-emblem); height: var(--ap-emblem); }
.aadi-plus--emblem .aadi-plus__label { font-size: var(--ap-title); }
@media (forced-colors: active) {
  .aadi-plus, .aadi-plus--filled.aadi-plus--pill { color: CanvasText; background: Canvas; border-color: CanvasText; }
  .aadi-plus .aadi-plus__diamond { fill: Canvas; stroke: CanvasText; }
  .aadi-plus--filled path { fill: CanvasText; }
}
</style>
