<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    src?: string;
    alt?: string;
    name?: string;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    alt: 'Avatar',
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'h-6 w-6 text-xs',
        sm: 'h-8 w-8 text-sm',
        md: 'h-10 w-10 text-base',
        lg: 'h-12 w-12 text-lg',
        xl: 'h-16 w-16 text-xl',
        '2xl': 'h-24 w-24 text-3xl',
    };
    return sizes[props.size];
});

const initials = computed(() => {
    if (!props.name) return '';
    
    const names = props.name.trim().split(' ');
    if (names.length >= 2) {
        return `${names[0][0]}${names[names.length - 1][0]}`.toUpperCase();
    }
    return names[0].substring(0, 2).toUpperCase();
});
</script>

<template>
    <div
        :class="[
            'relative inline-flex items-center justify-center rounded-full shrink-0 overflow-hidden',
            sizeClasses,
        ]"
    >
        <!-- Image -->
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            class="h-full w-full object-cover"
        />
        
        <!-- Initials Fallback -->
        <div
            v-else
            class="flex h-full w-full items-center justify-center bg-linear-to-br from-aadi-teal to-aadi-teal-800 text-white font-semibold"
        >
            {{ initials || alt[0]?.toUpperCase() }}
        </div>
    </div>
</template>
