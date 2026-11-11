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
                <span>{{ currentToast.message }}</span>
                <button type="button" aria-label="Dismiss notification" @click="dismissToast">×</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.app-toast-region { position:fixed; z-index:3000; top:calc(16px + env(safe-area-inset-top, 0px)); right:max(16px, env(safe-area-inset-right, 0px)); width:min(420px, calc(100vw - 32px)); pointer-events:none; }
.app-toast { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border:1px solid #a9d8c0; border-radius:12px; background:#fff; color:#193e3b; box-shadow:0 12px 32px #193e3b2e; font:600 14px/1.5 sans-serif; pointer-events:auto; }
.app-toast.error { border-color:#e5a89e; color:#9c261d; }
.app-toast span { flex:1; min-width:0; overflow-wrap:anywhere; }
.app-toast button { display:grid; place-items:center; flex:none; width:44px; height:44px; margin:-8px; border:0; border-radius:8px; background:transparent; color:inherit; font-size:22px; cursor:pointer; }
.app-toast button:focus-visible { outline:2px solid #137671; outline-offset:2px; }
@media(max-width:480px) { .app-toast-region { left:max(12px, env(safe-area-inset-left, 0px)); right:max(12px, env(safe-area-inset-right, 0px)); width:auto; } }
</style>
