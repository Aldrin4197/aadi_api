<script setup>
import { computed, ref, watch } from 'vue';
import ListingImage from './ListingImage.vue';
const props = defineProps({ photos: { type: Array, default: () => [] }, title: String, service: String, type: String });
const active = ref(0);
const count = computed(() => props.photos.length);
watch(() => props.photos, () => { active.value = 0; }, { deep:true });
function step(direction) { active.value = (active.value + direction + count.value) % count.value; }
</script>
<template><section class="listing-gallery" aria-label="Listing photo gallery">
    <div class="gallery-stage"><ListingImage :src="photos[active]" :alt="`${title || 'Listing'}, photo ${active+1}`" :service="service" :type="type"/>
        <template v-if="count > 1"><button type="button" class="gallery-prev" aria-label="Previous photo" @click="step(-1)">‹</button><button type="button" class="gallery-next" aria-label="Next photo" @click="step(1)">›</button></template>
        <span v-if="count" class="gallery-counter" aria-live="polite">{{ active+1 }} / {{ count }}<span v-if="active === 0"> · Featured</span></span>
    </div>
    <div v-if="count > 1" class="gallery-thumbnails" role="group" aria-label="Choose a photo"><button v-for="(photo,index) in photos" :key="index" type="button" :aria-label="`Show photo ${index+1}${index === 0 ? ', featured' : ''}`" :aria-pressed="active === index" @click="active = index"><img :src="photo" alt="" loading="lazy"/></button></div>
</section></template>
<style scoped>
.gallery-stage { position:relative; aspect-ratio:1.5; border-radius:14px; overflow:hidden; background:#eef5f3; }
.gallery-stage :deep(.listing-image),.gallery-stage :deep(img) { width:100%; height:100%; }
.gallery-stage :deep(img) { object-fit:contain; }
.gallery-stage>button { position:absolute; top:50%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; border:1px solid #dcece9; background:#fffffff0; color:#0b3a38; font-size:28px; cursor:pointer; }
.gallery-prev { left:12px; }.gallery-next { right:12px; }
.gallery-counter { position:absolute; bottom:12px; right:12px; padding:6px 12px; border-radius:20px; background:#0b3a38; color:white; font-size:12px; }
.gallery-thumbnails { display:flex; flex-wrap:wrap; gap:8px; margin:12px 0 24px; }
.gallery-thumbnails button { width:64px; height:56px; border-radius:8px; overflow:hidden; border:2px solid transparent; cursor:pointer; }
.gallery-thumbnails button[aria-pressed=true] { border-color:#137671; }
.gallery-thumbnails img { width:100%; height:100%; object-fit:cover; }
.listing-gallery button:focus-visible { outline:2px solid #137671; outline-offset:3px; }
</style>
