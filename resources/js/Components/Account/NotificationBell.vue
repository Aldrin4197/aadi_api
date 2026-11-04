<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const unread = ref(page.props.notifications?.unread ?? 0);
const open = ref(false);
const loading = ref(false);
const failed = ref(false);
const items = ref([]);
const root = ref(null);
const button = ref(null);
let timer;

watch(() => page.props.notifications?.unread, value => { if (typeof value === 'number') unread.value = value; });

const badge = computed(() => (unread.value > 9 ? '9+' : String(unread.value)));
const label = computed(() => (unread.value ? `Notifications, ${unread.value} unread` : 'Notifications'));

const icons = {
    approved: 'M5 12.5l4.2 4.2L19 7',
    rejected: 'M12 8v5m0 3.5h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
    application: 'm5 12 4 4L19 6M12 3l9 4v6c0 4-9 8-9 8S3 17 3 13V7z',
    ticket: 'M3 4h18v13H8l-5 4z',
    'ticket-reply': 'M3 4h18v13H8l-5 4z',
    'ticket-resolved': 'M5 12.5l4.2 4.2L19 7',
    review: 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
    info: 'M12 8h.01M11 12h1v5h1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};
const iconFor = kind => icons[kind] || icons.info;

function ago(iso) {
    const minutes = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (minutes < 1440) return `${Math.round(minutes / 60)}h ago`;
    if (minutes < 10080) return `${Math.round(minutes / 1440)}d ago`;
    return new Date(iso).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
}

async function refreshCount() {
    if (document.hidden) return;
    try { unread.value = (await axios.get('/notifications/count')).data.unread; } catch { /* the badge simply stays as it is */ }
}
async function load() {
    loading.value = true;
    failed.value = false;
    try {
        const { data } = await axios.get('/notifications');
        items.value = data.items;
        unread.value = data.unread;
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
}
async function toggle() {
    open.value = !open.value;
    if (open.value) await load();
}
function close(refocus = false) {
    open.value = false;
    if (refocus) button.value?.focus();
}
async function choose(item) {
    if (!item.read) {
        item.read = true;
        try { unread.value = (await axios.post(`/notifications/${item.id}/read`)).data.unread; } catch { item.read = false; }
    }
    if (item.href) {
        close();
        router.visit(item.href);
    }
}
async function readAll() {
    try {
        unread.value = (await axios.post('/notifications/read-all')).data.unread;
        items.value.forEach(item => { item.read = true; });
    } catch { /* leave the list unchanged so the user can retry */ }
}

function outside(event) { if (open.value && !root.value?.contains(event.target)) close(); }
function keydown(event) { if (event.key === 'Escape' && open.value) { event.stopPropagation(); close(true); } }
function visible() { if (!document.hidden) refreshCount(); }

onMounted(() => {
    document.addEventListener('pointerdown', outside);
    document.addEventListener('visibilitychange', visible);
    timer = setInterval(refreshCount, 60000);
});
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', outside);
    document.removeEventListener('visibilitychange', visible);
    clearInterval(timer);
});
watch(() => page.url, () => { if (open.value) close(); });
</script>

<template>
    <div ref="root" class="bell" @keydown="keydown">
        <button ref="button" type="button" class="bell-button" :aria-label="label" aria-haspopup="true" :aria-expanded="open" aria-controls="notification-panel" @click="toggle">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 1112 0c0 7 3 8 3 8H3s3-1 3-8M10.3 20a1.9 1.9 0 003.4 0" /></svg>
            <span v-if="unread" class="bell-badge" aria-hidden="true">{{ badge }}</span>
        </button>

        <section v-if="open" id="notification-panel" class="bell-panel" aria-label="Notifications">
            <header>
                <h2>Notifications</h2>
                <button v-if="unread" type="button" class="link" @click="readAll">Mark all as read</button>
            </header>

            <p v-if="loading && !items.length" class="state" role="status">Loading…</p>
            <p v-else-if="failed" class="state" role="alert">Could not load notifications. <button type="button" class="link" @click="load">Try again</button></p>
            <p v-else-if="!items.length" class="state empty">You're all caught up.<small>We'll let you know here when something needs your attention.</small></p>

            <ul v-else>
                <li v-for="item in items" :key="item.id">
                    <button type="button" class="item" :class="[`tone-${item.tone}`, { unread: !item.read, plain: !item.href }]" @click="choose(item)">
                        <span class="icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path :d="iconFor(item.kind)" /></svg></span>
                        <span class="body">
                            <strong>{{ item.title }}</strong>
                            <span v-if="item.text" class="text">{{ item.text }}</span>
                            <time :datetime="item.createdAt">{{ ago(item.createdAt) }}</time>
                        </span>
                        <span v-if="!item.read" class="dot"><span class="sr-only">Unread</span></span>
                    </button>
                </li>
            </ul>
        </section>
    </div>
