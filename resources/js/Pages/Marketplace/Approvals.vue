<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PromptModal from '@/Components/Aadi/Dialogs/PromptModal.vue';
import Layout from '@/Layouts/ProviderLayout.vue';
defineProps({applications:Array});
const titles={spaces:'Landlord / property merchant',haul:'Vehicle owner',raketeer:'Freelance service provider'};
const active=ref(null); const form=useForm({status:'approved',reviewNote:''});
function review(item,status){active.value=item;form.clearErrors();form.status=status;form.reviewNote='';}
function submit(){if(form.processing || !active.value)return; form.post(`/admin/approvals/${active.value.userId}/${active.value.service}`,{preserveScroll:true,onSuccess:()=>active.value=null});}
</script>
<template><Layout><Head title="Review provider applications"/><main id="provider-main" class="provider-onboarding"><p class="workspace-kicker">Administration</p><h1>Provider applications</h1><p>Review each role independently. Supporting documents are private.</p><div v-if="!applications.length" class="workspace-empty"><h2>All caught up.</h2><p>No provider applications are waiting for review.</p></div><article v-for="item in applications" :key="item.userId+item.service" class="application-review"><div class="role-title"><h2>{{ item.application?.displayName || item.name }}</h2><span class="availability-tag draft">{{ titles[item.service] }}</span></div><p>{{ item.name }} · {{ item.email }}</p><template v-if="item.application"><dl><dt>Phone</dt><dd>{{ item.application.phone }}</dd><dt>Primary area</dt><dd>{{ item.application.area }}</dd><dt>Offering</dt><dd>{{ item.application.description }}</dd></dl><div class="review-documents"><a v-for="doc in item.application.documents" :key="doc.url" :href="doc.url" class="document-link">Download {{ doc.name }} ↗</a></div></template><p v-else class="role-notice">This older request has no supporting details. The user must complete the application before approval.</p><div class="actions"><button v-if="item.application" class="button primary" @click="review(item,'approved')">Approve role</button><button class="button" @click="review(item,'rejected')">Request an update</button></div></article></main><PromptModal v-if="active" :open="true" v-model="form.reviewNote"
:title="form.status==='approved'?'Approve this provider role?':'Request an application update'"
:description="`${active.application?.displayName || active.name} · ${titles[active.service]}`"
label="Review note" :required="form.status==='rejected'" multiline :max-length="2000"
:hint="form.status==='approved'?'This approves only the selected provider role.':'Explain what the applicant needs to correct or provide.'"
confirm-label="Confirm decision" :busy="form.processing" :error="Object.values(form.errors).join(' ')"
@confirm="submit" @cancel="active=null" /></Layout></template>
