<script setup>
defineProps({ active: Boolean, summary: String });
defineEmits(['reset']);
</script>

<template>
    <form class="admin-filters" role="search" @submit.prevent>
        <div class="filter-fields"><slot /></div>
        <div class="filter-foot">
            <p v-if="summary" role="status">{{ summary }}</p>
            <button v-if="active" type="button" class="filter-reset" @click="$emit('reset')">Reset filters</button>
        </div>
    </form>
</template>

<style scoped>
.admin-filters { margin-bottom:16px; padding:14px 16px; background:#fff; border:1px solid #dce7e4; border-radius:12px; }
.filter-fields { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.filter-fields :deep(label) { display:grid; gap:5px; font-size:11px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:#637b76; min-width:140px; flex:0 1 170px; }
.filter-fields :deep(label.grow) { flex:1 1 240px; }
.filter-fields :deep(label.narrow) { flex:0 1 110px; min-width:100px; }
.filter-fields :deep(:is(input, select)) { min-height:40px; width:100%; padding:8px 10px; border:1px solid #cbded8; border-radius:9px; background:#fff; color:#183e3b; font-size:13px; font-weight:500; letter-spacing:0; text-transform:none; }
.filter-fields :deep(:is(input, select):focus-visible) { outline:2px solid #137671; outline-offset:1px; }
.filter-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:10px; font-size:12px; color:#637b76; }
.filter-foot p { margin:0; }
.filter-reset { min-height:32px; padding:0 12px; border:1px solid #cbded8; border-radius:8px; font-weight:700; color:#12675f; cursor:pointer; background:#fff; }
.filter-reset:focus-visible { outline:2px solid #137671; outline-offset:2px; }
</style>
