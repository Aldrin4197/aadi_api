<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import '../../css/marketplace.css';
import '../../css/map-explorer.css';
const page = usePage();
const isHome = computed(() => page.url.split(/[?#]/)[0] === '/');
</script>
<template>
  <div class="marketplace">
    <a v-if="isHome" href="#explore-map" class="market-skip-link">Skip to the listings map</a>
    <header class="market-header">
      <Link href="/" class="brand" aria-label="Aadi home"><img src="/images/logos/Aadi_Color.png" alt="Aadi" /><span>Good things. Nearby.</span></Link>
      <nav aria-label="Main navigation">
        <a v-if="isHome" href="#explore-map" class="current">Explore</a><Link v-else href="/explore" :class="{ current: page.url.startsWith('/explore') }">Explore</Link>
        <a v-if="isHome" href="#how-it-works" class="landing-nav-link">How it works</a>
        <a v-if="isHome" href="#for-providers" class="landing-nav-link">For providers</a>
        <template v-if="page.props.auth?.user">
          <Link v-if="Object.values(page.props.auth.providerRoles || {}).includes('approved')" href="/my-listings">Provider workspace</Link><Link v-else href="/become-provider">Become a provider</Link>
          <Link v-if="page.props.auth.user.is_admin" href="/admin/dashboard">Admin dashboard</Link>
          <Link href="/profile">{{ page.props.auth.user.name }}</Link>
          <Link href="/logout" method="post" as="button" class="quiet">Log out</Link>
        </template>
        <template v-else><Link href="/login">Log in</Link><Link href="/register" class="button primary">Register</Link></template>
      </nav>
    </header>
    <div v-if="page.props.flash?.success" class="notice success" role="status">{{ page.props.flash.success }}</div>
    <slot />
    <slot name="footer" />
  </div>
</template>
