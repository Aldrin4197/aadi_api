<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  Filler,
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  Filler
);

const props = defineProps({
  overview: {
    type: Object,
    required: true,
  },
  listingsByType: {
    type: Object,
    default: () => ({}),
  },
  topListings: {
    type: Array,
    default: () => [],
  },
  dailyViews: {
    type: Array,
    default: () => [],
  },
  dailyUsers: {
    type: Array,
    default: () => [],
  },
});

const typeLabels = {
  bedspace: 'Bedspace',
  boardingHouse: 'Boarding House',
  apartment: 'Apartment',
  house: 'House',
  commercial: 'Commercial',
};

// Chart data
const viewsChartData = computed(() => ({
  labels: props.dailyViews.map(v => new Date(v.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
  datasets: [{
    label: 'Daily Views',
    data: props.dailyViews.map(v => v.count),
    borderColor: '#1FA6A0',
    backgroundColor: 'rgba(31, 166, 160, 0.1)',
    tension: 0.4,
    fill: true,
  }],
}));

const usersChartData = computed(() => ({
  labels: props.dailyUsers.map(u => new Date(u.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
  datasets: [{
    label: 'New Users',
    data: props.dailyUsers.map(u => u.count),
    borderColor: '#F59E42',
    backgroundColor: 'rgba(245, 158, 66, 0.1)',
    tension: 0.4,
    fill: true,
  }],
}));

const listingsTypeChartData = computed(() => ({
  labels: Object.keys(props.listingsByType).map(key => typeLabels[key] || key),
  datasets: [{
    label: 'Listings',
    data: Object.values(props.listingsByType),
    backgroundColor: [
      'rgba(31, 166, 160, 0.8)',
      'rgba(245, 158, 66, 0.8)',
      'rgba(59, 130, 246, 0.8)',
      'rgba(16, 185, 129, 0.8)',
      'rgba(139, 92, 246, 0.8)',
    ],
    borderColor: [
      '#1FA6A0',
      '#F59E42',
      '#3B82F6',
      '#10B981',
      '#8B5CF6',
    ],
    borderWidth: 2,
  }],
}));

const topListingsChartData = computed(() => ({
  labels: props.topListings.slice(0, 10).map(l => l.title.length > 30 ? l.title.substring(0, 30) + '...' : l.title),
  datasets: [{
    label: 'Views',
    data: props.topListings.slice(0, 10).map(l => l.views_count || 0),
    backgroundColor: 'rgba(31, 166, 160, 0.8)',
    borderColor: '#1FA6A0',
    borderWidth: 2,
  }],
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top',
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
      },
    },
  },
};

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: {
        precision: 0,
      },
    },
  },
};

</script>

<template>
  <Head title="Analytics - Admin" />

  <AdminLayout>
    <template #header>
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Analytics & Insights</h1>
        <p class="text-sm text-slate-600">Business intelligence and performance metrics</p>
      </div>
    </template>

    <!-- Key Metrics Summary -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-600 uppercase">Total Listings</p>
        <p class="text-2xl font-bold text-aadi-teal mt-1">{{ overview.totalListings }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-600 uppercase">Active</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ overview.activeListings }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-600 uppercase">Total Users</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ overview.totalUsers }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-600 uppercase">Viewings</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ overview.totalViewings }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-600 uppercase">Page Views</p>
        <p class="text-2xl font-bold text-purple-600 mt-1">{{ overview.totalViews }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-600 uppercase">Favorites</p>
        <p class="text-2xl font-bold text-pink-600 mt-1">{{ overview.totalFavorites }}</p>
      </div>
    </div>

    <!-- Growth Trends -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Listing Views Trend -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-slate-900">Listing Views Trend</h2>
          <p class="text-sm text-slate-600">Daily views over the last 30 days</p>
        </div>
        <div class="h-64">
          <Line v-if="dailyViews.length > 0" :data="viewsChartData" :options="chartOptions" />
          <div v-else class="h-full flex items-center justify-center text-slate-400">
            No view data available
          </div>
        </div>
      </div>

      <!-- User Growth Trend -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-slate-900">User Growth</h2>
          <p class="text-sm text-slate-600">New user registrations over the last 30 days</p>
        </div>
        <div class="h-64">
          <Line v-if="dailyUsers.length > 0" :data="usersChartData" :options="chartOptions" />
          <div v-else class="h-full flex items-center justify-center text-slate-400">
            No user data available
          </div>
        </div>
      </div>
    </div>

    <!-- Listings Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Listings by Type -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-slate-900">Listings by Property Type</h2>
          <p class="text-sm text-slate-600">Distribution across different categories</p>
        </div>
        <div class="h-80">
          <Bar :data="listingsTypeChartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Top Performing Listings -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-slate-900">Top 10 Most Viewed Listings</h2>
          <p class="text-sm text-slate-600">Properties with the most engagement</p>
        </div>
        <div class="h-80">
          <Bar :data="topListingsChartData" :options="barChartOptions" />
        </div>
      </div>
    </div>

    <!-- Insights Summary -->
    <div class="bg-gradient-to-r from-aadi-teal to-aadi-teal-600 rounded-lg shadow-sm p-6 text-white">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <h3 class="text-lg font-semibold mb-2">Growth Rate</h3>
          <p class="text-sm opacity-90">
            {{ overview.activeListings }} active listings generating {{ overview.totalViews }} total views
          </p>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-2">User Engagement</h3>
          <p class="text-sm opacity-90">
            {{ overview.totalUsers }} registered users with {{ overview.totalViewings }} property viewings requested
          </p>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-2">Popular Category</h3>
          <p class="text-sm opacity-90">
            {{ Object.entries(listingsByType).sort((a, b) => b[1] - a[1])[0] ? 
               typeLabels[Object.entries(listingsByType).sort((a, b) => b[1] - a[1])[0][0]] : 'N/A' }} 
            is the most listed property type
          </p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
