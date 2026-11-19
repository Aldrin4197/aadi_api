# Listing badges and Aadi Plus

The shared badge components render in listing search results, previews, full details, saved listings, provider profiles and admin inspections. Badges are server-derived; clients do not infer verification, ranking, availability or premium access.

## Listing status badges

- **Featured:** the two latest published listings per marketplace category currently selected for the homepage's featured section.
- **Top Rated:** at least five listing reviews with a mean rating of 4.5 or higher.
- **New:** published in the last 30 days. `published_at` records each transition to a published state; existing published listings are backfilled from `created_at`.
- **Popular:** among the top 10% of active listings by views in the last 30 days, and has at least 20 views.
- **Available Now:** published/active, not paused, and not owned by a restricted account.
- **Aadi Recommended:** the viewer completed onboarding, selected the listing's marketplace category, and the listing satisfies every supplied city and budget preference. A viewer's own listings are excluded. Properties use their structured city; vehicle and service locations match the city as a whole term in the location text. Recommendations do not change search filters.

## Provider and premium badges

Provider-role icons require the corresponding admin-approved role. Verified User continues to mean only that the email address is verified; none of these badges verifies an individual listing.

Aadi Plus is reserved for future use and defaults to off (`AADI_PLUS_ENABLED=false`). While off, public badges and priority placement are hidden, Plus limits are not enforced, the existing 12-photo ceiling remains, and listing analytics remain available to all providers. Admins can still manage perk settings and grant, extend, or remove expiring entitlements under **Admin → Aadi Plus** or **Admin → Users → user listings**. Entitlement dates continue to expire during the suspension.

To enable the feature later, set `AADI_PLUS_ENABLED=true` in the deployment environment and rebuild Laravel's config cache. Review limits and entitlements first: enabling applies the configured free and member limits and displays badges for unexpired entitlements. Priority placement currently reorders only within each search page, and older API listing controllers do not enforce listing or photo limits. Payments, checkout, pricing, and an entitlement audit log are not implemented. Public profiles and listing DTOs expose only whether the entitlement is active and the feature is enabled, never its expiry date.

## Deployment

Run `php artisan migrate` to add user entitlements, perk settings, and listing publication timestamps.
