<script setup lang="ts">
import { computed } from 'vue';

interface Option {
    label: string;
    value: string | number;
    disabled?: boolean;
}

interface Props {
    modelValue: string | number;
    options: Option[];
    label?: string;
    error?: string;
    help?: string;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    id?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

const inputId = computed(() => props.id || `select-${Math.random().toString(36).substring(7)}`);

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

        <!-- Select -->
        <select
            :id="inputId"
            :value="modelValue"
            :disabled="disabled"
            :required="required"
            :class="[
                'block w-full rounded-lg border px-4 py-2.5 text-sm transition-colors',
                'focus:outline-hidden focus:ring-2 focus:ring-offset-2',
                hasError
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-slate-300 focus:border-aadi-teal focus:ring-aadi-teal',
                disabled ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'bg-white text-slate-900',
            ]"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </option>
        </select>

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
