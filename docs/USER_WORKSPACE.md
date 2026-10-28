# User workspace and listing photos

`/dashboard` is the authenticated user's overview. It counts only that user's non-deleted marketplace listings, including drafts, and shows their latest four listings and provider role statuses. `/admin/dashboard` remains separate. Explore keeps its public navigation. Dashboard, profile, listing management, and listing forms share the account sidebar, header, typography, and page heading.

My listings stays on the account page even when the user has no listings or approved provider roles. Its empty state links to applications instead of redirecting unexpectedly.

## Photo contract

The existing canonical marketplace `photos` array is an ordered gallery of up to 12 image URLs. `photos[0]` is the featured photo. Selecting **Set as featured** moves that photo to index zero; removing it promotes the next photo. Zero photos remains valid for existing listings and drafts; the UI shows an illustration fallback.

The web form uploads photos through `/listing-photos`, then sends the ordered array with the usual listing create/update request. Existing marketplace API clients should send the featured URL first. Property records store `photos`; vehicle and service records store `images`; Marketplace maps all three to `photos`. This change does not migrate the database, duplicate image files, or add a competing featured-photo field. Removing a photo from a listing does not delete the underlying uploaded file.

Full details display the ordered gallery with previous/next controls and selectable thumbnails. The featured photo is initially selected. Guest preview modals continue to show only the featured image.

## Verification

Check as a guest, a user without provider roles, and an approved provider. Confirm dashboard ownership isolation, profile changes, empty listing states, mobile sidebar navigation, multiple-photo upload, changing/removing the featured photo, save/reload order, and gallery navigation. Use `php artisan test --filter=AccountDashboardTest` for ownership and photo persistence coverage.
