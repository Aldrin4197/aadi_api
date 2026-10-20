<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string;
    label?: string;
    error?: string;
    help?: string;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    rows?: number;
    id?: string;
}

const props = withDefaults(defineProps<Props>(), {
    rows: 4,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputId = computed(() => props.id || `textarea-${Math.random().toString(36).substring(7)}`);

const hasError = computed(() => !!props.error);
</script>

<template>
    <div class="space-y-2">
        <!-- Label -->
        <label
            v-if="label"
            :for="inputId"
            class="block text-sm font-medium text-slate-700"
        >
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Textarea -->
        <textarea
            :id="inputId"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            :class="[
                'block w-full rounded-lg border px-4 py-2.5 text-sm transition-colors',
                'placeholder:text-slate-400',
                'focus:outline-hidden focus:ring-2 focus:ring-offset-2',
                hasError
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-slate-300 focus:border-aadi-teal focus:ring-aadi-teal',
                disabled ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'bg-white text-slate-900',
            ]"
            @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        />

        <!-- Help Text -->
        <p v-if="help && !error" class="text-sm text-slate-500">
            {{ help }}
        </p>

        <!-- Error Message -->
        <p v-if="error" class="text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
