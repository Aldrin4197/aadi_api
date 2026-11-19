<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    active: { type: String, required: true },
});
</script>

<template>
    <nav class="inbox-navigation" aria-label="Inbox sections">
        <Link href="/messages" class="inbox-navigation-link" :aria-current="active === 'messages' ? 'page' : undefined">Messages</Link>
        <Link href="/requests" class="inbox-navigation-link" :aria-current="active === 'requests' ? 'page' : undefined">Requests</Link>
    </nav>
</template>

<style scoped>
.inbox-navigation { display:flex; gap:24px; margin-bottom:16px; border-bottom:1px solid var(--line); }
.inbox-navigation-link { display:inline-flex; align-items:center; min-height:44px; margin-bottom:-1px; border-bottom:2px solid transparent; color:var(--muted); font-size:var(--aadi-text-small); font-weight:650; text-decoration:none; touch-action:manipulation; }
.inbox-navigation-link[aria-current="page"] { border-bottom-color:var(--teal); color:var(--teal); }
.inbox-navigation-link:focus-visible { outline:2px solid var(--teal); outline-offset:2px; }
</style>
