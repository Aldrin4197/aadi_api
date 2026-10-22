<script setup>
import { computed, ref, watch } from 'vue';
const props = defineProps({ user: Object });
const failed = ref(false);
const photo = computed(() => props.user?.avatar_url || '');
const initials = computed(() => (props.user?.name || 'Aadi user').trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase());
watch(photo, () => { failed.value = false; });
</script>
<template>
  <span class="user-avatar" aria-hidden="true">
    <img v-if="photo && !failed" :src="photo" alt="" referrerpolicy="no-referrer" @error="failed = true" />
    <span v-else>{{ initials }}</span>
  </span>
</template>
<style scoped>
.user-avatar { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex-shrink: 0; border-radius: 50%; overflow: hidden; background: #e1eeea; color: #0b3a38; font-size: 13px; font-weight: 700; }
.user-avatar img { width: 100%; height: 100%; object-fit: cover; }
</style>
