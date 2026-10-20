<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/Aadi/UI/Badge.vue';

defineProps({
  users: {
    type: Object,
    required: true,
  },
});

const statusColors = {
  none: 'slate',
  emailVerified: 'blue',
  pending: 'yellow',
  approved: 'green',
  rejected: 'red',
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};
</script>

<template>
  <Head title="Users - Admin" />

  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Users</h1>
        <BaseBadge :color="'blue'">{{ users.total }} Total</BaseBadge>
      </div>
    </template>

    <div class="bg-white rounded-lg shadow-xs border border-slate-200 overflow-hidden">
      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                User
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Email
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Landlord Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Listings
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Joined
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="user in users.data"
              :key="user.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-aadi-teal text-white flex items-center justify-center font-semibold">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex flex-col">
                    <span class="font-medium text-slate-900">{{ user.name }}</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ user.email }}</span>
              </td>
              <td class="px-6 py-4">
                <BaseBadge :color="statusColors[user.landlord_status] || 'slate'">
                  {{ user.landlord_status }}
                </BaseBadge>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm font-medium text-slate-900">{{ user.listings_count || 0 }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-500">
                  {{ formatDate(user.created_at) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="users.last_page > 1" class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
        <div class="text-sm text-slate-600">
          Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
        </div>
        <div class="flex gap-2">
          <Link
            v-for="link in users.links"
            :key="link.label"
            :href="link.url"
            :class="[
              'px-3 py-1 text-sm rounded border',
              link.active
                ? 'bg-aadi-teal text-white border-aadi-teal'
                : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50',
              !link.url && 'opacity-50 cursor-not-allowed',
            ]"
            :preserve-scroll="true"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
