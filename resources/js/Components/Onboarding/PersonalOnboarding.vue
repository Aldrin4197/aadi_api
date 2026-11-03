<script setup>
import {computed,ref,watch} from 'vue';
import {useForm,usePage} from '@inertiajs/vue3';
import GettingStartedModal from './GettingStartedModal.vue';
const page=usePage(),open=ref(false),step=ref(0);
const defaults=()=>({action:'save',personas:[],interests:[],city:'',address:'',budget_min:'',budget_max:'',budget_unit:'month'});
const form=useForm(defaults());
const personas={student:'Student',employee:'Employed professional',business_owner:'Business owner',freelancer:'Freelancer / self-employed',other:'Other / prefer not to say'};
const interests={spaces:'A place to stay or do business',haul:'A vehicle to rent',raketeer:'Local skills and services'};
const titles=['Tell us who you are','What brings you to Aadi?','Where and what budget?','Make yourself at home'];
const accountArea=computed(()=>['/profile','/dashboard'].includes(page.url.split('?')[0]));
function start(){Object.assign(form,defaults(),page.props.auth?.onboarding?.data||{});form.clearErrors();step.value=0;open.value=true;}
watch(()=>[page.props.auth?.user?.id,page.props.auth?.onboarding?.status,page.component],()=>{if(!page.props.auth?.user){open.value=false;return;}if(page.props.auth.onboarding?.status==='pending'&&!page.props.auth.user.is_admin&&!page.component?.includes('Provider')){if(!open.value)start();}},{immediate:true});
function save(action='save'){if(form.processing)return;form.action=action;form.put('/account/onboarding',{preserveScroll:true,onSuccess:()=>{open.value=false;},onError:()=>{step.value=2;}});}
function next(){if(step.value<3)step.value++;else save();}
</script>
<template>
<button v-if="page.props.auth?.user&&!page.props.auth.user.is_admin&&accountArea&&!open" type="button" class="getting-resume" @click="start">{{ page.props.auth.onboarding?.status==='completed'?'Your preferences':'Getting started' }}</button>
<GettingStartedModal :open="open" :title="titles[step]" :steps="['You','Interests','Preferences','Review']" :step="step" :busy="form.processing" @close="save('skip')">
<form @submit.prevent="next">
<div v-if="Object.keys(form.errors).length" class="getting-errors" role="alert"><div v-for="(error,key) in form.errors" :key="key">{{ error }}</div></div>
<template v-if="step===0"><p>Hi {{ page.props.auth?.user?.name }}. Help us understand how you’ll use Aadi. Choose any that fit—you can leave these blank.</p><div class="getting-grid"><label v-for="(label,key) in personas" :key="key" class="getting-check"><input type="checkbox" v-model="form.personas" :value="key"/>{{ label }}</label></div><p>These answers are private preferences. They do not grant or verify a provider role.</p></template>
<template v-if="step===1"><p>What are you looking for? You can choose more than one.</p><label v-for="(label,key) in interests" :key="key" class="getting-check"><input type="checkbox" v-model="form.interests" :value="key"/>{{ label }}</label><p>Want to offer something instead? You can apply as a Landlord, UV Owner, or Raketeer after setup.</p></template>
<template v-if="step===2"><p>All fields are optional. Save a preferred area and a budget to refer to later. These won’t change your current search filters.</p><label>Preferred city / municipality<input v-model="form.city" autocomplete="address-level2" maxlength="120" placeholder="e.g. Catbalogan City"/></label><label>Address or neighborhood (private, optional)<input v-model="form.address" autocomplete="street-address" maxlength="255" placeholder="Barangay or neighborhood is enough"/></label><div class="getting-grid"><label>Minimum budget (PHP)<input v-model="form.budget_min" inputmode="decimal" type="number" min="0" max="99999999" step="0.01" placeholder="No minimum"/></label><label>Maximum budget (PHP)<input v-model="form.budget_max" inputmode="decimal" type="number" :min="form.budget_min||0" max="99999999" step="0.01" placeholder="No maximum"/></label></div><label>Budget is per<select v-model="form.budget_unit"><option value="month">Month</option><option value="day">Day</option><option value="hour">Hour</option><option value="project">Job / project</option></select></label><p>Your budget and address are not shown on your public profile.</p></template>
<template v-if="step===3"><p>Ready to explore. You can change these answers from your profile at any time.</p><dl><dt>About you</dt><dd>{{ form.personas.map(key=>personas[key]).join(', ')||'Not specified' }}</dd><dt>Looking for</dt><dd>{{ form.interests.map(key=>interests[key]).join(', ')||'Just exploring' }}</dd><dt>Preferred area</dt><dd>{{ form.city||'Not specified' }}</dd><dt>Private address</dt><dd>{{ form.address||'Not provided' }}</dd><dt>Budget per {{ form.budget_unit }}</dt><dd>PHP {{ form.budget_min === '' || form.budget_min == null ? '0' : form.budget_min }} – {{ form.budget_max === '' || form.budget_max == null ? 'No maximum' : form.budget_max }}</dd></dl><p class="getting-note">Provider approval is a separate step. After saving, choose “Become a provider” to get started.</p></template>
<div class="getting-actions"><button type="button" :disabled="form.processing" @click="save('skip')">Do this later</button><button v-if="step>0" type="button" :disabled="form.processing" @click="step--">Back</button><button class="getting-primary" :disabled="form.processing">{{ form.processing?'Saving…':step===3?'Save preferences':'Continue' }}</button></div>
</form></GettingStartedModal>
</template>
<style scoped>.getting-resume{position:fixed;right:20px;bottom:calc(20px + env(safe-area-inset-bottom,0px));z-index:40;min-height:44px;padding:10px 16px;border:1px solid #c4ecea;border-radius:24px;background:#fff;color:#137671;box-shadow:0 4px 18px #0628271a;font-size:13px;font-weight:700}.getting-resume:focus-visible{outline:3px solid #e5b329;outline-offset:3px}</style>
