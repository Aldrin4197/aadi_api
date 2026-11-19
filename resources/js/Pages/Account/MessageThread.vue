<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, watch } from 'vue';
import UserAvatar from '@/Components/Account/UserAvatar.vue';
import MessengerLayout from '@/Components/Account/MessengerLayout.vue';
import InboxNavigation from '@/Components/Account/InboxNavigation.vue';
import Layout from '@/Layouts/AccountLayout.vue';
import { useMessageSync } from '@/composables/useMessageSync.mjs';
import { realtimeState } from '@/realtime.mjs';

const props = defineProps({
    conversations: { type: Object, required: true },
    conversation: { type: Object, required: true },
    messages: { type: Object, required: true },
});
const form = useForm({ body: '' });
const thread = ref(null);
const liveConversations = ref(props.conversations);
const liveMessages = ref(props.messages);

async function scrollToLatest() {
    await nextTick();
    if (thread.value) thread.value.scrollTop = thread.value.scrollHeight;
}

function submit() {
    form.post(`/messages/${props.conversation.id}`, {
        preserveScroll: true,
        onSuccess: async () => {
            form.reset('body');
            await scrollToLatest();
        },
    });
}

function handleEnter(event) {
    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) return;
    event.preventDefault();
    submit();
}

const { failed, refresh, invalidate } = useMessageSync(async data => {
    liveConversations.value = data.conversations;
    if (liveMessages.value.current_page !== 1) return;
    const nearBottom = !thread.value || thread.value.scrollHeight - thread.value.scrollTop - thread.value.clientHeight < 80;
    liveMessages.value = data.messages;
    if (nearBottom) await scrollToLatest();
});

onMounted(scrollToLatest);
watch(() => props.messages, value => { invalidate(); liveMessages.value = value; scrollToLatest(); });
watch(() => props.conversations, value => { invalidate(); liveConversations.value = value; });
</script>

<template>
    <Layout><Head :title="`Messages - ${conversation.other.name}`" />
        <main class="account-page thread-page">
            <InboxNavigation active="messages" />
            <p v-if="failed" class="sync-status" role="alert">Could not refresh messages. <button type="button" @click="refresh">Try again</button></p>
            <p v-else-if="!realtimeState.connected" class="sync-status" role="status">Live updates unavailable. Checking for messages periodically.</p>
            <MessengerLayout :conversations="liveConversations" :active-conversation-id="conversation.id">
                <section class="messenger" :aria-label="`Conversation with ${conversation.other.name}`">
                    <header class="thread-header">
                        <Link href="/messages" class="thread-back" aria-label="Back to messages">Back</Link>
                        <UserAvatar :user="conversation.other" />
                        <div class="thread-person">
                            <Link :href="`/providers/${conversation.other.id}`" class="person-name">{{ conversation.other.name }}</Link>
                            <Link v-if="conversation.listingUrl" :href="conversation.listingUrl" class="listing-title">{{ conversation.listingTitle }}</Link>
                            <span v-else class="listing-title">{{ conversation.listingTitle }} · Listing unavailable</span>
                        </div>
                    </header>
                    <div ref="thread" class="thread-history" role="log" aria-label="Message history" aria-live="polite">
                        <nav v-if="liveMessages.prev_page_url || liveMessages.next_page_url" class="message-history-nav" aria-label="Message history pages">
                            <Link v-if="liveMessages.next_page_url" :href="liveMessages.next_page_url">Older messages</Link>
                            <span>Page {{ liveMessages.current_page }} of {{ liveMessages.last_page }}</span>
                            <Link v-if="liveMessages.prev_page_url" :href="liveMessages.prev_page_url">Newer messages</Link>
                        </nav>
                        <ol class="messages">
                            <li v-for="message in liveMessages.data" :key="message.id" class="message-row" :class="{ mine: message.mine }">
                                <div class="bubble">
                                    <p>{{ message.body }}</p>
                                    <time :datetime="message.createdAt">{{ new Date(message.createdAt).toLocaleString() }}</time>
                                </div>
                            </li>
                        </ol>
                    </div>
                    <form class="reply" @submit.prevent="submit">
                        <label class="sr-only" for="message-reply">Your message</label>
                        <textarea
                            id="message-reply"
                            v-model="form.body"
                            rows="1"
                            maxlength="2000"
                            placeholder="Write a message..."
                            required
                            @keydown="handleEnter"
                        />
                        <button class="send-button" type="submit" :disabled="form.processing || !form.body.trim()">
                            {{ form.processing ? 'Sending…' : 'Send' }}
                        </button>
                        <p class="reply-hint">Enter to send, Shift+Enter for a new line</p>
                        <p v-if="form.errors.body" class="message-error" role="alert">{{ form.errors.body }}</p>
                    </form>
                </section>
            </MessengerLayout>
        </main>
    </Layout>
