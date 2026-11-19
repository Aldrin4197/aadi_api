<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AccountLayout from '@/Layouts/AccountLayout.vue';
import AccountPageHeader from '@/Components/Account/AccountPageHeader.vue';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
import { formatPrice } from '@/Components/Marketplace/comparison.mjs';
import { accountIcons } from '@/Components/Account/accountLinks.mjs';

const props = defineProps({
    inventory: { type: Array, default: () => [] },
    recentListings: { type: Array, default: () => [] },
    crmCurrent: { type: Number, default: 0 },
});

const page = usePage();
const activeService = ref('all');
const labels = { spaces: 'Properties', haul: 'Vehicles', raketeer: 'Services & rakets' };
const roleIcons = { spaces: accountIcons.spaces, haul: accountIcons.haul, raketeer: accountIcons.raketeer };
const statusLabels = { none: 'Not applied', pending: 'Under review', approved: 'Approved', rejected: 'Needs attention' };
const totals = computed(() => props.inventory.reduce((sum, row) => ({
    total: sum.total + row.total,
    published: sum.published + row.published,
    drafts: sum.drafts + row.drafts,
}), { total: 0, published: 0, drafts: 0 }));
const stats = computed(() => [
    { label: 'My listings', value: totals.value.total, icon: accountIcons.listings },
    { label: 'Published', value: totals.value.published, icon: accountIcons.published },
    { label: 'Drafts', value: totals.value.drafts, icon: accountIcons.drafts },
]);
const serviceTabs = computed(() => [
    { key: 'all', label: 'All listings', count: totals.value.total },
    ...props.inventory.map(row => ({
        key: row.service,
        label: labels[row.service] || row.service,
        count: row.total,
    })),
]);
const visibleRecentListings = computed(() => activeService.value === 'all'
    ? props.recentListings
    : props.recentListings.filter(item => item.service === activeService.value));

function roleUrl(row) {
    return row.roleStatus === 'approved'
        ? `/my-listings/${row.service}/new`
        : `/provider-applications/${row.service}`;
}

function roleAction(row) {
    return ({
        approved: 'Add listing',
        pending: 'View status',
        rejected: 'Update application',
        none: 'Apply',
    })[row.roleStatus] || 'View application';
}
</script>

