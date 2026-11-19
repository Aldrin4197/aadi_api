<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ item: { type: Object, required: true } });
const open = ref(false);
const converting = ref(false);
const checkedPeriod = ref('');
const edit = useForm({ status: props.item.status, amount: props.item.agreedCents === null ? '' : (props.item.agreedCents / 100).toFixed(2), ...(props.item.kind === 'tenancy' ? { start: props.item.start, end: props.item.end || '' } : {}) });
const payment = useForm({ amount: '', paid_on: '', period: '', note: '' });
const conversion = useForm({ start: '', end: '', amount: '' });
const voidForm = useForm({});
const activePayments = computed(() => props.item.payments.filter(entry => !entry.voided));
const paid = computed(() => activePayments.value.reduce((sum, entry) => sum + entry.amountCents, 0));
const periods = computed(() => Object.entries(activePayments.value.reduce((result, entry) => {
    result[entry.period] = (result[entry.period] || 0) + entry.amountCents;
    return result;
}, {})));
const checkedPaid = computed(() => activePayments.value.filter(entry => entry.period === checkedPeriod.value.trim()).reduce((sum, entry) => sum + entry.amountCents, 0));
const money = cents => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(cents / 100);
const label = { viewing: 'Viewing', tenancy: 'Tenancy', rental: 'Vehicle rental', job: 'Service job' };
const errors = form => Object.values(form.errors);
watch(() => props.item, item => {
    edit.status = item.status;
    edit.amount = item.agreedCents === null ? '' : (item.agreedCents / 100).toFixed(2);
    if (item.kind === 'tenancy') { edit.start = item.start; edit.end = item.end || ''; }
});
function save() { edit.patch(`/provider/records/${props.item.id}`, { preserveScroll: true }); }
function addPayment() { payment.post(`/provider/records/${props.item.id}/payments`, { preserveScroll: true, onSuccess: () => payment.reset() }); }
function convert() { conversion.post(`/provider/records/${props.item.id}/tenancy`, { preserveScroll: true, onSuccess: () => { conversion.reset(); converting.value = false; } }); }
function voidPayment(entry) {
    if (window.confirm('Void this payment entry? It will remain in the history.')) {
        voidForm.patch(`/provider/records/${props.item.id}/payments/${entry.id}/void`, { preserveScroll: true });
    }
}
</script>

