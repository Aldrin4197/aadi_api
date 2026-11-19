<script setup>
import ListingOwner from './ListingOwner.vue';
import ListingBadges from './ListingBadges.vue';
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ListingImage from './ListingImage.vue';
import { formatPrice } from './comparison.mjs';
defineProps({ item: { type: Object, required: true } });
const emit = defineEmits(['close']);
const dialog = ref(null);
const page = usePage();
const categories = { spaces: 'Space rental', haul: 'Vehicle rental', raketeer: 'Local service' };
let previousOverflow;
onMounted(() => {
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    dialog.value.showModal();
});
onBeforeUnmount(() => {
    dialog.value?.close();
    document.body.style.overflow = previousOverflow;
});
function close() { dialog.value?.close(); }
function backdrop(event) {
    if (event.target !== dialog.value) return;
    const b = dialog.value.getBoundingClientRect();
    if (event.clientX < b.left || event.clientX > b.right || event.clientY < b.top || event.clientY > b.bottom) close();
}
</script>
<template>
    <Teleport to="body">
        <dialog ref="dialog" class="listing-preview" :aria-label="`Preview: ${item.title}`" @close="emit('close')" @click="backdrop">
            <header><span>Listing preview</span><button type="button" aria-label="Close listing preview" autofocus @click="close">×</button></header>
            <div class="preview-image"><ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" :type="item.type" /></div>
            <div class="preview-body">
                <p class="preview-category">{{ categories[item.service] || 'Listing' }}</p>
                <ListingBadges :badges="item.badges" />
                <h2>{{ item.title }}</h2>
                <p class="preview-where">{{ item.location?.city || 'Location details available after login' }}<template v-if="item.type"> · <span class="preview-type">{{ item.type.replace(/([A-Z])/g, ' $1') }}</span></template></p>
                <p v-if="item.availabilityPaused" class="preview-category">{{ item.service === 'spaces' ? 'Occupied / unavailable' : 'Currently unavailable' }}</p><p class="preview-rate"><strong>{{ formatPrice(item.price) }}</strong> / {{ item.priceUnit }}</p>
                <p class="preview-note">Listed rates may exclude additional charges. Confirm availability and terms with the provider.</p>
                <ListingOwner :listing="item" preview />
                <footer>
                    <p v-if="!page.props.auth?.user">Preview freely. Log in or register to access full listing details.</p>
                    <Link :href="`/offers/${item.service}/${item.id}`" class="preview-primary">View Full Listing <span aria-hidden="true">→</span></Link>
                </footer>
            </div>
        </dialog>
    </Teleport>
</template>
<style scoped>
.listing-preview { margin:auto; padding:0; border:0; border-radius:18px; width:min(1000px,calc(100% - 48px)); max-height:calc(100dvh - 32px); overflow:auto; overscroll-behavior:contain; background:#fff; color:#0b3a38; font-family:Quicksand,sans-serif; box-shadow:0 24px 80px #062b2933; }
.listing-preview[open] { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(0,1fr); grid-template-rows:auto minmax(0,1fr); }
.listing-preview::backdrop { background:#062b2970; }
.listing-preview header { grid-column:1 / -1; display:flex; align-items:center; justify-content:space-between; padding:6px 20px 6px 24px; font-size:13px; font-weight:700; border-bottom:1px solid #dcece9; }
.listing-preview header button { width:44px; height:44px; border:0; background:transparent; font-size:26px; cursor:pointer; }
.preview-image { overflow:hidden; min-height:320px; }
.preview-body { display:flex; flex-direction:column; padding:24px 28px; min-width:0; }
.preview-body h2 { font-size:var(--aadi-text-heading); line-height:1.3; font-weight:700; margin:8px 0 10px; overflow-wrap:anywhere; }
.preview-body p { margin:6px 0; font-size:var(--aadi-text-body); line-height:1.6; overflow-wrap:anywhere; }
.preview-category { color:#137671; font-weight:700; }
.preview-type { text-transform:capitalize; }
.preview-rate strong { font-size:26px; }
.preview-body .preview-note { color:#586e6c; font-size:var(--aadi-text-small); margin:12px 0 0; }
.preview-body footer { margin-top:auto; border-top:1px solid #dcece9; padding-top:14px; }
.preview-body footer p { margin:0; font-size:12px; color:#586e6c; }
.preview-primary { display:flex; align-items:center; justify-content:space-between; gap:16px; background:#1ba8a1; color:#fff; border-radius:10px; padding:14px 18px; margin-top:12px; text-decoration:none; font-size:14px; font-weight:700; }
.listing-preview :is(a,button):focus-visible { outline:2px solid #137671; outline-offset:3px; }
@media(max-width:860px) {
  .listing-preview { width:min(540px,calc(100% - 32px)); }
  .listing-preview[open] { grid-template-columns:1fr; grid-template-rows:auto; }
  .preview-image { min-height:0; aspect-ratio:2.2; }
  .preview-body { padding:20px 24px; }
  .preview-body footer { margin-top:16px; }
}
@media(max-width:480px) {
  .listing-preview { width:calc(100% - 24px); max-height:calc(100dvh - 24px); }
  .listing-preview header { padding:0 8px 0 18px; }
  .preview-body { padding:16px 18px; }
  .preview-body h2 { margin:4px 0 6px; }
  .preview-rate strong { font-size:22px; }
  .preview-body .preview-note { margin:8px 0 0; }
  .preview-body footer { margin-top:10px; padding-top:10px; }
  .preview-primary { padding:12px 16px; margin-top:8px; }
}
</style>
