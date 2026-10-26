<script setup lang="ts">
import { useId } from 'vue';
import QuestionModal from './QuestionModal.vue';

withDefaults(defineProps<{
    open: boolean;
    modelValue: string;
    title: string;
    description?: string;
    label: string;
    hint?: string;
    required?: boolean;
    multiline?: boolean;
    maxLength?: number;
    confirmLabel?: string;
    cancelLabel?: string;
    busyLabel?: string;
    busy?: boolean;
    danger?: boolean;
    error?: string;
}>(), { maxLength: 2000 });
const emit = defineEmits<{ 'update:modelValue': [value: string]; confirm: []; cancel: [] }>();
const id = useId();
function update(event: Event) { emit('update:modelValue', (event.target as HTMLInputElement).value); }
function validate(event: Event, required: boolean) {
    const field = event.target as HTMLInputElement;
    field.setCustomValidity(required && !field.value.trim() ? 'Please enter a response.' : '');
}
</script>

<template>
    <QuestionModal :open="open" :title="title" :description="description" :confirm-label="confirmLabel"
        :cancel-label="cancelLabel" :busy-label="busyLabel" :busy="busy" :danger="danger" :error="error"
        @confirm="emit('confirm')" @cancel="emit('cancel')">
        <div class="aadi-prompt-field">
            <label :for="`${id}-input`">{{ label }} <span v-if="!required">(optional)</span></label>
            <component :is="multiline ? 'textarea' : 'input'" :id="`${id}-input`" :value="modelValue"
                :rows="multiline ? 4 : undefined" :required="required" :maxlength="maxLength" :disabled="busy"
                :aria-describedby="hint ? `${id}-hint` : undefined" :aria-invalid="error ? true : undefined"
                data-dialog-focus @input="update($event); validate($event, !!required)" />
            <p v-if="hint" :id="`${id}-hint`">{{ hint }}</p>
        </div>
    </QuestionModal>
</template>

<style scoped>
.aadi-prompt-field { display: grid; gap: .5rem; }
label { font-weight: 700; }
label span, p { color: #526764; font-weight: 400; }
p { margin: 0; font-size: .875rem; }
input, textarea { width: 100%; min-width: 0; border: 1px solid #849c99; border-radius: .5rem; padding: .75rem; font: inherit; color: inherit; background: white; }
textarea { resize: vertical; min-height: 7rem; }
input:focus, textarea:focus { outline: 2px solid var(--color-aadi-teal-600, #157873); outline-offset: 2px; }
</style>