<template>
    <article class="record">
        <button type="button" class="record-heading" :aria-expanded="open" @click="open = !open">
            <span><strong>{{ item.listingTitle }}</strong><small>{{ label[item.kind] }} · {{ item.customerName }}</small></span>
            <span class="record-meta">{{ item.start }}<template v-if="item.end"> – {{ item.end }}</template><small>{{ item.status }}</small></span>
            <span aria-hidden="true">{{ open ? '−' : '+' }}</span>
        </button>
        <div v-if="open" class="record-body">
            <form class="record-form" @submit.prevent="save">
                <label>Status<select v-model="edit.status" :disabled="item.status === 'cancelled'"><option value="scheduled">Scheduled</option><option value="active">Active</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></label>
                <label v-if="item.kind !== 'viewing'">Agreed amount {{ item.kind === 'tenancy' ? 'per period' : '' }} (PHP)<input v-model="edit.amount" type="number" min="0" max="99999999.99" step="0.01" placeholder="Not set"></label>
                <label v-if="item.kind === 'tenancy'">Start date<input v-model="edit.start" type="date" required></label>
                <label v-if="item.kind === 'tenancy'">End date (optional)<input v-model="edit.end" type="date" :min="edit.start"></label>
                <button class="button" :disabled="edit.processing">Save record</button>
                <p v-for="error in errors(edit)" :key="error" class="error" role="alert">{{ error }}</p>
            </form>
            <p v-if="item.kind !== 'tenancy'">To cancel an accepted request, use <Link href="/requests">Requests</Link>.</p>
            <template v-if="item.kind === 'viewing' && !item.converted && item.status !== 'cancelled'">
                <button v-if="!converting" class="button" type="button" @click="converting = true">Record tenancy</button>
                <form v-else class="record-form" @submit.prevent="convert">
                    <label>Start date<input v-model="conversion.start" type="date" required></label>
                    <label>End date (optional)<input v-model="conversion.end" type="date" :min="conversion.start"></label>
                    <label>Rent per period (PHP)<input v-model="conversion.amount" type="number" min="0" max="99999999.99" step="0.01"></label>
                    <button class="button primary" :disabled="conversion.processing">Save tenancy</button>
                    <button class="button" type="button" @click="converting = false">Cancel</button>
                    <p v-for="error in errors(conversion)" :key="error" class="error" role="alert">{{ error }}</p>
                </form>
            </template>
            <p v-if="item.kind === 'viewing' && item.converted">A tenancy was recorded from this viewing.</p>
            <section v-if="item.kind !== 'viewing'" class="payments" aria-label="Payment tracking">
                <h3>Provider-recorded payments</h3>
                <p>These entries are private to you. Aadi does not collect or verify payments.</p>
                <template v-if="item.kind !== 'tenancy'">
                    <p>Recorded: <strong>{{ money(paid) }}</strong><template v-if="item.agreedCents !== null"> · Agreed: {{ money(item.agreedCents) }} · {{ item.agreedCents === 0 ? 'No payment required' : paid >= item.agreedCents ? 'Paid' : paid > 0 ? 'Partially paid' : 'Not marked paid' }}<template v-if="paid < item.agreedCents"> · Remaining: {{ money(item.agreedCents - paid) }}</template></template></p>
                </template>
                <template v-else>
                    <p>Total recorded: <strong>{{ money(paid) }}</strong>. Use the same period label for partial payments. Each period is compared with the current rent amount, including past periods.</p>
                    <label class="period-check">Check a period<input v-model="checkedPeriod" maxlength="60" placeholder="e.g. 2026-11"></label>
                    <p v-if="checkedPeriod.trim()">{{ checkedPeriod.trim() }}: {{ money(checkedPaid) }} recorded · {{ item.agreedCents === null ? 'Agreed amount not set' : item.agreedCents === 0 ? 'No payment required' : checkedPaid >= item.agreedCents ? 'Paid' : checkedPaid > 0 ? `Partially paid · Remaining: ${money(item.agreedCents - checkedPaid)}` : 'Not marked paid' }}</p>
                    <ul v-if="periods.length"><li v-for="[period, amount] in periods" :key="period">{{ period }}: {{ money(amount) }}<template v-if="item.agreedCents !== null"> · {{ item.agreedCents === 0 ? 'No payment required' : amount >= item.agreedCents ? 'Paid' : `Partially paid · Remaining: ${money(item.agreedCents - amount)}` }}</template></li></ul>
                </template>
                <form class="record-form" @submit.prevent="addPayment">
                    <label>Amount (PHP)<input v-model="payment.amount" type="number" min="0.01" max="99999999.99" step="0.01" required></label>
                    <label>Payment date<input v-model="payment.paid_on" type="date" required></label>
                    <label v-if="item.kind === 'tenancy'">Period<input v-model="payment.period" maxlength="60" placeholder="e.g. 2026-11" required></label>
                    <label>Note (optional)<input v-model="payment.note" maxlength="500"></label>
                    <button class="button primary" :disabled="payment.processing">Record payment</button>
                    <p v-for="error in errors(payment)" :key="error" class="error" role="alert">{{ error }}</p>
                </form>
                <ul class="payment-list">
                    <li v-for="entry in item.payments" :key="entry.id">
                        <span>{{ entry.paidOn }} · {{ money(entry.amountCents) }}<template v-if="entry.period"> · {{ entry.period }}</template><template v-if="entry.note"> · {{ entry.note }}</template><strong v-if="entry.voided"> · Voided</strong></span>
                        <button v-if="!entry.voided" type="button" class="button" :disabled="voidForm.processing" @click="voidPayment(entry)">Void</button>
                    </li>
                </ul>
                <p v-for="error in errors(voidForm)" :key="error" class="error" role="alert">{{ error }}</p>
            </section>
        </div>
    </article>
</template>

<style scoped>
.record { background:#fff; border:1px solid #dce7e4; border-radius:10px; }
.record-heading { display:flex; align-items:center; justify-content:space-between; gap:16px; width:100%; padding:16px; text-align:left; color:#183e3b; }
.record-heading small { display:block; margin-top:4px; color:#637b76; }
.record-meta { margin-left:auto; text-align:right; font-size:13px; }
.record-body { border-top:1px solid #e5edeb; padding:16px; font-size:14px; }
.record-form { display:flex; align-items:end; flex-wrap:wrap; gap:12px; margin:12px 0; }
.record-form label { display:grid; gap:4px; color:#42665e; font-size:12px; font-weight:600; }
.record-form input,.record-form select { min-height:40px; padding:8px; border:1px solid #cfdcd8; border-radius:8px; font-size:16px; }
.record-form .error { width:100%; margin:0; color:#b42318; }
.payments { margin-top:18px; border-top:1px solid #e5edeb; padding-top:14px; }
.payments h3 { font-size:15px; }.payments p { margin:8px 0; color:#586e6c; }
.period-check { display:grid; gap:4px; max-width:220px; font-size:12px; font-weight:600; }.period-check input { min-height:40px; padding:8px; border:1px solid #cfdcd8; border-radius:8px; font-size:16px; }
.payment-list { padding:0; list-style:none; }.payment-list li { display:flex; gap:12px; align-items:center; justify-content:space-between; padding:8px 0; border-top:1px solid #e5edeb; overflow-wrap:anywhere; }
button:focus-visible { outline:2px solid #167c71; outline-offset:3px; }
</style>
