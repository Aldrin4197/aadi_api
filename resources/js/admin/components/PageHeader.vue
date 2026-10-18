<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: String,
});
</script>

<template>
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-900">{{ title }}</h1>
    <p v-if="subtitle" class="mt-2 text-slate-600">{{ subtitle }}</p>
    <div v-if="$slots.actions" class="mt-4">
      <slot name="actions" />
    </div>
  </div>
</template>
