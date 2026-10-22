# Aadi customer web app

This branch adds the first customer-facing web implementation to the existing Laravel/Inertia application. It uses the existing listings, vehicle_listings and service_listings tables so admin totals and records stay synchronized.

## Start locally

Use PHP 8.3+ (the locked test dependencies may require 8.4), Composer, Node 24, and MySQL. Run `composer install`, `npm ci`, copy `.env.example` to `.env`, configure database/mail/APP_URL, and run:

```sh
php artisan key:generate
php artisan migrate
php artisan storage:link
npm run build
php artisan serve
```

Register a normal account at `/register`. Grant an existing operator administrator access through the server CLI:

```sh
php artisan aadi:admin operator@example.com
```

There are no default administrator credentials. The new admin flag defaults to false, including for existing accounts. Promote your existing administrator before using the protected dashboard.

## Implemented flows

- `/` and `/explore`: public discovery for Spaces, Vehicles and Rakets, synchronized map pins/list selection, title/city search, type and price filters, current-location search, map-center/radius search, pagination and mobile list/map switching.
- `/offers/{service}/{id}`: listing details, photo gallery, location and owner name. Only owners/admins may inspect unpublished records.
- `/login`, `/register`, existing password-reset and profile routes: session authentication using the existing Laravel flow, with Aadi branding.
- `/my-listings`: a separate provider workspace with photo-led rows, filters, sorting and a linked map. Users without approved roles go to `/become-provider`.
- Create/edit forms: shared canonical fields, vehicle details, map location selection, image uploads, drafts, publishing, editing and confirmed deletion.
- `/admin/approvals`: administrator-only approve/reject queue. The existing admin tables continue displaying the same records.

Every account begins as a User. Users apply independently as landlords/property merchants, vehicle owners, or freelance service providers. Applications collect display/business name, phone, area, offering description, supporting documents and confirmation. Documents use private storage and authenticated owner/admin downloads. Approval is required to create or edit listings for that role, including drafts. Existing approved accounts retain access. Admins can edit records; publication still checks the owner's status. Rejections include review notes and allow resubmission. Existing pending requests without evidence must complete the application before approval. A merchant is a business acting in one of these three provider roles; this does not add a separate product-sales marketplace.

## Shared API

The web controller and versioned API share `App\Services\Marketplace` for validation, data mapping, reads and mutations.

| Method | Path | Access |
| --- | --- | --- |
| GET | `/api/v1/offers?service=spaces` | Public published results |
| GET | `/api/v1/offers/{service}/{id}` | Public published / owner or admin private |
| POST | `/api/v1/offers/{service}` | Sanctum account |
| PUT / DELETE | `/api/v1/offers/{service}/{id}` | Owner or admin |
| POST | `/api/v1/photos` | Sanctum account |

Service values: `spaces`, `haul`, `raketeer`. Listings return `id`, `service`, `ownerId`, `ownerName`, `title`, `description`, `type`, `price`, `priceUnit`, `currency`, nested `location`, `photos`, `status`, `details`, `createdAt`. Collections return `{data, meta: {total, currentPage, lastPage}}`; single results return `{data}`. Money is PHP major units. Create/update accept editable fields only; ownership is server assigned. Vehicles require `details.brand`, `details.model`, `details.year`. Writes support `draft`, `active`, and `reserved`; Spaces and Vehicles also support `rented`, and Spaces supports `archived`. Vehicle maintenance and Raketeer busy map to canonical `reserved`; inactive maps to `draft`.

Search supports `q`, `type`, `minPrice`, `maxPrice`, `lat`, `lng`, `radiusKm` (1–200), and `page`. Coordinates must be supplied together. With coordinates, search is distance sorted and excludes legacy records without coordinates. Without coordinates, it searches all published records, newest first. Text search matches existing title/city/address data; it is not a geocoding service. Move the map or use GPS to choose an arbitrary geographic center. Maps show the current page's results.

## Map and media configuration

Default tiles use OpenStreetMap with attribution. For production configure a tile provider suitable for expected traffic using `VITE_MAP_TILE_URL` and optionally `VITE_MAP_ATTRIBUTION`, then rebuild. Tile attribution must match the provider. Geolocation requires HTTPS except localhost. The UI handles denied location and failed tile loading without blocking the result list.

Photos are JPEG/PNG/WebP up to 5 MB each, 12 per listing. Set APP_URL correctly; the public storage disk must serve absolute URLs. Uploads require a session/CSRF or Sanctum authentication. Removing photos from a listing does not delete the uploaded object: orphan-media cleanup is follow-up work.

## Boundaries and next work

