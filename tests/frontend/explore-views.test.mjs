import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import assert from 'node:assert/strict';

test('Explore exposes all listing views without a fixed-height list cap', async () => {
    const carousel = await readFile(new URL('../../resources/js/Components/Marketplace/ListingCarousel.vue', import.meta.url), 'utf8');
    const explore = await readFile(new URL('../../resources/js/Pages/Marketplace/Explore.vue', import.meta.url), 'utf8');
    const styles = await readFile(new URL('../../resources/css/map-explorer.css', import.meta.url), 'utf8');

    for (const label of ['Carousel', 'List', 'Swipe']) assert.match(carousel, new RegExp(`label:'${label}'`));
    assert.match(carousel, /scroll-snap-type:x mandatory/);
    assert.match(carousel, /mode\.value === 'list'/);
    assert.doesNotMatch(carousel, /max-height:\s*(?:245|280)px/);
    assert.match(explore, /listingMode!=='carousel'/);
    assert.match(explore, /results\.meta\.lastPage>1/);
    assert.match(styles, /results-expanded \.map-results\{inset:0/);
    assert.match(styles, /has-comparison \.carousel-rail\{padding-bottom:/);
});
