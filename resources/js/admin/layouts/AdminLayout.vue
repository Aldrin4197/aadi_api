<script setup lang="ts">
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
    await authStore.logout();
    router.push({ name: 'login' });
};

const navSections = [
    {
        title: 'Overview',
        items: [
            { name: 'Dashboard', route: 'dashboard', icon: 'grid' },
        ],
    },
    {
        title: 'Users',
        items: [
            { name: 'All Users', route: 'users', icon: 'users' },
        ],
    },
    {
        title: 'Properties',
        items: [
            { name: 'Listings', route: 'listings', icon: 'home' },
        ],
    },
    {
        title: 'Operations',
        items: [
            { name: 'Support Tickets', route: 'tickets', icon: 'inbox' },
        ],
    },
    {
        title: 'System',
        items: [
            { name: 'Analytics', route: 'analytics', icon: 'chart' },
        ],
    },
];
</script>

<template>
    <div class="flex h-screen bg-slate-100">
        <!-- Sidebar -->
        <aside class="w-64 bg-linear-to-b from-aadi-teal-800 to-aadi-teal-900 text-aadi-teal-100 flex flex-col">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-aadi-teal-700">
                <img 
                    src="/images/logos/Aadi_icon.png" 
                    alt="Aadi" 
                    class="h-10 w-10 object-contain shrink-0"
                />
                <div>
                    <h1 class="text-xl font-bold text-white">Aadi</h1>
                    <p class="text-xs text-aadi-teal-200">Admin Panel</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <div v-for="section in navSections" :key="section.title" class="space-y-1">
                    <!-- Section Title -->
                    <h3 class="px-3 text-xs font-semibold text-aadi-teal-300 uppercase tracking-wider mb-2">
                        {{ section.title }}
                    </h3>

                    <!-- Nav Items -->
                    <router-link
                        v-for="item in section.items"
                        :key="item.route"
                        :to="{ name: item.route }"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        :class="$route.name === item.route 
                            ? 'bg-aadi-teal text-white' 
                            : 'text-aadi-teal-100 hover:bg-aadi-teal-700 hover:text-white'"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span>{{ item.name }}</span>
                    </router-link>
                </div>
            </nav>

            <!-- User Profile Footer -->
            <div class="border-t border-aadi-teal-700 px-3 py-4">
                <div v-if="authStore.user" class="flex items-center gap-3 px-3 py-2">
                    <div class="h-8 w-8 rounded-full bg-linear-to-br from-aadi-teal to-aadi-teal-800 flex items-center justify-center text-white font-semibold text-sm shrink-0">
                        {{ authStore.user.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ authStore.user.name }}</p>
                        <p class="text-xs text-aadi-teal-200 truncate">{{ authStore.user.email }}</p>
                    </div>
                </div>

                <!-- Logout -->
                <button
                    @click="handleLogout"
                    class="mt-2 w-full flex items-center gap-2 px-3 py-2 text-sm text-aadi-teal-200 hover:text-white hover:bg-aadi-teal-700 rounded-lg transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-6 py-4">
                <slot name="header">
                    <h2 class="text-2xl font-bold text-slate-900">Dashboard</h2>
                </slot>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
