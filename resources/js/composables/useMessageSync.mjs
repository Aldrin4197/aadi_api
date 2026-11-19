import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

export function useMessageSync(update) {
    const page = usePage();
    const failed = ref(false);
    let active = true;
    let pending = false;
    let repeat = false;
    let generation = 0;
    let timer;
    let controller;

    function invalidate() {
        generation++;
    }

    async function refresh() {
        if (!active || document.hidden) return;
        if (pending) {
            repeat = true;
            return;
        }
        pending = true;
        const currentGeneration = generation;
        const url = page.url;
        controller = new AbortController();
        try {
            const { data } = await axios.get(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (active && generation === currentGeneration && page.url === url) {
                update(data);
                failed.value = false;
            }
        } catch (error) {
            if (active && generation === currentGeneration && page.url === url && !axios.isCancel(error)) failed.value = true;
        } finally {
            pending = false;
            if (active && repeat) {
                repeat = false;
                refresh();
            }
        }
    }

    function onRealtime(event) {
        if (event.detail?.type === 'message' || event.detail?.type === 'sync') refresh();
    }
    function onVisible() {
        if (!document.hidden) refresh();
    }

    onMounted(() => {
        window.addEventListener('aadi:realtime', onRealtime);
        document.addEventListener('visibilitychange', onVisible);
        timer = setInterval(refresh, 30000);
    });
    onBeforeUnmount(() => {
        active = false;
        controller?.abort();
        clearInterval(timer);
        window.removeEventListener('aadi:realtime', onRealtime);
        document.removeEventListener('visibilitychange', onVisible);
    });

    return { failed, refresh, invalidate };
}
