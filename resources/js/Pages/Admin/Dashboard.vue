<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Aadi/UI/StatCard.vue';
import BaseBadge from '@/Components/Aadi/UI/Badge.vue';

defineProps({
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
        <p class="text-sm text-slate-600">Welcome back!</p>
      </div>
    </template>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <StatCard
        title="Total Users"
        :value="stats.totalUsers.toLocaleString()"
        icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
        color="teal"
      />
      
      <StatCard
        title="Space Rentals"
        :value="stats.totalSpaces.toLocaleString()"
        icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        color="orange"
      />

      <StatCard
        title="Aadi Haul"
        :value="stats.totalVehicles.toLocaleString()"
        icon="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"
        color="blue"
      />

      <StatCard
        title="Aadi Raketeer"
        :value="stats.totalServices.toLocaleString()"
        icon="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
        color="purple"
      />
      
      <StatCard
        title="Total Viewings"
        :value="stats.totalViewings.toLocaleString()"
        icon="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
        color="slate"
      />
      
      <StatCard
        title="Pending Landlords"
        :value="stats.pendingVerifications.toLocaleString()"
        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        color="emerald"
      />

      <StatCard
        title="Pending Aadi Haul Owners"
        :value="stats.pendingVehicleOwners.toLocaleString()"
        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        color="indigo"
      />

      <StatCard
        title="Pending Aadi Raketeers"
        :value="stats.pendingServiceProviders.toLocaleString()"
        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        color="rose"
      />
    </div>

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
