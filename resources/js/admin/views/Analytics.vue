<script setup>
import AdminLayout from '../layouts/AdminLayout.vue';
import EmptyState from '../components/EmptyState.vue';
import BaseButton from '../components/BaseButton.vue';
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-2xl font-bold text-slate-900">Analytics</h1>
    </template>

    <div class="bg-white rounded-lg shadow-xs border border-slate-200 p-8">
      <EmptyState
        title="Analytics Dashboard Coming Soon"
        description="View platform statistics, user growth metrics, revenue charts, and performance indicators."
        icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
      >
        <template #actions>
          <BaseButton variant="primary">
            Generate Report
          </BaseButton>
        </template>
      </EmptyState>
    </div>
  </AdminLayout>
</template>
