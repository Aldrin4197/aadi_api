<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>
<template><main class="min-h-screen bg-[#f5f8f3] flex flex-col items-center justify-center px-5 py-10"><Link href="/" aria-label="Aadi home"><img src="/images/logos/Aadi_Color.png" alt="Aadi" class="h-12 w-auto mb-8"/></Link><div class="w-full max-w-md rounded-2xl border border-[#dce6e2] bg-white p-8"><slot/></div><Link href="/" class="mt-6 text-sm text-[#167c71]">← Explore nearby listings</Link></main></template>
