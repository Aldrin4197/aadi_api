<script setup>
import { ref } from 'vue';
import ListingPreviewModal from './ListingPreviewModal.vue';
defineOptions({ inheritAttrs: false });
defineProps({ item: { type: Object, required: true } });
const open = ref(false);
</script>
<template>
    <button v-bind="$attrs" type="button" class="listing-preview-trigger" aria-haspopup="dialog" @click="open = true"><slot /></button>
    <ListingPreviewModal v-if="open" :item="item" @close="open = false" />
</template>
<style scoped>
.listing-preview-trigger { cursor:pointer; text-align:inherit; font:inherit; color:inherit; }
.listing-preview-trigger:focus-visible { outline:2px solid #137671; outline-offset:4px; }
</style>
