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
- `/my-listings`: private listing management, application submission and provider approval status.
- Create/edit forms: shared canonical fields, vehicle details, map location selection, image uploads, drafts, publishing, editing and confirmed deletion.
- `/admin/approvals`: administrator-only approve/reject queue. The existing admin tables continue displaying the same records.

Applicants submit an account-level review request in this initial version. Document collection and verification evidence remain follow-up work. Approval is required to publish, but not to save drafts. Admins can edit records through their detail-page link; publication still checks the owner's provider status.

## Shared API

The web controller and versioned API share `App\Services\Marketplace` for validation, data mapping, reads and mutations.

| Method | Path | Access |
| --- | --- | --- |
| GET | `/api/v1/offers?service=spaces` | Public published results |
| GET | `/api/v1/offers/{service}/{id}` | Public published / owner or admin private |
| POST | `/api/v1/offers/{service}` | Sanctum account |
| PUT / DELETE | `/api/v1/offers/{service}/{id}` | Owner or admin |
| POST | `/api/v1/photos` | Sanctum account |

Service values: `spaces`, `haul`, `raketeer`. Listings return `id`, `service`, `ownerId`, `ownerName`, `title`, `description`, `type`, `price`, `priceUnit`, `currency`, nested `location`, `photos`, `status`, `details`, `createdAt`. Collections return `{data, meta: {total, currentPage, lastPage}}`; single results return `{data}`. Money is PHP major units. Create/update accept editable fields only; ownership is server assigned. Vehicles require `details.brand`, `details.model`, `details.year`. Writes support `draft` and `active`; underlying specialized states remain preserved on reads but saving the form intentionally selects draft or published.

Search supports `q`, `type`, `minPrice`, `maxPrice`, `lat`, `lng`, `radiusKm` (1–200), and `page`. Coordinates must be supplied together. With coordinates, search is distance sorted and excludes legacy records without coordinates. Without coordinates, it searches all published records, newest first. Text search matches existing title/city/address data; it is not a geocoding service. Move the map or use GPS to choose an arbitrary geographic center. Maps show the current page's results.

## Map and media configuration

Default tiles use OpenStreetMap with attribution. For production configure a tile provider suitable for expected traffic using `VITE_MAP_TILE_URL` and optionally `VITE_MAP_ATTRIBUTION`, then rebuild. Tile attribution must match the provider. Geolocation requires HTTPS except localhost. The UI handles denied location and failed tile loading without blocking the result list.

Photos are JPEG/PNG/WebP up to 5 MB each, 12 per listing. Set APP_URL correctly; the public storage disk must serve absolute URLs. Uploads require a session/CSRF or Sanctum authentication. Removing photos from a listing does not delete the uploaded object: orphan-media cleanup is follow-up work.

## Boundaries and next work

This is the first web implementation, not complete mobile feature parity. Follow-up: saved listings/searches, viewing requests, notifications, messaging/booking, document-backed provider verification, mandatory email verification and Google/Facebook login. Flutter remains unchanged. Legacy unversioned APIs remain for compatibility; they still need full contract migration and authorization review before production mobile integration. Do not treat the versioned API as a drop-in replacement without updating Flutter repositories and serializers.

## Verification

`npm run build` checks TypeScript and generates the production bundle. `php artisan test` covers the new contract, CRUD for all services, draft visibility, ownership, publication approval, admin boundaries, nearby search and uploads. GitHub Actions runs both. Historical MySQL-specific ULID migrations now skip SQLite, permitting test database creation; MySQL behavior remains unchanged.