<template>
    <AccountLayout>
        <Head title="My dashboard" />
        <main class="account-page account-dashboard">
            <AccountPageHeader
                :title="`Welcome, ${page.props.auth.user.name.split(' ')[0]}.`"
                description="Your listings, provider roles, and account in one place."
            >
                <Link href="/my-listings" class="button primary">Manage listings <span aria-hidden="true">→</span></Link>
            </AccountPageHeader>

            <dl class="dashboard-stats" aria-label="Listing totals">
                <div v-for="stat in stats" :key="stat.label" class="dashboard-stat">
                    <dt>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="stat.icon" /></svg>
                        {{ stat.label }}
                    </dt>
                    <dd>{{ stat.value }}</dd>
                </div>
            </dl>
            <section class="dashboard-section">
                <header class="dashboard-section-heading"><h2>Rentals and jobs</h2><Link href="/provider/records" class="text-button">Open provider workspace →</Link></header>
                <p>{{ crmCurrent }} current tenancies, rentals and jobs. Track customers, schedules and provider-recorded payments in your workspace.</p>
            </section>

            <section class="dashboard-section" aria-labelledby="recent-listings-title">
                <header class="dashboard-section-heading">
                    <h2 id="recent-listings-title">Recent listings</h2>
                    <Link href="/my-listings" class="text-button">View all listings →</Link>
                </header>

                <nav class="workspace-tabs dashboard-tabs" aria-label="Filter recent listings by category">
                    <button
                        v-for="tab in serviceTabs"
                        :key="tab.key"
                        type="button"
                        :aria-pressed="activeService === tab.key"
                        @click="activeService = tab.key"
                    >
                        {{ tab.label }} <span>{{ tab.count }}</span>
                    </button>
                </nav>

                <div v-if="visibleRecentListings.length" class="dashboard-recent-list">
                    <Link
                        v-for="item in visibleRecentListings"
                        :key="item.service + item.id"
                        :href="`/offers/${item.service}/${item.id}`"
                        class="dashboard-recent-row"
                    >
                        <div class="account-recent-photo">
                            <ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" />
                        </div>
                        <div class="dashboard-recent-copy">
                            <strong>{{ item.title }}</strong>
                            <span>{{ labels[item.service] }}</span>
                        </div>
                        <div class="dashboard-recent-detail">
                            <span class="dashboard-status" :class="{ 'is-published': item.status === 'active' }">
                                {{ item.status === 'active' ? 'Published' : item.status }}
                            </span>
                            <span class="dashboard-recent-price">
                                {{ formatPrice(item.price) }} <small>/ {{ item.priceUnit }}</small>
                            </span>
                        </div>
                    </Link>
                </div>
                <div v-else class="dashboard-empty">
                    <h3>{{ totals.total ? 'No recent listings in this category.' : 'Your next listing starts here.' }}</h3>
                    <p v-if="totals.total">There are no recent listings in this category. Browse all your listings to see the rest.</p>
                    <p v-else>Apply for a provider role to offer a property, vehicle, or local service. Already approved? Add your first listing from My listings.</p>
                    <Link v-if="totals.total" href="/my-listings" class="text-button">View all listings →</Link>
                    <Link v-else href="/become-provider" class="button">Manage provider roles</Link>
                </div>
            </section>

            <div class="dashboard-lower">
                <section class="dashboard-section" aria-labelledby="provider-roles-title">
                    <header class="dashboard-section-heading">
                        <h2 id="provider-roles-title">Provider roles</h2>
                        <Link href="/become-provider" class="text-button">Manage roles →</Link>
                    </header>
                    <div v-for="row in inventory" :key="row.service" class="dashboard-role-row">
                        <div class="dashboard-role-identity">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="roleIcons[row.service]" /></svg>
                            <div>
                                <strong>{{ labels[row.service] }}</strong>
                                <span class="dashboard-status" :class="`is-${row.roleStatus}`">
                                    {{ statusLabels[row.roleStatus] || row.roleStatus }}
                                </span>
                            </div>
                        </div>
                        <Link :href="roleUrl(row)" class="button" :class="{ primary: row.roleStatus === 'approved' }">
                            {{ roleAction(row) }} <span aria-hidden="true">→</span>
                        </Link>
                    </div>
                    <p class="dashboard-note">Each provider role is reviewed separately.</p>
                </section>

                <section class="dashboard-settings" aria-labelledby="account-settings-title">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path :d="accountIcons.settings" /></svg>
                    <div>
                        <h2 id="account-settings-title">Account settings</h2>
                        <p>Manage your name, email, password, and account preferences.</p>
                        <Link href="/settings" class="button">Open settings <span aria-hidden="true">→</span></Link>
                    </div>
                </section>
            </div>
        </main>
    </AccountLayout>
</template>

<style scoped>
.account-dashboard {
    max-width: 1440px;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin: 0 0 32px;
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
}

.dashboard-stat {
    min-width: 0;
    padding: 18px 24px;
}

.dashboard-stat + .dashboard-stat {
    border-left: 1px solid var(--line);
}

.dashboard-stat dt {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--muted);
    font-size: var(--aadi-text-small);
    font-weight: 600;
}

.dashboard-stat svg,
.dashboard-role-identity > svg,
.dashboard-settings > svg {
    width: 20px;
    height: 20px;
    flex: none;
    fill: none;
    stroke: var(--teal);
    stroke-width: 1.6;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.dashboard-stat dd {
    margin: 8px 0 0 29px;
    color: var(--ink);
    font-size: 30px;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    line-height: 1.1;
}

.dashboard-section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 8px;
}

.dashboard-section h2,
.dashboard-settings h2 {
    margin: 0;
    font-size: var(--aadi-text-heading);
    font-weight: 700;
    line-height: 1.3;
}

.dashboard-section-heading .text-button {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    font-weight: 600;
    text-decoration: none;
}

.dashboard-tabs {
    margin-bottom: 4px;
}

.dashboard-recent-row {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr) auto;
    align-items: center;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid var(--line);
    color: var(--ink);
    text-decoration: none;
}

