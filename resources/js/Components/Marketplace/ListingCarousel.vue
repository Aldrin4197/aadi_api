<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, computed, useId, watch } from 'vue';

const props = defineProps<{ resetKey: string; visible?: boolean; selectedId?: string | null }>();
const id = useId();
const mode = ref('carousel');
const position = ref(1), total = ref(0);
const modes = [{key:'carousel', label:'Carousel', icon:'M3 5h5v14H3zM11 5h10v14H11z'}, {key:'list', label:'List', icon:'M8 6h13M8 12h13M8 18h13M3 6h1M3 12h1M3 18h1'}, {key:'single', label:'Swipe', icon:'M7 4h10v16H7zM3 9l-2 3 2 3M21 9l2 3-2 3'}];
const description = computed(() => mode.value === 'list' ? 'Matching listings' : 'Listing cards. Use left and right arrow keys to browse.');
function revealSelected() {
    const element = rail.value;
    const card = Array.from(element?.children || []).find(child => child.id === 'map-card-' + props.selectedId) as HTMLElement | undefined;
    if (!element || !card) return;
    if (mode.value === 'list') element.scrollTo({top:card.offsetTop - element.offsetTop, behavior:'auto'});
    else element.scrollTo({left:card.offsetLeft - element.offsetLeft - parseFloat(getComputedStyle(element).paddingLeft), behavior:'auto'});
}

const rail = ref<HTMLElement>();
const previous = ref<HTMLButtonElement>();
const next = ref<HTMLButtonElement>();
const canPrevious = ref(false), canNext = ref(false);
let observer: ResizeObserver | undefined;

