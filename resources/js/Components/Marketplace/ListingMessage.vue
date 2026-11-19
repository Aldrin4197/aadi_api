<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ listing: { type: Object, required: true } });
const open = ref(false);
const form = useForm({ body: '' });
function submit() {
    form.post(`/offers/${props.listing.service}/${props.listing.id}/messages`, { preserveScroll: true });
}
</script>

<template>
    <div class="listing-message" :class="{ composing: open }">
        <button v-if="!open" type="button" class="button message-trigger" aria-label="Message provider" @click="open = true">Message</button>
        <form v-else @submit.prevent="submit">
            <label for="listing-message-body">Message about this listing</label>
            <textarea id="listing-message-body" v-model="form.body" rows="3" maxlength="2000" required />
            <p v-if="form.errors.body" class="message-error" role="alert">{{ form.errors.body }}</p>
            <div class="message-actions">
                <button class="button primary" :disabled="form.processing">{{ form.processing ? 'Sending…' : 'Send message' }}</button>
                <button type="button" class="button" @click="open = false">Cancel</button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.listing-message { margin:12px 0; }
.message-trigger { width:100%; justify-content:center; }
form { display:grid; gap:8px; }
label { font-size:13px; font-weight:700; }
textarea { width:100%; border:1px solid #c9dbd8; border-radius:8px; padding:10px; font-size:16px; }
.message-actions { display:flex; flex-wrap:wrap; gap:8px; }
.message-error { color:#a12626; font-size:13px; }
</style>
