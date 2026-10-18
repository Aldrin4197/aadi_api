<script setup lang="ts">
defineProps<{
  title: string;
  description?: string;
  icon?: string;
}>();
</script>

<template>
  <div class="text-center py-12">
    <!-- Icon -->
    <div v-if="icon" class="flex justify-center mb-4">
      <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
        </svg>
      </div>
    </div>

    <!-- Content -->
    <h3 class="text-lg font-semibold text-slate-900 mb-2">
      {{ title }}
    </h3>
    <p v-if="description" class="text-slate-600 mb-6 max-w-md mx-auto">
      {{ description }}
    </p>

    <!-- Actions Slot -->
    <div v-if="$slots.actions" class="flex justify-center gap-3">
      <slot name="actions" />
    </div>
  </div>
</template>
