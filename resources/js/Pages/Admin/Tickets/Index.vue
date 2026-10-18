<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import EmptyState from '@/Components/Aadi/UI/EmptyState.vue';
import BaseButton from '@/Components/Aadi/UI/Button.vue';

defineProps({
  tickets: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <Head title="Support Tickets - Admin" />

  <AdminLayout>
    <template #header>
      <h1 class="text-2xl font-bold text-slate-900">Support Tickets</h1>
    </template>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
      <EmptyState
        title="Ticket Management Coming Soon"
        description="View and respond to support tickets, track resolution status, and manage customer inquiries."
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