function measure() {
    const element = rail.value;
    if (!element) return;
    total.value = element.children.length;
    const card = element.firstElementChild as HTMLElement | null;
    const step = card ? card.getBoundingClientRect().width + parseFloat(getComputedStyle(element).gap || '0') : 1;
    position.value = Math.min(total.value, Math.max(1, Math.round(element.scrollLeft / step) + 1));
    Array.from(element.children).forEach((child, index) => { (child as HTMLElement).inert = mode.value === 'single' && index !== position.value - 1; });
    canPrevious.value = element.scrollLeft > 2;
    canNext.value = element.scrollWidth - element.clientWidth - element.scrollLeft > 2;
    if ((!canPrevious.value && document.activeElement === previous.value) ||
        (!canNext.value && document.activeElement === next.value)) element.focus({ preventScroll: true });
}
function move(direction: number) {
    const element = rail.value;
    if (!element) return;
    const card = element.firstElementChild as HTMLElement | null;
    const step = card ? card.getBoundingClientRect().width + parseFloat(getComputedStyle(element).gap || '0') : element.clientWidth;
    const count = mode.value === 'single' ? 1 : Math.max(1, Math.floor(element.clientWidth / step));
    element.scrollBy({ left: direction * step * count, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
}
function keydown(event: KeyboardEvent) {
    // Preserve arrow-key behavior within card links, checkboxes and other controls.
    if (event.target !== rail.value) return;
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault(); move(event.key === 'ArrowLeft' ? -1 : 1);
    }
}
async function refresh(reset = false) {
    await nextTick();
    if (reset) rail.value?.scrollTo({ left: 0, behavior: 'instant' });
    observer?.disconnect();
    if (rail.value) {
        observer?.observe(rail.value);
        Array.from(rail.value.children).forEach(child => observer?.observe(child));
    }
    measure();
}
onMounted(() => {
    try { const saved = localStorage.getItem('aadi-listing-view'); if (modes.some(item => item.key === saved)) mode.value = saved!; } catch {}
    observer = new ResizeObserver(measure); refresh();
});
watch(mode, async () => { try { localStorage.setItem('aadi-listing-view', mode.value); } catch {} await refresh(true); revealSelected(); });
watch(() => props.selectedId, async () => { await nextTick(); revealSelected(); });
watch(() => props.resetKey, () => refresh(true));
watch(() => props.visible, () => refresh());
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div class="listing-carousel" :class="'view-' + mode" role="region" aria-label="Matching listing cards">
        <div class="view-toolbar"><div class="view-modes" role="group" aria-label="Listing view"><button v-for="view in modes" :key="view.key" type="button" :aria-pressed="mode === view.key" :aria-controls="id" @click="mode = view.key"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path :d="view.icon"/></svg>{{ view.label }}</button></div><span v-if="mode === 'single'" class="view-position" aria-live="polite">{{ position }} / {{ total }}</span></div>
        <div :id="id" ref="rail" class="map-card-rail carousel-rail" tabindex="0"
            :aria-label="description" @scroll.passive="measure" @keydown="keydown">
            <slot />
        </div>
        <button v-show="mode !== 'list' && canPrevious" ref="previous" type="button" class="carousel-arrow carousel-previous"
            aria-label="Show previous listings" :aria-controls="id" @click="move(-1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14 6-6 6 6 6" /></svg>
        </button>
        <button v-show="mode !== 'list' && canNext" ref="next" type="button" class="carousel-arrow carousel-next"
            aria-label="Show more listings" :aria-controls="id" @click="move(1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m10 6 6 6-6 6" /></svg>
        </button>
    </div>
</template>

<style scoped>
.listing-carousel { position: relative; }
.view-toolbar { display:flex; align-items:center; gap:12px; margin:0 24px 8px; }
.view-modes { display:flex; padding:3px; border:1px solid #dde2e9; border-radius:12px; background:#fff; }
.view-modes button { display:flex; align-items:center; gap:6px; min-height:44px; padding:8px 12px; border-radius:9px; font-size:12px; font-weight:700; color:#0b3a38; }
.view-modes svg { width:18px; height:18px; }
.view-modes button[aria-pressed=true] { background:#fff3bf; }
.view-position { background:white; border-radius:12px; padding:10px; font-size:12px; font-variant-numeric:tabular-nums; }
.view-single { max-width:520px; margin-inline:auto; }
.view-single .carousel-rail { scroll-snap-type:x mandatory; }
.view-single :deep(.map-listing) { flex:0 0 100%; max-width:none; scroll-snap-stop:always; }
.view-list .carousel-rail { display:grid; gap:10px; max-height:280px; overflow-y:auto; overscroll-behavior:contain; scroll-snap-type:none; scrollbar-width:thin; max-width:600px; }
.view-list :deep(.map-listing) { width:100%; max-width:none; grid-template-columns:90px minmax(0,1fr); }
:deep(.map-card-actions) { flex-wrap:wrap; }
:deep(.map-card-actions .listing-view-button) { flex-basis:100%; }
@media(max-width:700px) { .view-toolbar { margin-inline:12px; } .view-list .carousel-rail { max-height:245px; } }

.carousel-rail { scrollbar-width: none; -ms-overflow-style: none; overscroll-behavior-x: contain; }
.carousel-rail::-webkit-scrollbar { display: none; }
.carousel-arrow { position: absolute; top: 50%; transform: translateY(-50%); z-index: 2; display: grid; place-items: center; width: 44px; height: 44px; border: 1px solid #ffffffdf; border-radius: 50%; background: #ffffffd9; color: var(--aadi-dark, #0b3a38); box-shadow: 0 4px 18px #0b3a3833; backdrop-filter: blur(14px) saturate(140%); -webkit-backdrop-filter: blur(14px) saturate(140%); transition: background .15s ease, box-shadow .15s ease; }
.carousel-arrow:hover { background: #e3f6f4f2; box-shadow: 0 5px 20px #0b3a3840; }
.carousel-arrow svg { width: 22px; height: 22px; }
.carousel-previous { left: 8px; }
.carousel-next { right: 8px; }
@media (prefers-reduced-motion: reduce) { .carousel-arrow { transition: none; } }
</style>
