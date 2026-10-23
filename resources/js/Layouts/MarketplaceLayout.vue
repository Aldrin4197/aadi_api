<script setup>
import { computed } from 'vue';
import SiteFooter from '@/Components/Aadi/Layout/SiteFooter.vue';
import AccountMenu from '@/Components/Account/AccountMenu.vue';
import { Link, usePage } from '@inertiajs/vue3';
import '../../css/marketplace.css';
import '../../css/map-explorer.css';
const page = usePage();
const isHome = computed(() => page.url.split(/[?#]/)[0] === '/');
</script>
<template>
  <div class="marketplace browsing-layout">
    <a v-if="isHome" href="#explore-map" class="market-skip-link">Skip to the listings map</a>
    <header class="market-header">
      <Link href="/" class="brand" aria-label="Aadi home"><img src="/images/logos/Aadi_Color.png" alt="Aadi" /><span>Good things. Nearby.</span></Link>
      <nav aria-label="Main navigation">
        <a v-if="isHome" href="#explore-map" class="current">Explore</a><Link v-else href="/explore" :class="{ current: page.url.startsWith('/explore') }">Explore</Link>
        <a v-if="isHome" href="#how-it-works" class="landing-nav-link">How it works</a>
        <a v-if="isHome" href="#for-providers" class="landing-nav-link">For providers</a>
        <template v-if="page.props.auth?.user">
          <AccountMenu />
        </template>
        <template v-else><Link href="/login">Log in</Link><Link href="/register" class="button primary">Register</Link></template>
      </nav>
    </header>
    <div class="browsing-content">
      <div v-if="page.props.flash?.success" class="notice success" role="status">{{ page.props.flash.success }}</div>
      <slot />
    </div>
    <SiteFooter />
  </div>
</template>

<style scoped>
.browsing-layout { display: flex; flex-direction: column; min-height: 100dvh; }
.browsing-content { flex: 1; min-width: 0; }
</style>
