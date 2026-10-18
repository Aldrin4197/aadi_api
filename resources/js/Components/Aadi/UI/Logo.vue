<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'icon' | 'logo' | 'full';
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'logo',
    size: 'md',
});

const logoSrc = computed(() => {
    const logos = {
        icon: '/images/logos/Aadi_icon.png',
        logo: '/images/logos/Aadi_Color.png',
        full: '/images/logos/Aadi_Color_w_tagname.png',
    };
    return logos[props.variant];
});

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'h-6',
        sm: 'h-8',
        md: 'h-10',
        lg: 'h-16',
        xl: 'h-24',
    };
    return sizes[props.size];
});
</script>

<template>
    <img 
        :src="logoSrc" 
        alt="Aadi" 
        :class="['w-auto object-contain', sizeClasses]"
    />
</template>
