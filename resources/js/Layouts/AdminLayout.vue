<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Logo from '@/Components/Aadi/UI/Logo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const sidebarOpen = ref(true);

const logout = () => {
  router.post(route('admin.logout'));
};

const navSections = [
  {
    title: 'Overview',
    items: [
      { name: 'Dashboard', route: 'admin.dashboard', icon: 'grid' },
    ],
  },
  {
    title: 'Users',
    items: [
      { name: 'All Users', route: 'admin.users.index', icon: 'users' },
    ],
  },
  {
    title: 'Properties',
    items: [
      { name: 'Space Rentals', route: 'admin.listings.index', icon: 'home' },
      { name: 'Aadi Haul', route: 'admin.vehicles.index', icon: 'truck' },
      { name: 'Aadi Raketeer', route: 'admin.services.index', icon: 'briefcase' },
    ],
  },
  {
    title: 'Operations',
    items: [
      { name: 'Support Tickets', route: 'admin.tickets.index', icon: 'inbox' },
    ],
  },
  {
    title: 'System',
    items: [
      { name: 'Analytics', route: 'admin.analytics', icon: 'chart' },
    ],
  },
];

// SVG Icon paths
const icons = {
  grid: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  truck: 'M9 17a2 2 0 11-4 0m4 0a2 2 0 104 0m-4 0H5m8 0h3m0 0a2 2 0 104 0m-4 0h-3m3 0V9a1 1 0 00-1-1h-3m0 0V6a1 1 0 00-1-1H3a1 1 0 00-1 1v11h3m8-9h3l3 4v5h-3',
  briefcase: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  inbox: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
  chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
};

const isActive = (routeName) => {
  return route().current(routeName);
};
</script>

<template>
  <div class="flex h-screen bg-slate-100">
    <!-- Sidebar -->
    <aside 
      class="w-64 bg-linear-to-b from-aadi-teal-800 to-aadi-teal-900 text-aadi-teal-100 flex flex-col transition-all duration-300"
      :class="{ 'w-20': !sidebarOpen }"
    >
      <!-- Logo -->
      <div class="p-6 border-b border-aadi-teal-700">
        <div class="flex items-center justify-between">
          <Logo variant="icon" size="md" class="shrink-0" />
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 hover:bg-aadi-teal-700 rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto p-4 space-y-6">
        <div v-for="section in navSections" :key="section.title">
          <h3 v-if="sidebarOpen" class="text-xs font-semibold text-aadi-teal-300 uppercase tracking-wider mb-2">
            {{ section.title }}
          </h3>
          <div class="space-y-1">
            <Link
              v-for="item in section.items"
              :key="item.route"
              :href="route(item.route)"
              class="flex items-center px-3 py-2 rounded-lg transition-colors"
              :class="isActive(item.route) 
                ? 'bg-aadi-teal-700 text-white' 
                : 'text-aadi-teal-100 hover:bg-aadi-teal-700/50'"
            >
              <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons[item.icon]" />
              </svg>
              <span v-if="sidebarOpen" class="ml-3">{{ item.name }}</span>
            </Link>
          </div>
        </div>
      </nav>

      <!-- User Profile -->
      <div class="p-4 border-t border-aadi-teal-700">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-full bg-linear-to-br from-aadi-teal to-aadi-teal-800 flex items-center justify-center text-white font-semibold">
            {{ user?.name?.charAt(0) || 'A' }}
          </div>
          <div v-if="sidebarOpen" class="flex-1">
            <p class="text-sm font-medium text-white">{{ user?.name || 'Admin' }}</p>
            <button
              @click="logout"
              class="text-xs text-aadi-teal-300 hover:text-white transition-colors"
            >
              Sign out
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <header class="bg-white border-b border-slate-200 px-6 py-4">
        <slot name="header">
          <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        </slot>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
