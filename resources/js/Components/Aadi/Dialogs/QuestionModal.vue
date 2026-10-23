<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';

const props = withDefaults(defineProps<{
    open: boolean;
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    busyLabel?: string;
    busy?: boolean;
    danger?: boolean;
    error?: string;
}>(), { confirmLabel: 'Continue', cancelLabel: 'Cancel', busyLabel: 'Saving…' });
const emit = defineEmits<{ confirm: []; cancel: [] }>();
const id = useId();
const dialog = ref<HTMLDialogElement>();
const cancelButton = ref<HTMLButtonElement>();
let previousFocus: HTMLElement | null = null;
let previousOverflow: string | null = null;

function restore() {
    if (previousOverflow !== null) {
        document.body.style.overflow = previousOverflow;
        previousOverflow = null;
    }
    if (previousFocus?.isConnected) previousFocus.focus();
    previousFocus = null;
}
watch(() => props.open, async open => {
    await nextTick();
    if (open && props.open && dialog.value && !dialog.value.open) {
        previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        dialog.value.showModal();
        // A prompt can opt its field into focus; questions default to the safe action.
        const field = dialog.value.querySelector<HTMLElement>('[data-dialog-focus]');
        (field ?? cancelButton.value)?.focus();
    } else if (!props.open) {
        dialog.value?.close();
        restore();
    }
}, { immediate: true });
onBeforeUnmount(() => { dialog.value?.close(); restore(); });
function trapFocus(event: KeyboardEvent) {
    if (event.key !== 'Tab' || !dialog.value) return;
    const controls = Array.from(dialog.value.querySelectorAll<HTMLElement>(
        'button:not(:disabled), input:not(:disabled), textarea:not(:disabled), select:not(:disabled), a[href], [tabindex="0"]',
    )).filter(element => element.getClientRects().length > 0);
    const first = controls[0], last = controls[controls.length - 1];
    if (!first) { event.preventDefault(); dialog.value.focus(); return; }
    if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog.value)) {
        event.preventDefault(); last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || document.activeElement === dialog.value)) {
        event.preventDefault(); first.focus();
    }
}
function cancel() { if (!props.busy) emit('cancel'); }
function confirm() { if (!props.busy) emit('confirm'); }
</script>

<template>
    <Teleport to="body">
        <dialog ref="dialog" class="aadi-question" :aria-labelledby="`${id}-title`"
            :aria-describedby="description ? `${id}-description` : undefined" :aria-busy="busy"
            @cancel.prevent="cancel" @keydown="trapFocus" tabindex="-1">
            <form class="aadi-question-content" @submit.prevent="confirm">
                <header>
                    <h2 :id="`${id}-title`">{{ title }}</h2>
                    <p v-if="description" :id="`${id}-description`">{{ description }}</p>
                </header>
                <slot />
                <p v-if="error" class="aadi-question-error" role="alert">{{ error }}</p>
                <footer>
                    <button ref="cancelButton" type="button" :disabled="busy" @click="cancel">{{ cancelLabel }}</button>
                    <button type="submit" class="aadi-question-confirm" :class="{ 'is-danger': danger }" :disabled="busy">
                        {{ busy ? busyLabel : confirmLabel }}
                    </button>
                </footer>
                <span class="sr-only" role="status">{{ busy ? busyLabel : '' }}</span>
            </form>
        </dialog>
    </Teleport>
</template>

<style scoped>
.aadi-question { width: min(32rem, calc(100% - 2rem)); max-height: calc(100dvh - 2rem); padding: 0; border: 1px solid var(--color-aadi-teal-100, #c4ecea); border-radius: 1rem; background: white; color: var(--color-aadi-teal-900, #062827); overflow: auto; box-shadow: 0 1rem 3rem #06282726; }
.aadi-question::backdrop { background: #06282780; }
.aadi-question-content { display: grid; gap: 1.5rem; padding: 1.5rem; overflow-wrap: anywhere; }
h2 { font-size: 1.25rem; font-weight: 700; line-height: 1.4; margin: 0; }
header p { margin: .75rem 0 0; color: #526764; line-height: 1.6; }
footer { display: flex; justify-content: flex-end; gap: .75rem; flex-wrap: wrap; }
button { min-height: 44px; padding: .625rem 1rem; border: 1px solid var(--color-aadi-teal-100, #c4ecea); border-radius: .5rem; background: white; font: inherit; font-weight: 700; }
button:focus-visible { outline: 3px solid var(--color-aadi-teal-600, #157873); outline-offset: 3px; }
button:disabled { opacity: .65; cursor: wait; }
.aadi-question-confirm { background: var(--color-aadi-teal-700, #0f5f5c); color: white; border-color: transparent; }
.aadi-question-confirm.is-danger { background: #b42318; }
.aadi-question-error { color: #b42318; margin: 0; }
@media (max-width: 480px) { footer { flex-direction: column; } footer button { width: 100%; } }
</style>
