<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Components/Aadi/UI/Avatar.vue';

interface NavItem {
    label: string;
    href: string;
    icon?: string;
    active?: boolean;
    badge?: string | number;
}

interface NavSection {
    title?: string;
    items: NavItem[];
}

interface Props {
    sections: NavSection[];
    user?: {
        name: string;
        email: string;
        avatar?: string;
    };
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'logout'): void;
}>();

const isSidebarOpen = ref(true);
</script>

<template>
    <div class="flex h-screen bg-slate-100">
        <!-- Sidebar -->
        <aside
            :class="[
                'flex flex-col bg-gradient-to-b from-aadi-teal-800 to-aadi-teal-900 text-aadi-teal-100 transition-all duration-300',
                isSidebarOpen ? 'w-64' : 'w-20',
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-aadi-teal-700">
                <img 
                    src="/images/logos/Aadi_icon.png" 
                    alt="Aadi" 
                    class="h-10 w-10 object-contain flex-shrink-0"
                />
                <Transition name="fade">
                    <div v-if="isSidebarOpen">
                        <h1 class="text-xl font-bold text-white">Aadi</h1>
                        <p class="text-xs text-aadi-teal-200">Rental Marketplace</p>
                    </div>
                </Transition>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <div v-for="(section, idx) in sections" :key="idx" class="space-y-1">
                    <!-- Section Title -->
                    <h3
                        v-if="section.title && isSidebarOpen"
                        class="px-3 text-xs font-semibold text-aadi-teal-300 uppercase tracking-wider mb-2"
                    >
                        {{ section.title }}
                    </h3>

                    <!-- Nav Items -->
                    <Link
                        v-for="item in section.items"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            item.active
                                ? 'bg-aadi-teal text-white'
                                : 'text-aadi-teal-100 hover:bg-aadi-teal-700 hover:text-white',
                        ]"
                    >
                        <!-- Icon placeholder -->
                        <div class="h-5 w-5 flex-shrink-0">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        
                        <Transition name="fade">
                            <span v-if="isSidebarOpen" class="flex-1">{{ item.label }}</span>
                        </Transition>

                        <!-- Badge -->
                        <span
                            v-if="item.badge && isSidebarOpen"
                            class="px-2 py-0.5 text-xs font-semibold rounded-full bg-aadi-orange text-white"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>
                </div>
            </nav>

            <!-- User Profile -->
            <div class="border-t border-aadi-teal-700 px-3 py-4">
                <div
                    v-if="user"
                    class="flex items-center gap-3 px-3 py-2"
                >
                    <Avatar
                        :src="user.avatar"
                        :name="user.name"
                        size="sm"
                    />
                    
                    <Transition name="fade">
                        <div v-if="isSidebarOpen" class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                            <p class="text-xs text-aadi-teal-200 truncate">{{ user.email }}</p>
                        </div>
                    </Transition>
                </div>

                <!-- Logout -->
                <button
                    v-if="isSidebarOpen"
                    @click="emit('logout')"
                    class="mt-2 w-full flex items-center gap-2 px-3 py-2 text-sm text-aadi-teal-200 hover:text-white hover:bg-aadi-teal-700 rounded-lg transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </div>

            <!-- Toggle Button -->
            <button
                @click="isSidebarOpen = !isSidebarOpen"
                class="mx-3 mb-3 px-3 py-2 text-aadi-teal-200 hover:text-white hover:bg-aadi-teal-700 rounded-lg transition-colors"
            >
                <svg
                    :class="['h-5 w-5 mx-auto transition-transform', isSidebarOpen ? '' : 'rotate-180']"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <slot name="header" />
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
