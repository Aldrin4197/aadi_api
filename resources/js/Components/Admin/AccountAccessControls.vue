<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ owner: { type: Object, required: true }, reasons: { type: Object, required: true }, history: { type: Array, required: true } });
const action = ref('');
const form = useForm({ action: '', reason: '', until: '' });
const reset = useForm({});
const resetConfirm = ref(false);
const message = ref('');
function submit() {
    form.action = action.value;
    message.value = '';
    form.transform(data => ({ ...data, until: data.action === 'lock' && data.until ? new Date(data.until).toISOString() : null, reason: data.action === 'restore' ? null : data.reason }))
        .patch(`/admin/users/${props.owner.id}/restriction`, { preserveScroll: true, onSuccess: () => { action.value = ''; form.reset(); message.value = 'Account access updated.'; } });
}
function sendReset() {
    message.value = '';
    reset.post(`/admin/users/${props.owner.id}/password-reset`, { preserveScroll: true, onSuccess: () => { resetConfirm.value = false; message.value = 'Password reset link sent.'; } });
}
</script>

<template>
    <section class="account-access" aria-label="Account access">
        <h2>Account access</h2>
        <p v-if="message" role="status">{{ message }}</p>
        <p v-if="owner.restricted && owner.restriction_type">Currently {{ owner.restriction_type }}<span v-if="owner.restricted_until"> until {{ new Date(owner.restricted_until).toLocaleString() }}</span>. Reason: {{ reasons[owner.restriction_reason] }}</p>
        <p v-else-if="!owner.deletion_scheduled_for">Account access is active<span v-if="owner.restriction_type === 'locked'"> (temporary lock expired)</span>.</p>
        <p>Restricted providers' listings stay visible but cannot receive requests. Existing requests are not cancelled.</p>
        <div v-if="!owner.is_admin && !action" class="actions">
            <button type="button" class="button" @click="action = 'lock'">Temporary lock</button>
            <button type="button" class="button" @click="action = 'disable'">Disable account</button>
            <button v-if="owner.restriction_type" type="button" class="button" @click="action = 'restore'">Restore access</button>
        </div>
        <form v-if="action" @submit.prevent="submit">
            <strong>{{ action === 'lock' ? 'Temporary lock' : action === 'disable' ? 'Disable account' : 'Restore account access' }}</strong>
            <label v-if="action !== 'restore'">Reason
                <select v-model="form.reason" required><option value="" disabled>Choose a reason</option><option v-for="(label, value) in reasons" :key="value" :value="value">{{ label }}</option></select>
            </label>
            <label v-if="action === 'lock'">Lock until ({{ Intl.DateTimeFormat().resolvedOptions().timeZone }}) <input v-model="form.until" type="datetime-local" required /></label>
            <p v-for="(error, key) in form.errors" :key="key" role="alert">{{ error }}</p>
            <div class="actions"><button type="submit" class="button primary" :disabled="form.processing">Confirm</button><button type="button" class="button" @click="action = ''">Cancel</button></div>
        </form>
        <div class="reset">
            <button v-if="!resetConfirm" type="button" class="button" @click="resetConfirm = true">Send password reset email</button>
            <div v-else class="actions"><span>Send a reset link to {{ owner.email }}?</span><button type="button" class="button primary" :disabled="reset.processing" @click="sendReset">Send email</button><button type="button" class="button" @click="resetConfirm = false">Cancel</button></div>
            <p v-for="(error, key) in reset.errors" :key="key" role="alert">{{ error }}</p>
        </div>
        <details v-if="history.length"><summary>Access history</summary><ul><li v-for="(entry, index) in history" :key="index">{{ entry.created_at }}: {{ entry.action }} by {{ entry.admin || 'Former administrator' }}<span v-if="entry.reason">, {{ reasons[entry.reason] }}</span><span v-if="entry.until">, until {{ entry.until }}</span></li></ul></details>
    </section>
</template>

<style scoped>
.account-access { margin: 20px 0; padding: 20px; border: 1px solid #dce7e4; border-radius: 12px; background: white; }
.account-access h2 { font-size: 18px; font-weight: 700; }
.account-access p, .account-access li { margin: 8px 0; font-size: 13px; line-height: 1.5; }
.account-access form, .account-access label { display: grid; gap: 8px; margin-top: 12px; }
.account-access input, .account-access select { max-width: 400px; padding: 10px; border: 1px solid #b5ceca; border-radius: 8px; font-size: 16px; }
.account-access .button { min-height: 44px; padding: 9px 14px; border: 1px solid #b5ceca; border-radius: 8px; color: #12675f; background: white; font-weight: 700; cursor: pointer; }
.account-access .button.primary { border-color: #137671; color: white; background: #137671; }
.account-access .button:disabled { opacity: .6; cursor: wait; }
.account-access [role="alert"] { color: #a12626; }
.actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 12px; }
.reset, details { margin-top: 16px; }
summary { cursor: pointer; font-weight: 700; }
</style>
