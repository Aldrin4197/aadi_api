<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    ticketId: { type: String, required: true },
    options: { type: Object, required: true },
    me: { type: String, default: null },
});
const emit = defineEmits(['close', 'changed']);

const dialog = ref(null);
const thread = ref(null);
const ticket = ref(null);
const loadError = ref('');
const busy = ref(false);
const error = ref('');
const kind = ref('reply');
const body = ref('');

const mine = computed(() => ticket.value && ticket.value.assignedToId === props.me);
const dateText = value => (value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—');
const span = (from, to) => {
    if (!from || !to) return '—';
    const minutes = Math.max(0, Math.round((new Date(to) - new Date(from)) / 60000));
    if (minutes < 60) return `${minutes} min`;
    return minutes < 2880 ? `${(minutes / 60).toFixed(1)} h` : `${Math.round(minutes / 1440)} days`;
};

async function load() {
    try {
        const { data } = await axios.get(`/admin/tickets/${props.ticketId}`);
        ticket.value = data.ticket;
        loadError.value = '';
        await nextTick();
        if (thread.value) thread.value.scrollTop = thread.value.scrollHeight;
    } catch {
        loadError.value = 'This ticket could not be loaded.';
    }
}
async function send(method, url, payload) {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    try {
        const { data } = await axios({ method, url, data: payload });
        ticket.value = data.ticket;
        emit('changed');
        await nextTick();
        if (thread.value) thread.value.scrollTop = thread.value.scrollHeight;
        return true;
    } catch (failure) {
        const errors = failure.response?.data?.errors;
        error.value = errors ? Object.values(errors).flat().join(' ') : 'That did not save. Please try again.';
        return false;
    } finally {
        busy.value = false;
    }
}
const update = changes => send('patch', `/admin/tickets/${props.ticketId}`, changes);
async function post() {
    if (body.value.trim().length < 2) return;
    if (await send('post', `/admin/tickets/${props.ticketId}/messages`, { kind: kind.value, body: body.value.trim() })) body.value = '';
}

let overflow, opener;
onMounted(() => {
    opener = window.document.activeElement;
    overflow = window.document.body.style.overflow;
    window.document.body.style.overflow = 'hidden';
    dialog.value.showModal();
    load();
});
onBeforeUnmount(() => { dialog.value?.close(); window.document.body.style.overflow = overflow; opener?.focus?.(); });
</script>

<template>
    <Teleport to="body">
        <dialog ref="dialog" class="ticket" aria-labelledby="ticket-title" @close="emit('close')" @click="event => { if (event.target === dialog) dialog.close(); }">
            <p v-if="loadError" class="load-error" role="alert">{{ loadError }} <button type="button" @click="load">Try again</button></p>
            <template v-else-if="ticket">
                <header>
                    <div>
                        <p class="ref">{{ ticket.reference }} · {{ options.categories[ticket.category] }}</p>
                        <h2 id="ticket-title">{{ ticket.subject }}</h2>
                        <p class="who"><strong>{{ ticket.name }}</strong> · <a :href="`mailto:${ticket.email}`">{{ ticket.email }}</a><span v-if="ticket.member" class="tag">Aadi member</span><span v-else class="tag plain">Guest</span></p>
                    </div>
                    <button type="button" class="close" aria-label="Close ticket" autofocus @click="dialog.close()">×</button>
                </header>

                <div class="grid">
                    <section class="main" aria-label="Conversation">
                        <div ref="thread" class="thread">
                            <article class="bubble original">
                                <span class="meta">{{ ticket.name }} · {{ dateText(ticket.createdAt) }}</span>
                                <p>{{ ticket.message }}</p>
                            </article>
                            <template v-for="message in ticket.messages" :key="message.id">
                                <p v-if="message.kind === 'event'" class="event">{{ message.body }} <span>{{ message.author }} · {{ dateText(message.createdAt) }}</span></p>
                                <article v-else class="bubble" :class="message.kind">
                                    <span class="meta">
                                        <strong>{{ message.kind === 'note' ? 'Internal note' : 'Reply sent' }}</strong> · {{ message.author }} · {{ dateText(message.createdAt) }}
                                        <em v-if="message.kind === 'reply'" :class="message.emailed ? 'ok' : 'warn'">{{ message.emailed ? 'Emailed' : 'Not emailed' }}</em>
                                    </span>
                                    <p>{{ message.body }}</p>
                                </article>
                            </template>
                        </div>

                        <form class="composer" @submit.prevent="post">
                            <div class="kinds" role="group" aria-label="Message type">
                                <button type="button" :aria-pressed="kind === 'reply'" @click="kind = 'reply'">Reply to {{ ticket.name.split(' ')[0] }}</button>
                                <button type="button" :aria-pressed="kind === 'note'" @click="kind = 'note'">Internal note</button>
                            </div>
                            <label class="sr-only" for="ticket-body">{{ kind === 'reply' ? 'Reply' : 'Internal note' }}</label>
                            <textarea id="ticket-body" v-model="body" rows="4" maxlength="4000" :placeholder="kind === 'reply' ? `Written to ${ticket.email} by email.` : 'Only visible to admins.'" :disabled="busy" />
                            <div class="composer-foot">
                                <small>{{ kind === 'reply' ? 'Sends an email and marks the first response.' : 'Saved to the ticket history. Not emailed.' }}</small>
                                <button type="submit" class="primary" :disabled="busy || body.trim().length < 2">{{ busy ? 'Sending…' : kind === 'reply' ? 'Send reply' : 'Save note' }}</button>
                            </div>
                        </form>
                        <p v-if="error" class="error" role="alert">{{ error }}</p>
                    </section>

                    <aside aria-label="Ticket details">
                        <label>Status
                            <select :value="ticket.status" :disabled="busy" @change="update({ status: $event.target.value })"><option v-for="(label, key) in options.statuses" :key="key" :value="key">{{ label }}</option></select>
                        </label>
                        <label>Priority
                            <select :value="ticket.priority" :disabled="busy" @change="update({ priority: $event.target.value })"><option v-for="(label, key) in options.priorities" :key="key" :value="key">{{ label }}</option></select>
                        </label>
                        <div class="assign">
                            <span>Assigned to</span>
                            <strong>{{ ticket.assignee || 'Nobody yet' }}</strong>
                            <button type="button" :disabled="busy" @click="update({ assignee: mine ? 'none' : 'me' })">{{ mine ? 'Unassign me' : 'Assign to me' }}</button>
                        </div>
                        <button v-if="!['resolved', 'closed'].includes(ticket.status)" type="button" class="resolve" :disabled="busy" @click="update({ status: 'resolved' })">Mark as resolved</button>
                        <dl>
                            <dt>Opened</dt><dd>{{ dateText(ticket.createdAt) }}</dd>
                            <dt>First response</dt><dd>{{ ticket.firstResponseAt ? `${dateText(ticket.firstResponseAt)} (${span(ticket.createdAt, ticket.firstResponseAt)})` : 'Not yet' }}</dd>
                            <dt>Resolved</dt><dd>{{ ticket.resolvedAt ? `${dateText(ticket.resolvedAt)} (${span(ticket.createdAt, ticket.resolvedAt)})` : 'Not yet' }}</dd>
                        </dl>
                    </aside>
                </div>
            </template>
            <p v-else class="loading" role="status">Loading ticket…</p>
        </dialog>
    </Teleport>
</template>

<style scoped>
.ticket { margin:auto; width:min(1040px, calc(100% - 32px)); max-height:calc(100dvh - 32px); overflow:auto; overscroll-behavior:contain; border:0; border-radius:18px; padding:0; background:#fff; color:#183e3b; font-family:Quicksand, system-ui, sans-serif; }
.ticket::backdrop { background:#082c2999; }
.ticket header { display:flex; justify-content:space-between; gap:20px; padding:22px 24px; border-bottom:1px solid #dce7e4; }
.ref { font-size:12px; color:#637b76; margin:0 0 6px; font-weight:700; letter-spacing:.03em; }
.ticket h2 { font-size:22px; font-weight:700; margin:0; overflow-wrap:anywhere; }
.who { margin:10px 0 0; font-size:13px; color:#42665e; }
.who a { color:#137671; text-decoration:underline; }
.tag { margin-left:10px; padding:3px 8px; border-radius:20px; background:#e1f5ec; color:#12744a; font-size:11px; font-weight:700; }
.tag.plain { background:#eef2f1; color:#586e6c; }
.close { width:44px; height:44px; font-size:28px; flex:none; cursor:pointer; }
.grid { display:grid; grid-template-columns:minmax(0, 1fr) 290px; }
.main { padding:20px 24px; min-width:0; display:flex; flex-direction:column; gap:16px; }
aside { padding:20px 22px; background:#f4f8f6; border-left:1px solid #dce7e4; display:flex; flex-direction:column; gap:16px; }
.thread { display:flex; flex-direction:column; gap:12px; max-height:340px; overflow-y:auto; padding-right:4px; }
.bubble { padding:12px 14px; border-radius:12px; background:#f4f8f6; max-width:92%; }
.bubble.original { background:#fff; border:1px solid #dce7e4; }
.bubble.reply { background:#e3f6f4; align-self:flex-end; }
.bubble.note { background:#fff6dc; align-self:flex-end; }
.bubble p { margin:6px 0 0; font-size:13px; line-height:1.6; white-space:pre-wrap; overflow-wrap:anywhere; }
.meta { display:block; font-size:11px; color:#637b76; }
.meta em { margin-left:8px; font-style:normal; font-weight:700; }
.meta em.ok { color:#12744a; }
.meta em.warn { color:#b45f12; }
.event { margin:0; text-align:center; font-size:12px; color:#586e6c; }
.event span { display:block; font-size:11px; color:#8aa09c; }
.composer { display:flex; flex-direction:column; gap:10px; }
.kinds { display:flex; gap:6px; }
.kinds button { padding:7px 14px; border:1px solid #cbded8; border-radius:20px; font-size:12px; font-weight:700; color:#42665e; cursor:pointer; background:#fff; }
.kinds button[aria-pressed=true] { background:#103f3b; border-color:#103f3b; color:#fff; }
.ticket textarea, .ticket select { width:100%; border:1px solid #b7d8d0; border-radius:10px; padding:10px; font-size:13px; background:#fff; color:#183e3b; }
.ticket select { min-height:42px; }
.composer-foot { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.composer-foot small { color:#637b76; font-size:12px; }
.primary { min-height:42px; padding:0 18px; border-radius:10px; background:#137671; color:#fff; font-weight:700; font-size:13px; cursor:pointer; }
.error { color:#b42318; font-size:13px; margin:0; }
aside label { display:grid; gap:6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#637b76; }
.assign { display:flex; flex-direction:column; gap:6px; font-size:12px; }
.assign span { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#637b76; }
.assign strong { font-size:14px; }
.assign button, .resolve { min-height:40px; border:1px solid #b7d8d0; border-radius:10px; font-size:13px; font-weight:700; color:#12675f; background:#fff; cursor:pointer; }
.resolve { background:#137671; border-color:#137671; color:#fff; }
aside dl { margin:0; display:grid; gap:4px; font-size:12px; }
aside dt { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#637b76; margin-top:8px; }
aside dd { margin:0; }
.loading, .load-error { padding:48px 24px; text-align:center; font-size:14px; color:#586e6c; margin:0; }
.load-error button { margin-left:8px; text-decoration:underline; color:#137671; cursor:pointer; }
.ticket button:disabled, .ticket select:disabled { opacity:.55; cursor:not-allowed; }
.ticket :is(button, a, select, textarea):focus-visible { outline:3px solid #e6bb51; outline-offset:2px; }
@media (max-width:820px) {
  .grid { grid-template-columns:1fr; }
  aside { border-left:0; border-top:1px solid #dce7e4; order:-1; }
  .ticket { width:calc(100% - 16px); max-height:calc(100dvh - 16px); }
  .ticket header, .main { padding:18px; }
  .bubble { max-width:100%; }
}
</style>
