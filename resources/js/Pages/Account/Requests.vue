<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import RequestCard from '@/Components/Account/RequestCard.vue';
import Layout from '@/Layouts/AccountLayout.vue';

defineProps({ requests: Object, timezone: String });
</script>

<template>
    <Layout><Head title="Requests" />
        <main class="account-page">
            <AccountPageHeader title="Requests" description="Track your viewing and booking requests, and respond to requests for your listings." />
            <p class="requests-intro">Requests require provider acceptance. Contact details are shared only after acceptance, and payment is arranged directly.</p>
            <p class="requests-intro"><Link href="/calendar">View accepted requests on your calendar →</Link></p>
            <div v-if="requests.data.length" class="requests-list"><RequestCard v-for="item in requests.data" :key="item.id" :item="item" :timezone="timezone" /></div>
            <div v-else class="account-panel"><h2>No requests yet</h2><p>Explore listings to request a viewing, vehicle, or service.</p><Link href="/explore" class="button">Explore listings</Link></div>
            <nav v-if="requests.prev_page_url || requests.next_page_url" class="requests-pagination" aria-label="Requests pagination"><Link v-if="requests.prev_page_url" :href="requests.prev_page_url">Previous</Link><span>Page {{ requests.current_page }} of {{ requests.last_page }}</span><Link v-if="requests.next_page_url" :href="requests.next_page_url">Next</Link></nav>
        </main>
    </Layout>
</template>

<style scoped>
.requests-intro { color:#586e6c; font-size:14px; line-height:1.6; margin-bottom:24px; }
.requests-list { display:grid; gap:16px; }
.requests-pagination { display:flex; gap:20px; justify-content:center; margin-top:24px; }
.requests-pagination a { color:#12675f; text-decoration:underline; }
</style>