</template>

<style scoped>
.bell { position:relative; flex-shrink:0; }
.bell-button { position:relative; display:grid; place-items:center; width:44px; height:44px; border-radius:50%; color:#183e3b; cursor:pointer; }
.bell-button:hover, .bell-button[aria-expanded=true] { background:#e3f6f4; }
.bell-button:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.bell-button svg { width:22px; height:22px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.bell-badge { position:absolute; top:1px; right:0; min-width:19px; height:19px; padding:0 4px; box-sizing:border-box; border-radius:10px; background:#e0522d; color:#fff; font-size:10px; font-weight:700; line-height:15px; text-align:center; border:2px solid #fff; }

.bell-panel { position:absolute; right:-6px; top:calc(100% + 10px); z-index:1300; width:min(380px, calc(100vw - 24px)); max-height:min(520px, calc(100dvh - 100px)); display:flex; flex-direction:column; border:1px solid var(--line, #dde2e9); border-radius:14px; background:#fff; box-shadow:0 16px 44px #193e3b2b; color:#183e3b; text-align:left; font-family:Quicksand, system-ui, sans-serif; overflow:hidden; }
.bell-panel header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; border-bottom:1px solid #e7efec; }
.bell-panel h2 { margin:0; font-size:15px; font-weight:700; }
.link { min-height:32px; font-size:12px; font-weight:700; color:#137671; text-decoration:underline; cursor:pointer; }
.link:focus-visible { outline:2px solid #137671; outline-offset:2px; border-radius:4px; }
.state { margin:0; padding:32px 20px; text-align:center; font-size:13px; color:#586e6c; }
.state.empty { color:#183e3b; font-weight:700; }
.state small { display:block; margin-top:6px; font-weight:500; color:#7b918d; font-size:12px; line-height:1.5; }
ul { list-style:none; margin:0; padding:0; overflow-y:auto; overscroll-behavior:contain; }
li + li { border-top:1px solid #eef2f1; }
.item { display:flex; align-items:flex-start; gap:12px; width:100%; padding:12px 16px; text-align:left; cursor:pointer; background:#fff; }
.item.plain { cursor:default; }
.item:hover { background:#f6faf9; }
.item.unread { background:#f2faf8; }
.item.unread:hover { background:#e9f6f3; }
.item:focus-visible { outline:2px solid #137671; outline-offset:-2px; }
.icon { display:grid; place-items:center; width:34px; height:34px; border-radius:50%; flex-shrink:0; background:#e3f6f4; color:#137671; }
.tone-warning .icon { background:#fdeadb; color:#b45f12; }
.tone-good .icon { background:#e1f5ec; color:#12744a; }
.icon svg { width:17px; height:17px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
.body { display:flex; flex-direction:column; gap:3px; min-width:0; flex:1; }
.body strong { font-size:13px; line-height:1.4; }
.unread .body strong { font-weight:700; }
.item:not(.unread) .body strong { font-weight:600; color:#42665e; }
.text { font-size:12px; line-height:1.5; color:#586e6c; overflow-wrap:anywhere; }
.body time { font-size:11px; color:#8aa09c; }
.dot { width:9px; height:9px; margin-top:6px; border-radius:50%; background:#1fa6a0; flex-shrink:0; }
@media (max-width:520px) { .bell { position:static; } .bell-panel { position:fixed; left:12px; right:12px; top:72px; width:auto; } }
</style>
