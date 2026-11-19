<script setup>
import { computed, nextTick, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Layout from '@/Layouts/MarketplaceLayout.vue';
import DiscoveryMap from '@/Components/Marketplace/DiscoveryMap.vue';
import ListingDirections from '@/Components/Marketplace/ListingDirections.vue';
import ListingGallery from '@/Components/Marketplace/ListingGallery.vue';
import ListingMessage from '@/Components/Marketplace/ListingMessage.vue';
import ListingOwner from '@/Components/Marketplace/ListingOwner.vue';
import ListingRequest from '@/Components/Marketplace/ListingRequest.vue';
import ListingReviews from '@/Components/Marketplace/ListingReviews.vue';
import ListingBadges from '@/Components/Marketplace/ListingBadges.vue';

const props = defineProps({
    listing: Object,
    engagement: Object,
    timezone: String,
    unavailableDates: { type: Array, default: () => [] },
});
const page = usePage();
const save = useForm({});
const user = computed(() => page.props.auth?.user);
const reviewFormOpen = ref(false);
const directionsOpen = ref(false);
const directions = ref(null);
const directionsRoute = ref(null);
const directionsOrigin = ref(null);
const pickingOrigin = ref(false);
const listingDetails = computed(() => Object.entries(props.listing.details || {})
    .filter(([, value]) => Boolean(value))
    .map(([label, value]) => ({ label, value })));
const canMessage = computed(() => user.value && user.value.id !== props.listing.ownerId
    && props.listing.status === 'active' && !props.listing.isSample && !props.listing.availabilityPaused);
const canReview = computed(() => user.value && user.value.id !== props.listing.ownerId
    && props.listing.status === 'active');
const listingState = computed(() => {
    if (props.listing.availabilityPaused) {
        return props.listing.service === 'spaces' ? 'Occupied / unavailable' : 'Unavailable';
    }

    const status = props.listing.status || 'Unknown';
    return status.charAt(0).toUpperCase() + status.slice(1);
});
const money = amount => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);

async function showReviewForm() {
    reviewFormOpen.value = true;
    await nextTick();

    document.getElementById('write-review')?.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'center',
    });
}
</script>

