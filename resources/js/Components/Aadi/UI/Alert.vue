<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    type?: 'success' | 'error' | 'warning' | 'info';
    title?: string;
    dismissible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    dismissible: false,
});

const emit = defineEmits<{
    (e: 'dismiss'): void;
}>();

const typeClasses = computed(() => {
    const types = {
        success: {
            container: 'bg-emerald-50 border-emerald-200',
            icon: 'text-emerald-600',
            title: 'text-emerald-800',
            text: 'text-emerald-700',
        },
        error: {
            container: 'bg-red-50 border-red-200',
            icon: 'text-red-600',
            title: 'text-red-800',
            text: 'text-red-700',
        },
        warning: {
            container: 'bg-amber-50 border-amber-200',
            icon: 'text-amber-600',
            title: 'text-amber-800',
            text: 'text-amber-700',
        },
        info: {
            container: 'bg-aadi-teal-50 border-aadi-teal-200',
            icon: 'text-aadi-teal-600',
            title: 'text-aadi-teal-800',
            text: 'text-aadi-teal-700',
        },
    };
    return types[props.type];
});

const iconPath = computed(() => {
    const paths = {
        success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    };
    return paths[props.type];
});
</script>

<template>
    <div
        :class="[
            'relative rounded-lg border p-4',
            typeClasses.container,
        ]"
    >
        <div class="flex items-start gap-3">
            <!-- Icon -->
            <svg
                :class="['h-5 w-5 flex-shrink-0 mt-0.5', typeClasses.icon]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath" />
            </svg>

            <!-- Content -->
            <div class="flex-1">
                <h4 v-if="title" :class="['text-sm font-semibold mb-1', typeClasses.title]">
                    {{ title }}
                </h4>
                <div :class="['text-sm', typeClasses.text]">
                    <slot />
                </div>
            </div>

            <!-- Dismiss Button -->
            <button
                v-if="dismissible"
                type="button"
                :class="['flex-shrink-0 rounded p-1 hover:bg-black/5', typeClasses.icon]"
                @click="emit('dismiss')"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>
