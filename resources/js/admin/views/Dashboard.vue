<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import AdminLayout from '../layouts/AdminLayout.vue';
import PageHeader from '../components/PageHeader.vue';
import StatCard from '../components/StatCard.vue';

const authStore = useAuthStore();

const stats = ref({
  totalUsers: 0,
  totalSpaces: 0,
  bookings: 0,
  pendingVerifications: 0,
});

const loading = ref(true);

onMounted(async () => {
  try {
    // TODO: Fetch real stats from API
    // const response = await axios.get('/admin/api/stats');
    // stats.value = response.data;
    
    // Mock data for now
    stats.value = {
      totalUsers: 1247,
      totalSpaces: 89,
      bookings: 456,
      pendingVerifications: 12,
    };
  } catch (error) {
    console.error('Failed to load stats:', error);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <AdminLayout>
    <template #header>
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-sm text-slate-600">Welcome back, {{ authStore.userName }}! 👋</p>
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
        title="Total Spaces"
        :value="stats.totalSpaces.toLocaleString()"
        icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        color="orange"
      />
      
      <StatCard
        title="Bookings"
        :value="stats.bookings.toLocaleString()"
        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
        color="blue"
      />
      
      <StatCard
        title="Pending Verifications"
        :value="stats.pendingVerifications.toLocaleString()"
        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        color="emerald"
      />
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-xs border border-slate-200 p-6">
      <h2 class="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <router-link
          to="/admin/users"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Manage Users</h3>
          <p class="text-sm text-slate-600 mt-1">View and approve landlords</p>
        </router-link>
        
        <router-link
          to="/admin/listings"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Review Listings</h3>
          <p class="text-sm text-slate-600 mt-1">Moderate property listings</p>
        </router-link>
        
        <router-link
          to="/admin/tickets"
          class="p-4 border-2 border-slate-200 rounded-lg hover:border-aadi-teal hover:bg-aadi-teal-50 transition-colors"
        >
          <h3 class="font-medium text-slate-900">Support Tickets</h3>
          <p class="text-sm text-slate-600 mt-1">Respond to user inquiries</p>
        </router-link>
      </div>
    </div>
  </AdminLayout>
</template>
