<script setup>
import { Link } from '@inertiajs/vue3';
import UserAvatar from '@/Components/Account/UserAvatar.vue';

defineProps({
    conversations: { type: Object, required: true },
    activeConversationId: { type: String, default: null },
});
</script>

<template>
    <div class="messenger-shell" :class="{ 'has-active-conversation': activeConversationId }">
        <aside class="inbox-sidebar" aria-label="Messages">
            <header class="inbox-header">
                <h1>Messages</h1>
                <span v-if="conversations.total" class="inbox-count">{{ conversations.total }}</span>
            </header>
            <nav class="inbox-list" aria-label="Conversations">
                <Link
                    v-for="item in conversations.data"
                    :key="item.id"
                    :href="`/messages/${item.id}`"
                    class="inbox-row"
                    :class="{ active: item.id === activeConversationId }"
                    :aria-current="item.id === activeConversationId ? 'page' : undefined"
                >
                    <UserAvatar :user="item.other" />
                    <span class="inbox-copy">
                        <span class="inbox-name">{{ item.other.name }}</span>
                        <span class="inbox-listing">{{ item.listingTitle }}</span>
                        <span class="inbox-preview">{{ item.latestBody || 'No messages yet' }}</span>
                    </span>
                    <time :datetime="item.updatedAt">{{ new Date(item.updatedAt).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}</time>
                </Link>
                <div v-if="!conversations.data.length" class="inbox-empty">
                    <p>No conversations yet</p>
                </div>
            </nav>
            <nav v-if="conversations.prev_page_url || conversations.next_page_url" class="inbox-pagination" aria-label="Conversation pages">
                <Link v-if="conversations.prev_page_url" :href="conversations.prev_page_url" aria-label="Previous conversations">Previous</Link>
                <span>{{ conversations.current_page }} / {{ conversations.last_page }}</span>
                <Link v-if="conversations.next_page_url" :href="conversations.next_page_url" aria-label="Next conversations">Next</Link>
            </nav>
        </aside>
        <slot />
    </div>
</template>

<style scoped>
.messenger-shell { display:grid; grid-template-columns:minmax(230px,1fr) minmax(0,3fr); height:min(760px,calc(100dvh - 238px)); min-height:420px; overflow:hidden; border:1px solid var(--line); border-radius:14px; background:#fff; }
.inbox-sidebar { display:flex; flex-direction:column; min-width:0; min-height:0; border-right:1px solid var(--line); background:#fff; }
.inbox-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex:none; min-height:62px; padding:12px 16px; border-bottom:1px solid var(--line); }
.inbox-header h1 { margin:0; color:var(--ink); font-size:var(--aadi-text-body); font-weight:700; }
.inbox-count { min-width:24px; padding:3px 7px; border-radius:10px; background:#e5f2ee; color:var(--teal); font-size:11px; font-variant-numeric:tabular-nums; text-align:center; }
.inbox-list { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:contain; }
.inbox-row { display:flex; align-items:center; gap:10px; min-width:0; padding:12px; border-bottom:1px solid #edf1ef; color:var(--ink); text-decoration:none; transition:background 140ms ease; }
.inbox-row:hover { background:#f4faf8; }
.inbox-row.active { background:#eaf5f1; }
.inbox-row :deep(.user-avatar) { width:42px; height:42px; flex:none; }
.inbox-copy { display:grid; flex:1; gap:3px; min-width:0; }
.inbox-copy>span,.inbox-row time { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.inbox-name { color:var(--ink); font-size:var(--aadi-text-small); font-weight:700; }
.inbox-listing,.inbox-preview,.inbox-row time { color:var(--muted); font-size:11px; }
.inbox-preview { font-size:12px; }
.inbox-row time { flex:none; align-self:flex-start; max-width:54px; }
.inbox-empty { display:grid; justify-items:start; gap:8px; padding:18px 16px; color:var(--muted); font-size:var(--aadi-text-small); }
.inbox-empty p { margin:0; }
.inbox-pagination a { color:var(--teal); font-weight:700; text-decoration:none; }
.inbox-pagination { display:flex; align-items:center; justify-content:space-between; gap:8px; flex:none; padding:10px 12px; border-top:1px solid var(--line); color:var(--muted); font-size:11px; }
.messenger-shell :deep(a:focus-visible) { outline:2px solid var(--teal); outline-offset:2px; }
@media(max-width:760px) {
    .messenger-shell { grid-template-columns:minmax(0,1fr); height:calc(100dvh - 232px); min-height:320px; border-radius:10px; }
    .messenger-shell.has-active-conversation .inbox-sidebar { display:none; }
    .messenger-shell:not(.has-active-conversation) :slotted(.conversation-empty) { display:none; }
    .messenger-shell:not(.has-active-conversation) .inbox-sidebar { border-right:0; }
    .inbox-row { padding:11px 12px; }
}
</style>
