<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountNavigation from '@/Components/Account/AccountNavigation.vue';
import AccountMenu from '@/Components/Account/AccountMenu.vue';
import '../../css/marketplace.css';
import '../../css/account.css';
const page = usePage();
const drawer = ref(null);
const drawerOpen = ref(false);
const title = computed(() => page.url.startsWith('/profile') ? 'Profile & settings' : page.url.startsWith('/my-listings') ? 'My listings' : page.url.startsWith('/admin/approvals') ? 'Review applications' : 'Provider roles');
function closeDrawer() { drawer.value?.close(); drawerOpen.value = false; }
function openDrawer() { drawer.value?.showModal(); drawerOpen.value = true; }
let desktop;
let previousOverflow;
watch(drawerOpen, open => {
  if (open) { previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; }
  else if (previousOverflow !== undefined) { document.body.style.overflow = previousOverflow; previousOverflow = undefined; }
});
function resize() { if (desktop?.matches) closeDrawer(); }
onMounted(() => { desktop = window.matchMedia('(min-width: 1024px)'); desktop.addEventListener('change', resize); });
onBeforeUnmount(() => {
  desktop?.removeEventListener('change', resize);
  if (previousOverflow !== undefined) document.body.style.overflow = previousOverflow;
});
watch(() => page.url, closeDrawer);
</script>
<template>
  <div class="marketplace account-layout">
    <a class="account-skip" href="#account-content">Skip to content</a>
    <aside class="account-sidebar"><AccountNavigation /></aside>
    <dialog id="account-drawer" ref="drawer" class="account-drawer" aria-label="Account navigation" @keydown.esc.prevent.stop="closeDrawer" @close="drawerOpen = false" @click="event => { if (event.target === drawer) closeDrawer(); }">
      <div class="account-drawer-inner"><button class="account-drawer-close" aria-label="Close navigation" autofocus @click="closeDrawer">✕</button><AccountNavigation /></div>
    </dialog>
    <div class="account-body">
      <header class="account-header">
        <button class="account-mobile-toggle" aria-label="Open account navigation" aria-controls="account-drawer" :aria-expanded="drawerOpen" @click="openDrawer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg></button>
        <div class="account-header-title"><span>My account</span><strong>{{ title }}</strong></div>
        <Link href="/explore" class="account-explore-link">Explore nearby ↗</Link>
        <AccountMenu />
      </header>
      <div id="account-content" tabindex="-1" class="account-content">
        <div v-if="page.props.flash?.success" class="notice success" role="status">{{ page.props.flash.success }}</div>
        <slot />
      </div>
    </div>
  </div>
</template>
