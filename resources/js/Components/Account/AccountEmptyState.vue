<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, required: true },
    actionHref: String,
    actionLabel: String,
});
</script>

<template>
    <section class="account-empty-state">
        <span class="account-empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path :d="icon" /></svg>
        </span>
        <div class="account-empty-copy">
            <h2>{{ title }}</h2>
            <p>{{ description }}</p>
        </div>
        <Link v-if="actionHref && actionLabel" :href="actionHref" class="button primary">
            {{ actionLabel }} <span aria-hidden="true">→</span>
        </Link>
    </section>
</template>
