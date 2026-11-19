<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { currentToast, dismissToast, showToast } from './toast.mjs';

const page = usePage();
const target = ref('body');
let observer;
let removeSuccess;
let removeError;

function notify(event) { showToast(event.detail?.message, event.detail?.kind); }
function updateTarget() {
    const dialogs = [...document.querySelectorAll('dialog[open]')];
    target.value = dialogs.at(-1) || 'body';
}
onMounted(() => {
    updateTarget();
    observer = new MutationObserver(updateTarget);
    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['open'] });
    window.addEventListener('aadi:toast', notify);
    removeSuccess = router.on('success', event => showToast(event.detail.page.props.flash?.success));
    removeError = router.on('error', () => showToast('Please check the highlighted fields and try again.', 'error'));
    showToast(page.props.flash?.success);
});
onBeforeUnmount(() => {
    dismissToast();
    observer?.disconnect();
    removeSuccess?.();
    removeError?.();
    window.removeEventListener('aadi:toast', notify);
});
</script>

<template>
    <Teleport :to="target">
        <div class="app-toast-region" aria-live="polite">
            <div v-if="currentToast" class="app-toast" :class="currentToast.kind" :role="currentToast.kind === 'error' ? 'alert' : 'status'">
                <span class="app-toast-icon" aria-hidden="true">
                    <svg v-if="currentToast.kind === 'error'" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><path d="M12 7v6M12 17h.01" /></svg>
                    <svg v-else viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><path d="m8 12 2.5 2.5L16 9" /></svg>
                </span>
                <span class="app-toast-message">{{ currentToast.message }}</span>
                <button type="button" aria-label="Dismiss notification" @click="dismissToast">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.app-toast-region { position:fixed; z-index:3000; right:max(16px, env(safe-area-inset-right, 0px)); bottom:calc(16px + env(safe-area-inset-bottom, 0px)); width:min(400px, calc(100vw - 32px)); pointer-events:none; }
.app-toast { display:flex; align-items:center; gap:12px; padding:12px 12px 12px 14px; border:1px solid #cfe5da; border-radius:12px; background:#fff; color:#193e3b; box-shadow:0 12px 32px rgb(25 62 59 / .18); font:600 14px/1.5 sans-serif; pointer-events:auto; }
.app-toast.error { border-color:#efcec9; color:#8d2921; }
.app-toast-icon { display:grid; place-items:center; flex:none; width:34px; height:34px; border-radius:10px; background:#e4f4ed; color:#137671; }
.app-toast.error .app-toast-icon { background:#fcece9; color:#a4352b; }
.app-toast-icon svg { width:20px; height:20px; }
.app-toast svg { fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.app-toast-message { flex:1; min-width:0; overflow-wrap:anywhere; }
.app-toast button { display:grid; place-items:center; flex:none; width:44px; height:44px; margin:-6px; border:0; border-radius:8px; background:transparent; color:inherit; cursor:pointer; touch-action:manipulation; }
.app-toast button svg { width:18px; height:18px; }
.app-toast button:active { background:rgb(25 62 59 / .08); }
.app-toast button:focus-visible { outline:2px solid #137671; outline-offset:2px; }
@media(max-width:700px) {
    .app-toast-region { top:calc(12px + env(safe-area-inset-top, 0px)); right:max(12px, env(safe-area-inset-right, 0px)); bottom:auto; left:max(12px, env(safe-area-inset-left, 0px)); width:auto; max-width:440px; margin-inline:auto; }
    .app-toast { padding:8px 12px; border-radius:10px; font-size:13px; }
    .app-toast-icon { width:30px; height:30px; border-radius:8px; }
}
</style>
