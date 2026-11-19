<script setup>
import ListingViewButton from '@/Components/Marketplace/ListingViewButton.vue';
import ProviderBadge from '@/Components/Account/ProviderBadge.vue';
import AadiPlusBadge from '@/Components/Badges/AadiPlusBadge.vue';
import ListingBadges from '@/Components/Marketplace/ListingBadges.vue';
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AccountLayout from '@/Layouts/AccountLayout.vue';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import AccountEmptyState from '@/Components/Account/AccountEmptyState.vue';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
import ListingPreviewModal from '@/Components/Marketplace/ListingPreviewModal.vue';
import { accountIcons } from '@/Components/Account/accountLinks.mjs';
defineProps({ results: Object });
const selected = ref(null);
const form = useForm({});
const categories = { spaces: 'Space rental', haul: 'Vehicle rental', raketeer: 'Local service' };
const price = item => new Intl.NumberFormat('en-PH', { style: 'currency', currency: item.currency || 'PHP' }).format(item.price);
function remove(item) {
    form.delete(`/offers/${item.service}/${item.id}/save`, { preserveScroll: true });
}
</script>
<template>
    <AccountLayout>
        <Head title="Saved listings" />
        <main class="account-page saved-page">
            <AccountPageHeader title="Saved listings" description="Your favorite places, vehicles and local services, ready when you are." />
            <AccountEmptyState
                v-if="!results.data.length"
                :icon="accountIcons.saved"
                title="Your next find belongs here"
                description="Save listings while you explore, then come back to compare your options."
                action-href="/explore"
                action-label="Explore nearby"
            />
            <div v-if="results.data.length" class="saved-toolbar">
                <p class="saved-count">{{ results.total }} saved listings</p>
            </div>
            <ul v-if="results.data.length" class="saved-list" aria-label="Saved listings">
                <li v-for="item in results.data" :key="`${item.service}-${item.id}`" class="saved-card">
                    <template v-if="!item.unavailable">
                        <button type="button" class="saved-photo" :aria-label="`Preview ${item.title}`" aria-haspopup="dialog" @click="selected = item"><ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" :type="item.type" /></button>
                        <div class="saved-info">
                            <div class="saved-info-heading">
                                <p class="saved-category">{{ categories[item.service] }}</p>
                                <button type="button" class="saved-remove" :disabled="form.processing" :aria-label="`Remove ${item.title} from saved`" @click="remove(item)">Remove</button>
                            </div>
                            <h2><button type="button" aria-haspopup="dialog" @click="selected = item">{{ item.title }}</button></h2>
                            <div class="saved-signals">
                                <ListingBadges :badges="item.badges"/>
                                <ProviderBadge role="user" :approved="item.ownerEmailVerified"/>
                                <ProviderBadge :role="item.service" :approved="item.providerApproved"/>
                                <AadiPlusBadge :active="item.ownerAadiPlus" variant="icon" size="sm"/>
                            </div>
                            <p class="saved-location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ item.location.city || item.location.address || 'Location not provided' }}</p>
                            <p class="saved-description">{{ item.description || 'Open the preview to learn more about this listing.' }}</p>
                        </div>
                        <div class="saved-actions"><p class="saved-price"><strong>{{ price(item) }}</strong><span>/ {{ item.priceUnit }}</span></p><ListingViewButton :item="item"/></div>
                    </template>
                    <template v-else><div class="saved-unavailable"><h2>Listing unavailable</h2><p>This listing is no longer publicly available.</p></div><button type="button" class="saved-remove" :disabled="form.processing" @click="remove(item)">Remove from saved</button></template>
                </li>
            </ul>
            <nav v-if="results.last_page > 1" class="account-pagination" aria-label="Saved listing pages"><Link v-if="results.prev_page_url" :href="results.prev_page_url">Previous</Link><span>Page {{ results.current_page }} of {{ results.last_page }}</span><Link v-if="results.next_page_url" :href="results.next_page_url">Next</Link></nav>
        </main>
        <ListingPreviewModal v-if="selected" :item="selected" @close="selected = null" />
    </AccountLayout>
</template>
<style scoped>
.saved-toolbar{margin:0 0 12px}
.saved-count{margin:0;color:var(--muted);font-size:var(--aadi-text-small);font-variant-numeric:tabular-nums}
.saved-list{list-style:none;display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,300px),1fr));gap:12px;margin:0;padding:0}
.saved-card{display:grid;grid-template-columns:minmax(0,1fr);align-content:start;gap:10px;border:1px solid var(--line);border-radius:10px;padding:12px;background:#fff;min-width:0}
.saved-photo{width:100%;height:148px;min-height:148px;border:0;padding:0;border-radius:8px;overflow:hidden;background:#eaf5f2;cursor:pointer}
.saved-photo :deep(.listing-image){height:100%;min-height:148px}
.saved-info{min-width:0;padding:0}
.saved-info-heading{display:flex;align-items:center;justify-content:flex-start;gap:8px;margin-bottom:3px}
.saved-category{font-size:11px;font-weight:650;color:var(--teal);margin:0}
.saved-info-heading .saved-remove{align-self:center;min-height:32px;padding:0 2px;border:0;border-radius:0;background:transparent;text-decoration:underline;text-underline-offset:3px}
.saved-info h2,.saved-unavailable h2{font-size:var(--aadi-text-body);line-height:1.35;font-weight:700;margin:0 0 4px;overflow-wrap:anywhere}
.saved-info h2 button{font:inherit;color:inherit;text-align:left;background:none;border:0;padding:0;cursor:pointer}
.saved-location{display:flex;align-items:flex-start;gap:6px;font-size:var(--aadi-text-small);color:var(--muted);overflow-wrap:anywhere;margin:8px 0 0}
.saved-location svg{width:16px;height:18px;flex-shrink:0;fill:none;stroke:currentColor;stroke-width:1.6}
.saved-description{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;overflow-wrap:anywhere;margin:8px 0 0;font-size:var(--aadi-text-small);line-height:1.5;color:var(--muted)}
.saved-signals{display:flex;min-width:0;flex-wrap:wrap;align-items:center;gap:4px}
.saved-actions{display:grid;grid-template-columns:minmax(0,1fr) auto;border-top:1px solid var(--line);padding-top:10px}
.saved-price{margin:0;display:flex;align-items:baseline;gap:4px}
.saved-price strong{font-size:var(--aadi-text-body);font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
.saved-price span{font-size:var(--aadi-text-small);color:var(--muted)}
.saved-remove{align-self:flex-start;background:none;border:0;color:var(--muted);font:inherit;font-size:var(--aadi-text-small);text-decoration:underline;text-underline-offset:3px;min-height:44px;padding:0 8px;cursor:pointer}
.saved-remove:disabled{opacity:.5;cursor:wait}
.saved-unavailable{align-self:center;padding:4px}
.saved-page :is(button,a):focus-visible{outline:2px solid var(--teal);outline-offset:3px}
@media(max-width:700px){
    .saved-list{grid-template-columns:minmax(0,1fr)}
    .saved-info-heading .saved-remove{min-height:32px;padding-inline:2px;font-size:11px}
    .saved-info h2{font-size:var(--aadi-text-body)}
    .saved-actions{gap:8px}
    .saved-price strong{font-size:var(--aadi-text-small)}
    .saved-remove{min-height:38px}
}
</style>
