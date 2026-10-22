<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import UserAvatar from './UserAvatar.vue';
const page = usePage();
const user = computed(() => page.props.auth?.user);
const path = computed(() => page.url.split(/[?#]/)[0]);
const items = [
  { label: 'My listings', href: '/my-listings', icon: 'M3 10l9-7 9 7M5 9v12h14V9M9 21v-8h6v8' },
  { label: 'Provider roles', href: '/become-provider', icon: 'M9 6V3h6v3M3 6h18v14H3zM3 11l9 4 9-4M12 12v4' },
  { label: 'Profile & settings', href: '/profile', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0M4 21v-2a8 8 0 0116 0v2' },
];
function active(item) { return path.value === item.href || path.value.startsWith(item.href + '/') || (item.href === '/become-provider' && path.value.startsWith('/provider-applications/')); }
</script>
<template>
  <Link href="/" class="account-brand" aria-label="Aadi home"><img src="/images/logos/Aadi_icon.png" alt="" /><span>Aadi<small>Good things. Nearby.</small></span></Link>
  <nav class="account-navigation" aria-label="User workspace">
    <p class="account-nav-label">Discover</p>
    <Link href="/" class="account-nav-link"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3l6 3 6-3v15l-6 3-6-3-6 3V6zM9 3v15M15 6v15" /></svg>Back to Explore<span class="account-nav-arrow" aria-hidden="true">↗</span></Link>
    <p class="account-nav-label">My workspace</p>
    <Link v-for="item in items" :key="item.href" :href="item.href" class="account-nav-link" :aria-current="active(item) ? 'page' : undefined"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="item.icon" /></svg>{{ item.label }}</Link>
    <template v-if="user?.is_admin"><p class="account-nav-label">Administration</p><Link href="/admin/dashboard" class="account-nav-link">Admin dashboard ↗</Link><Link href="/admin/approvals" class="account-nav-link" :aria-current="path === '/admin/approvals' ? 'page' : undefined">Review applications</Link></template>
  </nav>
  <div class="account-sidebar-footer"><UserAvatar :user="user" /><div><strong>{{ user?.name }}</strong><Link href="/logout" method="post" as="button">Log out</Link></div></div>
</template>
