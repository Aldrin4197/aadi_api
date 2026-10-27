<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AccountLayout from '@/Layouts/AccountLayout.vue';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
import { formatPrice } from '@/Components/Marketplace/comparison.mjs';
const props = defineProps({ inventory: { type: Array, default: () => [] }, recentListings: { type: Array, default: () => [] } });
const page = usePage();
const totals = computed(() => props.inventory.reduce((sum,row) => ({total:sum.total+row.total,published:sum.published+row.published,drafts:sum.drafts+row.drafts}), {total:0,published:0,drafts:0}));
const labels = {spaces:'Properties',haul:'Vehicles',raketeer:'Services & rakets'};
const statusLabels = {none:'Not applied',pending:'Under review',approved:'Approved',rejected:'Needs attention'};
</script>
<template><AccountLayout><Head title="My dashboard"/><main class="account-page">
    <AccountPageHeader :title="`Welcome, ${page.props.auth.user.name.split(' ')[0]}.`" description="Your listings, provider roles, and account in one place."><Link href="/my-listings" class="button primary">Manage listings</Link></AccountPageHeader>
    <dl class="account-stats"><div><dt>My listings</dt><dd>{{ totals.total }}</dd></div><div><dt>Published</dt><dd>{{ totals.published }}</dd></div><div><dt>Drafts</dt><dd>{{ totals.drafts }}</dd></div></dl>
    <div class="account-dashboard-grid"><section class="account-panel"><div class="account-panel-heading"><h2>Recent listings</h2><Link href="/my-listings">View all →</Link></div>
        <div v-if="recentListings.length" class="account-recent"><Link v-for="item in recentListings" :key="item.service+item.id" :href="`/offers/${item.service}/${item.id}`" class="account-recent-row"><div class="account-recent-photo"><ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service"/></div><div class="account-recent-copy"><strong>{{ item.title }}</strong><span>{{ labels[item.service] }} · {{ item.status === 'active' ? 'Published' : item.status }}</span></div><span>{{ formatPrice(item.price) }}<small> / {{ item.priceUnit }}</small></span></Link></div>
        <div v-else class="account-empty"><h3>Your next listing starts here.</h3><p>Apply for a provider role to offer a property, vehicle, or local service. Already approved? Add your first listing from My listings.</p><Link href="/become-provider" class="button">Manage provider roles</Link></div>
    </section><section class="account-panel"><div class="account-panel-heading"><h2>Provider roles</h2></div><div class="account-role-row" v-for="row in inventory" :key="row.service"><div><strong>{{ labels[row.service] }}</strong><span>{{ statusLabels[row.roleStatus] || row.roleStatus }}</span></div><Link :href="row.roleStatus === 'approved' ? `/my-listings/${row.service}/new` : `/provider-applications/${row.service}`">{{ row.roleStatus === 'approved' ? 'Add listing' : row.roleStatus === 'pending' ? 'View status' : 'View application' }} →</Link></div><p class="account-panel-note">Each provider role is reviewed separately.</p></section></div>
    <section class="account-panel account-settings-link"><div><h2>Keep your account up to date</h2><p>Manage your name, email, password, and account settings.</p></div><Link href="/profile" class="button">Profile & settings</Link></section>
</main></AccountLayout></template>
