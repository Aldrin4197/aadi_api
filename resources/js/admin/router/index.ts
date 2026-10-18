import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/admin/login',
            name: 'login',
            component: () => import('../views/Login.vue'),
            meta: { guest: true },
        },
        {
            path: '/admin/dashboard',
            name: 'dashboard',
            component: () => import('../views/Dashboard.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/admin/users',
            name: 'users',
            component: () => import('../views/Users.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/admin/listings',
            name: 'listings',
            component: () => import('../views/Listings.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/admin/analytics',
            name: 'analytics',
            component: () => import('../views/Analytics.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/admin/tickets',
            name: 'tickets',
            component: () => import('../views/Tickets.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/admin',
            redirect: '/admin/dashboard',
        },
    ],
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Try to fetch user if not already loaded
    if (!authStore.user && to.meta.requiresAuth) {
        await authStore.fetchUser();
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