This is the first web implementation, not complete mobile feature parity. Follow-up: saved listings/searches, viewing requests, notifications, messaging/booking, mandatory email verification and Google/Facebook login. Flutter remains unchanged. Legacy unversioned APIs remain for compatibility; they still need full contract migration and authorization review before production mobile integration. Do not treat the versioned API as a drop-in replacement without updating Flutter repositories and serializers.

## Verification

`npm run build` checks TypeScript and generates the production bundle. `php artisan test` covers the new contract, CRUD for all services, draft visibility, ownership, publication approval, admin boundaries, nearby search and uploads. GitHub Actions runs both. Historical MySQL-specific ULID migrations now skip SQLite, permitting test database creation; MySQL behavior remains unchanged.

Local verification: production frontend build and static parsing of 18 changed PHP files passed. The first GitHub Actions job failed before executing any steps, with no retrievable log; backend runtime tests and browser QA remain unverified.


## Provider workspace update

Run `php artisan migrate` for the provider_applications table. Login returns to browsing. The same account can hold multiple independently approved roles. The new provider shell is used by My listings, listing forms, role selection, application status/forms and review pages. It has an explicit switch back to customer browsing. Listing previews and public discovery keep the customer shell.

Private uploads accept up to five JPEG/PNG/WebP/PDF files, 5 MB per file. Resubmission replaces the previous documents after a successful database transaction. Files are not added to the public storage symlink. Only administrators and the applicant may download them. Apply the deployment's normal backup and retention controls to private application storage.

Validation for this update: frontend production build and static PHP parsing. The new feature tests cover application validation, independent roles, approval, rejection/resubmission, private downloads and duplicate submissions. Backend execution remains unverified in this environment; GitHub Actions was previously blocked by an account billing lock. Browser visual QA has not been run.

## Public homepage and sign-in flow

The customer entry point is `/`: visitors can search properties, vehicle rentals, and rakets, filter prices/types/location, and browse results alongside a map without an account. Opening a listing card or clicking a map marker requests the protected `/offers/{service}/{id}` page and prompts login, with registration available on that screen. Both successful login and registration return to the selected listing using the server session's intended URL. Ordinary Users can view active listing details without applying as providers. The existing admin dashboard stays at `/admin/dashboard`. This gate applies to web detail routes; the mobile API contract is unchanged.

## Flutter-themed map homepage

The customer shell uses Flutter's bundled Quicksand font, teal #1FA6A0, dark teal #0B3A38, orange #F59E42, neutral surfaces, and rounded controls. The homepage map fills the width with search/filter controls and a horizontally scrollable listing rail over it. Markers include billing units. Selecting a marker previews/highlights its card; View/title links open the existing protected detail route.

Nearby search uses the existing server radius filter and distance ordering. The map and rail display the same current page (up to 30 records), with an explicit count and pagination; listings without coordinates remain in the rail. Search near map center applies the chosen radius, not viewport bounds. Comparison retains up to three listing summaries during Inertia searches and displays original rates/units without inventing normalized prices. Distances are straight-line estimates from the applied search center. Admin routes and mobile API contracts are unchanged.

## Homepage landing content

The public `/` page combines the full-width map with scrollable landing sections: an Aadi introduction, Spaces / Vehicles / Rakets, how discovery works, provider applications, common questions, and a navigation footer. Category buttons update the existing search and return focus to its input. Header anchors and a keyboard skip link let visitors reach the informational sections directly. Quicksand and the Flutter teal/orange colors continue throughout.

Mouse-wheel zoom is disabled on the homepage map so scrolling can reach the content below; zoom buttons and touch gestures remain available. `/explore` retains the map-only view. Guest discovery and comparison remain public, listing details still require authentication, and provider calls to action use the existing application/workspace routes. The landing copy describes the current offering without advertising online booking or payments. The old repository `Welcome.vue` contains Laravel's default welcome screen, so the new sections use Aadi-specific copy rather than reusing that template.

## Tailwind CSS 4

The Vue 3/Inertia app and Blade admin pages use Tailwind CSS 4 through `@tailwindcss/vite`. The Aadi teal/orange palette, Quicksand font stack, Blade brand aliases, source paths, and forms plugin are configured in `resources/css/app.css`. There is no longer a JavaScript Tailwind configuration or a PostCSS configuration; do not register `tailwindcss` directly as a PostCSS plugin.

After pulling this upgrade, stop any running Vite process and use Node 24 to install the committed dependency versions:

```sh
npm ci
npm run build
npm run dev
```

Use `npm run dev` for local development alongside the Laravel server. Production deployments should run `npm run build` and serve the generated `public/build` assets. Blade admin layouts now load that compiled stylesheet through `@vite`, so they also require built assets or a running Vite development server.

