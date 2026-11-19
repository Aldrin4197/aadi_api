<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const seo = computed(() => page.props.seo);
</script>

<template>
    <Head>
        <meta head-key="robots" name="robots" :content="seo.robots" />
        <template v-if="seo.public">
            <meta head-key="description" name="description" :content="seo.description" />
            <link head-key="canonical" rel="canonical" :href="seo.canonical" />
            <meta head-key="og-title" property="og:title" :content="seo.title" />
            <meta head-key="og-description" property="og:description" :content="seo.description" />
            <meta head-key="og-url" property="og:url" :content="seo.canonical" />
            <meta head-key="og-type" property="og:type" content="website" />
            <meta head-key="og-image" property="og:image" :content="seo.image" />
            <meta head-key="twitter-card" name="twitter:card" content="summary" />
        </template>
    </Head>
</template>
