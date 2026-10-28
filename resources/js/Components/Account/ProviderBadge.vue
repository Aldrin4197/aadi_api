<script setup>
import { computed } from 'vue';
const props=defineProps({ role:String, approved:{type:Boolean,default:false} });
const roles={spaces:{label:'Landlord',path:'m4 11 8-7 8 7M6 10v10h12V10M10 20v-6h4v6'},haul:{label:'Vehicle Owner',path:'M3 7h12v10H3zM15 10h4l3 4v3h-7M6 17v3M18 17v3'},raketeer:{label:'Raketeer',path:'M4 8h16v12H4zM9 8V4h6v4M4 12l8 4 8-4'}};
const badge=computed(()=>roles[props.role]);
</script>
<template><span v-if="approved && badge" class="aadi-role-badge" :title="`${badge.label}: provider role approved by an admin. Individual listings are not verified by this badge.`" :aria-label="`${badge.label}, approved provider role`"><svg class="role-symbol" viewBox="0 0 24 24" aria-hidden="true"><path :d="badge.path"/></svg><span>{{ badge.label }}</span><svg class="role-check" viewBox="0 0 16 16" aria-hidden="true"><path d="m8 1 5 2v4c0 3-5 7-5 7S3 10 3 7V3z"/><path d="m5.5 7 1.7 1.7 3.3-3.4"/></svg></span></template>
<style scoped>
.aadi-role-badge{display:inline-flex!important;align-items:center;gap:6px;vertical-align:middle;width:fit-content;white-space:nowrap;border:1px solid #b8dcd3;border-radius:6px;padding:5px 8px!important;background:#e9f5ef!important;color:#12675f!important;font:700 11px/1.2 Quicksand,sans-serif!important;letter-spacing:0!important}.aadi-role-badge>span{margin:0!important;padding:0!important;background:none!important;color:inherit!important;font:inherit!important}.role-symbol{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.6;stroke-linejoin:round;stroke-linecap:round}.role-check{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:1.3}
</style>
