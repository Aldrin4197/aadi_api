<script setup>
import { computed } from 'vue';
const props=defineProps({ role:String, approved:{type:Boolean,default:false} });
const roles={
    user:{label:'Verified User',detail:'Email verified. This is not an identity-document check.',path:'M15.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0M5 21v-2a7 7 0 0 1 14 0v2'},
    spaces:{label:'Verified Landlord',detail:'Landlord role approved by an admin. Individual listings are not verified by this badge.',path:'m4 11 8-7 8 7M6 10v10h12V10M10 20v-6h4v6'},
    haul:{label:'Verified UV Owner',detail:'Vehicle-owner role approved by an admin. Individual vehicles are not verified by this badge.',path:'M3 7h12v10H3zM15 10h4l3 4v3h-7M6 17v3M18 17v3'},
    raketeer:{label:'Verified Raketeer',detail:'Service-provider role approved by an admin. Individual services are not verified by this badge.',path:'M4 8h16v12H4zM9 8V4h6v4M4 12l8 4 8-4'},
};
const badge=computed(()=>roles[props.role]);
</script>
<template><span v-if="approved && badge" class="aadi-role-badge" tabindex="0" role="img" :aria-label="`${badge.label}. ${badge.detail}`" :title="`${badge.label} — ${badge.detail}`"><svg class="role-symbol" viewBox="0 0 24 24" aria-hidden="true"><path :d="badge.path"/></svg><svg class="role-check" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="7"/><path d="m4.5 8 2.3 2.3 4.7-4.8"/></svg></span></template>
<style scoped>
.aadi-role-badge{display:inline-flex!important;align-items:center;justify-content:center;position:relative;vertical-align:middle;flex-shrink:0;width:34px!important;height:34px!important;border:1px solid #e6bb51!important;border-radius:50%!important;padding:0!important;margin:2px 3px 2px 0!important;background:#fff3bf!important;color:#79540a!important;cursor:help}.role-symbol{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linejoin:round;stroke-linecap:round}.role-check{position:absolute;right:-4px;bottom:-3px;width:14px;height:14px;overflow:visible}.role-check circle{fill:#f1c550;stroke:#fff;stroke-width:1.5}.role-check path{fill:none;stroke:#644600;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}.aadi-role-badge:focus-visible{outline:2px solid #137671;outline-offset:3px}
</style>
