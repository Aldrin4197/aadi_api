<script setup>
import AdminLayout from '../layouts/AdminLayout.vue';
import EmptyState from '../components/EmptyState.vue';
import BaseButton from '../components/BaseButton.vue';
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-2xl font-bold text-slate-900">Support Tickets</h1>
    </template>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
      <EmptyState
        title="Ticket Management Coming Soon"
        description="View and respond to support tickets, track resolution status, and manage customer inquiries."
        icon="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
      >
        <template #actions>
          <BaseButton variant="primary">
            View Open Tickets
          </BaseButton>
        </template>
      </EmptyState>
    </div>
  </AdminLayout>
</template>
