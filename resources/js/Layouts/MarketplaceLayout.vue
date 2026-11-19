<script setup>
import { computed } from 'vue';
import AccountMenu from '@/Components/Account/AccountMenu.vue';
import NotificationBell from '@/Components/Account/NotificationBell.vue';
import EmailVerifyBanner from '@/Components/Account/EmailVerifyBanner.vue';
import MobileBottomNavigation from '@/Components/Account/MobileBottomNavigation.vue';
import { Link, usePage } from '@inertiajs/vue3';
import '../../css/marketplace.css';
import '../../css/map-explorer.css';
const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth?.user));
const isHome = computed(() => !signedIn.value && page.url.split(/[?#]/)[0] === '/');
</script>
<template>
  <div class="marketplace" :class="{ 'has-mobile-navigation': signedIn }">
    <a v-if="isHome" href="#explore-map" class="market-skip-link">Skip to the listings map</a>
    <header class="market-header">
      <Link :href="signedIn ? '/explore' : '/'" class="brand" :aria-label="signedIn ? 'Aadi map' : 'Aadi home'"><img src="/images/logos/Aadi_Color.png" alt="Aadi" /><span>Good things. Nearby.</span></Link>
      <nav aria-label="Main navigation">
        <a v-if="isHome" href="#explore-map" class="current" aria-current="location">Explore</a><Link v-else href="/explore" :class="{ current: page.url.startsWith('/explore'), 'signed-in-map-link': signedIn }" :aria-current="page.url.startsWith('/explore') ? 'page' : undefined">{{ signedIn ? 'Map' : 'Explore' }}</Link>
        <template v-if="!signedIn">
          <a :href="isHome ? '#how-it-works' : '/#how-it-works'" class="landing-nav-link">How it works</a>
          <a :href="isHome ? '#for-providers' : '/#for-providers'" class="landing-nav-link">For providers</a>
          <a :href="isHome ? '#support' : '/#support'" class="landing-nav-link">Support</a>
        </template>
        <template v-if="signedIn">
          <NotificationBell v-if="page.props.auth?.profileComplete" />
          <AccountMenu />
        </template>
        <template v-else><Link href="/login">Log in</Link><Link href="/register" class="button primary">Register</Link></template>
      </nav>
    </header>
    <EmailVerifyBanner />
    <div v-if="page.props.flash?.success" class="notice success" role="status">{{ page.props.flash.success }}</div>
    <slot />
    <slot name="footer" />
    <MobileBottomNavigation v-if="signedIn" />
  </div>
</template>
