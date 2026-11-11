# User workspace and listing photos

## Signed-in flow and role-based navigation

Visitors see the marketing landing page at `/`. Signed-in users never do: `/` redirects them (keeping any query string) to `/explore`, the map-only page, and login, social login, email verification and password confirmation all end there unless the user was sent to login from a specific page, in which case that page is restored. The browser PWA `start_url` stays `/`, so an installed app opens the map for signed-in users and the landing page for visitors. Logging out returns to the landing page. Signed-in users also lose the landing-page anchors (How it works, For providers, Support) from the header; the brand links to the map.

Every signed-in user gets **Map**, **Saved listings**, **Requests**, **Profile**, **Settings** and **Become a provider** (labelled **Provider roles** once approved). **Dashboard** and **My listings** appear only after at least one provider role is approved (`auth.isProvider`, from `User::isApprovedProvider()`). `/dashboard` redirects everyone else to the map. `/my-listings` stays reachable by direct link so pending applicants can still use the draft flow described below. The link list lives in `resources/js/Components/Account/accountLinks.mjs`.

On screens up to 700px wide, signed-in marketplace and account pages also show a bottom navigation bar for Map, Saved, Requests, Roles and Settings. Settings links to Profile, which retains personal details and the profile photo. Settings contains connected sign-in accounts, password changes and account deletion. Provider dashboard and listing management remain available from the account menu or drawer. The bar accounts for the device's bottom safe area; the map and scrollable account pages reserve space for it.

On signed-in mobile marketplace pages, the header uses the Aadi logo instead of a duplicate Map link; the bottom bar provides the Map tab. The desktop header retains its Map link.

The visitor landing hero uses a Three.js scene that changes with the selected search category, with animated details for spaces, vehicles, and services. It can be rotated with a pointer or keyboard, and ambient motion can be paused; reduced-motion preferences are respected. If WebGL cannot initialize, the existing category illustration is shown instead. Signed-in users continue directly to the map.

Dialog scrollbars are visually hidden while their content remains scrollable. The page scrollbar is also hidden on narrow screens and in installed PWA display modes; scrolling is not disabled.

Explore offers Carousel, List and Swipe views for spaces, vehicles and rakets. Swipe fills the available map area with one listing at a time; horizontal swipes, arrow buttons or keyboard arrow keys browse without saving or dismissing anything. List uses the available height and scrolls through all results on the current page. Use the page controls for additional results and **Map** to return to the map. Listing previews, map selection and comparison remain available, and the view preference is stored in the browser.

