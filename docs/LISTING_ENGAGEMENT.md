# Listing reviews and saved listings

Deploy with `php artisan migrate` after pulling the change. The additive migration creates marketplace_reviews and marketplace_saves; it does not recreate existing tables. Properties continue using favorites, preserving existing mobile favorites. Vehicles and services use marketplace_saves. No demo reviews are generated.

Authenticated web users can save/unsave listings, browse `/saved-listings`, and create/update/delete their own review on a listing's full details page. Each user has at most one review per service/listing. Owners cannot review their own listing. Ratings are integers 1–5 with a 10–2000 character body. Reviews are community feedback, not verified transactions. The provider badge reflects admin approval of that role, not approval of the individual listing.

Sanctum API contract (service: spaces, haul, raketeer):

- GET `/api/v1/saved-listings`: paginated canonical listing DTOs; unavailable listings return only id, service and unavailable=true.
- GET `/api/v1/offers/{service}/{id}/engagement`: saved, average, count, breakdown, paginated reviews and myReview. `reviewPage` selects review page.
- PUT/DELETE `/api/v1/offers/{service}/{id}/save`: idempotent save/remove, 204.
- PUT `/api/v1/offers/{service}/{id}/review`: rating and body; creates or updates own review, 204.
- DELETE `/api/v1/offers/{service}/{id}/review`: deletes own review, 204.

New saves/reviews require an active listing. Removal remains possible after a listing becomes unavailable. Public previews and the existing full-detail login policy are unchanged. Reviews are displayed on the authenticated full listing page. Aggregates derive from actual reviews, independent of legacy rating counters. No user email, address or verification documents are included in review responses.

Cross-category target IDs cannot have a foreign key to three tables. User deletion cascades; target access always resolves the listing and checks availability. Deleted vehicle/service targets can leave inaccessible review records and removable saved placeholders. Admin review moderation and automatic document-expiry revocation are outside this change.

Validation: run `php artisan test --filter=ListingEngagementTest`, `npm run build`, and `npm run test:frontend`. PHP is unavailable in the implementation environment; PHP tests must run in CI or locally before merge.

## Saved cards and public provider profiles

Saved listings now use responsive photo cards. Photo/title/View opens the shared preview dialog; Remove remains a separate action. Public `/providers/{user}` and `/api/v1/providers/{user}` return only name, avatar, bio, city, membership year, approved roles and paginated active listing previews. Use service=spaces|haul|raketeer and page. The profile response excludes email, phone, address, documents, drafts and exact listing coordinates.

Avatars with a tracked avatar_path are now served by `/avatars/{user}/{filename}`. This removes the public-storage symlink dependency for avatars, including previously uploaded tracked photos. The handler serves only the current avatar, never arbitrary storage paths. Legacy external avatar URLs still work. Avatar uploads still require the previous profile migration and writable storage/app/public. No additional migration is introduced by this follow-up. A live installation upload failure has not been reproduced; verify server upload limits and storage permissions if uploads still fail.
