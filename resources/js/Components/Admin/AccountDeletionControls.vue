<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ owner: { type: Object, required: true }, reasons: { type: Object, required: true } });
const action = ref('');
const form = useForm({ action: '', reason: '', note: '', when: '', confirm: '' });
const minWhen = () => new Date(Date.now() + 60000).toISOString().slice(0, 16);

function open(next) {
    form.reset();
    form.clearErrors();
    action.value = next;
}
function submit() {
    form.action = action.value;
    form.transform(data => ({
        ...data,
        when: data.action === 'schedule' && data.when ? new Date(data.when).toISOString() : null,
        reason: data.action === 'cancel' ? null : data.reason,
    })).patch(`/admin/users/${props.owner.id}/deletion`, { preserveScroll: true, onSuccess: () => { action.value = ''; form.reset(); } });
}
</script>

<template>
    <section v-if="!owner.is_admin" class="account-deletion" aria-label="Account deletion">
        <h2>Account deletion</h2>
        <p v-if="owner.deletion_scheduled_for">Deactivated and scheduled for permanent deletion on <strong>{{ new Date(owner.deletion_scheduled_for).toLocaleString() }}</strong>.</p>
        <p v-else>Not scheduled. Scheduling deactivates the account immediately and deletes it, with its listings and files, on the chosen date. Only the reason is kept, for 6 months.</p>
        <div v-if="!action" class="actions">
            <button v-if="!owner.deletion_scheduled_for" type="button" class="button" @click="open('schedule')">Schedule deletion</button>
            <button v-else type="button" class="button" @click="open('cancel')">Cancel scheduled deletion</button>
            <button type="button" class="button danger" @click="open('delete_now')">Delete now</button>
        </div>
        <form v-else @submit.prevent="submit">
            <strong>{{ action === 'schedule' ? 'Schedule deletion' : action === 'cancel' ? 'Cancel scheduled deletion' : 'Delete account permanently' }}</strong>
            <label v-if="action !== 'cancel'">Reason
                <select v-model="form.reason" required><option value="" disabled>Choose a reason</option><option v-for="(label, value) in reasons" :key="value" :value="value">{{ label }}</option></select>
            </label>
            <label v-if="action !== 'cancel'">Note (optional, no personal details)
                <textarea v-model="form.note" maxlength="500" rows="2" />
            </label>
            <label v-if="action === 'schedule'">Delete on ({{ Intl.DateTimeFormat().resolvedOptions().timeZone }}) <input v-model="form.when" type="datetime-local" :min="minWhen()" required /></label>
            <label v-if="action === 'delete_now'">Type {{ owner.email }} to confirm. This cannot be undone. <input v-model="form.confirm" type="email" autocomplete="off" required /></label>
            <p v-for="(error, key) in form.errors" :key="key" role="alert">{{ error }}</p>
            <div class="actions"><button type="submit" class="button primary" :disabled="form.processing">Confirm</button><button type="button" class="button" @click="action = ''">Back</button></div>
        </form>
    </section>
</template>

<style scoped>
.account-deletion { margin: 20px 0; padding: 20px; border: 1px solid #ecd3d3; border-radius: 12px; background: white; }
.account-deletion h2 { font-size: 18px; font-weight: 700; }
.account-deletion p { margin: 8px 0; font-size: 13px; line-height: 1.5; }
.account-deletion form, .account-deletion label { display: grid; gap: 8px; margin-top: 12px; font-size: 13px; }
.account-deletion input, .account-deletion select, .account-deletion textarea { max-width: 400px; padding: 10px; border: 1px solid #b5ceca; border-radius: 8px; font-size: 16px; }
.account-deletion .button { min-height: 44px; padding: 9px 14px; border: 1px solid #b5ceca; border-radius: 8px; color: #12675f; background: white; font-weight: 700; cursor: pointer; }
.account-deletion .button.primary { border-color: #137671; color: white; background: #137671; }
.account-deletion .button.danger { border-color: #a12626; color: #a12626; }
.account-deletion .button:disabled { opacity: .6; cursor: wait; }
.account-deletion [role="alert"] { color: #a12626; }
.actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 12px; }
</style>
