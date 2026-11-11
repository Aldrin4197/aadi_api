import { ref } from 'vue';

export const currentToast = ref(null);
let timer;

export function dismissToast() {
    clearTimeout(timer);
    currentToast.value = null;
}

export function showToast(message, kind = 'success') {
    if (!message) return;
    clearTimeout(timer);
    currentToast.value = { message, kind };
    if (kind !== 'error') timer = setTimeout(dismissToast, 6500);
}
