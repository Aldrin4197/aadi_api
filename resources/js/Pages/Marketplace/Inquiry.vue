<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Layout from '@/Layouts/AccountLayout.vue';
import '../../../css/inquiries.css';
const props = defineProps({inquiry:Object,messages:Object,listingAvailable:Boolean});
const page = usePage();
const form = useForm({message:''});
const date = value => new Date(value).toLocaleString('en-PH', {month:'short',day:'numeric',hour:'numeric',minute:'2-digit'});
function send() { form.post(`/inquiries/${props.inquiry.id}/messages`, {preserveScroll:true,onSuccess:()=>form.reset()}); }
</script>
<template><Layout><Head :title="`Inquiry · ${inquiry.listingTitle}`" />
  <main class="inquiries-content"><Link href="/inquiries" class="text-button">← My inquiries</Link><h1>{{ inquiry.listingTitle }}</h1><p class="muted">Conversation with {{ inquiry.direction==='received'?inquiry.customer.name:inquiry.owner.name }}</p>
    <Link v-if="listingAvailable" :href="`/offers/${inquiry.service}/${inquiry.listingId}`" class="text-button">View listing →</Link><p v-else class="notice">This listing is no longer publicly available. You can still read and reply to this conversation.</p>
    <nav v-if="messages.meta.lastPage>1" class="pagination" aria-label="Message history"><Link v-if="messages.meta.currentPage<messages.meta.lastPage" :href="`/inquiries/${inquiry.id}?page=${messages.meta.currentPage+1}`">← Older messages</Link><span>Page {{ messages.meta.currentPage }} of {{ messages.meta.lastPage }}</span><Link v-if="messages.meta.currentPage>1" :href="`/inquiries/${inquiry.id}?page=${messages.meta.currentPage-1}`">Newer messages →</Link></nav>
    <ol class="inquiry-messages" aria-label="Messages"><li v-for="message in messages.data" :key="message.id" class="inquiry-message" :class="{mine:message.senderId===page.props.auth.user.id}"><div><strong>{{ message.senderId===page.props.auth.user.id?'You':inquiry.direction==='received'?inquiry.customer.name:inquiry.owner.name }}</strong><time :datetime="message.createdAt">{{ date(message.createdAt) }}</time></div><p>{{ message.body }}</p></li></ol>
    <form class="inquiry-compose" @submit.prevent="send"><label for="reply-message">Your reply<textarea id="reply-message" v-model="form.message" required maxlength="3000" rows="4" placeholder="Write a reply…" :aria-invalid="!!form.errors.message" aria-describedby="reply-error" /></label><p id="reply-error" class="error" role="alert">{{ form.errors.message }}</p><div class="actions"><button class="button primary" type="submit" :disabled="form.processing">{{ form.processing?'Sending…':'Send reply' }}</button><span class="muted">Your message is shared only with this conversation’s participants.</span></div></form>
  </main>
</Layout></template>
