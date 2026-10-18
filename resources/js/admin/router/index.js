import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Layouts
import AdminLayout from '../layouts/AdminLayout.vue';
import GuestLayout from '../layouts/GuestLayout.vue';

// Views
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue';
import Users from '../views/Users.vue';
import Listings from '../views/Listings.vue';
import Tickets from '../views/Tickets.vue';
import Analytics from '../views/Analytics.vue';

const routes = [
  {
    path: '/admin/login',
    name: 'login',
    component: Login,
    meta: { layout: 'guest', requiresAuth: false },
  },
  {
    path: '/admin',
    redirect: '/admin/dashboard',
  },
  {
    path: '/admin/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { layout: 'admin', requiresAuth: true },
  },
  {
    path: '/admin/users',
    name: 'users',
    component: Users,
    meta: { layout: 'admin', requiresAuth: true },
  },
  {
    path: '/admin/listings',
    name: 'listings',
    component: Listings,
    meta: { layout: 'admin', requiresAuth: true },
  },
  {
    path: '/admin/tickets',
    name: 'tickets',
    component: Tickets,
    meta: { layout: 'admin', requiresAuth: true },
  },
  {
    path: '/admin/analytics',
    name: 'analytics',
    component: Analytics,
    meta: { layout: 'admin', requiresAuth: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Try to fetch user if not already loaded
  if (!authStore.user && to.meta.requiresAuth) {
    try {
      await authStore.fetchUser();
    } catch (error) {
      // User not authenticated
    }
  }

  // Check authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' });
  } else if (to.name === 'login' && authStore.isAuthenticated) {
    next({ name: 'dashboard' });
  } else {
    next();
  }
});

export default router;
