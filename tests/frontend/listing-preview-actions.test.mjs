import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

test('listing preview keeps only the full-listing action and opens reports in a dialog', async () => {
    const [preview, owner, report, badge, verifiedBadge] = await Promise.all([
        readFile(new URL('../../resources/js/Components/Marketplace/ListingPreviewModal.vue', import.meta.url), 'utf8'),
        readFile(new URL('../../resources/js/Components/Marketplace/ListingOwner.vue', import.meta.url), 'utf8'),
        readFile(new URL('../../resources/js/Components/Account/ReportAccount.vue', import.meta.url), 'utf8'),
        readFile(new URL('../../resources/js/Components/Account/ProviderBadge.vue', import.meta.url), 'utf8'),
        readFile(new URL('../../resources/js/Components/Badges/AadiVerifiedBadge.vue', import.meta.url), 'utf8'),
    ]);
    assert.match(preview, /<ListingOwner :listing="item" preview\s*\/>/);
    assert.match(preview, /View Full Listing/);
    assert.doesNotMatch(preview, /preview-back|Keep exploring|Save listing|Review/);
    assert.match(owner, /listing-action\s*\/>/);
    assert.match(owner, /v-if="listing\.ownerId && !preview"/);
    assert.doesNotMatch(owner, /View profile/);
    assert.match(owner, /\.listing-owner\s*\{[\s\S]*?margin:\s*0/);
    assert.match(owner, /\.listing-owner\s*\{[\s\S]*?padding:\s*16px 0/);
    assert.doesNotMatch(owner, /border-radius:12px|background:#f8fbfa/);
    assert.match(report, /\.listing-action \.report-toggle\s*\{[^}]*width:40px; height:40px/);
    assert.match(report, /listingAction \? 'Report'/);
    assert.match(report, /:aria-label="listingAction \|\| profileAction \? `Report \$\{name\}`/);
    assert.match(report, /<dialog ref="dialog" class="report-dialog"/);
    assert.match(report, /showModal\(\)/);
    assert.match(report, /@submit\.prevent="submit"/);
    assert.doesNotMatch(report, /report-expanded/);
    assert.match(owner, /<div class="owner-header">/);
    assert.match(owner, /<ProviderBadge role="user" :approved="listing\.ownerEmailVerified" compact \/>/);
    assert.match(owner, /<ProviderBadge :role="listing\.service" :approved="listing\.providerApproved" compact \/>/);
    assert.match(badge, /import AadiVerifiedBadge/);
    assert.match(badge, /spaces: 'landlord'/);
    assert.match(badge, /haul: 'vehicle-owner'/);
    assert.match(badge, /variant="icon"/);
    assert.match(badge, /icon-style="filled"/);
    assert.match(badge, /user: 'user'/);
    assert.doesNotMatch(badge, /compactLabel|badge-label/);
    assert.match(verifiedBadge, /user: \{ label: 'User'.*color: '#0e7490'/);
    assert.match(verifiedBadge, /'user', 'landlord', 'vehicle-owner', 'raketeer'/);
    assert.match(verifiedBadge, /Verified user\. Email verified; this is not an identity-document check\./);
    assert.match(verifiedBadge, /aadi-badge__check-disc/);
    assert.match(report, /@click="openDialog"/);
    assert.match(report, /form\.post\(`\/users\/\$\{props\.userId\}\/reports`/);
});

test('public profile like and report actions are icon-only and accessible', async () => {
    const [likes, report] = await Promise.all([
        readFile(new URL('../../resources/js/Components/Account/ProfileLikes.vue', import.meta.url), 'utf8'),
        readFile(new URL('../../resources/js/Components/Account/ReportAccount.vue', import.meta.url), 'utf8'),
    ]);
    assert.match(likes, /:aria-label="likes\.liked \? 'Unlike profile' : 'Like profile'"/);
    assert.match(likes, /<svg viewBox="0 0 24 24" aria-hidden="true" :fill="likes\.liked \? 'currentColor' : 'none'">/);
    assert.match(report, /aria-label="listingAction \|\| profileAction \? `Report \$\{name\}` : undefined"/);
    assert.match(report, /<svg v-if="profileAction \|\| listingAction" viewBox="0 0 24 24" aria-hidden="true" fill="none">/);
});
