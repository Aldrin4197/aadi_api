<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import '../../css/marketplace.css';
import '../../css/provider.css';
const page = usePage();
const hasRole = computed(() => Object.values(page.props.auth?.providerRoles || {}).includes('approved'));
</script>

<template>
  <div class="marketplace provider-workspace">
    <a class="provider-skip" href="#provider-main">Skip to content</a>
    <header class="provider-header">
      <Link href="/" class="provider-brand" aria-label="Aadi home"><img src="/images/logos/Aadi_Color.png" alt="Aadi" /><span>Provider workspace</span></Link>
      <div class="provider-account"><Link href="/" class="browse-link">Switch to browsing ↗</Link><span class="account-initial" aria-hidden="true">{{ page.props.auth?.user?.name?.slice(0,1) }}</span><Link href="/profile">{{ page.props.auth?.user?.name }}</Link></div>
    </header>
    <nav class="provider-nav" aria-label="Provider navigation">
      <Link v-if="hasRole" href="/my-listings" :aria-current="page.url.startsWith('/my-listings')?'page':undefined">My listings</Link>
      <Link href="/become-provider" :aria-current="page.url.startsWith('/become-provider') || page.url.startsWith('/provider-applications')?'page':undefined">Provider roles</Link>
      <Link v-if="page.props.auth?.user?.is_admin" href="/admin/approvals" :aria-current="page.url.startsWith('/admin/approvals')?'page':undefined">Review applications</Link>
      <Link href="/logout" method="post" as="button" class="provider-signout">Log out</Link>
    </nav>
    <div v-if="page.props.flash?.success" class="notice success" role="status">{{ page.props.flash.success }}</div>
    <slot />
  </div>
</template>