<template>
    <Layout>
        <Head :title="listing.title" />
        <main class="content detail">
            <div class="detail-topline">
                <Link :href="`/explore?service=${listing.service}`" class="detail-back">← Back to explore</Link>
                <p class="eyebrow">{{ listing.service === 'haul' ? 'AADI VEHICLES' : listing.service === 'raketeer' ? 'AADI RAKETS' : 'AADI SPACES' }}</p>
            </div>
            <header class="detail-heading">
                <div class="detail-title-row">
                    <h1>{{ listing.title }}</h1>
                    <ListingBadges :badges="listing.badges" />
                </div>
                <p class="detail-location">{{ listing.location.address }}</p>
            </header>

            <div class="detail-grid">
                <section class="detail-gallery" aria-label="Listing photos">
                    <ListingGallery :photos="listing.photos" :title="listing.title" :service="listing.service" :type="listing.type" />
                </section>

                <aside class="detail-aside" aria-label="Listing actions and provider">
                    <div class="listing-price-block">
                        <p class="large-price">{{ money(listing.price) }} <small>/ {{ listing.priceUnit }}</small></p>
                        <p class="listing-state">
                            <span>Availability</span>
                            <strong :class="{ 'listing-state__value--active': !listing.availabilityPaused && listing.status === 'active', 'listing-state__value--paused': listing.availabilityPaused }">{{ listingState }}</strong>
                        </p>
                    </div>
                    <div class="listing-actions" aria-label="Listing actions">
                        <button v-if="canReview" type="button" class="button review-link" @click="showReviewForm">Rate</button>
                        <button
                            type="button"
                            class="button save-listing"
                            :aria-label="engagement.saved ? 'Remove saved listing' : 'Save listing'"
                            :aria-pressed="engagement.saved"
                            :disabled="save.processing"
                            @click="engagement.saved
                                ? save.delete(`/offers/${listing.service}/${listing.id}/save`, { preserveScroll: true })
                                : save.put(`/offers/${listing.service}/${listing.id}/save`, { preserveScroll: true })"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true" :fill="engagement.saved ? 'currentColor' : 'none'">
                                <path d="M6 3h12v18l-6-4-6 4z" />
                            </svg>
                            {{ engagement.saved ? 'Saved' : 'Save' }}
                        </button>
                        <ListingMessage v-if="canMessage" :listing="listing" />
                    </div>
                    <p v-if="save.errors.save" class="save-error" role="alert">{{ save.errors.save }}</p>
                    <ListingOwner :listing="listing" />
                    <div class="detail-aside-action">
                        <Link
                            v-if="user?.id === listing.ownerId || user?.is_admin"
                            :href="`/my-listings/${listing.service}/${listing.id}/edit`"
                            class="button edit-listing"
                        >Edit listing</Link>
                        <ListingRequest
                            v-else-if="listing.status === 'active' && !listing.availabilityPaused"
                            :listing="listing"
                            :unavailable-dates="unavailableDates"
                        />
                        <p v-else class="muted">This listing is not accepting requests.</p>
                    </div>
                </aside>

                <div class="detail-content">
                    <section class="detail-section" aria-labelledby="about-listing-title">
                        <h2 id="about-listing-title">About this listing</h2>
                        <p class="description">{{ listing.description }}</p>
                    </section>

                    <section class="detail-section" aria-labelledby="listing-details-title">
                        <h2 id="listing-details-title">Details &amp; features</h2>
                        <ul v-if="listing.comparison?.features?.length || listing.comparison?.scope" class="listing-features">
                            <li v-for="feature in listing.comparison?.features" :key="feature">{{ feature }}</li>
                            <li v-if="listing.comparison?.scope">{{ listing.comparison.scope }}</li>
                        </ul>
                        <dl v-if="listingDetails.length" class="listing-details">
                            <div v-for="detail in listingDetails" :key="detail.label">
                                <dt>{{ detail.label }}</dt>
                                <dd>{{ detail.value }}</dd>
                            </div>
                        </dl>
                        <p v-if="!listing.comparison?.features?.length && !listing.comparison?.scope && !listingDetails.length" class="detail-muted">
                            No additional features provided.
                        </p>
                    </section>

                    <section
                        v-if="listing.service === 'spaces' && (listing.minimumStayMonths || listing.contractRules)"
                        class="detail-section"
                        aria-labelledby="rental-rules-title"
                    >
                        <h2 id="rental-rules-title">Rental contract rules</h2>
                        <dl class="listing-details">
                            <div v-if="listing.minimumStayMonths">
                                <dt>Minimum stay</dt>
                                <dd>{{ listing.minimumStayMonths }} {{ listing.minimumStayMonths === 1 ? 'month' : 'months' }}</dd>
                            </div>
                            <div v-if="listing.contractRules">
                                <dt>Contract rules</dt>
                                <dd class="description">{{ listing.contractRules }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section v-if="listing.service === 'haul'" class="detail-section" aria-labelledby="vehicle-details-title">
                        <h2 id="vehicle-details-title">Vehicle details</h2>
                        <p class="detail-copy">
                            <strong>Driver</strong>
                            {{ listing.driverAvailable ? 'Available to request; any additional charge is arranged with the owner.' : 'Not available to request.' }}
                        </p>
                    </section>

                    <section class="detail-section" aria-labelledby="listing-location-title">
                        <h2 id="listing-location-title">Location</h2>
                        <template v-if="listing.location.lat != null && listing.location.lng != null">
                            <button v-if="!directionsOpen" type="button" class="button directions-button" @click="directionsOpen = true">Directions</button>
                            <ListingDirections v-else ref="directions" :listing="listing"
                                @route="directionsRoute = $event" @origin="directionsOrigin = $event" @pick="pickingOrigin = $event" @close="directionsOpen = false" />
                        </template>
                        <DiscoveryMap
                            v-if="listing.location.lat != null && listing.location.lng != null"
                            :listings="[listing]"
                            :center="[listing.location.lat, listing.location.lng]"
                            :route="directionsRoute"
                            :origin="directionsOrigin"
                            :pick-origin="pickingOrigin"
                            @pick-origin="directions?.choosePoint($event)"
                        />
                        <p v-else class="detail-muted">Map location has not been provided.</p>
                    </section>
                    <ListingReviews v-model:form-open="reviewFormOpen" :listing="listing" :engagement="engagement" />
                </div>
            </div>
        </main>
    </Layout>
</template>

<style scoped>
.directions-button, .detail-section :deep(.directions-panel) { margin-bottom:12px; }
.detail {
    max-width: 1320px;
    padding: 32px clamp(20px, 4vw, 48px) 72px;
}

.detail-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.detail-back {
    color: var(--teal);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.detail-topline .eyebrow {
    margin: 0;
}

.detail-heading {
    padding-bottom: 22px;
    border-bottom: 1px solid var(--line);
}

.detail-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

.detail-title-row h1 {
    min-width: 0;
    margin: 0;
    overflow-wrap: anywhere;
}

.detail-location {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: var(--aadi-text-body);
}

.detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.85fr) minmax(270px, .85fr);
    grid-template-areas:
        "gallery aside"
        "content aside";
    align-items: start;
    gap: 28px clamp(32px, 4vw, 56px);
    margin-top: 28px;
}

.detail-gallery {
    grid-area: gallery;
    min-width: 0;
}

.detail-content {
    grid-area: content;
    min-width: 0;
}

.detail :deep(.map-wrap) {
    position: relative;
    top: auto;
    height: 320px;
    min-height: 0;
    margin: 16px 0 0;
    border-radius: 12px;
    isolation: isolate;
}

