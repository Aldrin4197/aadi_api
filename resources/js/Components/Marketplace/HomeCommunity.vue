<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ reviews: { type: Array, default: () => [] } });
</script>

<template>
    <section id="community-reviews" class="landing-community" aria-labelledby="community-title">
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
                <div class="community-art"><img src="/images/illustrations/raket-design.svg" alt="Illustration of a local creative workspace" width="480" height="300" loading="lazy" /><span>Every connection starts somewhere.</span></div>
                <div><span class="community-status">Community stories coming soon</span><h3>A new neighborhood of possibilities.</h3><p>We’re making space for real experiences from people who use Aadi. Customer reviews will appear here as they’re shared and approved for publication.</p><Link href="/explore" class="landing-text-link">Find your first Aadi connection <span aria-hidden="true">↗</span></Link></div>
            </div>
        </div>
    </section>
</template>
