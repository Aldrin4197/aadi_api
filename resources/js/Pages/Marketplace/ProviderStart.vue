<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import { accountIcons } from '@/Components/Account/accountLinks.mjs';
import Layout from '@/Layouts/ProviderLayout.vue';
const props=defineProps({statuses:Object,applications:{type:Object,default:()=>({})}});
const unverified=usePage().props.auth?.user?.emailVerified===false;
const roles = [
  {service:'spaces',title:'Aadi Properties',description:'Offer rooms, apartments, houses, commercial spaces, or bedspaces.',action:'List a property',icon:accountIcons.spaces},
  {service:'haul',title:'Aadi UV',description:'Offer vehicles for rent, from multicabs and vans to trucks.',action:'List a vehicle',icon:accountIcons.haul},
  {service:'raketeer',title:'Aadi Raket',description:'Offer your skills and local services through Aadi Raketeer.',action:'Offer a service',icon:accountIcons.raketeer},
];
const statusLabel = status => ({draft:'Draft saved',none:'Not applied',pending:'Under review',approved:'Approved',rejected:'Needs an update'}[status]);
const roleStatus = service => props.applications[service]?.status === 'draft' ? 'draft' : props.statuses[service];
const roleAction = role => props.statuses[role.service] === 'approved'
  ? role.action
  : props.statuses[role.service] === 'pending'
    ? 'View application'
    : props.statuses[role.service] === 'rejected'
      ? 'Update application'
      : 'Apply for this role';
</script>
<template>
    <Layout>
        <Head title="Become an Aadi provider" />
        <main id="provider-main" class="account-page provider-onboarding">
            <AccountPageHeader
                title="Provider roles"
                description="Apply to list a property, rent out a vehicle, or offer a local service. You can apply for more than one role and keep using Aadi to browse."
            />
            <ol class="application-steps" aria-label="How provider applications work">
                <li><span>1</span>Choose a role</li>
                <li><span>2</span>Submit details and documents</li>
                <li><span>3</span>Get reviewed, then list</li>
            </ol>
            <div v-if="unverified" class="role-feedback verify-first" role="status">
                <strong>Verify your email first.</strong>
                <span>You can browse the requirements, but you can only submit an application once your email is confirmed.</span>
            </div>
            <section class="role-options" aria-label="Provider roles">
                <article v-for="role in roles" :key="role.service" class="role-option">
                    <svg class="provider-role-icon" viewBox="0 0 24 24" aria-hidden="true"><path :d="role.icon" /></svg>
                    <div class="provider-role-main">
                        <div class="role-title">
                            <h2>{{ role.title }}</h2>
                            <span class="account-status" :class="`is-${roleStatus(role.service)}`">
                                {{ statusLabel(roleStatus(role.service)) }}
                            </span>
                        </div>
                        <p>{{ role.description }}</p>
                        <p v-if="props.applications[role.service]?.reviewNote" class="role-feedback">
                            <strong>Admin feedback:</strong> {{ props.applications[role.service].reviewNote }}
                        </p>
                    </div>
                    <div class="provider-role-actions">
                        <Link
                            :href="props.statuses[role.service] === 'approved' ? `/my-listings/${role.service}/new` : `/provider-applications/${role.service}`"
                            class="button"
                            :class="{ primary: props.statuses[role.service] === 'approved' }"
                        >
                            {{ roleAction(role) }} <span aria-hidden="true">→</span>
                        </Link>
                        <Link v-if="props.statuses[role.service] === 'approved'" :href="`/provider-applications/${role.service}`" class="text-button">
                            View verification
                        </Link>
                    </div>
                </article>
            </section>
            <aside class="provider-explanation">
                <strong>Your account stays yours.</strong>
                <p>Provider approval adds listing permissions for the selected role. It does not replace your User account, and approval for one role does not automatically approve another.</p>
                <Link href="/" class="text-button">Continue exploring Aadi →</Link>
            </aside>
        </main>
    </Layout>
</template>

<style scoped>
.verify-first { display:grid; gap:4px; margin:0 0 16px; }
.role-feedback { padding:12px; background:#f7f2e7; border-left:2px solid #b48b44; color:#6f572c!important; overflow-wrap:anywhere; }
</style>
