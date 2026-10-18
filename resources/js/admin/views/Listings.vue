<script setup>
import AdminLayout from '../layouts/AdminLayout.vue';
import EmptyState from '../components/EmptyState.vue';
import BaseButton from '../components/BaseButton.vue';
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-2xl font-bold text-slate-900">Listings</h1>
    </template>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
      <EmptyState
        title="Listings Management Coming Soon"
        description="Browse property listings, approve or reject submissions, and manage featured listings."
        icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
      >
        <template #actions>
          <BaseButton variant="primary">
            View All Listings
          </BaseButton>
        </template>
      </EmptyState>
    </div>
  </AdminLayout>
</template>
