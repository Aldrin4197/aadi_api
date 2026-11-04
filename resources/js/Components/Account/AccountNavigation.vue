<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
const props = defineProps({ compact: Boolean });
const page = usePage();
const user = computed(() => page.props.auth?.user);
const path = computed(() => page.url.split(/[?#]/)[0]);
const items = [
  { label: 'Saved listings', href: '/saved-listings', icon: 'M6 3h12v18l-6-4-6 4z' },
  { label: 'Dashboard', href: '/dashboard', icon: 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z' },
  { label: 'My listings', href: '/my-listings', icon: 'M3 10l9-7 9 7M5 9v12h14V9M9 21v-8h6v8' },
  { label: 'Provider roles', href: '/become-provider', icon: 'M9 6V3h6v3M3 6h18v14H3zM3 11l9 4 9-4M12 12v4' },
  { label: 'Profile & settings', href: '/profile', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0M4 21v-2a8 8 0 0116 0v2' },
];
function active(item) { return path.value === item.href || path.value.startsWith(item.href + '/') || (item.href === '/become-provider' && path.value.startsWith('/provider-applications/')); }
const hint = label => props.compact ? label : undefined;
</script>
<template>
  <Link href="/" class="account-brand" aria-label="Aadi home"><img src="/images/logos/Aadi_icon.png" alt="" /><span v-if="!compact">Aadi<small>Good things. Nearby.</small></span></Link>
  <nav class="account-navigation" aria-label="User workspace">
    <p v-if="!compact" class="account-nav-label">Discover</p>
    <Link href="/" class="account-nav-link" aria-label="Back to Explore" :title="hint('Back to Explore')"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3l6 3 6-3v15l-6 3-6-3-6 3V6zM9 3v15M15 6v15" /></svg><template v-if="!compact">Back to Explore<span class="account-nav-arrow" aria-hidden="true">↗</span></template></Link>
    <p v-if="!compact" class="account-nav-label">My workspace</p>
    <Link v-for="item in items" :key="item.href" :href="item.href" class="account-nav-link" :aria-label="item.label" :title="hint(item.label)" :aria-current="active(item) ? 'page' : undefined"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="item.icon" /></svg><template v-if="!compact">{{ item.label }}</template></Link>
    <template v-if="user?.is_admin">
      <p v-if="!compact" class="account-nav-label">Administration</p>
      <Link href="/admin/dashboard" class="account-nav-link" aria-label="Admin dashboard" :title="hint('Admin dashboard')"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6M12 3l9 4v6c0 4-9 8-9 8S3 17 3 13V7z" /></svg><template v-if="!compact">Admin dashboard<span class="account-nav-arrow" aria-hidden="true">↗</span></template></Link>
      <Link href="/admin/approvals" class="account-nav-link" aria-label="Review applications" :title="hint('Review applications')" :aria-current="path === '/admin/approvals' ? 'page' : undefined"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h18v13H8l-5 4z" /></svg><template v-if="!compact">Review applications</template></Link>
    </template>
  </nav>
</template>
