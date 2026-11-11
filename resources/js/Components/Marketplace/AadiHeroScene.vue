<script setup>
import { ref, watch, onMounted, onBeforeUnmount, onActivated, onDeactivated } from 'vue';

const props = defineProps({
    category: { type: String, default: 'spaces' },
    fallbackSrc: { type: String, required: true },
    label: { type: String, default: 'Aadi rental scene' },
});

const canvas = ref(null);
const ready = ref(false);
const paused = ref(false);
const reduced = ref(false);
let controller = null;
let disposed = false;
let suspended = false;

watch(() => props.category, value => controller?.setCategory(value));

onMounted(async () => {
    try {
        // Vite creates a separate chunk; browser-only WebGL setup also supports Inertia SSR.
        const { createAadiHeroScene } = await import('./aadiHeroScene.js');
        if (disposed || !canvas.value) return;
        controller = createAadiHeroScene({
            canvas: canvas.value,
            category: props.category,
            onMotionChange(state) {
                if (disposed) return;
                paused.value = state.paused;
                reduced.value = state.reduced;
            },
            onError(error) {
                ready.value = false;
                controller?.dispose();
                controller = null;
                console.warn('[AADI hero] Showing the illustration fallback.', error);
            },
        });
        controller.setSuspended(suspended);
        ready.value = true;
    } catch (error) {
        controller?.dispose();
        controller = null;
        ready.value = false;
        console.warn('[AADI hero] Showing the illustration fallback.', error);
    }
});

onActivated(() => { suspended = false; controller?.setSuspended(false); });
onDeactivated(() => { suspended = true; controller?.setSuspended(true); });
onBeforeUnmount(() => { disposed = true; controller?.dispose(); controller = null; });
</script>

<template>
    <div class="aadi-3d-scene">
        <canvas
            ref="canvas"
            class="aadi-3d-canvas"
            :class="{ 'is-ready': ready }"
            :tabindex="ready ? 0 : -1"
            :aria-hidden="!ready"
            :aria-label="`${label}. Drag or use Left and Right arrow keys to turn. Press Home to reset.`"
        />
        <img
            v-if="!ready"
            class="aadi-3d-fallback"
            :src="fallbackSrc"
            :alt="`${label} illustration`"
            width="480"
            height="300"
            fetchpriority="high"
        />
        <div v-if="ready" class="aadi-3d-controls">
            <span>Drag to turn</span>
            <button
                type="button"
                :aria-pressed="paused || reduced"
                :disabled="reduced"
                @click="controller?.togglePaused()"
            >{{ reduced ? 'Motion reduced' : paused ? 'Resume motion' : 'Pause motion' }}</button>
        </div>
    </div>
</template>

<style scoped>
.aadi-3d-scene{position:relative;display:block;width:100%;min-width:0;aspect-ratio:1.18;isolation:isolate}
.aadi-3d-canvas{position:absolute;inset:0;display:block!important;width:100%!important;height:100%!important;max-width:none;opacity:0;cursor:grab;touch-action:pan-y;outline:none}
.aadi-3d-canvas.is-ready{opacity:1}
.aadi-3d-canvas.is-dragging{cursor:grabbing}
.aadi-3d-canvas:focus-visible{outline:2px solid #50b8af;outline-offset:-5px;border-radius:20px}
.aadi-3d-fallback{position:absolute;inset:0;display:block!important;width:100%!important;height:100%!important;object-fit:contain}
.aadi-3d-controls{position:absolute;z-index:1;top:8px;right:10px;display:flex;align-items:center;gap:12px;color:#637b76;font-size:11px}
.aadi-3d-controls button{min-height:44px;padding:7px 10px;border:1px solid #dbe5df;border-radius:999px;color:#42665e;background:#ffffffed;font:inherit;font-weight:650;cursor:pointer}
.aadi-3d-controls button:focus-visible{outline:2px solid #137671;outline-offset:2px}
.aadi-3d-controls button:disabled{opacity:.6;cursor:default}
@media(max-width:700px){.aadi-3d-scene{aspect-ratio:1.1}.aadi-3d-controls{font-size:10px}}
</style>
