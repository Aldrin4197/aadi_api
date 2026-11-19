<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({ status: Number, title: String, message: String });
</script>

<template>
    <Head :title="title">
        <link rel="stylesheet" href="/css/error-page.css">
    </Head>
    <main class="error-page">
        <div class="error-content">
            <a class="error-brand" href="/login"><img src="/images/logos/Aadi_icon.png" alt="">Aadi</a>
            <p class="error-code">ERROR {{ status }}</p>
            <h1>{{ title }}</h1>
            <p class="error-message">{{ message }}</p>
            <a class="error-link" href="/login">Go to sign in <span aria-hidden="true">→</span></a>
        </div>
    </main>
</template>