.dashboard-recent-row:first-child {
    border-top: 1px solid var(--line);
}

.dashboard-recent-row .account-recent-photo {
    width: 64px;
    height: 60px;
    margin: 0;
    border-radius: 8px;
}

.dashboard-recent-copy {
    display: grid;
    min-width: 0;
    gap: 5px;
}

.dashboard-recent-copy strong {
    overflow-wrap: anywhere;
    font-size: var(--aadi-text-body);
}

.dashboard-recent-copy > span {
    color: var(--muted);
    font-size: var(--aadi-text-small);
}

.dashboard-recent-detail {
    display: grid;
    justify-items: end;
    gap: 6px;
    text-align: right;
}

.dashboard-status {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    min-height: 24px;
    padding: 3px 8px;
    border-radius: 5px;
    background: #edf1ed;
    color: #5f7068;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.4;
}

.dashboard-status.is-published,
.dashboard-status.is-approved {
    background: #e1f2e7;
    color: #23694b;
}

.dashboard-status.is-pending {
    background: #faf0dc;
    color: #86652a;
}

.dashboard-status.is-rejected {
    background: #f9e9e6;
    color: #9e342b;
}

.dashboard-recent-price {
    font-size: var(--aadi-text-body);
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    white-space: nowrap;
}

.dashboard-recent-price small {
    color: var(--muted);
    font-size: var(--aadi-text-small);
    font-weight: 400;
}

.dashboard-empty {
    padding: 24px 0;
    border-top: 1px solid var(--line);
}

.dashboard-empty h3 {
    margin: 0;
    font-size: var(--aadi-text-subheading);
    font-weight: 700;
}

.dashboard-empty p,
.dashboard-note,
.dashboard-settings p {
    color: var(--muted);
    font-size: var(--aadi-text-body);
    line-height: 1.6;
}

.dashboard-empty p {
    max-width: 58ch;
    margin: 7px 0 14px;
}

.dashboard-lower {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(260px, .65fr);
    gap: 32px;
    margin-top: 36px;
    padding-top: 24px;
    border-top: 1px solid var(--line);
}

.dashboard-role-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 13px 0;
    border-top: 1px solid var(--line);
}

.dashboard-role-identity {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 12px;
}

.dashboard-role-identity > div {
    display: grid;
    justify-items: start;
    gap: 5px;
}

.dashboard-role-identity strong {
    font-size: var(--aadi-text-body);
}

.dashboard-note {
    margin: 12px 0 0;
    font-size: var(--aadi-text-small);
}

.dashboard-settings {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 4px 0 0 24px;
    border-left: 1px solid var(--line);
}

.dashboard-settings p {
    margin: 8px 0 14px;
}

.account-dashboard :is(a, button):focus-visible {
    outline: 2px solid var(--teal);
    outline-offset: 3px;
}

@media (max-width: 900px) {
    .dashboard-lower {
        grid-template-columns: minmax(0, 1fr);
        gap: 24px;
    }

    .dashboard-settings {
        padding: 22px 0 0;
        border-top: 1px solid var(--line);
        border-left: 0;
    }
}

@media (max-width: 600px) {
    .dashboard-stats {
        margin-bottom: 24px;
    }

    .dashboard-stat {
        padding: 14px 10px;
    }

    .dashboard-stat dt {
        align-items: flex-start;
        flex-direction: column;
        gap: 6px;
        font-size: 11px;
    }

    .dashboard-stat dd {
        margin: 8px 0 0;
        font-size: 25px;
    }

    .dashboard-section-heading {
        align-items: flex-start;
    }

    .dashboard-section-heading .text-button {
        font-size: 12px;
        text-align: right;
    }

    .dashboard-recent-row {
        grid-template-columns: 54px minmax(0, 1fr);
        gap: 10px;
    }

    .dashboard-recent-row .account-recent-photo {
        width: 54px;
        height: 54px;
    }

    .dashboard-recent-detail {
        grid-column: 2;
        grid-template-columns: auto 1fr;
        align-items: center;
        justify-items: start;
        gap: 8px;
        text-align: left;
    }

    .dashboard-recent-price {
        font-size: var(--aadi-text-small);
    }

    .dashboard-role-row {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
