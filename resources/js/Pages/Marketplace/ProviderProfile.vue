<script setup>
import ReportAccount from '@/Components/Account/ReportAccount.vue';
import ProfileLikes from '@/Components/Account/ProfileLikes.vue';
import ListingViewButton from '@/Components/Marketplace/ListingViewButton.vue';
import ProviderBadge from '@/Components/Account/ProviderBadge.vue';
import AadiPlusBadge from '@/Components/Badges/AadiPlusBadge.vue';
import ListingBadges from '@/Components/Marketplace/ListingBadges.vue';
import { Head, Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/MarketplaceLayout.vue';
import UserAvatar from '@/Components/Account/UserAvatar.vue';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';

defineProps({ provider: Object, counts: Object, service: String, results: Object, profileLikes: Object });

const labels = { spaces: 'Properties', haul: 'Vehicles', raketeer: 'Rakets' };
const money = item => new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: item.currency || 'PHP',
}).format(item.price);
</script>

<template>
    <Layout>
        <Head :title="`${provider.name} - Profile`" />
        <main class="content provider-profile">
            <Link href="/explore" class="profile-back">← Explore listings</Link>

            <section class="provider-overview" aria-labelledby="provider-name">
                <header class="provider-header">
                    <UserAvatar :user="provider" />
                    <div class="provider-identity">
                        <p class="eyebrow">PUBLIC PROFILE</p>
                        <h1 id="provider-name">{{ provider.name }}</h1>
                        <p class="provider-meta">
                            {{ provider.city || 'Aadi community' }}
                            <span v-if="provider.memberSince"> · Member since {{ provider.memberSince }}</span>
                        </p>
                        <div class="provider-roles" aria-label="Provider badges">
                            <ProviderBadge role="user" :approved="provider.emailVerified" />
                            <ProviderBadge v-for="role in provider.approvedRoles" :key="role" :role="role" approved />
                            <AadiPlusBadge :active="provider.aadiPlus" variant="icon" size="sm" />
                        </div>
                    </div>
                </header>

                <ProfileLikes :user-id="provider.id" :likes="profileLikes">
                    <ReportAccount :user-id="provider.id" :name="provider.name" show-guest profile-action />
                </ProfileLikes>
            </section>

            <section class="provider-about" aria-labelledby="provider-about-title">
                <h2 id="provider-about-title">About</h2>
                <p>{{ provider.bio || 'This user has not added a bio yet.' }}</p>
                <small v-if="provider.approvedRoles.length">
                    Badges indicate admin approval of the provider role, not verification of individual listings.
                </small>
            </section>

            <section class="provider-listings" aria-labelledby="provider-listings-title">
                <h2 id="provider-listings-title">Listings</h2>
                <nav class="provider-tabs" aria-label="Listing categories">
                    <Link
                        v-for="(label, key) in labels"
                        :key="key"
                        :href="`/providers/${provider.id}?service=${key}`"
                        :aria-current="service === key ? 'page' : undefined"
                        preserve-scroll
                    >
                        {{ label }} <span>({{ counts[key] }})</span>
                    </Link>
                </nav>

                <p v-if="!results.data.length" class="provider-empty">
                    No listings in this category.
                </p>
                <div v-else class="provider-grid">
                    <article v-for="item in results.data" :key="item.id" class="provider-listing">
                        <div class="provider-photo">
                            <ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" :type="item.type" />
                        </div>
                        <div class="provider-listing-content">
                            <h3>{{ item.title }}</h3>
                            <ListingBadges :badges="item.badges" />
                            <p v-if="item.availabilityPaused" class="provider-listing-status">Currently unavailable</p>
                            <p class="provider-listing-price">
                                <strong>{{ money(item) }}</strong>
                                <span>/ {{ item.priceUnit }}</span>
                            </p>
                            <ListingViewButton :item="item">View listing</ListingViewButton>
                        </div>
                    </article>
                </div>

                <nav v-if="results.last_page > 1" class="provider-pagination" aria-label="Listing pages">
                    <Link v-if="results.prev_page_url" :href="results.prev_page_url">Previous</Link>
                    <span>Page {{ results.current_page }} of {{ results.last_page }}</span>
                    <Link v-if="results.next_page_url" :href="results.next_page_url">Next</Link>
                </nav>
            </section>
        </main>
    </Layout>
</template>

<style scoped>
.provider-profile {
    max-width: 1280px;
    padding: 32px clamp(20px, 4vw, 48px) 72px;
}

