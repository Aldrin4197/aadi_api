<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
interface InstallEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: string }>;
}
const installEvent = ref<InstallEvent | null>(null);
const waiting = ref<ServiceWorker | null>(null);
const offline = ref(false), dismissed = ref(false), installing = ref(false), updating = ref(false);
let registration: ServiceWorkerRegistration | undefined;
let disposed = false;
const connectivity = () => { offline.value = !navigator.onLine; };
const captureInstall = (event: Event) => { event.preventDefault(); installEvent.value = event as InstallEvent; };
const installed = () => { installEvent.value = null; };
const controllerChanged = () => { if (updating.value) window.location.reload(); };
async function install() {
    const event = installEvent.value;
    if (!event || installing.value) return;
    installing.value = true;
    try { await event.prompt(); await event.userChoice; }
    finally { installEvent.value = null; installing.value = false; }
}
function update() {
    updating.value = true;
    waiting.value?.postMessage({ type: 'ACTIVATE_UPDATE' });
}
function detectUpdate() {
    const worker = registration?.installing;
    worker?.addEventListener('statechange', () => {
        if (!disposed && worker.state === 'installed' && navigator.serviceWorker.controller) waiting.value = registration?.waiting ?? null;
    });
}
onMounted(async () => {
    if (!import.meta.env.PROD || !('serviceWorker' in navigator) || !window.isSecureContext) return;
    connectivity();
    window.addEventListener('online', connectivity);
    window.addEventListener('offline', connectivity);
    window.addEventListener('beforeinstallprompt', captureInstall);
    window.addEventListener('appinstalled', installed);
    navigator.serviceWorker.addEventListener('controllerchange', controllerChanged);
    try {
        registration = await navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' });
        if (disposed) return;
        waiting.value = registration.waiting;
        registration.addEventListener('updatefound', detectUpdate);
        detectUpdate();
    } catch { /* Installation is optional; the online web app remains usable. */ }
});
onBeforeUnmount(() => {
    disposed = true;
    window.removeEventListener('online', connectivity);
    window.removeEventListener('offline', connectivity);
    window.removeEventListener('beforeinstallprompt', captureInstall);
    window.removeEventListener('appinstalled', installed);
    navigator.serviceWorker?.removeEventListener('controllerchange', controllerChanged);
    registration?.removeEventListener('updatefound', detectUpdate);
});
</script>
<template>
    <aside v-if="offline || waiting || (installEvent && !dismissed)" class="pwa-notice" aria-label="Aadi app status">
        <p role="status">{{ offline ? 'You are offline. Reconnect before searching or saving changes.' : waiting ? 'An Aadi update is ready. Save your work before reloading.' : 'Keep Aadi handy. Install it on your device.' }}</p>
        <template v-if="!offline">
            <button v-if="waiting" :disabled="updating" @click="update">{{ updating ? 'Updating…' : 'Reload to update' }}</button>
            <template v-else><button :disabled="installing" @click="install">Install Aadi</button><button @click="dismissed=true">Not now</button></template>
        </template>
    </aside>
</template>
<style scoped>
.pwa-notice{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:.5rem 1rem;padding:.5rem 1rem;background:#e3f6f4;color:#0b3a38;font: .8125rem/1.5 system-ui,sans-serif;border-bottom:1px solid #c4ecea}.pwa-notice p{margin:0}.pwa-notice button{min-height:44px;padding:.5rem .75rem;border:1px solid #157873;border-radius:.5rem;font-weight:700}.pwa-notice button:focus-visible{outline:3px solid #f59e42;outline-offset:2px}
</style>
