<script setup lang="ts">
interface Props {
    title?: string;
    padding?: boolean;
    border?: boolean;
}

withDefaults(defineProps<Props>(), {
    padding: true,
    border: true,
});
</script>

<template>
    <div
        :class="[
            'bg-white rounded-lg',
            border ? 'border border-slate-200 shadow-xs' : '',
        ]"
    >
        <!-- Header -->
        <div
            v-if="title || $slots.actions"
            :class="[
                'flex items-center justify-between',
                padding ? 'px-6 py-4' : 'p-0',
                'border-b border-slate-200',
            ]"
        >
            <h3 v-if="title" class="text-lg font-semibold text-slate-900">
                {{ title }}
            </h3>
            
            <div v-if="$slots.actions" class="flex items-center gap-3">
                <slot name="actions" />
            </div>
        </div>

        <!-- Content -->
        <div :class="padding ? 'p-6' : 'p-0'">
            <slot />
        </div>

        <!-- Footer -->
        <div
            v-if="$slots.footer"
            :class="[
                padding ? 'px-6 py-4' : 'p-0',
                'border-t border-slate-200 bg-slate-50',
            ]"
        >
            <slot name="footer" />
        </div>
    </div>
</template>
