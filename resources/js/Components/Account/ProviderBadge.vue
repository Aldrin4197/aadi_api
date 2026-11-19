<script setup>
import AadiVerifiedBadge from '@/Components/Badges/AadiVerifiedBadge.vue';
import { computed } from 'vue';

const props = defineProps({ role: String, approved: Boolean, compact: Boolean });
const providerRole = computed(() => ({
    user: 'user',
    spaces: 'landlord',
    haul: 'vehicle-owner',
    raketeer: 'raketeer',
}[props.role]));
</script>

<template>
    <AadiVerifiedBadge
        v-if="providerRole"
        :role="providerRole"
        :verified="approved"
        icon-style="filled"
        variant="icon"
        :size="compact ? 'sm' : 'md'"
    />
</template>
