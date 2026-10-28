<script setup>
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
                <h2>{{ item.title }}</h2>
                <p>{{ item.location?.city || 'Location details available after login' }}</p>
                <p v-if="item.type" class="preview-type">{{ item.type.replace(/([A-Z])/g, ' $1') }}</p>
                <p class="preview-rate"><strong>{{ formatPrice(item.price) }}</strong> / {{ item.priceUnit }}</p>
                <p class="preview-note">Listed rates may exclude additional charges. Confirm availability and terms with the provider.</p>
                <p v-if="item.ownerId && item.ownerName"><Link :href="`/providers/${item.ownerId}`">Listed by {{ item.ownerName }} · View profile →</Link></p>
                <footer>
                    <p v-if="!page.props.auth?.user">Preview freely. Log in or register to access full listing details.</p>
                    <Link :href="`/offers/${item.service}/${item.id}`" class="preview-primary">{{ page.props.auth?.user ? 'View full details' : 'Log in to view full details' }} <span aria-hidden="true">→</span></Link>
                    <button type="button" class="preview-back" @click="close">Keep exploring</button>
                </footer>
            </div>
        </dialog>
    </Teleport>
</template>
<style scoped>
.listing-preview { margin:auto; padding:0; border:0; border-radius:18px; width:min(540px,calc(100% - 32px)); max-height:calc(100dvh - 32px); overflow:auto; overscroll-behavior:contain; background:#fff; color:#0b3a38; font-family:Quicksand,sans-serif; box-shadow:0 24px 80px #062b2933; }
.listing-preview::backdrop { background:#062b2970; }
.listing-preview header { display:flex; align-items:center; justify-content:space-between; padding:10px 20px; font-size:13px; font-weight:700; }
.listing-preview header button { width:44px; height:44px; border:0; background:transparent; font-size:26px; cursor:pointer; }
.preview-image { aspect-ratio:1.8; overflow:hidden; }
.preview-body { padding:24px; }
.preview-body h2 { font-size:26px; line-height:1.25; font-weight:700; margin:8px 0 12px; overflow-wrap:anywhere; }
.preview-body p { margin:8px 0; font-size:14px; line-height:1.6; overflow-wrap:anywhere; }
.preview-category { color:#137671; font-weight:700; }
.preview-type { text-transform:capitalize; }
.preview-rate strong { font-size:26px; }
.preview-body .preview-note { color:#586e6c; font-size:12px; margin:16px 0 24px; }
.preview-body footer { border-top:1px solid #dcece9; padding-top:16px; }
.preview-primary { display:flex; align-items:center; justify-content:space-between; gap:16px; background:#1ba8a1; color:#fff; border-radius:10px; padding:14px 18px; margin-top:16px; text-decoration:none; font-size:14px; font-weight:700; }
.preview-back { display:block; width:100%; min-height:44px; margin-top:8px; border:0; background:transparent; color:#0b3a38; cursor:pointer; font-size:14px; }
.listing-preview :is(a,button):focus-visible { outline:2px solid #137671; outline-offset:3px; }
@media(max-width:480px) { .preview-body { padding:20px; } .preview-body h2 { font-size:23px; } }
</style>
