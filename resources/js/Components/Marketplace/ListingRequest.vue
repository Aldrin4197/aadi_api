<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import BookingDatePicker from './BookingDatePicker.vue';

const props = defineProps({ listing: { type: Object, required: true } });
const page = usePage();
const open = ref(false);
const form = useForm({ requestedAt: '', driverRequested: false, note: '', shareContact: false });
const label = { spaces: 'Request property viewing', haul: 'Request vehicle rental', raketeer: 'Request service' }[props.listing.service];
function submit() {
    form.post(`/offers/${props.listing.service}/${props.listing.id}/requests`, { preserveScroll: true });
}
</script>

<template>
    <div class="request-box">
        <p v-if="listing.isSample">This is a sample listing and does not accept requests.</p>
        <button v-else-if="!open" type="button" class="button primary request-trigger" @click="open = true">{{ label }}</button>
        <form v-else class="request-form" @submit.prevent="submit">
            <h2>{{ label }}</h2>
            <p>This is a request, not a confirmed booking. The provider must accept it.</p>
            <p v-if="listing.service === 'spaces' && listing.minimumStayMonths">Minimum stay: {{ listing.minimumStayMonths }} month(s).</p>
            <p v-if="listing.service === 'spaces' && listing.contractRules" class="request-rules">Contract rules: {{ listing.contractRules }}</p>
            <div>
                <strong>{{ listing.service === 'spaces' ? 'Preferred viewing date and time' : listing.service === 'haul' ? 'Rental start date' : 'Preferred start date' }}</strong>
                <BookingDatePicker v-model="form.requestedAt" :timezone="page.props.timezone" :with-time="listing.service === 'spaces'" />
            </div>
            <label v-if="listing.service === 'haul' && listing.driverAvailable" class="request-check">
                <input v-model="form.driverRequested" type="checkbox" /> Request a driver
            </label>
            <p v-if="listing.service === 'haul'">Rental duration, driver charges and payment are arranged directly with the provider. The listed vehicle rate is per day.</p>
            <p v-else>Payment and further details are arranged directly with the provider.</p>
            <label>Message to provider (optional)<textarea v-model="form.note" maxlength="2000" rows="3"></textarea></label>
            <label class="request-check"><input v-model="form.shareContact" type="checkbox" required /> If the provider accepts, share my account email and phone with them and show me theirs.</label>
            <div v-if="Object.keys(form.errors).length" class="notice error" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
            <div class="request-actions"><button class="button primary" :disabled="form.processing">{{ form.processing ? 'Sending…' : 'Send request' }}</button><button type="button" class="button" @click="open = false">Cancel</button></div>
        </form>
    </div>
</template>

<style scoped>
.request-trigger { width:100%; justify-content:center; }
.request-form { display:grid; gap:12px; margin:16px 0; }
.request-form h2 { font-size:18px; margin:0; }
.request-form p { font-size:13px; line-height:1.6; color:#586e6c; margin:0; }
.request-form label:not(.request-check) { display:grid; gap:6px; font-size:13px; font-weight:700; }
.request-form input:not([type=checkbox]), .request-form textarea { width:100%; border:1px solid #c9dbd8; border-radius:8px; padding:10px; font-size:16px; }
.request-check { display:flex; gap:8px; align-items:flex-start; font-size:13px; line-height:1.5; }
.request-check input { flex:none; margin-top:3px; }
.request-actions { display:flex; flex-wrap:wrap; gap:8px; }
.request-rules { white-space:pre-wrap; overflow-wrap:anywhere; }
</style>
