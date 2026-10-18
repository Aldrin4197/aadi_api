<script setup lang="ts">
interface Props {
    sortable?: boolean;
    sorted?: 'asc' | 'desc' | null;
    align?: 'left' | 'center' | 'right';
}

const props = withDefaults(defineProps<Props>(), {
    sortable: false,
    sorted: null,
    align: 'left',
});

const emit = defineEmits<{
    (e: 'sort'): void;
}>();
</script>

<template>
    <th
        :class="[
            'px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-slate-50',
            align === 'left' ? 'text-left' : align === 'center' ? 'text-center' : 'text-right',
            sortable ? 'cursor-pointer hover:text-aadi-teal transition-colors' : '',
        ]"
        @click="sortable && emit('sort')"
    >
        <div class="flex items-center gap-2">
            <slot />
            
            <!-- Sort Icon -->
            <svg
                v-if="sortable"
                class="h-4 w-4 flex-shrink-0"
                :class="sorted ? 'text-aadi-teal' : 'text-slate-400'"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    v-if="sorted === 'asc'"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 15l7-7 7 7"
                />
                <path
                    v-else-if="sorted === 'desc'"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
                <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                />
            </svg>
        </div>
    </th>
</template>
