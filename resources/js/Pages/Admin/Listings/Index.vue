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
  draft: 'slate',
  active: 'green',
  reserved: 'yellow',
  rented: 'blue',
  archived: 'slate',
};

const typeLabels = {
  bedspace: 'Bedspace',
  boardingHouse: 'Boarding House',
  apartment: 'Apartment',
  house: 'House',
  commercial: 'Commercial',
};

const formatPrice = (price, currency = '₱') => {
  return `${currency}${parseFloat(price).toLocaleString()}`;
};

</script>

<template>
  <Head title="Listings - Admin" />

  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Listings</h1>
        <BaseBadge :color="'blue'">{{ listings.total }} Total</BaseBadge>
      </div>
    </template>

    <div class="bg-white rounded-lg shadow-xs border border-slate-200 overflow-hidden">
      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Listing
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Price
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Owner
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                Created
              </th>
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
                  <span class="text-sm text-slate-500">{{ listing.city }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ typeLabels[listing.type] || listing.type }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="font-medium text-slate-900">{{ formatPrice(listing.price, listing.currency) }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ listing.owner_name }}</span>
              </td>
              <td class="px-6 py-4">
                <BaseBadge :color="statusColors[listing.status]">
                  {{ listing.status }}
                </BaseBadge>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-500">
                  {{ new Date(listing.created_at).toLocaleDateString() }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="listings.last_page > 1" class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
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
