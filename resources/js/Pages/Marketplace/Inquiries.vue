<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/AccountLayout.vue';
import '../../../css/inquiries.css';
defineProps({inquiries:Object});
const serviceNames = {spaces:'Property',haul:'Vehicle',raketeer:'Raket'};
const date = value => new Date(value).toLocaleDateString('en-PH', {month:'short',day:'numeric',year:'numeric'});
</script>
<template><Layout><Head title="My inquiries" />
  <main class="inquiries-content"><p class="eyebrow">Your conversations</p><h1>My inquiries</h1><p class="muted">Ask about a listing, discuss the details, and keep your replies together.</p>
    <div v-if="!inquiries.data.length" class="empty-state"><h2>No inquiries yet.</h2><p>Open a listing and send the provider a message. Inquiries about your own listings will also appear here.</p><Link href="/explore" class="button primary">Explore nearby</Link></div>
    <div v-else class="inquiry-list"><Link v-for="item in inquiries.data" :key="item.id" :href="`/inquiries/${item.id}`" class="inquiry-row"><div><span class="eyebrow">{{ serviceNames[item.service] }} · {{ item.direction==='received'?'Received':'Sent' }}</span><h2>{{ item.listingTitle }}</h2><p class="inquiry-person">{{ item.direction==='received'?item.customer.name:item.owner.name }}</p><p class="inquiry-snippet">{{ item.lastMessage }}</p></div><time :datetime="item.updatedAt">{{ date(item.updatedAt) }}</time></Link></div>
    <nav v-if="inquiries.meta.lastPage>1" class="pagination" aria-label="Inquiry pages"><Link v-if="inquiries.meta.currentPage>1" :href="`/inquiries?page=${inquiries.meta.currentPage-1}`">← Previous</Link><span>Page {{ inquiries.meta.currentPage }} of {{ inquiries.meta.lastPage }}</span><Link v-if="inquiries.meta.currentPage<inquiries.meta.lastPage" :href="`/inquiries?page=${inquiries.meta.currentPage+1}`">Next →</Link></nav>
  </main>
</Layout></template>
