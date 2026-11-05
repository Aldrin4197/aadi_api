<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({ paginator: { type: Object, required: true }, label: { type: String, default: 'Pages' }, sizes: { type: Array, default: () => [10, 15, 25, 50] } });

const total = computed(() => props.paginator.total ?? 0);
const from = computed(() => props.paginator.from ?? 0);
const to = computed(() => props.paginator.to ?? 0);
const options = computed(() => [...new Set([...props.sizes, props.paginator.per_page])].sort((a, b) => a - b));
// Laravel's link list holds Previous, Next, and the numbered pages; ellipses have no URL.
const pages = computed(() => (props.paginator.links ?? []).slice(1, -1).map(link => ({ label: link.label, url: link.url, active: link.active })));

function resize(event) {
    const query = Object.fromEntries(new URLSearchParams(window.location.search));
    delete query.page;
    query.per_page = event.target.value;
    router.get(props.paginator.path, query, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <div v-if="total > 0" class="admin-pagination">
        <p class="admin-pagination-summary" role="status">Showing <strong>{{ from.toLocaleString() }}–{{ to.toLocaleString() }}</strong> of <strong>{{ total.toLocaleString() }}</strong></p>
        <nav v-if="paginator.last_page > 1" class="admin-pagination-nav" :aria-label="label">
            <Link v-if="paginator.prev_page_url" :href="paginator.prev_page_url" class="step" preserve-scroll preserve-state aria-label="Previous page">←<span> Previous</span></Link>
            <span v-else class="step is-disabled" aria-disabled="true">←<span> Previous</span></span>
            <template v-for="(page, index) in pages" :key="index">
                <Link v-if="page.url && !page.active" :href="page.url" class="num" preserve-scroll preserve-state :aria-label="`Page ${page.label}`">{{ page.label }}</Link>
                <span v-else-if="page.active" class="num is-current" aria-current="page">{{ page.label }}</span>
                <span v-else class="gap" aria-hidden="true">…</span>
            </template>
            <Link v-if="paginator.next_page_url" :href="paginator.next_page_url" class="step" preserve-scroll preserve-state aria-label="Next page"><span>Next </span>→</Link>
            <span v-else class="step is-disabled" aria-disabled="true"><span>Next </span>→</span>
        </nav>
        <label class="admin-pagination-size">Rows per page
            <select :value="paginator.per_page" @change="resize"><option v-for="size in options" :key="size" :value="size">{{ size }}</option></select>
        </label>
    </div>
</template>

<style scoped>
.admin-pagination { display:flex; align-items:center; justify-content:space-between; gap:16px 24px; margin-top:20px; font-size:13px; flex-wrap:wrap; }
.admin-pagination-summary { margin:0; color:#586e6c; }
.admin-pagination-summary strong { color:#103f3b; }
.admin-pagination-nav { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.admin-pagination-nav .step, .admin-pagination-nav .num { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-width:40px; min-height:40px; padding:0 12px; border:1px solid #cbded8; border-radius:9px; background:#fff; font-weight:700; color:#12675f; }
.admin-pagination-nav .num { padding:0 8px; }
.admin-pagination-nav a:hover { background:#f1f8f6; }
.admin-pagination-nav .is-disabled { color:#a2b4b0; background:#f4f8f6; }
.admin-pagination-nav .is-current { background:#103f3b; border-color:#103f3b; color:#fff; }
.admin-pagination-nav .gap { min-width:20px; text-align:center; color:#7b918d; }
.admin-pagination-nav a:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.admin-pagination-size { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:#586e6c; }
.admin-pagination-size select { min-height:40px; padding:0 10px; border:1px solid #cbded8; border-radius:9px; background:#fff; color:#103f3b; font-weight:700; }
@media (max-width:640px) { .admin-pagination { justify-content:center; text-align:center; } .admin-pagination-nav .step span { display:none; } }
</style>
