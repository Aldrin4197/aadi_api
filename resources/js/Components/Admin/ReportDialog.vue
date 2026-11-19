<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ report: { type: Object, required: true } });
const emit = defineEmits(['close']);
const dialog = ref(null);
const form = useForm({ action: '', points: 0, note: '' });
const dateText = value => value ? new Date(value.replace(' ', 'T')).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
function review(action) {
    form.clearErrors();
    form.action = action;
    form.points = 0;
    form.note = '';
}
function submit() {
    form.patch(`/admin/reports/${props.report.id}`, { preserveScroll: true, onSuccess: () => emit('close') });
}
let overflow, opener;
onMounted(() => {
    opener = document.activeElement;
    overflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    dialog.value.showModal();
});
onBeforeUnmount(() => {
    dialog.value?.close();
    document.body.style.overflow = overflow;
    opener?.focus?.();
});
</script>

<template>
    <Teleport to="body">
        <dialog ref="dialog" class="report-dialog" aria-labelledby="report-title" @close="emit('close')" @click="event => { if (event.target === dialog) dialog.close(); }">
            <header>
                <div><p class="eyebrow">Account report · {{ report.status }}</p><h2 id="report-title">Report about {{ report.reported }}</h2><p class="who">Submitted by <strong>{{ report.reporter }}</strong> · {{ dateText(report.createdAt) }}</p></div>
                <button type="button" class="close" aria-label="Close report" autofocus @click="dialog.close()">×</button>
            </header>
            <div class="grid">
                <section class="main" aria-label="Report and review history">
                    <article class="bubble"><span class="meta">Report from {{ report.reporter }}</span><p>{{ report.reason }}</p></article>
                    <h3 v-if="report.history.length">Review history</h3>
                    <article v-for="(entry, index) in report.history" :key="index" class="bubble history">
                        <span class="meta">{{ entry.action }} by {{ entry.admin }} · {{ dateText(entry.createdAt) }} · {{ entry.points }} points</span>
                        <p>{{ entry.note }}</p>
                    </article>
                    <p v-if="!report.history.length" class="muted">No review decisions yet.</p>
                </section>
                <aside aria-label="Report details and actions">
                    <div class="detail"><span>Reported account</span><strong>{{ report.reported }}</strong><Link :href="`/admin/users/${report.reportedId}/listings`">View account</Link></div>
                    <div class="detail"><span>Current trust score</span><strong>{{ report.trustScore }}</strong></div>
                    <div class="detail"><span>Status</span><strong class="status">{{ report.status }}</strong><small v-if="report.status !== 'pending'">{{ report.points }} points applied</small></div>
                    <div v-if="!form.action" class="actions">
                        <button v-if="report.status === 'pending'" type="button" class="primary" @click="review('confirm')">Confirm report</button>
                        <button v-if="report.status === 'pending'" type="button" class="secondary" @click="review('dismiss')">Dismiss report</button>
                        <button v-if="report.status === 'confirmed'" type="button" class="secondary" @click="review('reverse')">Reverse confirmation</button>
                    </div>
                    <form v-else @submit.prevent="submit">
                        <h3>{{ form.action === 'confirm' ? 'Confirm report' : form.action === 'dismiss' ? 'Dismiss report' : 'Reverse confirmation' }}</h3>
                        <label v-if="form.action === 'confirm'">Points to deduct<input v-model.number="form.points" type="number" min="0" max="255" required /><small>The deduction is limited to the current trust score.</small></label>
                        <label>Review note<textarea v-model="form.note" rows="4" minlength="10" maxlength="2000" required /></label>
                        <p v-for="(error, key) in form.errors" :key="key" class="error" role="alert">{{ error }}</p>
                        <div class="actions"><button type="submit" class="primary" :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save decision' }}</button><button type="button" class="secondary" :disabled="form.processing" @click="form.action = ''">Cancel</button></div>
                    </form>
                </aside>
            </div>
        </dialog>
    </Teleport>
</template>

<style scoped>
.report-dialog { width:min(940px, calc(100% - 32px)); max-width:none; max-height:calc(100dvh - 32px); padding:0; border:0; border-radius:16px; background:#fff; color:#183e3b; box-shadow:0 20px 70px #103f3b33; overflow:auto; }
.report-dialog::backdrop { background:#082c2970; }
header { display:flex; justify-content:space-between; gap:20px; padding:24px; border-bottom:1px solid #e7efec; }
header h2 { margin:4px 0; font-size:22px; font-weight:700; overflow-wrap:anywhere; }
.eyebrow { margin:0; font-size:11px; font-weight:700; color:#137671; text-transform:uppercase; letter-spacing:.05em; }
.who, .muted { margin:6px 0 0; font-size:13px; color:#637b76; }
.close { width:40px; height:40px; flex-shrink:0; border-radius:8px; font-size:26px; cursor:pointer; }
.close:hover { background:#eef6f3; }
.grid { display:grid; grid-template-columns:minmax(0, 1fr) 280px; }
.main { min-width:0; padding:24px; background:#f7faf8; }
.main h3, form h3 { margin:20px 0 10px; font-size:14px; font-weight:700; }
.bubble { padding:16px; margin-bottom:12px; border:1px solid #dce7e4; border-radius:12px; background:white; }
.bubble.history { background:#eef6f3; }
.meta { font-size:11px; font-weight:700; color:#637b76; text-transform:capitalize; }
.bubble p { margin:8px 0 0; white-space:pre-wrap; overflow-wrap:anywhere; font-size:13px; line-height:1.6; }
aside { padding:24px; border-left:1px solid #e7efec; }
.detail { display:grid; gap:5px; padding-bottom:16px; margin-bottom:16px; border-bottom:1px solid #e7efec; font-size:13px; }
.detail span, .detail small { color:#637b76; font-size:11px; }
.detail a { color:#12675f; font-weight:700; }
.status { text-transform:capitalize; }
form label { display:grid; gap:6px; margin-top:12px; font-size:12px; font-weight:700; }
form label small { color:#637b76; font-weight:400; }
input, textarea { width:100%; padding:10px; border:1px solid #cbded8; border-radius:9px; font-size:16px; }
textarea { resize:vertical; }
.actions { display:grid; gap:8px; margin-top:16px; }
.actions button { min-height:40px; padding:8px 12px; border:1px solid #cbded8; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; }
.primary { background:#103f3b; border-color:#103f3b !important; color:white; }
.secondary { background:white; color:#12675f; }
.actions button:disabled { opacity:.6; cursor:wait; }
.error { color:#a12626; font-size:12px; }
.report-dialog :is(button, input, textarea, a):focus-visible { outline:2px solid #137671; outline-offset:2px; }
@media (max-width:700px) { .grid { grid-template-columns:1fr; } aside { border-left:0; border-top:1px solid #e7efec; } header, .main, aside { padding:18px; } }
</style>
