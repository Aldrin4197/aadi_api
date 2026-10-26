<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Layout from '@/Layouts/MarketplaceLayout.vue';
import DiscoveryMap from '@/Components/Marketplace/DiscoveryMap.vue';
import ListingImage from '@/Components/Marketplace/ListingImage.vue';
const props = defineProps({listing:Object});
const preview = computed(() => props.listing.access?.level === 'preview');
const unlock = computed(() => `/offers/${props.listing.service}/${props.listing.id}/unlock`);
const form = useForm({message:''});
const money = n => new Intl.NumberFormat('en-PH',{style:'currency',currency:props.listing.currency || 'PHP'}).format(n);
function inquire() { form.post(`/offers/${props.listing.service}/${props.listing.id}/inquiries`, {preserveScroll:true}); }
</script>
<template>
  <Layout><Head :title="listing.title" />
    <main class="content detail">
      <Link :href="`/explore?service=${listing.service}`" class="text-button">← Back to explore</Link>
      <p class="eyebrow">{{ listing.service==='haul'?'AADI VEHICLES':listing.service==='raketeer'?'AADI RAKETS':'AADI SPACES' }}</p>
      <h1>{{ listing.title }}</h1>
      <p>{{ listing.location.address || listing.location.city || 'Nearby area' }}<span v-if="preview"> · Approximate location</span></p>
      <div class="detail-grid">
        <section aria-label="Listing details">
          <div class="gallery">
            <ListingImage v-for="(photo,i) in listing.photos" :key="photo" :src="photo" :alt="`${listing.title}, photo ${i+1}`" :service="listing.service" :type="listing.type" />
            <ListingImage v-if="!listing.photos.length" :service="listing.service" :type="listing.type" />
          </div>
          <template v-if="!preview">
            <h2>About this listing</h2><p class="description">{{ listing.description }}</p>
            <dl v-if="Object.keys(listing.details || {}).length" class="listing-specs"><template v-for="(value,key) in listing.details" :key="key"><dt>{{ key }}</dt><dd>{{ value || 'Not specified' }}</dd></template></dl>
          </template>
          <div v-else class="listing-unlock">
            <p class="eyebrow">A closer look</p><h2>See the full picture.</h2>
            <p>Log in to read the full description, see all photos and the exact location, and send an inquiry to the provider.</p>
            <div class="actions"><Link :href="unlock" class="button primary">Log in for full details</Link><Link :href="`${unlock}?register=1`" class="button">Create an account</Link></div>
          </div>
          <template v-if="listing.comparison?.features?.length"><h2>Highlights</h2><ul class="listing-features"><li v-for="feature in listing.comparison.features" :key="feature">{{ feature }}</li></ul></template>
          <h2>{{ preview ? 'General area' : 'Location' }}</h2>
          <p v-if="preview" class="muted">The marker shows an approximate area. Log in to see the listing’s exact pin.</p>
          <DiscoveryMap v-if="listing.location.lat!=null && listing.location.lng!=null" :listings="[listing]" :center="[listing.location.lat,listing.location.lng]" :searchable="false" />
          <p v-else>Map location has not been provided.</p>
        </section>
        <aside class="detail-aside" aria-label="Price and inquiry">
          <p class="large-price">{{ money(listing.price) }} <small>/ {{ listing.priceUnit }}</small></p>
          <p class="muted">Confirm availability, scope, and any extra charges with the provider.</p><hr />
          <template v-if="preview"><h2>Interested?</h2><p class="muted">Sign in to view the provider and ask about this listing.</p><Link :href="unlock" class="button primary">Log in to inquire</Link></template>
          <template v-else>
            <p class="eyebrow">LISTED BY</p><h2>{{ listing.ownerName }}</h2>
            <Link v-if="listing.access?.canManage" :href="`/my-listings/${listing.service}/${listing.id}/edit`" class="button">Edit listing</Link>
            <form v-if="listing.access?.canInquire" class="inquiry-form" @submit.prevent="inquire">
              <label for="inquiry-message">Ask the provider<textarea id="inquiry-message" v-model="form.message" required minlength="5" maxlength="3000" rows="5" placeholder="Ask about availability, pricing, or what you need." :aria-invalid="!!form.errors.message" aria-describedby="inquiry-error" /></label>
              <p id="inquiry-error" class="error" role="alert">{{ form.errors.message }}</p>
              <button type="submit" class="button primary" :disabled="form.processing">{{ form.processing?'Sending…':'Send inquiry' }}</button>
            </form>
            <p v-else class="muted">{{ listing.access?.canManage ? 'Manage inquiries from your account.' : 'This listing is not accepting new inquiries.' }}</p>
            <Link href="/inquiries" class="text-button inquiry-inbox-link">Open my inquiries →</Link>
          </template>
        </aside>
      </div>
    </main>
  </Layout>
</template>
<style scoped>
.gallery > :deep(.listing-image) { height: 240px; border-radius: 12px; }
.gallery > :deep(.listing-image:first-child) { grid-column: 1 / -1; height: 360px; }
.gallery :deep(.listing-image img) { height: 100%; }
.listing-unlock { padding: 24px; background: #eef4ef; border: 1px solid var(--line); border-radius: 12px; margin-top: 24px; }
.listing-unlock p { color: var(--muted); }
.listing-features { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
.listing-features li { background: #eef4ef; border-radius: 6px; padding: 8px 12px; font-size: 13px; }
.listing-specs { display: grid; grid-template-columns: auto 1fr; gap: 8px 24px; margin-top: 24px; font-size: 14px; }
.listing-specs dt { text-transform: capitalize; color: var(--muted); }
.inquiry-form { margin-top: 24px; }
.inquiry-form button { width: 100%; margin-top: 12px; }
.inquiry-inbox-link { display: inline-block; margin-top: 20px; }
.detail section :deep(.map-wrap) { position: relative; height: 360px; min-height: 360px; top: 0; margin-top: 16px; }
@media (max-width: 700px) { .gallery > :deep(.listing-image) { height: 150px; } .gallery > :deep(.listing-image:first-child) { height: 260px; } }
</style>
