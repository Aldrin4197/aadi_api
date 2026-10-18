<script setup lang="ts">
interface Props {
    striped?: boolean;
    hoverable?: boolean;
}

withDefaults(defineProps<Props>(), {
    striped: false,
    hoverable: true,
});
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <slot />
        </table>
    </div>
</template>
