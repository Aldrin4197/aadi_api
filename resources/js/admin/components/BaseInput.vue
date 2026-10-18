<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  label: String,
  error: String,
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
});

const emit = defineEmits(['update:modelValue']);

const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`);
</script>

<template>
  <div class="space-y-2">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-slate-700">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <input
      :id="inputId"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :class="[
        'block w-full rounded-lg border px-4 py-2.5 text-sm transition-colors',
        'placeholder:text-slate-400',
        'focus:outline-none focus:ring-2 focus:ring-aadi-teal focus:border-aadi-teal',
        error ? 'border-red-300' : 'border-slate-300',
        disabled ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'bg-white',
      ]"
      @input="emit('update:modelValue', $event.target.value)"
    />
    
    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
  </div>
</template>
