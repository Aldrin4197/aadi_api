<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ paginator: { type: Object, required: true }, label: { type: String, default: 'Pages' } });
</script>

<template>
    <nav v-if="paginator.last_page > 1" class="admin-pagination" :aria-label="label">
        <Link v-if="paginator.prev_page_url" :href="paginator.prev_page_url" preserve-scroll preserve-state>← Previous</Link>
        <span v-else class="is-disabled" aria-disabled="true">← Previous</span>
        <span class="admin-pagination-status">Page {{ paginator.current_page }} of {{ paginator.last_page }}<small> · {{ paginator.total.toLocaleString() }} total</small></span>
        <Link v-if="paginator.next_page_url" :href="paginator.next_page_url" preserve-scroll preserve-state>Next →</Link>
        <span v-else class="is-disabled" aria-disabled="true">Next →</span>
    </nav>
</template>

<style scoped>
.admin-pagination { display:flex; align-items:center; justify-content:center; gap:16px; margin-top:20px; font-size:13px; flex-wrap:wrap; }
.admin-pagination a, .admin-pagination .is-disabled { display:inline-flex; align-items:center; min-height:40px; padding:0 14px; border:1px solid #cbded8; border-radius:9px; background:#fff; font-weight:700; color:#12675f; }
.admin-pagination .is-disabled { color:#a2b4b0; background:#f4f8f6; }
.admin-pagination a:focus-visible { outline:2px solid #137671; outline-offset:2px; }
.admin-pagination small { color:#7b918d; }
</style>
