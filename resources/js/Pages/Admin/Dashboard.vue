<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DashboardKpis from '@/Components/Aadi/Admin/DashboardKpis.vue';
import BaseBadge from '@/Components/Aadi/UI/Badge.vue';

defineProps({
  metrics: {type: Object, required: true},
  stats: {
    type: Object,
    required: true,
  },
  recentListings: {
    type: Array,
    default: () => [],
  },
  recentUsers: {
    type: Array,
    default: () => [],
  },
  recentViewings: {
    type: Array,
    default: () => [],
  },
});

const formatDate = (date) => new Date(date).toLocaleDateString();
</script>

<template>
  <Head title="Admin Dashboard" />

  <AdminLayout>
    <template #header>
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-sm text-slate-600">Marketplace health, provider approvals, and recent activity.</p>
      </div>
    </template>

    <DashboardKpis :metrics="metrics" />

    <!-- Recent Activity Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Recent Listings -->
      <div class="bg-white rounded-lg shadow-xs border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-slate-900">Recent Listings</h2>
          <Link :href="route('admin.listings.index')" class="text-sm text-aadi-teal hover:text-aadi-teal-600">
            View all →
          </Link>
        </div>
        <div class="space-y-3">
          <div
            v-for="listing in recentListings"
            :key="listing.id"
            class="flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors"
          >
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 truncate">{{ listing.title }}</p>
              <p class="text-sm text-slate-600">{{ listing.city }} • ₱{{ listing.price.toLocaleString() }}</p>
            </div>
            <BaseBadge :color="listing.status === 'active' ? 'green' : 'slate'">
              {{ listing.status }}
            </BaseBadge>
          </div>
        </div>
      </div>

      <!-- Recent Users -->
      <div class="bg-white rounded-lg shadow-xs border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-slate-900">Recent Users</h2>
          <Link :href="route('admin.users.index')" class="text-sm text-aadi-teal hover:text-aadi-teal-600">
            View all →
          </Link>
        </div>
        <div class="space-y-3">
          <div
            v-for="user in recentUsers"
            :key="user.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors"
          >
            <div class="w-10 h-10 rounded-full bg-aadi-teal text-white flex items-center justify-center font-semibold">
              {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 truncate">{{ user.name }}</p>
              <p class="text-sm text-slate-600 truncate">{{ user.email }}</p>
            </div>
            <span class="text-xs text-slate-500">{{ formatDate(user.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-xs border border-slate-200 p-6">
      <h2 class="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <Link
          :href="route('admin.users.index')"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Manage Users</h3>
          <p class="text-sm text-slate-600 mt-1">View and approve landlords</p>
        </Link>
        
        <Link
          :href="route('admin.listings.index')"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Space Rentals</h3>
          <p class="text-sm text-slate-600 mt-1">Moderate property listings</p>
        </Link>

        <Link
          :href="route('admin.vehicles.index')"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Aadi Haul</h3>
          <p class="text-sm text-slate-600 mt-1">Review PUV inventory and availability</p>
        </Link>

        <Link
          :href="route('admin.services.index')"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Aadi Raketeer</h3>
          <p class="text-sm text-slate-600 mt-1">Review service offers and provider coverage</p>
        </Link>
        
        <Link
          :href="route('admin.tickets.index')"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Support Tickets</h3>
          <p class="text-sm text-slate-600 mt-1">Respond to user inquiries</p>
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>
