<script setup>
import { Link } from '@inertiajs/vue3';
import UserAvatar from '@/Components/Account/UserAvatar.vue';
import ProviderBadge from '@/Components/Account/ProviderBadge.vue';
defineProps({listing:Object});
</script>
<template><section class="listing-owner" aria-label="Listing provider"><UserAvatar :user="{name:listing.ownerName,avatar_url:listing.ownerAvatarUrl}"/><div class="listing-owner-info"><p class="owner-caption">Listed by</p><Link v-if="listing.ownerId" :href="`/providers/${listing.ownerId}`" class="owner-name">{{ listing.ownerName || 'Aadi provider' }}</Link><strong v-else>{{ listing.ownerName || 'Aadi provider' }}</strong><div class="owner-badge"><ProviderBadge role="user" :approved="listing.ownerEmailVerified"/><ProviderBadge :role="listing.service" :approved="listing.providerApproved"/></div><Link v-if="listing.ownerId" :href="`/providers/${listing.ownerId}`" class="owner-profile">View profile <span aria-hidden="true">→</span></Link></div></section></template>
<style scoped>
.listing-owner{display:flex;align-items:flex-start;gap:14px;margin:20px 0;padding:18px 0;border-top:1px solid #dcece9}.listing-owner :deep(.user-avatar){width:52px;height:52px;font-size:17px}.listing-owner-info{min-width:0;display:flex;align-items:flex-start;flex-direction:column;gap:7px}.listing-owner .owner-caption{font-size:11px!important;letter-spacing:.04em;text-transform:uppercase;color:#637b76;margin:0!important}.owner-name{font-size:16px;font-weight:700;overflow-wrap:anywhere}.owner-badge{display:flex;align-items:center;gap:6px}.owner-badge:empty{display:none}.owner-profile{font-size:12px;font-weight:600;color:#137671}.listing-owner a:focus-visible{outline:2px solid #137671;outline-offset:3px}
</style>
