<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import MessengerLayout from '@/Components/Account/MessengerLayout.vue';
import InboxNavigation from '@/Components/Account/InboxNavigation.vue';
import Layout from '@/Layouts/AccountLayout.vue';
import { useMessageSync } from '@/composables/useMessageSync.mjs';
import { realtimeState } from '@/realtime.mjs';

const props = defineProps({ conversations: { type: Object, required: true } });
const liveConversations = ref(props.conversations);
const { failed, refresh, invalidate } = useMessageSync(data => { liveConversations.value = data.conversations; });
watch(() => props.conversations, value => { invalidate(); liveConversations.value = value; });
</script>

<template>
    <Layout><Head title="Messages" />
        <main class="account-page messages-page">
            <InboxNavigation active="messages" />
            <p v-if="failed" class="sync-status" role="alert">Could not refresh messages. <button type="button" @click="refresh">Try again</button></p>
            <p v-else-if="!realtimeState.connected" class="sync-status" role="status">Live updates unavailable. Checking for messages periodically.</p>
            <MessengerLayout :conversations="liveConversations">
                <section class="conversation-empty" aria-label="Conversation">
                    <div v-if="liveConversations.data.length" class="empty-prompt">
                        <h2>Select a conversation</h2>
                        <p>Choose someone from your messages to see the conversation.</p>
                    </div>
                    <div v-else class="empty-prompt">
                        <h2>Start a conversation</h2>
                        <p>Message a provider from a listing to begin.</p>
                        <Link href="/explore">Explore listings</Link>
                    </div>
                </section>
            </MessengerLayout>
        </main>
    </Layout>
</template>

<style scoped>
.messages-page { max-width:1440px; }
.sync-status { margin:0 0 8px; color:var(--muted); font-size:12px; }
.sync-status button { color:var(--teal); font-weight:700; text-decoration:underline; }
.conversation-empty { display:grid; place-items:center; min-width:0; background:#f5f8f7; text-align:center; }
.empty-prompt { max-width:360px; padding:24px; }
.empty-prompt h2 { margin:0 0 8px; color:var(--ink); font-size:var(--aadi-text-body); }
.empty-prompt p { margin:0; color:var(--muted); font-size:var(--aadi-text-small); line-height:1.6; }
.empty-prompt a { display:inline-flex; margin-top:14px; color:var(--teal); font-size:var(--aadi-text-small); font-weight:700; }
</style>
