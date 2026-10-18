<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/Aadi/UI/Badge.vue';

defineProps({
  listings: {
    type: Object,
    required: true,
  },
});

const statusColors = {
  available: 'green',
  busy: 'yellow',
  inactive: 'slate',
};

const priceTypeLabels = {
  fixed: 'Fixed',
  hourly: 'Hourly',
  per_project: 'Per Project',
};

const formatPrice = (price) => {
  return `PHP ${parseFloat(price).toLocaleString()}`;
};
</script>

<template>
  <Head title="Aadi Raketeer - Admin" />

  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Aadi Raketeer</h1>
        <BaseBadge :color="'blue'">{{ listings.total }} Total</BaseBadge>
      </div>
    </template>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Service</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Pricing</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Provider</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Created</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="listing in listings.data"
              :key="listing.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex flex-col">
                  <span class="font-medium text-slate-900">{{ listing.title }}</span>
                  <span class="text-sm text-slate-500">{{ listing.service_area }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-slate-700">{{ listing.service_type }}</div>
                <div class="text-xs text-slate-500" v-if="listing.service_category">{{ listing.service_category }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-slate-900 font-medium">{{ formatPrice(listing.price_amount) }}</div>
                <div class="text-xs text-slate-500">{{ priceTypeLabels[listing.price_type] || listing.price_type }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ listing.provider?.name || 'Unknown' }}</span>
              </td>
              <td class="px-6 py-4">
                <BaseBadge :color="statusColors[listing.status] || 'slate'">
                  {{ listing.status }}
                </BaseBadge>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-500">{{ new Date(listing.created_at).toLocaleDateString() }}</span>
              </td>
            </tr>
            <tr v-if="listings.data.length === 0">
              <td colspan="6" class="px-6 py-10 text-center text-slate-500">No Aadi Raketeer listings found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="listings.last_page > 1"
        class="px-6 py-4 border-t border-slate-200 flex items-center justify-between"
      >
        <div class="text-sm text-slate-600">
          Showing {{ listings.from }} to {{ listings.to }} of {{ listings.total }} results
        </div>
        <div class="flex gap-2">
          <Link
            v-for="link in listings.links"
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