.profile-back {
    color: var(--teal);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.provider-overview {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 24px;
    padding: 28px 0 20px;
    border-bottom: 1px solid var(--line);
}

.provider-header {
    display: grid;
    grid-template-columns: 80px minmax(0, 1fr);
    align-items: center;
    gap: 20px;
    min-width: 0;
}

.provider-header :deep(.user-avatar) {
    width: 80px;
    height: 80px;
    font-size: 25px;
}

.provider-identity {
    min-width: 0;
}

.provider-identity .eyebrow {
    margin: 0 0 5px;
}

.provider-header h1 {
    margin: 0;
    font-size: var(--aadi-text-page-title);
    font-weight: 700;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.provider-meta {
    margin: 7px 0 0;
    color: var(--muted);
    font-size: var(--aadi-text-body);
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.provider-roles {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
    margin-top: 11px;
}

.provider-overview :deep(.profile-likes) {
    margin: 0;
}

.provider-overview :deep(.like-actions) {
    gap: 8px;
}

.provider-about {
    max-width: 75ch;
    padding: 22px 0 26px;
    border-bottom: 1px solid var(--line);
}

.provider-profile h2 {
    margin: 0;
    font-size: var(--aadi-text-heading);
    font-weight: 700;
    line-height: 1.3;
}

.provider-about p {
    margin: 9px 0 0;
    color: var(--ink);
    line-height: 1.65;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.provider-about small {
    display: block;
    margin-top: 12px;
    color: var(--muted);
    font-size: var(--aadi-text-small);
    line-height: 1.5;
}

.provider-listings {
    padding-top: 24px;
}

.provider-tabs {
    display: flex;
    gap: 18px;
    margin: 12px 0 20px;
    overflow-x: auto;
    border-bottom: 1px solid var(--line);
    scrollbar-width: thin;
}

.provider-tabs a {
    display: inline-flex;
    flex: none;
    align-items: center;
    gap: 5px;
    min-height: 44px;
    padding: 10px 2px 9px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: var(--muted);
    font-size: var(--aadi-text-body);
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
}

.provider-tabs a span {
    color: inherit;
    font-size: var(--aadi-text-small);
    font-weight: 500;
}

.provider-tabs a[aria-current="page"] {
    border-bottom-color: var(--teal);
    color: var(--teal);
}

.provider-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 290px), 1fr));
    align-items: stretch;
    gap: 20px;
}

.provider-listing {
    display: grid;
    grid-template-rows: auto 1fr;
    min-width: 0;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
}

.provider-photo {
    aspect-ratio: 1.58;
    min-width: 0;
    overflow: hidden;
    background: #e8eee6;
}

.provider-listing-content {
    display: flex;
    min-width: 0;
    flex-direction: column;
    align-items: flex-start;
    padding: 15px 16px 16px;
}

.provider-listing-content h3 {
    margin: 0;
    font-size: var(--aadi-text-body);
    font-weight: 700;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.provider-listing-content :deep(.listing-badges) {
    margin-top: 7px;
}

.provider-listing-status {
    margin: 8px 0 0;
    color: #875522;
    font-size: var(--aadi-text-small);
    line-height: 1.5;
}

.provider-listing-price {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 5px;
    margin: 12px 0 0;
    color: var(--muted);
    font-size: var(--aadi-text-small);
}

.provider-listing-price strong {
    color: var(--ink);
    font-size: var(--aadi-text-body);
    font-variant-numeric: tabular-nums;
    font-weight: 700;
}

.provider-listing-content :deep(.listing-view-button) {
    margin-top: 14px;
    border-radius: 8px !important;
}

.provider-empty {
    margin: 0;
    padding: 22px 0;
    color: var(--muted);
    border-top: 1px solid var(--line);
    line-height: 1.6;
}

.provider-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    margin-top: 24px;
    color: var(--muted);
    font-size: var(--aadi-text-small);
}

.provider-pagination a {
    color: var(--teal);
    font-weight: 600;
    text-decoration: none;
}

.provider-profile :is(a, button):focus-visible {
    outline: 2px solid var(--teal);
    outline-offset: 3px;
}

@media (max-width: 760px) {
    .provider-overview {
        grid-template-columns: minmax(0, 1fr);
        justify-items: start;
        gap: 17px;
        padding-top: 24px;
    }

    .provider-header {
        grid-template-columns: 68px minmax(0, 1fr);
        gap: 16px;
    }

    .provider-header :deep(.user-avatar) {
        width: 68px;
        height: 68px;
        font-size: 21px;
    }
}

@media (max-width: 520px) {
    .provider-profile {
        padding-top: 24px;
        padding-bottom: 48px;
    }

    .provider-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 16px;
    }

    .provider-tabs {
        gap: 16px;
    }
}
</style>
