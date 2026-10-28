<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import UserAvatar from '@/Components/Account/UserAvatar.vue';
const props = defineProps({ listing: Object, engagement: Object });
const user = computed(() => usePage().props.auth?.user);
const form = useForm({ rating: props.engagement.myReview?.rating || 5, body: props.engagement.myReview?.body || '' });
const remove = useForm({});
const url = computed(() => `/offers/${props.listing.service}/${props.listing.id}/review`);
</script>
<template>
<section class="reviews" aria-labelledby="reviews-title">
  <h2 id="reviews-title">Reviews <span>({{ engagement.count }})</span></h2>
  <div v-if="engagement.count" class="review-summary"><strong>{{ engagement.average }} <small>/ 5</small></strong><div><div v-for="row in engagement.breakdown" :key="row.rating" class="rating-row"><span>{{ row.rating }} ★</span><meter :value="row.count" :max="engagement.count" :aria-label="`${row.rating} stars: ${row.count} reviews`"/><span>{{ row.count }}</span></div></div></div>
  <p v-else>No reviews yet. Share your experience to help others decide.</p>
  <p class="review-note">Community feedback. Reviews do not indicate a verified booking or purchase.</p>
  <article v-for="review in engagement.reviews.data" :key="review.id" class="review"><header><UserAvatar :user="review"/><div><strong>{{ review.name }}</strong><p>{{ review.rating }} / 5 · {{ new Date(review.updated_at).toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' }) }}</p></div></header><p class="review-body">{{ review.body }}</p></article>
  <nav v-if="engagement.reviews.last_page > 1" aria-label="Review pages"><Link v-if="engagement.reviews.prev_page_url" :href="engagement.reviews.prev_page_url" preserve-scroll>Previous</Link><span>Page {{ engagement.reviews.current_page }} of {{ engagement.reviews.last_page }}</span><Link v-if="engagement.reviews.next_page_url" :href="engagement.reviews.next_page_url" preserve-scroll>Next</Link></nav>
  <form v-if="user && user.id !== listing.ownerId && listing.status === 'active'" @submit.prevent="form.put(url, {preserveScroll:true})"><h3>{{ engagement.myReview ? 'Your review' : 'Write a review' }}</h3><label>Rating<select v-model="form.rating"><option v-for="n in [5,4,3,2,1]" :key="n" :value="n">{{ n }} {{ n === 1 ? 'star' : 'stars' }}</option></select></label><p v-if="form.errors.rating" role="alert">{{ form.errors.rating }}</p><label>Your experience<textarea v-model="form.body" required minlength="10" maxlength="2000" rows="4" placeholder="What would you like others to know?"/></label><p v-if="form.errors.body" role="alert">{{ form.errors.body }}</p><div class="review-actions"><button class="button primary" :disabled="form.processing || remove.processing">{{ engagement.myReview ? 'Update review' : 'Post review' }}</button><button v-if="engagement.myReview" type="button" class="button" :disabled="form.processing || remove.processing" @click="remove.delete(url, {preserveScroll:true, onSuccess:()=>{form.body='';form.rating=5;}})">Delete my review</button></div></form>
</section>
</template>
<style scoped>
.reviews{margin-top:40px;border-top:1px solid #dcece9;padding-top:24px}.reviews h2 span,.review-note{color:#586e6c;font-size:14px}.review-summary{display:flex;gap:32px;align-items:center}.review-summary>strong{font-size:40px}.review-summary small{font-size:16px}.rating-row{display:flex;align-items:center;gap:12px;font-size:13px}meter{width:140px;accent-color:#1ba8a1}.review{padding:24px 0;border-bottom:1px solid #dcece9}.review header{display:flex;align-items:center;gap:12px}.review header p{margin:4px 0;font-size:13px;color:#586e6c}.review-body{white-space:pre-wrap;overflow-wrap:anywhere}.reviews form{margin-top:28px;display:grid;gap:12px}.reviews label{display:grid;gap:8px;font-weight:600}.reviews select,.reviews textarea{border:1px solid #b7ceca;border-radius:10px;padding:12px;font:inherit;max-width:100%;width:100%}.review-actions,nav{display:flex;flex-wrap:wrap;gap:12px;margin-top:16px}.reviews button:disabled{opacity:.6}a:focus-visible,button:focus-visible,select:focus-visible,textarea:focus-visible{outline:2px solid #137671;outline-offset:3px}
</style>
