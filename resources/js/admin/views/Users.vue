<script setup>
import AdminLayout from '../layouts/AdminLayout.vue';
import PageHeader from '../components/PageHeader.vue';
import EmptyState from '../components/EmptyState.vue';
import BaseButton from '../components/BaseButton.vue';
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-2xl font-bold text-slate-900">Users</h1>
    </template>

    <div class="bg-white rounded-lg shadow-xs border border-slate-200 p-8">
      <EmptyState
        title="User Management Coming Soon"
        description="View and manage all users, approve landlords, and handle user verification."
        icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
      >
        <template #actions>
          <BaseButton variant="primary">
            Add New User
          </BaseButton>
        </template>
      </EmptyState>
    </div>
  </AdminLayout>
</template>