The Flutter app is a separate repository and was not changed; it should apply the same rules (open on the map after sign-in, offer dashboard/listing tools only when a provider role is `approved` in the user's `providerRoles`).

## Listing requests

Signed-in customers can request a property viewing with a preferred date and time, or a vehicle rental or service with a preferred start date. Fictional seeded sample listings cannot receive requests. Property owners can specify a minimum stay in months and additional contract rules; vehicle owners can indicate whether a driver can be requested. Vehicle prices are per day; duration, driver availability and any extra charge are agreed directly. Times for viewings use the application's configured timezone, shown in the form and requests page.

Requests appear at `/requests` for both parties. The provider can accept or decline a pending request, and either party can cancel a pending or accepted request. Request submission and acceptance each require consent to share account email and phone with the other party; these contact details are shown only while the request is accepted. A phone may be absent. Notifications are in-app only. A request does not reserve inventory, prevent overlapping requests, collect payment or create a lease. Payment and all further arrangements occur directly between the parties; there is no in-app messaging or online payment. Listing records and their requests are separate from the legacy mobile property-viewing API; Flutter does not yet support these new requests.

Request cards link to the current listing when it is visible to the viewer. Deleted listings and inactive listings not owned by the viewer are marked unavailable; the request history remains accessible.

Everyone can open **Calendar** from account navigation or Requests. Accepted requests appear automatically for both parties on their requested date; pending, cancelled and declined requests do not. Personal events and optional notes can be added, edited and deleted and are private to their creator. Dates and optional times use the application's configured timezone. Date-only requests do not imply a duration. The booking form offers a calendar and native date input, but neither personal events nor requests block dates or guarantee availability. Apply the `calendar_events` database migration before using the calendar.

`/dashboard` is the authenticated user's overview. It counts only that user's non-deleted marketplace listings, including drafts, and shows their latest four listings and provider role statuses. `/admin/dashboard` remains separate. Explore keeps its public navigation. Dashboard, profile, listing management, and listing forms share the account sidebar, header, typography, and page heading.

My listings stays on the account page even when the user has no listings or approved provider roles. Its empty state links to applications instead of redirecting unexpectedly.

## Browser drafts

Pinia manages Explore's search form and comparison selection while Inertia and the URL remain authoritative for applied filters and server results. Comparison selections are cleared when leaving Explore.

Listing create/edit and provider application forms save partial, unsubmitted drafts to browser local storage. Drafts are separated by account, service and (for edits) listing ID. Returning to a form offers **Restore draft** or **Discard draft**; a warning appears if the saved fields or provider application revision changed on the server. Saving the form successfully removes its browser draft. An account change while a form is open requires a reload.

Listing drafts include the title, description, type, price, status and vehicle details, but not location, photos, contract rules, minimum stay or driver availability. Provider drafts include the area, description, applicant type and offering details, but not name, business name, phone, documents, document selections or confirmation. Re-enter omitted fields after restoring. The provider form's server-side **Save draft** remains separate and can retain additional application information.

Browser storage is not encrypted or private from other people using the same browser profile. Free-text fields may contain personal information, so avoid entering sensitive information in drafts on shared devices. Drafts remain on that browser until discarded, successfully saved, or browser storage is cleared. If storage is unavailable, the form reports the problem and can continue without browser drafts; form data then does not survive a reload.

## Contact, locations, and verification files

Profile and provider application phone fields offer international country codes, defaulting to the Philippines. New input accepts digits only, preserving leading zeroes; pasted international numbers can select their country automatically. Existing saved numbers remain unchanged until edited. Choosing a country and entering a number saves an international-format number when it can be parsed; provider application contact information remains separate from the profile contact shared after a listing request is accepted.

Onboarding, profile, provider application, and listing location forms use a city selector backed by the bundled Philippine city directory. Street addresses and service-area details remain manually editable; the directory is served locally, not sent to an external address API. If it cannot load, users can enter the location manually. Existing city values not in the directory remain selectable. Selecting a listing city does not set the map pin, which must be checked separately. In verification forms, select **Add document**, choose its type, then select a file; each file is uploaded when the application is saved or submitted. Finish or cancel an in-progress selection before continuing or saving. Previously saved private documents and newly selected files are shown separately. Removing a saved document is applied only when the application is saved; it can be restored before then.

In-app toasts report server success and validation failures and can be dismissed. Verification email feedback uses the same component, including when a modal is open. They use ordinary web UI and safe-area offsets, not native webview APIs; error details remain available inline.

## Photo contract

The existing canonical marketplace `photos` array is an ordered gallery of up to 12 image URLs. `photos[0]` is the featured photo. Selecting **Set as featured** moves that photo to index zero; removing it promotes the next photo. Zero photos remains valid for existing listings and drafts; the UI shows an illustration fallback.

The web form uploads photos through `/listing-photos`, then sends the ordered array with the usual listing create/update request. Existing marketplace API clients should send the featured URL first. Property records store `photos`; vehicle and service records store `images`; Marketplace maps all three to `photos`. This change does not migrate the database, duplicate image files, or add a competing featured-photo field. Removing a photo from a listing does not delete the underlying uploaded file.

Full details display the ordered gallery with previous/next controls and selectable thumbnails. The featured photo is initially selected. Guest preview modals continue to show only the featured image.

## Verification

Check as a guest, a user without provider roles, and an approved provider. Confirm dashboard ownership isolation, profile changes, empty listing states, mobile sidebar navigation, multiple-photo upload, changing/removing the featured photo, save/reload order, and gallery navigation. Use `php artisan test --filter=AccountDashboardTest` for ownership and photo persistence coverage.
