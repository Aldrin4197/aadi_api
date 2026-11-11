<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ item: { type: Object, required: true }, timezone: { type: String, required: true } });
const accepting = ref(false);
const form = useForm({ action: '', shareContact: false });
const kind = { spaces: 'Property viewing', haul: 'Vehicle rental', raketeer: 'Service request' };
function update(action) {
    form.action = action;
    form.patch(`/requests/${props.item.id}`, { preserveScroll: true, onSuccess: () => { accepting.value = false; form.shareContact = false; } });
}
</script>

<template>
    <article class="request-card">
        <div class="request-card-heading">
            <div><p class="request-kind">{{ kind[item.service] }} · {{ item.isProvider ? 'Received' : 'Sent' }}</p><h2>{{ item.listingTitle }}</h2></div>
            <strong class="request-status">{{ item.status }}</strong>
        </div>
        <p>{{ item.isProvider ? 'Customer' : 'Provider' }}: {{ item.otherName }}</p>
        <p>{{ item.service === 'spaces' ? 'Preferred viewing' : 'Preferred start' }}: {{ item.requestedAt }}<span v-if="item.service === 'spaces'"> ({{ timezone }})</span></p>
        <p v-if="item.service === 'haul'">Driver requested: {{ item.driverRequested ? 'Yes' : 'No' }}</p>
        <p v-if="item.note" class="request-note">{{ item.note }}</p>
        <Link v-if="item.canViewListing" class="request-listing-link" :href="`/offers/${item.service}/${item.listingId}`" :aria-label="`View listing: ${item.listingTitle}`">View listing →</Link>
        <p v-else class="request-listing-unavailable">This listing is no longer available to view.</p>
        <div v-if="item.contact" class="request-contact">
            <strong>Contact details</strong>
            <p>Email: <a :href="`mailto:${item.contact.email}`">{{ item.contact.email }}</a></p>
            <p>Phone: <a v-if="item.contact.phone" :href="`tel:${item.contact.phone}`">{{ item.contact.phone }}</a><span v-else>Not provided</span></p>
            <p>Arrange the details and payment directly. No payment is collected here.</p>
        </div>
        <div v-if="Object.keys(form.errors).length" class="notice error" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
        <form v-if="accepting" class="request-confirm" @submit.prevent="update('accept')">
            <label><input v-model="form.shareContact" type="checkbox" required /> I agree to share my account email and phone with this customer and see theirs.</label>
            <div class="request-actions"><button class="button primary" :disabled="form.processing">Confirm acceptance</button><button type="button" class="button" @click="accepting = false">Back</button></div>
        </form>
        <div v-else class="request-actions">
            <button v-if="item.isProvider && item.status === 'pending'" type="button" class="button primary" @click="accepting = true">Accept</button>
            <button v-if="item.isProvider && item.status === 'pending'" type="button" class="button" :disabled="form.processing" @click="update('decline')">Decline</button>
            <button v-if="['pending', 'accepted'].includes(item.status)" type="button" class="button" :disabled="form.processing" @click="update('cancel')">Cancel request</button>
        </div>
    </article>
</template>

<style scoped>
.request-card { padding:24px; background:#fff; border:1px solid #dcece9; border-radius:12px; }
.request-card-heading { display:flex; gap:16px; justify-content:space-between; align-items:start; }
.request-card h2 { font-size:19px; margin:4px 0 12px; overflow-wrap:anywhere; }
.request-card p { color:#586e6c; margin:8px 0; font-size:14px; line-height:1.55; overflow-wrap:anywhere; }
.request-card .request-kind { font-size:12px; font-weight:700; color:#137671; margin:0; }
.request-status { text-transform:capitalize; font-size:12px; color:#12675f; background:#e6f6f2; padding:6px 10px; border-radius:20px; }
.request-note { white-space:pre-wrap; }
.request-listing-link { display:inline-flex; align-items:center; min-height:44px; color:#12675f; font-size:14px; font-weight:700; text-decoration:underline; }
.request-contact { padding:14px; background:#f3f8f6; border-radius:8px; margin:16px 0; }
.request-contact a { color:#12675f; text-decoration:underline; }
.request-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; }
.request-confirm { margin-top:16px; }
.request-confirm label { display:flex; align-items:flex-start; gap:8px; font-size:14px; line-height:1.5; }
.request-confirm input { flex:none; margin-top:4px; }
@media(max-width:600px) { .request-card { padding:18px; } }
</style>
