<script setup>
import ListingViewButton from '@/Components/Marketplace/ListingViewButton.vue';
import ListingPreviewLink from '@/Components/Marketplace/ListingPreviewLink.vue';
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ListingImage from './ListingImage.vue';
import { formatPrice } from './comparison.mjs';
const props = defineProps({ items: { type: Array, default: () => [] } });
const active = ref('all');
const categories = { all: 'All finds', spaces: 'Spaces', haul: 'Vehicles', raketeer: 'Rakets' };
const visible = computed(() => props.items.filter(item => active.value === 'all' || item.service === active.value));
</script>

<template>
    <section id="featured-listings" class="landing-section landing-featured" aria-labelledby="featured-title">
        <div class="landing-section-heading">
            <div><p class="landing-kicker">A few places to start</p><h2 id="featured-title">Featured finds on Aadi.</h2></div>
            <Link href="/explore" class="landing-text-link">Explore all listings <span aria-hidden="true">↗</span></Link>
        </div>
        <div class="featured-toolbar">
            <div class="featured-filters" role="group" aria-label="Featured listing categories">
                <button v-for="(label, key) in categories" :key="key" type="button" :aria-pressed="active === key" @click="active = key">{{ label }}</button>
            </div>
            <p>A look at the latest published listings across Aadi.</p>
        </div>
        <div v-if="visible.length" class="featured-grid">
            <article v-for="item in visible" :key="item.service + ':' + item.id" class="featured-listing">
                <ListingPreviewLink :item="item" class="featured-photo" :aria-label="`View ${item.title}`"><ListingImage :src="item.photos?.[0]" :alt="item.title" :service="item.service" :type="item.type" /></ListingPreviewLink>
                <div class="featured-listing-copy">
                    <span class="featured-category">{{ categories[item.service] }}</span>
                    <h3><ListingPreviewLink :item="item">{{ item.title }}</ListingPreviewLink></h3>
                    <p>{{ item.location.city || item.location.address || 'Ask the provider for the location' }}</p>
                    <div class="featured-listing-bottom"><strong>{{ formatPrice(item.price) }} <small>/ {{ item.priceUnit }}</small></strong><ListingViewButton :item="item"/></div>
                </div>
            </article>
        </div>
        <div v-else class="featured-empty" role="status"><h3>Make room for the next good find.</h3><p>There are no published {{ active === 'all' ? 'listings' : categories[active].toLowerCase() }} to feature yet. Explore the map or become one of the first providers in your area.</p><Link href="/become-provider" class="landing-text-link">List what you offer <span aria-hidden="true">↗</span></Link></div>
        <p class="featured-note">Rates keep their original billing units. Preview freely; log in for full details.</p>
    </section>
</template>
