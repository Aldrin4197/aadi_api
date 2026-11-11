import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import assert from 'node:assert/strict';

test('request cards link to visible listings and explain unavailable ones', async () => {
    const component = await readFile(new URL('../../resources/js/Components/Account/RequestCard.vue', import.meta.url), 'utf8');
    assert.match(component, /v-if="item\.canViewListing"/);
    assert.match(component, /\/offers\/\$\{item\.service\}\/\$\{item\.listingId\}/);
    assert.match(component, /v-else class="request-listing-unavailable"/);
});
