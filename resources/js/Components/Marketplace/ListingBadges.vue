<script setup>
import AadiStatusBadge from '@/Components/Badges/AadiStatusBadge.vue';
import AadiRecommendedBadge from '@/Components/Badges/AadiRecommendedBadge.vue';

defineProps({ badges: { type: Object, default: () => ({}) } });
const statusTypes = ['featured', 'top-rated', 'new', 'popular', 'available-now'];
</script>

<template>
    <div v-if="badges && Object.values(badges).some(Boolean)" class="listing-badges" aria-label="Listing badges">
        <AadiStatusBadge
            v-for="type in statusTypes"
            :key="type"
            :type="type"
            :active="Boolean(badges[type])"
            icon-style="filled"
            variant="icon"
            size="sm"
        />
        <AadiRecommendedBadge
            :active="Boolean(badges.recommended)"
            icon-style="filled"
            variant="icon"
            size="sm"
        />
    </div>
</template>

<style scoped>
.listing-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    min-height: 28px;
}
</style>
