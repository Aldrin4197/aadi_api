<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'primary' | 'orange' | 'success' | 'danger' | 'warning' | 'info' | 'secondary' | 'dark';
    size?: 'sm' | 'md' | 'lg';
    dot?: boolean;
    rounded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    dot: false,
    rounded: false,
});

const variantClasses = computed(() => {
    const variants = {
        primary: 'bg-aadi-teal-100 text-aadi-teal-800 ring-aadi-teal-200',
        orange: 'bg-aadi-orange-100 text-aadi-orange-800 ring-aadi-orange-200',
        success: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        danger: 'bg-red-100 text-red-800 ring-red-200',
        warning: 'bg-amber-100 text-amber-800 ring-amber-200',
        info: 'bg-blue-100 text-blue-800 ring-blue-200',
        secondary: 'bg-slate-100 text-slate-800 ring-slate-200',
        dark: 'bg-slate-800 text-white ring-slate-700',
    };
    return variants[props.variant];
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-sm',
        lg: 'px-3 py-1.5 text-base',
    };
    return sizes[props.size];
});

const dotColorClass = computed(() => {
    const colors = {
        primary: 'bg-aadi-teal-500',
        orange: 'bg-aadi-orange-500',
        success: 'bg-emerald-500',
        danger: 'bg-red-500',
        warning: 'bg-amber-500',
        info: 'bg-blue-500',
        secondary: 'bg-slate-500',
        dark: 'bg-slate-300',
    };
    return colors[props.variant];
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 font-medium ring-1 ring-inset',
            rounded ? 'rounded-full' : 'rounded-md',
            variantClasses,
            sizeClasses,
        ]"
    >
        <span v-if="dot" :class="['h-1.5 w-1.5 rounded-full', dotColorClass]"></span>
        <slot />
    </span>
</template>
