<script setup>
defineProps({ title: String, description: String });
</script>
<template><header class="account-page-heading"><div><p class="account-page-kicker">Your Aadi workspace</p><h1>{{ title }}</h1><p v-if="description">{{ description }}</p></div><div v-if="$slots.default" class="account-page-actions"><slot /></div></header></template>
