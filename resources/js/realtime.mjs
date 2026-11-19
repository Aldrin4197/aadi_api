import { reactive } from 'vue';

export const realtimeState = reactive({ connected: false });

export function notifyRealtime(type) {
    window.dispatchEvent(new CustomEvent('aadi:realtime', { detail: { type } }));
}
