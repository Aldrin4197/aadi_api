<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
const page = usePage();
const signedIn = computed(() => !!page.props.auth?.user);
const section = id => page.url.split(/[?#]/)[0] === '/' ? `#${id}` : `/#${id}`;
const year = new Date().getFullYear();
</script>

<template>
  <footer class="site-footer" aria-label="Aadi footer">
    <div class="site-footer-inner">
      <div class="site-footer-grid">
        <div class="site-footer-brand">
          <Link href="/" aria-label="Aadi home"><img src="/images/logos/Aadi_Color.png" alt="Aadi" width="100" height="44" loading="lazy" /></Link>
          <p class="site-footer-tagline">Good things. Nearby.</p>
          <p>Find a space, rent a ride, or discover local talent. Your neighborhood has more to offer.</p>
        </div>
        <nav class="site-footer-links" aria-label="Discover Aadi">
          <h2>Discover Aadi</h2>
          <Link href="/explore">Explore the map</Link>
          <a :href="section('featured-listings')">Featured finds</a>
          <a :href="section('how-it-works')">How it works</a>
          <a :href="section('community-reviews')">Community stories</a>
          <a :href="section('download-aadi')">Get the app</a>
        </nav>
        <nav class="site-footer-links" aria-label="Your Aadi account">
          <h2>{{ signedIn ? 'Your account' : 'Join the neighborhood' }}</h2>
          <template v-if="signedIn">
            <Link href="/profile">Profile & settings</Link>
            <Link href="/inquiries">My inquiries</Link>
            <Link href="/my-listings">My listings</Link>
            <Link href="/become-provider">Provider roles</Link>
          </template>
          <template v-else>
            <Link href="/login">Log in</Link>
            <Link href="/register">Create an account</Link>
            <Link href="/become-provider">Become a provider</Link>
          </template>
        </nav>
      </div>
      <div class="site-footer-bottom"><small>© {{ year }} Aadi. All rights reserved.</small><span>Spaces · Vehicles · Rakets</span></div>
    </div>
  </footer>
</template>

<style scoped>
.site-footer { flex-shrink: 0; background: #f2f9f8; border-top: 1px solid var(--line, #dce6e2); color: var(--ink, #193e3b); padding: 40px 32px 20px; }
.site-footer-inner { max-width: 1200px; margin: auto; }
.site-footer-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) repeat(2, minmax(0, 1fr)); gap: 40px; }
.site-footer-brand>a { display: inline-flex; min-height: 44px; align-items: center; }
.site-footer-brand img { width: 100px; height: 44px; object-fit: contain; }
.site-footer-brand p { max-width: 320px; font-size: 13px; line-height: 1.7; color: var(--muted, #64746d); margin-top: 12px; }
.site-footer-brand .site-footer-tagline { font-weight: 700; color: var(--ink, #193e3b); }
.site-footer-links { display: flex; align-items: flex-start; flex-direction: column; min-width: 0; }
.site-footer-links h2 { font-size: 13px; font-weight: 700; margin: 0 0 12px; line-height: 1.5; }
.site-footer-links a { display: inline-flex; align-items: center; min-height: 44px; padding: 8px 0; font-size: 13px; line-height: 1.5; overflow-wrap: anywhere; }
.site-footer a:hover { text-decoration: underline; text-underline-offset: 4px; }
.site-footer a:focus-visible { outline: 3px solid var(--aadi-orange, #e3a44b); outline-offset: 4px; border-radius: 2px; }
.site-footer-bottom { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px 24px; border-top: 1px solid var(--line, #dce6e2); margin-top: 32px; padding-top: 20px; color: var(--muted, #64746d); font-size: 12px; }
.site-footer-bottom small { font-size: inherit; }
@media (max-width: 1100px) { .site-footer-grid { gap: 24px; grid-template-columns: repeat(2, minmax(0, 1fr)); } .site-footer-brand { grid-column: 1 / -1; } .site-footer-brand p { max-width: 480px; } }
@media (max-width: 600px) { .site-footer { padding: 32px 20px 20px; } .site-footer-grid { gap: 24px 20px; } .site-footer-bottom { margin-top: 24px; flex-direction: column; } }
</style>