.detail-aside {
    grid-area: aside;
    align-self: start;
    position: sticky;
    top: 24px;
    min-width: 0;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}

.listing-price-block {
    padding-bottom: 4px;
}

.large-price {
    margin: 0;
    color: var(--ink);
    font-size: clamp(26px, 2.5vw, 32px);
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    letter-spacing: -.035em;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.large-price small {
    color: var(--muted);
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0;
}

.listing-state {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 6px 9px;
    margin: 8px 0 0;
    color: var(--muted);
    font-size: var(--aadi-text-small);
    line-height: 1.5;
}

.listing-state strong {
    color: #526662;
    font-size: var(--aadi-text-small);
    font-weight: 700;
}

.listing-state .listing-state__value--active {
    color: var(--teal);
}

.listing-state .listing-state__value--paused {
    color: #875522;
}

.listing-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
    gap: 8px;
    padding: 16px 0 18px;
}

.listing-actions > .button,
.listing-actions :deep(.message-trigger) {
    width: 100%;
    min-width: 0;
    min-height: 46px;
    margin: 0;
    padding: 10px 12px;
    font-size: 13px;
}

.save-error { margin:8px 0 0; font-size:13px; color:#b42318; }
.listing-actions .save-listing {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.save-listing svg {
    width: 18px;
    height: 18px;
    flex: none;
    stroke: currentColor;
    stroke-width: 1.6;
}

.listing-actions :deep(.listing-message) {
    min-width: 0;
    margin: 0;
}

.listing-actions :deep(.listing-message.composing) {
    grid-column: 1 / -1;
}

.listing-actions .review-link {
    text-decoration: none;
}

.detail-aside :deep(.listing-owner) {
    border-top-color: var(--line);
}

.detail-aside-action {
    padding-top: 16px;
    border-top: 1px solid var(--line);
}

.edit-listing {
    width: 100%;
    justify-content: center;
}

.detail-aside-action :deep(.request-sample) {
    margin: 0;
    padding: 12px 14px;
    border-left: 3px solid #c68d34;
    border-radius: 0 8px 8px 0;
    background: #fff8e9;
    color: #5f4b24;
    font-size: var(--aadi-text-small);
    line-height: 1.55;
}

.detail-aside-action > .muted {
    margin: 0;
    color: var(--muted);
    line-height: 1.55;
}

.detail-section {
    margin-top: 32px;
}

.detail-section h2 {
    margin: 0 0 12px;
}

.detail-section:first-child {
    margin-top: 0;
}

.detail-section + .detail-section {
    padding-top: 24px;
    border-top: 1px solid var(--line);
}

.description,
.detail-copy {
    max-width: 72ch;
    margin: 0;
    color: #394d49;
    line-height: 1.7;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.detail-copy strong {
    margin-right: 6px;
}

.detail-muted {
    margin: 0;
    color: var(--muted);
    font-size: var(--aadi-text-small);
    line-height: 1.55;
}

.listing-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 0;
    list-style: none;
}

.listing-features li {
    padding: 7px 10px;
    border-radius: 6px;
    background: #edf5f2;
    color: #315d57;
    font-size: var(--aadi-text-small);
    line-height: 1.45;
}

.listing-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
    column-gap: 24px;
    margin: 0;
}

.listing-details > div {
    min-width: 0;
    padding: 12px 0;
    border-bottom: 1px solid var(--line);
}

.listing-details dt {
    color: var(--muted);
    font-size: var(--aadi-text-small);
    line-height: 1.45;
}

.listing-details dd {
    margin: 4px 0 0;
    color: var(--ink);
    font-size: var(--aadi-text-body);
    line-height: 1.5;
    overflow-wrap: anywhere;
}

@media (max-width: 900px) {
    .detail-grid {
        grid-template-columns: minmax(0, 1fr);
        grid-template-areas:
            "gallery"
            "aside"
            "content";
        gap: 22px;
    }

    .detail-aside {
        position: static;
        padding: 18px 0;
        border-top: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
    }

    .listing-actions {
        grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
    }

    .detail-aside :deep(.listing-owner) {
        padding: 14px 0;
    }

    .detail-aside-action {
        padding-top: 14px;
    }
}

@media (max-width: 600px) {
    .detail {
        padding-top: 24px;
        padding-bottom: 48px;
    }

    .detail-topline {
        align-items: flex-start;
        flex-direction: column-reverse;
        gap: 8px;
    }

    .detail-title-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 8px;
    }

    .detail-heading {
        padding-bottom: 18px;
    }

    .detail-grid {
        margin-top: 20px;
    }

    .detail-section {
        margin-top: 26px;
    }

    .detail-section + .detail-section {
        padding-top: 20px;
    }

    .detail :deep(.map-wrap) {
        height: 260px;
    }
}
</style>
