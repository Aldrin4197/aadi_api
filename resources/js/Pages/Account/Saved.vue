<script setup>
import ProviderBadge from '@/Components/Account/ProviderBadge.vue';
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AccountLayout from '@/Layouts/AccountLayout.vue';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
import ListingPreviewModal from '@/Components/Marketplace/ListingPreviewModal.vue';
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
        <main class="saved-page">
            <header class="saved-heading"><p class="eyebrow">YOUR SHORTLIST</p><h1>Saved listings</h1><p>Your favorite places, vehicles and local services, ready when you are.</p></header>
            <div v-if="!results.data.length" class="saved-empty"><h2>Your next find belongs here</h2><p>Save listings while you explore, then come back to compare your options.</p><Link href="/explore" class="button primary">Explore nearby</Link></div>
            <ul v-else class="saved-list" aria-label="Saved listings">
                <li v-for="item in results.data" :key="`${item.service}-${item.id}`" class="saved-card">
                    <template v-if="!item.unavailable">
                        <button type="button" class="saved-photo" :aria-label="`Preview ${item.title}`" aria-haspopup="dialog" @click="selected = item"><ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" :type="item.type" /></button>
                        <div class="saved-info"><p class="saved-category">{{ categories[item.service] }}</p><h2><button type="button" aria-haspopup="dialog" @click="selected = item">{{ item.title }}</button></h2><p class="saved-location"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ item.location.city || item.location.address || 'Location not provided' }}</p><ProviderBadge role="user" :approved="item.ownerEmailVerified"/><ProviderBadge :role="item.service" :approved="item.providerApproved"/><p class="saved-description">{{ item.description || 'Open the preview to learn more about this listing.' }}</p></div>
                        <div class="saved-actions"><p class="saved-price"><strong>{{ price(item) }}</strong><span>/ {{ item.priceUnit }}</span></p><button type="button" class="saved-view" aria-haspopup="dialog" @click="selected = item">View listing <span aria-hidden="true">↗</span></button><button type="button" class="saved-remove" :disabled="form.processing" :aria-label="`Remove ${item.title} from saved`" @click="remove(item)">Remove from saved</button></div>
                    </template>
                    <template v-else><div class="saved-unavailable"><h2>Listing unavailable</h2><p>This listing is no longer publicly available.</p></div><button type="button" class="saved-remove" :disabled="form.processing" @click="remove(item)">Remove from saved</button></template>
                </li>
            </ul>
            <nav v-if="results.last_page > 1" class="saved-pagination" aria-label="Saved listing pages"><Link v-if="results.prev_page_url" :href="results.prev_page_url">Previous</Link><span>Page {{ results.current_page }} of {{ results.last_page }}</span><Link v-if="results.next_page_url" :href="results.next_page_url">Next</Link></nav>
        </main>
        <ListingPreviewModal v-if="selected" :item="selected" @close="selected = null" />
    </AccountLayout>
</template>
<style scoped>
.saved-page{max-width:1120px;margin-inline:auto}.saved-heading{margin-bottom:28px}.saved-heading h1{font-size:clamp(28px,3vw,36px);font-weight:700;letter-spacing:-.025em;margin:6px 0 10px}.saved-heading>p:last-child,.saved-empty p{color:#586e6c;line-height:1.6}.saved-list{list-style:none;display:grid;gap:18px;margin:0;padding:0}.saved-card{display:flex;align-items:stretch;gap:24px;border:1px solid #dcece9;border-radius:16px;padding:16px;background:#fff;min-width:0}.saved-photo{flex:0 0 216px;width:216px;min-height:172px;border:0;padding:0;border-radius:10px;overflow:hidden;background:#eaf5f2;cursor:pointer}.saved-photo :deep(.listing-image){height:100%;min-height:172px}.saved-info{flex:1;min-width:0;padding:4px 0}.saved-category{font-size:12px;font-weight:700;color:#137671;margin:0 0 8px}.saved-info h2,.saved-unavailable h2{font-size:20px;line-height:1.4;font-weight:700;margin:0 0 8px;overflow-wrap:anywhere}.saved-info h2 button{font:inherit;color:inherit;text-align:left;background:none;border:0;padding:0;cursor:pointer}.saved-location{display:flex;align-items:flex-start;gap:6px;font-size:13px;color:#586e6c;overflow-wrap:anywhere;margin:0}.saved-location svg{width:16px;height:18px;flex-shrink:0;fill:none;stroke:currentColor;stroke-width:1.6}.saved-badge{display:inline-block;font-size:11px;font-weight:700;color:#12675f;background:#e6f6f2;border-radius:20px;padding:5px 9px;margin:10px 0 0}.saved-description{font-size:13px;line-height:1.6;color:#586e6c;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;overflow-wrap:anywhere;margin:12px 0 0}.saved-actions{display:flex;flex-direction:column;justify-content:center;gap:8px;flex:0 0 180px;border-left:1px solid #e5efed;padding-left:24px}.saved-price{margin:0 0 8px;display:flex;flex-wrap:wrap;align-items:baseline;gap:5px}.saved-price strong{font-size:21px;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}.saved-price span{font-size:12px;color:#586e6c}.saved-view{display:flex;align-items:center;justify-content:space-between;background:#1ba8a1;color:white;font-weight:700;border:0;border-radius:9px;padding:12px 14px;min-height:44px;cursor:pointer}.saved-remove{background:none;border:0;color:#586e6c;font-size:12px;text-decoration:underline;text-underline-offset:3px;min-height:44px;cursor:pointer}.saved-remove:disabled{opacity:.5;cursor:wait}.saved-unavailable{flex:1;padding:12px}.saved-empty{background:white;border:1px dashed #b7ceca;border-radius:16px;padding:40px 24px;text-align:center}.saved-empty h2{font-size:22px;font-weight:700}.saved-empty .button{display:inline-flex;margin-top:16px}.saved-pagination{display:flex;justify-content:center;gap:20px;margin-top:28px;font-size:14px}.saved-page :is(button,a):focus-visible{outline:2px solid #137671;outline-offset:4px}.saved-view:active{transform:scale(.98)}
@media(max-width:1200px){.saved-photo{flex-basis:176px;width:176px}.saved-card{gap:18px}.saved-actions{flex-basis:156px;padding-left:18px}}
@media(max-width:700px){.saved-card{display:grid;grid-template-columns:minmax(0,1fr);gap:14px;padding:12px}.saved-photo{width:100%;height:190px;min-height:0}.saved-info{padding:0 4px}.saved-actions{border-left:0;border-top:1px solid #e5efed;padding:14px 4px 0;display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:4px 12px}.saved-price{margin:0}.saved-remove{grid-column:1/-1;justify-self:start}.saved-unavailable{padding:8px}}
@media(prefers-reduced-motion:reduce){.saved-view:active{transform:none}}
</style>
