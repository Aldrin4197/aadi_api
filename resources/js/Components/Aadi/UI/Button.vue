<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    variant?: 'primary' | 'orange' | 'secondary' | 'success' | 'danger' | 'warning' | 'outline' | 'ghost';
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    icon?: string;
    iconPosition?: 'left' | 'right';
    href?: string;
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    iconPosition: 'left',
    type: 'button',
    disabled: false,
    loading: false,
});

const variantClasses = computed(() => {
    const variants = {
        primary: 'bg-aadi-teal hover:bg-aadi-teal-500 focus:ring-aadi-teal text-white shadow-xs',
        orange: 'bg-aadi-orange hover:bg-aadi-orange-500 focus:ring-aadi-orange text-white shadow-xs',
        secondary: 'bg-slate-600 hover:bg-slate-700 focus:ring-slate-500 text-white shadow-xs',
        success: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 text-white shadow-xs',
        danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white shadow-xs',
        warning: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 text-white shadow-xs',
        outline: 'border-2 border-aadi-teal text-aadi-teal hover:bg-aadi-teal-50 focus:ring-aadi-teal',
        ghost: 'text-aadi-teal hover:bg-aadi-teal-50 focus:ring-aadi-teal',
    };
    return variants[props.variant];
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'px-2.5 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2.5 text-sm',
        lg: 'px-5 py-3 text-base',
        xl: 'px-6 py-3.5 text-base',
    };
    return sizes[props.size];
});

const buttonClasses = computed(() => {
    return [
        'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200',
        'focus:outline-hidden focus:ring-2 focus:ring-offset-2',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        variantClasses.value,
        sizeClasses.value,
    ].join(' ');
});

const component = computed(() => props.href ? Link : 'button');
</script>

<template>
    <component
        :is="component"
        :href="href"
        :type="!href ? type : undefined"
        :disabled="disabled || loading"
        :class="buttonClasses"
    >
        <svg
            v-if="loading"
            class="animate-spin h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        
        <slot v-if="!loading" />
    </component>
</template>
