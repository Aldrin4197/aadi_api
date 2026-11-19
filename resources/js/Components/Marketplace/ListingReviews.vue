<script setup>
import RatingStars from './RatingStars.vue';
import { computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import UserAvatar from '@/Components/Account/UserAvatar.vue';

const props = defineProps({
    listing: Object,
    engagement: Object,
    formOpen: { type: Boolean, default: false },
});
const emit = defineEmits(['update:formOpen']);
const user = computed(() => usePage().props.auth?.user);
const form = useForm({ rating: props.engagement.myReview?.rating || 0, body: props.engagement.myReview?.body || '' });
const remove = useForm({});

watch(() => props.engagement.myReview, review => {
    form.rating = review?.rating || 0;
    form.body = review?.body || '';
});

function submit() {
    if (!form.rating) {
        form.setError('rating', 'Choose a star rating.');
        return;
    }

    form.clearErrors('rating');
    form.put(url.value, {
        preserveScroll: true,
        onSuccess: () => emit('update:formOpen', false),
    });
}

const url = computed(() => `/offers/${props.listing.service}/${props.listing.id}/review`);
</script>
<template>
<section class="reviews" aria-labelledby="reviews-title">
  <header class="reviews-heading">
    <h2 id="reviews-title">Reviews <span>({{ engagement.count }})</span></h2>
    <p class="review-note">Community feedback. Reviews do not indicate a verified booking or purchase.</p>
  </header>

  <div v-if="engagement.count" class="review-summary" role="group" aria-label="Review summary">
    <div class="review-score">
      <strong class="review-average">{{ Number(engagement.average).toFixed(1) }}</strong>
      <div class="review-score-details">
        <RatingStars :model-value="Number(engagement.average) || 0"/>
        <span>{{ engagement.count }} {{ engagement.count === 1 ? 'rating' : 'ratings' }}</span>
      </div>
    </div>
    <div class="rating-breakdown">
      <div v-for="row in engagement.breakdown" :key="row.rating" class="rating-row">
        <span>{{ row.rating }} <span aria-hidden="true">★</span></span>
        <meter :value="row.count" :max="engagement.count" :aria-label="`${row.rating} stars: ${row.count} reviews`"/>
        <span>{{ row.count }}</span>
      </div>
    </div>
  </div>
  <div v-else class="review-empty">
    <strong>No reviews yet</strong>
    <p>Share your experience to help others decide.</p>
  </div>

  <article v-for="review in engagement.reviews.data" :key="review.id" class="review">
    <header class="review-header">
      <UserAvatar :user="review"/>
      <div class="review-author">
        <div class="review-author-line">
          <strong>{{ review.name }}</strong>
          <time :datetime="review.updated_at">{{ new Date(review.updated_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) }}</time>
        </div>
        <RatingStars :model-value="Number(review.rating)"/>
      </div>
    </header>
    <p class="review-body">{{ review.body }}</p>
  </article>

  <nav v-if="engagement.reviews.last_page > 1" class="review-pagination" aria-label="Review pages">
    <Link v-if="engagement.reviews.prev_page_url" :href="engagement.reviews.prev_page_url" preserve-scroll>Previous</Link>
    <span>Page {{ engagement.reviews.current_page }} of {{ engagement.reviews.last_page }}</span>
    <Link v-if="engagement.reviews.next_page_url" :href="engagement.reviews.next_page_url" preserve-scroll>Next</Link>
  </nav>

  <form v-if="user && user.id !== listing.ownerId && listing.status === 'active' && formOpen" id="write-review" class="review-form" @submit.prevent="submit">
    <h3>{{ engagement.myReview ? 'Your review' : 'Write a review' }}</h3>
    <RatingStars v-model="form.rating" editable :disabled="form.processing || remove.processing" label="Your rating"/>
    <p v-if="form.errors.rating" class="review-error" role="alert">{{ form.errors.rating }}</p>
    <label>Your experience<textarea v-model="form.body" required minlength="10" maxlength="2000" rows="4" placeholder="What would you like others to know?"/></label>
    <p v-if="form.errors.body" class="review-error" role="alert">{{ form.errors.body }}</p>
    <div class="review-actions">
      <button class="button primary" :disabled="form.processing || remove.processing">{{ engagement.myReview ? 'Update review' : 'Post review' }}</button>
      <button v-if="engagement.myReview" type="button" class="button" :disabled="form.processing || remove.processing" @click="remove.delete(url, { preserveScroll: true, onSuccess: () => { form.body = ''; form.rating = 0; emit('update:formOpen', false); } })">Delete my review</button>
      <button type="button" class="button" :disabled="form.processing || remove.processing" @click="emit('update:formOpen', false)">Cancel</button>
    </div>
  </form>
</section>
</template>
<style scoped>
.reviews {
    margin-top: 40px;
    padding-top: 28px;
    border-top: 1px solid var(--line);
    font-size: var(--aadi-text-body);
}

.reviews-heading {
    margin-bottom: 20px;
}

.reviews h2 {
    margin: 0;
    font-size: var(--aadi-text-heading);
    line-height: 1.3;
}

.reviews h2 span {
    margin-left: 4px;
    color: var(--muted);
    font-size: var(--aadi-text-body);
    font-weight: 500;
}

.review-note {
    max-width: 62ch;
    margin: 7px 0 0;
    color: var(--muted);
    font-size: var(--aadi-text-small);
    line-height: 1.5;
}

.review-summary {
    display: grid;
    grid-template-columns: minmax(170px, .75fr) minmax(0, 1.25fr);
    align-items: center;
    gap: 30px;
    padding: 2px 0 24px;
    border-bottom: 1px solid var(--line);
}

.review-score {
    display: flex;
    align-items: center;
    gap: 14px;
}

.review-average {
    color: var(--ink);
    font-size: 42px;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    letter-spacing: -.05em;
    line-height: 1;
}

.review-score-details {
    display: grid;
    gap: 5px;
    color: var(--muted);
    font-size: var(--aadi-text-small);
    line-height: 1.4;
}

.rating-breakdown {
    display: grid;
    gap: 7px;
}

.rating-row {
    display: grid;
    grid-template-columns: 32px minmax(0, 1fr) 24px;
    align-items: center;
    gap: 10px;
    color: var(--muted);
    font-size: var(--aadi-text-small);
    line-height: 1.4;
}

.rating-row > span:last-child {
    text-align: right;
}

.rating-row meter {
    display: block;
    width: 100%;
    height: 7px;
    overflow: hidden;
    appearance: none;
    border: 0;
    border-radius: 999px;
    background: #e6eeeb;
}

.rating-row meter::-webkit-meter-bar {
    border: 0;
    border-radius: 999px;
    background: #e6eeeb;
}

.rating-row meter::-webkit-meter-optimum-value {
    border-radius: 999px;
    background: var(--teal);
}

.rating-row meter::-moz-meter-bar {
    border-radius: 999px;
    background: var(--teal);
}

.review-empty {
    padding: 2px 0 22px;
    border-bottom: 1px solid var(--line);
}

.review-empty strong {
    font-size: var(--aadi-text-body);
}

.review-empty p {
    margin: 5px 0 0;
    color: var(--muted);
    line-height: 1.6;
}

.review {
    padding: 22px 0;
    border-bottom: 1px solid var(--line);
}

.review-header {
    display: flex;
    align-items: flex-start;
    gap: 13px;
}

.review-author {
    display: grid;
    flex: 1;
    min-width: 0;
    gap: 6px;
}

.review-author-line {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
}

.review-author-line strong {
    font-size: var(--aadi-text-body);
}

.review-author-line time {
    flex: none;
    color: var(--muted);
    font-size: var(--aadi-text-small);
}

.review-body {
    max-width: 70ch;
    margin: 14px 0 0;
    color: #394d49;
    line-height: 1.65;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.review-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 0 0;
    color: var(--muted);
    font-size: var(--aadi-text-small);
}

.review-pagination a {
    color: var(--teal);
    font-weight: 600;
}

.review-form {
    display: grid;
    max-width: 700px;
    gap: 14px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--line);
}

.review-form h3 {
    margin: 0;
    font-size: var(--aadi-text-subheading);
    font-weight: 700;
    line-height: 1.4;
}

.review-form label {
    display: grid;
    gap: 8px;
    font-size: var(--aadi-text-small);
    font-weight: 600;
    line-height: 1.4;
}

.review-form textarea {
    width: 100%;
    max-width: 100%;
    min-height: 112px;
    padding: 12px;
    border: 1px solid #b7ceca;
    border-radius: 8px;
    font: inherit;
    font-size: var(--aadi-text-body);
    line-height: 1.5;
    resize: vertical;
}

.review-error {
    margin: 0;
    color: #9e342b;
    font-size: var(--aadi-text-small);
    line-height: 1.5;
}

.review-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.reviews button:disabled {
    opacity: .6;
}

.reviews :is(a, button, select, textarea):focus-visible {
    outline: 2px solid #137671;
    outline-offset: 3px;
}

@media (max-width: 620px) {
    .review-summary {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .review-author-line {
        align-items: flex-start;
        flex-direction: column;
        gap: 3px;
    }
}
</style>
