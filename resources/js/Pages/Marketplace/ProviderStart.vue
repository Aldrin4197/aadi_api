<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/ProviderLayout.vue';
defineProps({statuses:Object,applications:{type:Object,default:()=>({})}});
const roles = [
  {service:'spaces',number:'01',title:'Aadi Properties',description:'Offer rooms, apartments, houses, commercial spaces, or bedspaces.',action:'List a property'},
  {service:'haul',number:'02',title:'Aadi UV',description:'Offer vehicles for rent, from multicabs and vans to trucks.',action:'List a vehicle'},
  {service:'raketeer',number:'03',title:'Aadi Raket',description:'Offer your skills and local services through Aadi Raketeer.',action:'Offer a service'},
];
const statusLabel = status => ({draft:'Draft saved',none:'Not applied',pending:'Under review',approved:'Approved',rejected:'Needs an update'}[status]);
</script>
<template><Layout><Head title="Become an Aadi provider"/><main id="provider-main" class="provider-onboarding"><div class="onboarding-intro"><p class="workspace-kicker">One account. More ways to take part.</p><h1>Bring what you offer<br/>to your neighborhood.</h1><p>You already have a User account. Apply for a provider role to list a property, rent out a vehicle, or offer a freelance service. You can hold more than one role and keep using Aadi to browse.</p><div class="application-steps"><span><b>1</b> Choose a role</span><span><b>2</b> Submit details & documents</span><span><b>3</b> Get reviewed, then list</span></div></div>
<div class="role-options"><article v-for="role in roles" :key="role.service" class="role-option"><span class="role-number">{{ role.number }}</span><div><div class="role-title"><h2>{{ role.title }}</h2><span class="availability-tag" :class="statuses[role.service]==='approved'?'active':'draft'">{{ statusLabel(applications[role.service]?.status==='draft'?'draft':statuses[role.service]) }}</span></div><p>{{ role.description }}</p><p v-if="applications[role.service]?.reviewNote" class="role-feedback"><strong>Admin feedback:</strong> {{ applications[role.service].reviewNote }}</p></div><Link :href="statuses[role.service]==='approved'?`/my-listings/${role.service}/new`:`/provider-applications/${role.service}`" class="button" :class="{primary:statuses[role.service]==='approved'}">{{ statuses[role.service]==='approved'?role.action:statuses[role.service]==='pending'?'View application':statuses[role.service]==='rejected'?'Update application':'Apply for this role' }} →</Link><Link v-if="statuses[role.service]==='approved'" :href="`/provider-applications/${role.service}`" class="text-button">View verification application →</Link></article></div>
<div class="provider-explanation"><strong>Your account stays yours.</strong><p>Provider approval adds listing permissions for the selected role. It does not replace your User account, and approval for one role does not automatically approve another.</p><Link href="/" class="text-button">Continue exploring Aadi →</Link></div></main></Layout></template>

<style scoped>
.role-feedback{padding:12px;background:#fff3bf;border-radius:8px;color:#79540a!important;overflow-wrap:anywhere}.role-option>.text-button{grid-column:2/-1;min-height:44px;display:flex;align-items:center}@media(max-width:600px){.role-option>.text-button{grid-column:1/-1}}
</style>
