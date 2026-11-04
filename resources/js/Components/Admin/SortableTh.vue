<script setup>
import { computed } from 'vue';

const props = defineProps({ column: { type: String, required: true }, sort: String, dir: String });
defineEmits(['sort']);
const current = computed(() => props.sort === props.column);
const ariaSort = computed(() => (current.value ? (props.dir === 'asc' ? 'ascending' : 'descending') : 'none'));
</script>

<template>
    <th scope="col" :aria-sort="ariaSort" class="sortable-th">
        <button type="button" :class="{ active: current }" @click="$emit('sort', column)">
            <slot />
            <svg viewBox="0 0 12 14" aria-hidden="true">
                <path d="M6 1 2.5 5h7z" :class="{ on: current && dir === 'asc' }" />
                <path d="M6 13 2.5 9h7z" :class="{ on: current && dir === 'desc' }" />
            </svg>
        </button>
    </th>
</template>

<style scoped>
.sortable-th button { display:inline-flex; align-items:center; gap:6px; min-height:32px; font:inherit; letter-spacing:inherit; text-transform:inherit; color:inherit; cursor:pointer; white-space:nowrap; }
.sortable-th button:hover, .sortable-th button.active { color:#0f5f58; }
.sortable-th button:focus-visible { outline:2px solid #137671; outline-offset:2px; border-radius:4px; }
.sortable-th svg { width:9px; height:11px; fill:#c3d3cf; flex-shrink:0; }
.sortable-th svg .on { fill:#137671; }
</style>
