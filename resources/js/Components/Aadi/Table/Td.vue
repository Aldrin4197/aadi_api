<script setup lang="ts">
interface Props {
    align?: 'left' | 'center' | 'right';
}

const props = withDefaults(defineProps<Props>(), {
    align: 'left',
});
</script>

<template>
    <td
        :class="[
            'px-6 py-4 text-sm text-slate-900 whitespace-nowrap',
            align === 'left' ? 'text-left' : align === 'center' ? 'text-center' : 'text-right',
        ]"
    >
        <slot />
    </td>
</template>
