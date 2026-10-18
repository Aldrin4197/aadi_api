<script setup>
defineProps({
  title: {
    type: String,
    default: 'No data available',
  },
  description: String,
  icon: String,
});
</script>

<template>
  <div class="text-center py-12">
    <div v-if="icon" class="mx-auto h-12 w-12 text-slate-400">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
      </svg>
    </div>
    <h3 class="mt-4 text-lg font-medium text-slate-900">{{ title }}</h3>
    <p v-if="description" class="mt-2 text-sm text-slate-600">{{ description }}</p>
    <div v-if="$slots.actions" class="mt-6">
      <slot name="actions" />
    </div>
  </div>
</template>