</template>

<style scoped>
.thread-page { max-width:1440px; }
.sync-status { margin:0 0 8px; color:var(--muted); font-size:12px; }
.sync-status button { color:var(--teal); font-weight:700; text-decoration:underline; }
.messenger { display:flex; flex-direction:column; min-width:0; min-height:0; overflow:hidden; background:#fff; }
.thread-header { display:flex; align-items:center; gap:12px; flex:none; min-height:66px; padding:10px 16px; border-bottom:1px solid var(--line); background:#fff; }
.thread-header :deep(.user-avatar) { width:40px; height:40px; flex:none; }
.thread-person { display:grid; gap:3px; min-width:0; }
.person-name { color:var(--ink); font-size:var(--aadi-text-body); font-weight:700; text-decoration:none; }
.listing-title { overflow:hidden; color:var(--muted); font-size:var(--aadi-text-small); text-overflow:ellipsis; white-space:nowrap; }
.thread-back { display:none; color:var(--teal); font-size:var(--aadi-text-small); font-weight:700; text-decoration:none; }
.thread-history { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:contain; padding:14px 18px; background:#f5f8f7; }
.message-history-nav { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:8px 14px; margin:0 0 12px; color:var(--muted); font-size:12px; }
.message-history-nav a { color:var(--teal); font-weight:700; text-decoration:none; }
.messages { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
.message-row { display:flex; }
.message-row.mine { justify-content:flex-end; }
.bubble { max-width:min(78%,620px); padding:9px 13px 7px; border:1px solid #e0e9e6; border-radius:16px 16px 16px 5px; background:#fff; }
.message-row.mine .bubble { border-color:#d4ebe4; border-radius:16px 16px 5px 16px; background:#e3f3ee; }
.bubble p { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; line-height:1.5; }
.bubble time { display:block; margin-top:4px; color:var(--muted); font-size:10px; text-align:right; }
.reply { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:5px 10px; flex:none; padding:10px 12px; border-top:1px solid var(--line); background:#fff; }
.reply textarea { width:100%; min-height:42px; max-height:120px; resize:vertical; padding:10px 14px; border:1px solid #c9dbd8; border-radius:18px; background:#fff; color:var(--ink); font:inherit; font-size:16px; line-height:1.4; }
.send-button { min-width:68px; min-height:42px; padding:8px 16px; border:1px solid var(--teal); border-radius:22px; background:var(--teal); color:#fff; font:inherit; font-size:var(--aadi-text-small); font-weight:700; cursor:pointer; }
.send-button:disabled { opacity:.55; cursor:not-allowed; }
.reply-hint { grid-column:1/-1; margin:0 4px; color:var(--muted); font-size:11px; }
.message-error { grid-column:1/-1; margin:0; color:#9e342b; font-size:var(--aadi-text-small); }
.messenger :is(a,button,textarea):focus-visible { outline:2px solid var(--teal); outline-offset:2px; }
@media(max-width:760px) {
    .thread-back { display:inline-flex; align-items:center; flex:none; }
    .thread-header { gap:9px; padding:8px 10px; }
    .thread-header :deep(.user-avatar) { width:36px; height:36px; }
    .thread-history { padding:12px 10px; }
    .bubble { max-width:88%; }
    .reply { padding:8px; }
    .reply-hint { display:none; }
}
</style>
