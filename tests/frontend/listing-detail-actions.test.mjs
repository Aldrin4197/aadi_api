import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

test('listing save, message, and review actions share one action row', async () => {
    const [page, message, reviews] = await Promise.all([
        readFile(new URL('../../resources/js/Pages/Marketplace/Show.vue', import.meta.url), 'utf8'),
        readFile(new URL('../../resources/js/Components/Marketplace/ListingMessage.vue', import.meta.url), 'utf8'),
        readFile(new URL('../../resources/js/Components/Marketplace/ListingReviews.vue', import.meta.url), 'utf8'),
    ]);

    assert.match(page, /class="listing-actions" aria-label="Listing actions"/);
    const rateActionIndex = page.indexOf('class="button review-link" @click="showReviewForm">Rate</button>');
    const saveActionIndex = page.indexOf('class="button save-listing"');
    assert.ok(rateActionIndex >= 0 && rateActionIndex < saveActionIndex, 'Rate should be the first listing action');
    assert.match(page, /class="button save-listing"/);
    assert.match(page, /<ListingMessage v-if="canMessage"/);
    assert.match(page, /v-model:form-open="reviewFormOpen"/);
    assert.match(reviews, /&& formOpen" id="write-review"/);
    assert.match(page, /grid-template-columns: repeat\(auto-fit, minmax\(70px, 1fr\)\)/);
    assert.match(message, /class="listing-message" :class="\{ composing: open \}"/);
    assert.match(reviews, /id="write-review"/);
});
