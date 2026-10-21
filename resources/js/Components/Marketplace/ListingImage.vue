<script setup>
import { computed, ref, watch } from 'vue';
import { listingIllustration } from './illustrations.mjs';

const props = defineProps({ src: String, alt: String, service: String, type: String });
const photoFailed = ref(false);
const illustrationFailed = ref(false);
const isIllustration = computed(() => !props.src || photoFailed.value);
const image = computed(() => isIllustration.value ? listingIllustration(props.service, props.type) : props.src);
const category = computed(() => ({ spaces: 'Rental space', haul: 'Vehicle rental', raketeer: 'Local service' })[props.service] || 'Rental space');
watch(() => [props.src, props.service, props.type], () => {
    photoFailed.value = false;
    illustrationFailed.value = false;
});
function failed() {
    if (isIllustration.value) illustrationFailed.value = true;
    else photoFailed.value = true;
}
</script>

<template>
    <span class="listing-image">
        <img v-if="!illustrationFailed" :src="image" :alt="isIllustration ? `${category} illustration; listing photo unavailable` : alt" width="480" height="300" loading="lazy" decoding="async" @error="failed" />
        <span v-else class="listing-image-unavailable">{{ category }}<small>Photo unavailable</small></span>
        <span v-if="isIllustration && !illustrationFailed" class="listing-image-caption">Illustration</span>
    </span>
</template>

<style scoped>
.listing-image { display: block; position: relative; width: 100%; height: 100%; overflow: hidden; background: #e3f6f4; }
.listing-image img { display: block; width: 100%; height: 100%; object-fit: cover; }
.listing-image-caption { position: absolute; bottom: 7px; left: 7px; right: 7px; width: fit-content; padding: 3px 6px; border-radius: 4px; background: #ffffffed; color: #0b3a38; font-size: 9px; line-height: 1.4; font-weight: 600; text-transform: none; }
.listing-image-unavailable { display: flex; min-height: 160px; height: 100%; flex-direction: column; justify-content: center; align-items: center; padding: 12px; text-align: center; font-size: 13px; color: #0b3a38; }
.listing-image-unavailable small { font-size: 11px; margin-top: 5px; }
</style>