Existing utility names have been migrated for Tailwind 4, with compatibility defaults for borders, placeholders, focus rings, and button cursors. Tailwind 4 targets Safari 16.4+, Chrome 111+, and Firefox 128+; see the [official upgrade guide](https://tailwindcss.com/docs/upgrade-guide). Vue 3 supports either Tailwind major version; this project now standardizes on version 4.

### Troubleshooting local builds

Both npm scripts explicitly load `vite.config.js`, which registers the Vue SFC and Tailwind Vite plugins. Inline `css.postcss` configuration prevents Vite from loading leftover PostCSS configuration files in this checkout or its parent directories. Add any future PostCSS plugins in that inline configuration.

An `Unexpected JSX expression` error at the opening `<script setup>` of a `.vue` file means the raw Vue component reached the JavaScript parser without the required transformation. Enabling JSX is not a fix for a Vue single-file component. Confirm your checkout includes the current Vite config and package scripts, then stop the development server and run these commands from the directory containing `artisan` and `package.json`:

```powershell
git status --short
git branch --show-current
npm ci
npm run build
npm run dev -- --force
```

The build banner should show `vite build --config vite.config.js`. If it does not, you are running older package scripts or a different project directory. If the failure persists, capture `node --version`, `npm ls vite @vitejs/plugin-vue tailwindcss @tailwindcss/vite`, and `npm run build -- --debug`. The frontend CI checks run on Linux and Windows, including a regression fixture that compiles a TypeScript Vue component, scoped styles, and Aadi utilities with a stale PostCSS config present.

## Restored illustrated landing and minimal map

The homepage restores the original Aadi rental explorer's “Make room for possibility” hero and original SVG artwork (retained prototype commit a1c1127), above the full-width discovery map. Category sections, provider information and FAQs explain the current offering. Missing or failed listing photos use labelled illustrations; actual photos are preserved. No demo listings are inserted. Quicksand and the Aadi teal/orange palette remain unchanged.

The map uses compact rounded controls, teal price markers, a search-center dot and a reset-view control. Set `VITE_MAPBOX_TOKEN` to a public, read-only, URL-restricted Mapbox token and rebuild to use the same `mapbox/light-v11` raster style as Flutter (512px tiles with zoom offset -1). Without a token, the existing OpenStreetMap tiles remain available with a muted presentation. `VITE_MAP_TILE_URL` overrides this provider; set its matching `VITE_MAP_ATTRIBUTION`. These Vite values ship to browsers; never use secret tokens. This is a Vue adaptation of the minimal mapcn appearance, not the React mapcn package.

## Sample marketplace listings

To populate a development or demo database with the current marketplace tables, run:

```sh
php artisan migrate
php artisan db:seed --class=MarketplaceDemoSeeder
```

This explicit seeder adds five spaces, five vehicles and five rakets around Catbalogan, Samar, with different prices and types for filters and comparison. Titles and descriptions identify fictional samples. Empty photo arrays intentionally display the category illustrations added with the restored homepage, labelled as illustrations. No external image downloads are needed.

It creates one non-admin sample provider (`marketplace-demo@aadi.example`) with all three roles approved. Its password is `password`, and rerunning resets that account to this password. Stable ULIDs preserve listing edits and soft-deleted vehicles/services. A hard-deleted sample can be recreated. The operation runs in a transaction.

Use this named seeder rather than the older `AadiSeeder`, which uses the legacy mixed-category table. `DatabaseSeeder` creates the admin and basic test account: samples are opt-in and are not installed automatically by ordinary migrations. The samples are publicly visible once seeded, so use a development/demo database when testing.

Regression coverage: `php artisan test --filter=MarketplaceDemoSeederTest` checks public API visibility, billing units, stable identifiers and repeat-run behavior.

### Demo users with their own listings

To test login and separate provider workspaces, run this named seeder against your development/demo database:

```bash
php artisan migrate
php artisan db:seed --class=MarketplaceUsersSeeder
```

It adds seven fictional non-admin accounts with three listings each (21 total: seven spaces, seven vehicles and seven rakets):

| Email | Approved roles | Location | Listings |
| --- | --- | --- | --- |
| maria@users.aadi.example | Landlord | Catbalogan | 3 properties |
| pedro@users.aadi.example | Landlord | Calbayog | 3 properties |
| carlo@users.aadi.example | Vehicle owner | Catbalogan | 3 vehicles |
| ana@users.aadi.example | Vehicle owner | Calbayog | 3 vehicles |
| luis@users.aadi.example | Service provider | Catbalogan | 3 rakets |
| leah@users.aadi.example | Service provider | Tacloban | 3 rakets |
| alex@users.aadi.example | All three roles | Cebu City | 1 property, 1 vehicle, 1 raket |

All seven demo accounts use `password`. Sign in at `/login`; `/my-listings` shows each account's own listings. Rerunning explicitly resets these demo passwords to `password` while preserving names, roles, listing edits and soft deletions. The former `AADI_DEMO_PASSWORD` setting is no longer used. Hard-deleted samples are recreated if the account still has the relevant approved role.

Users have verified demo email timestamps and approved provider roles for testing; no verification emails are sent, no admin privileges or trust badges are granted, and no fake reviews are added. Names use `[Demo]`, titles use `[Sample]`, and descriptions identify fictional offers. Coordinates and rates are illustrative. Existing category illustrations appear when photos are empty.

This seeder uses its own stable ULIDs and can coexist with `MarketplaceDemoSeeder`. Neither seeder runs automatically in `DatabaseSeeder`. An existing unrelated account with a reserved demo email causes the transaction to roll back instead of adopting or modifying that account. Do not use `migrate:fresh` to add samples to an existing database.

Regression coverage: `php artisan test --filter=MarketplaceUsersSeederTest` checks per-user ownership, public visibility, password hashing, login/workspace isolation, repeat-run preservation and email-conflict rollback.

## Map comparison and location selection

The map now follows the reference explorer's broad search panel, bottom listing rail, thumbnail selection dock and photo-led “Find your best fit” comparison dialog. Select two or three listings, remove individual columns, or clear the dock. The table scrolls horizontally on small screens and retains the original billing unit for each rate. Features/skills, seating capacity or estimated service duration come from existing records; unspecified inclusions or exclusions are not fabricated. The additive `comparison` object is shared by the web and versioned offers API. Comparisons retain selection-time summaries while browsing and identify that limitation in the dialog.

The city picker searches a bundled GeoNames directory of 1,647 Philippine cities, municipalities and the national capital independently of marketplace records. Typing a city or province produces up to 20 matching locations with province/region labels. Select a result to apply its coordinates and the current radius, including when that city has zero listings. GPS and map-center searches remain available. “All locations” removes the geographic restriction; keyword and price/type filters remain independent. The selected label, coordinates and radius stay in the URL. `/explore` searches remain on `/explore`.

The directory is downloaded only when the picker opens, then searched locally. No geocoding credentials, database seeding or external request per user search is needed. Deploy `public/data/ph-cities.json` along with the frontend build. Search supports accents and aliases; same-name municipalities show their province/region. Failed loads show Retry; unmatched searches show an empty result message rather than sample locations. Place coordinates are search centers rather than municipal boundaries. Attribution and refresh instructions are in `public/data/README.md`; regenerate with `python3 scripts/build-city-directory.py`.

## Homepage discovery sections

The public homepage includes featured finds, community reviews and app download sections, with section shortcuts and footer links. `/explore` remains the dedicated map view and `/admin/dashboard` is unchanged.

Featured finds show the two latest published records per service, using the same marketplace DTO as the offers API. Draft, unavailable and soft-deleted records are excluded. Space records must be space rentals. These are recent listings, not paid placements or a claim of popularity. The section is independent of the map's current filters. Category buttons filter the six featured records locally. Listing links retain the existing authentication gate. Missing photos use the labeled illustrations already used by the map; no invented products or prices are added.

Store links are configured with `AADI_GOOGLE_PLAY_URL` and `AADI_APP_STORE_URL`. Only HTTPS URLs on `play.google.com` and `apps.apple.com` respectively are accepted. Leave these empty until the real Aadi listings are published. The section then shows a non-clickable “Coming soon” state and a working web-app link. The phone artwork is decorative, not a screenshot of a released app. Refresh Laravel's configuration cache after changing environment values.

Add approved, authentic testimonial quotes to `config/aadi_landing.php` under `reviews`. Required fields are `published` (boolean true), `quote`, `name`, and `source_url` (public HTTPS original review); `context` is optional. Obtain permission to reuse the quote and display name. Only the first three published entries with complete fields are shown; no star scores are inferred. Without supplied reviews, visitors see a community invitation explaining that stories are coming soon. This is curated homepage content, not a review submission or moderation system; no fictional testimonials are included.

Validation: `npm run build`, `npm run test:frontend`, and `php artisan test --filter=LandingPageTest`. The PHP test covers publication visibility, independence from map filters, homepage-only props, empty states and configured external links.


### Seeded administrator login

Run `php artisan db:seed` to create or update `admin@aadi.test` with password `password` and `is_admin=true`. Sign in at `/admin/login`, then open `/admin/dashboard`. The basic `test@example.com` account also uses `password`. Passwords are stored as hashes. These predictable credentials are for development/demo databases.

To reset all current demo logins without deleting listings:

```bash
php artisan db:seed
php artisan db:seed --class=MarketplaceUsersSeeder
php artisan db:seed --class=MarketplaceDemoSeeder
```
