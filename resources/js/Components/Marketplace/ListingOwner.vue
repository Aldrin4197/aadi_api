<script setup>
import { Link } from '@inertiajs/vue3';
import UserAvatar from '@/Components/Account/UserAvatar.vue';
import ProviderBadge from '@/Components/Account/ProviderBadge.vue';
import AadiPlusBadge from '@/Components/Badges/AadiPlusBadge.vue';
import ReportAccount from '@/Components/Account/ReportAccount.vue';
defineProps({listing:Object, preview:Boolean});
</script>
<template>
    <section class="listing-owner" aria-label="Listing provider">
        <div class="owner-header">
            <div class="owner-identity">
                <UserAvatar :user="{ name: listing.ownerName, avatar_url: listing.ownerAvatarUrl }" />
                <div class="listing-owner-info">
                    <p class="owner-caption">Listed by</p>
                    <Link v-if="listing.ownerId" :href="`/providers/${listing.ownerId}`" class="owner-name">
                        {{ listing.ownerName || 'Aadi provider' }}
                    </Link>
                    <strong v-else class="owner-name">{{ listing.ownerName || 'Aadi provider' }}</strong>
                    <div class="owner-badge" aria-label="Provider verification">
                        <ProviderBadge role="user" :approved="listing.ownerEmailVerified" compact />
                        <ProviderBadge :role="listing.service" :approved="listing.providerApproved" compact />
                        <AadiPlusBadge :active="listing.ownerAadiPlus" variant="icon" size="sm" />
                    </div>
                </div>
            </div>
            <ReportAccount
                v-if="listing.ownerId && !preview"
                :user-id="listing.ownerId"
                :name="listing.ownerName || 'provider'"
                listing-action
            />
        </div>
        <p v-if="!preview && listing.ownerBio" class="owner-bio">{{ listing.ownerBio }}</p>
    </section>
</template>
<style scoped>
.listing-owner {
    display: grid;
    gap: 12px;
    margin: 0;
    padding: 16px 0;
    border-top: 1px solid #dcece9;
}
.owner-header { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:start; gap:0 10px; }
.owner-identity { display:grid; grid-template-columns:44px minmax(0,1fr); align-items:center; gap:0 12px; }
.listing-owner :deep(.user-avatar) { width:44px; height:44px; font-size:15px; }
.listing-owner-info { min-width:0; display:grid; justify-items:start; gap:3px; }
.listing-owner .owner-caption {
    margin:0;
    color:#607975;
    font-size:10px;
    font-weight:700;
    letter-spacing:.07em;
    text-transform:uppercase;
}
.owner-name { color:#193e3b; font-size:15px; font-weight:700; line-height:1.3; overflow-wrap:anywhere; text-decoration:none; }
.owner-badge { display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-top:4px; }
.owner-badge:empty { display:none; }
.owner-bio { margin:0; color:#586e6c; font-size:13px; line-height:1.55; white-space:pre-line; overflow-wrap:anywhere; }
.listing-owner a:focus-visible { outline:2px solid #137671; outline-offset:3px; }
@media(max-width:420px) {
    .listing-owner { padding:12px; }
    .owner-identity { grid-template-columns:40px minmax(0,1fr); gap:0 10px; }
    .listing-owner :deep(.user-avatar) { width:40px; height:40px; font-size:14px; }
    .owner-header { gap:0 8px; }
}
</style>
