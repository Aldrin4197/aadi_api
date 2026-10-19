<script setup>
import {Head,Link} from '@inertiajs/vue3';
import Layout from '@/Layouts/MarketplaceLayout.vue';
defineProps({users:Array});const fields={spaces:'landlord_status',haul:'vehicle_owner_status',raketeer:'service_provider_status'};
</script>
<template><Layout><Head title="Provider approvals"/><main class="content"><p class="eyebrow">ADMINISTRATION</p><h1>Provider applications.</h1><p>Review applicants before allowing them to publish.</p><Link href="/admin/dashboard" class="text-button">Open admin dashboard →</Link><div v-if="!users.length" class="empty-state">No applications waiting for review.</div><div v-for="user in users" :key="user.id" class="provider-card"><h2>{{ user.name }}</h2><p>{{ user.email }}</p><template v-for="(field,service) in fields"><div v-if="user[field]==='pending'" class="actions"><strong>{{ service }}</strong><Link :href="`/admin/approvals/${user.id}/${service}`" method="post" :data="{status:'approved'}" as="button" class="button primary">Approve</Link><Link :href="`/admin/approvals/${user.id}/${service}`" method="post" :data="{status:'rejected'}" as="button" class="button">Reject</Link></div></template></div></main></Layout></template>
