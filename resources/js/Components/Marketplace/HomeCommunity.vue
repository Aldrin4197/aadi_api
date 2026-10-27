<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ reviews: { type: Array, default: () => [] } });
</script>

<template>
    <section id="community-reviews" class="landing-community" :class="{ 'community-empty': !reviews.length }" aria-labelledby="community-title">
        <div class="landing-section">
            <div class="landing-section-heading"><div><p class="landing-kicker">From the Aadi community</p><h2 id="community-title">Good finds. Shared experiences.</h2></div><p>Stories about the spaces, wheels, and skills that help everyday plans happen.</p></div>
            <div v-if="reviews.length" class="community-reviews">
                <figure v-for="review in reviews" :key="review.sourceUrl + review.name" class="community-review">
                    <span class="review-quote-mark" aria-hidden="true">“</span>
                    <blockquote>{{ review.quote }}</blockquote>
                    <figcaption><span class="review-initial" aria-hidden="true">{{ review.name.slice(0, 1) }}</span><div><strong>{{ review.name }}</strong><span>{{ review.context }}</span></div></figcaption>
                    <a :href="review.sourceUrl" target="_blank" rel="noopener noreferrer" class="landing-text-link">Read the original review <span aria-hidden="true">↗</span></a>
                </figure>
            </div>
            <div v-else class="community-invitation">
                <p>Real stories belong here. Community reviews will appear as they’re shared and approved.</p>
                <Link href="/explore" class="landing-text-link">Explore Aadi <span aria-hidden="true">↗</span></Link>
            </div>
        </div>
    </section>
</template>
