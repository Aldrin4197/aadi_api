<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';

const props = defineProps<{ resetKey: string; visible?: boolean }>();
const id = useId();
const rail = ref<HTMLElement>();
const previous = ref<HTMLButtonElement>();
const next = ref<HTMLButtonElement>();
const canPrevious = ref(false), canNext = ref(false);
let observer: ResizeObserver | undefined;

function measure() {
    const element = rail.value;
    if (!element) return;
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
    const count = Math.max(1, Math.floor(element.clientWidth / step));
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
onMounted(() => { observer = new ResizeObserver(measure); refresh(); });
watch(() => props.resetKey, () => refresh(true));
watch(() => props.visible, () => refresh());
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div class="listing-carousel" role="region" aria-roledescription="carousel" aria-label="Matching listing cards">
        <div :id="id" ref="rail" class="map-card-rail carousel-rail" tabindex="0"
            aria-label="Listing cards. Use left and right arrow keys to browse." @scroll.passive="measure" @keydown="keydown">
            <slot />
        </div>
        <button v-show="canPrevious" ref="previous" type="button" class="carousel-arrow carousel-previous"
            aria-label="Show previous listings" :aria-controls="id" @click="move(-1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14 6-6 6 6 6" /></svg>
        </button>
        <button v-show="canNext" ref="next" type="button" class="carousel-arrow carousel-next"
            aria-label="Show more listings" :aria-controls="id" @click="move(1)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m10 6 6 6-6 6" /></svg>
        </button>
    </div>
</template>

<style scoped>
.listing-carousel { position: relative; }
.carousel-rail { scrollbar-width: none; -ms-overflow-style: none; overscroll-behavior-x: contain; }
.carousel-rail::-webkit-scrollbar { display: none; }
.carousel-arrow { position: absolute; top: 50%; transform: translateY(-50%); z-index: 2; display: grid; place-items: center; width: 44px; height: 44px; border: 1px solid #ffffffdf; border-radius: 50%; background: #ffffffd9; color: var(--aadi-dark, #0b3a38); box-shadow: 0 4px 18px #0b3a3833; backdrop-filter: blur(14px) saturate(140%); -webkit-backdrop-filter: blur(14px) saturate(140%); transition: background .15s ease, box-shadow .15s ease; }
.carousel-arrow:hover { background: #e3f6f4f2; box-shadow: 0 5px 20px #0b3a3840; }
.carousel-arrow svg { width: 22px; height: 22px; }
.carousel-previous { left: 8px; }
.carousel-next { right: 8px; }
@media (prefers-reduced-motion: reduce) { .carousel-arrow { transition: none; } }
</style>
