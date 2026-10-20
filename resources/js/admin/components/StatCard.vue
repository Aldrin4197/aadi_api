<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    default: '',
  },
  color: {
    type: String,
    default: 'teal', // teal, orange, blue, emerald
  },
});

const colorClasses = {
  teal: 'bg-linear-to-br from-aadi-teal to-aadi-teal-600',
  orange: 'bg-linear-to-br from-aadi-orange to-aadi-orange-600',
  blue: 'bg-linear-to-br from-blue-500 to-blue-600',
  emerald: 'bg-linear-to-br from-emerald-500 to-emerald-600',
};
</script>

<template>
  <div class="bg-white rounded-lg shadow-xs border border-slate-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-slate-600 mb-2">{{ title }}</p>
        <p class="text-3xl font-bold text-slate-900">{{ value }}</p>
      </div>
      <div v-if="icon" :class="['w-12 h-12 rounded-lg flex items-center justify-center', colorClasses[color]]">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
        </svg>
      </div>
    </div>
  </div>
</template>
