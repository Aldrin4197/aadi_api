<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { accountLinks, isActiveLink } from './accountLinks.mjs';
const props = defineProps({ compact: Boolean });
const page = usePage();
const user = computed(() => page.props.auth?.user);
const path = computed(() => page.url.split(/[?#]/)[0]);
const links = computed(() => accountLinks({ isProvider: page.props.auth?.isProvider }));
const sections = computed(() => [
  { key: 'discover', label: 'Discover' },
  { key: 'account', label: 'My account' },
  { key: 'provider', label: 'Provider tools' },
].map(section => ({ ...section, items: links.value.filter(link => link.section === section.key) })).filter(section => section.items.length));
const hint = label => props.compact ? label : undefined;
</script>
<template>
  <Link href="/explore" class="account-brand" aria-label="Aadi map"><img src="/images/logos/Aadi_icon.png" alt="" /><span v-if="!compact">Aadi<small>Good things. Nearby.</small></span></Link>
  <nav class="account-navigation" aria-label="User workspace">
    <template v-for="section in sections" :key="section.key">
      <p v-if="!compact" class="account-nav-label">{{ section.label }}</p>
      <Link v-for="item in section.items" :key="item.href" :href="item.href" class="account-nav-link" :aria-label="item.label" :title="hint(item.label)" :aria-current="isActiveLink(path, item) ? 'page' : undefined"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="item.icon" /></svg><template v-if="!compact">{{ item.label }}</template></Link>
    </template>
    <template v-if="user?.is_admin">
      <p v-if="!compact" class="account-nav-label">Administration</p>
      <Link href="/admin/dashboard" class="account-nav-link" aria-label="Admin dashboard" :title="hint('Admin dashboard')"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6M12 3l9 4v6c0 4-9 8-9 8S3 17 3 13V7z" /></svg><template v-if="!compact">Admin dashboard<span class="account-nav-arrow" aria-hidden="true">↗</span></template></Link>
      <Link href="/admin/approvals" class="account-nav-link" aria-label="Review applications" :title="hint('Review applications')" :aria-current="path === '/admin/approvals' ? 'page' : undefined"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h18v13H8l-5 4z" /></svg><template v-if="!compact">Review applications</template></Link>
    </template>
  </nav>
</template>
