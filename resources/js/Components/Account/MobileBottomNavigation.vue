<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { isActiveLink, mobileAccountLinks } from './accountLinks.mjs';
import '../../../css/mobile-navigation.css';

const page = usePage();
const links = computed(() => mobileAccountLinks({ isProvider: page.props.auth?.isProvider }));
const path = computed(() => page.url.split(/[?#]/)[0]);
</script>

<template>
    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        <Link v-for="link in links" :key="link.href" :href="link.href" class="mobile-bottom-nav-link"
            :aria-current="isActiveLink(path, link) ? 'page' : undefined">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="link.icon" /></svg>
            <span>{{ link.label }}</span>
        </Link>
    </nav>
</template>
