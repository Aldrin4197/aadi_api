<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import UserAvatar from './UserAvatar.vue';
const page = usePage();
const user = computed(() => page.props.auth?.user);
const details = ref(null);
function close() { if (details.value) details.value.open = false; }
function outside(event) { if (!details.value?.contains(event.target)) close(); }
function escape(event) {
  if (event.key === 'Escape' && details.value?.open) {
    close(); details.value.querySelector('summary')?.focus();
    event.stopPropagation();
  }
}
watch(() => page.url, close);
onMounted(() => document.addEventListener('pointerdown', outside));
onBeforeUnmount(() => document.removeEventListener('pointerdown', outside));
</script>
<template>
  <details v-if="user" ref="details" class="account-menu" @keydown="escape" @focusout="event => { if (!event.currentTarget.contains(event.relatedTarget)) close(); }">
    <summary :aria-label="`Account menu for ${user.name}`"><UserAvatar :user="user" /><span class="account-menu-label">My account</span><span aria-hidden="true">⌄</span></summary>
    <div class="account-menu-panel">
      <div class="account-menu-identity"><strong>{{ user.name }}</strong><span>{{ user.email }}</span></div>
      <nav aria-label="Account navigation" @click="close">
        <Link href="/dashboard">My dashboard</Link>
        <Link href="/profile">Profile & settings</Link>
        <Link href="/my-listings">My listings</Link>
        <Link href="/become-provider">Provider roles</Link>
        <Link v-if="user.is_admin" href="/admin/dashboard">Admin dashboard</Link>
        <Link href="/logout" method="post" as="button">Log out</Link>
      </nav>
    </div>
  </details>
</template>
<style scoped>
.account-menu { position: relative; }
.account-menu summary { list-style: none; display: flex; align-items: center; gap: 10px; cursor: pointer; min-height: 44px; border-radius: 24px; font-size: 13px; }
.account-menu summary::-webkit-details-marker { display: none; }
.account-menu-panel { position: absolute; right: 0; top: calc(100% + 12px); width: min(280px, calc(100vw - 32px)); z-index: 1200; border: 1px solid var(--line); border-radius: 12px; background: #fff; box-shadow: 0 12px 36px #193e3b20; padding: 8px; color: var(--ink); }
.account-menu-identity { display: flex; flex-direction: column; gap: 4px; padding: 12px; border-bottom: 1px solid var(--line); overflow-wrap: anywhere; }
.account-menu-identity strong { font-size: 14px; }
.account-menu-identity span { font-size: 12px; color: var(--muted); font-weight: 400; }
.account-menu-panel nav { display: flex; flex-direction: column; align-items: stretch; gap: 2px; padding-top: 6px; }
.account-menu-panel a, .account-menu-panel button { display: block; padding: 12px; border-radius: 6px; font-size: 13px; text-align: left; min-height: 44px; width: 100%; }
.account-menu-panel a:hover, .account-menu-panel button:hover { background: #eff5f1; }
@media (max-width: 700px) { .account-menu-label { display: none; } }
</style>
