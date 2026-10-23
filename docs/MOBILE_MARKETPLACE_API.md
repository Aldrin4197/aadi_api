# Aadi marketplace API for web and Flutter

Base path: `/api/v1`. Use this contract for all three services: `spaces`, `haul`, and `raketeer`. These endpoints share the web application's models, permissions, provider applications, and inquiry records. The older `/api/listings`, `/api/vehicles`, and `/api/services` routes remain authenticated legacy endpoints; do not mix their DTOs with the versioned contract.

## Authentication and preview access

Existing authentication endpoints are `POST /api/auth/register` (`name`, `email`, `password`), `POST /api/auth/login` (`email`, `password`), `GET /api/me`, and `POST /api/auth/logout`. Send the returned `accessToken` as `Authorization: Bearer <token>` and `Accept: application/json`. Logout revokes the current token. The legacy response also contains `refreshToken`, but it is the same token: there is no refresh-token endpoint. Do not implement a refresh flow around that field.

`GET /offers` and `GET /offers/{service}/{id}` work without a token. Guests receive only the preview allowlist: ID, service, title, type, rate/unit/currency, status, cover photo, comparison highlights, and general location. Coordinates are rounded to two decimal places; exact address, description, remaining photos, owner identity, and specifications are omitted server-side. `location.approximate` is true. The landing-page featured listings also use the preview allowlist.

A valid token unlocks full listing data. Invalid/revoked tokens return 401, rather than silently returning an authenticated response. A regular signed-in user can read any published listing. Draft and other non-public records are visible only to their owner or an administrator. Unknown/unpublished guest requests return 404. Non-owner attempts to read unpublished records return 403. Read responses use `Cache-Control: private, no-store` and vary by authorization/cookie. Clear full details and comparisons on logout in mobile as well.

Every customer offer response has `access`:

```json
{"level":"preview","canInquire":false,"canManage":false,"requiresLogin":true}
```

Authenticated responses have `level: "full"`. `canInquire` is true only for published listings belonging to another user. `canManage` identifies owner/admin access; posting and publication still require the provider checks enforced by the server. A preview is intentionally **not** the old Flutter `Listing` model: its mandatory owner and description fields are missing. Use separate preview/full DTOs or nullable fields, and gate detail widgets on `access.level`.

## Discovery and CRUD

| Method and path | Access | Result |
| --- | --- | --- |
| GET `/catalog` | Public | Valid type values and rate units per service |
| GET `/offers` | Public; optional token | Paginated published offers |
| GET `/offers/{service}/{id}` | Public; optional token | Preview or full offer under `data` |
| GET `/me/offers?service=spaces` | Token | Only this user's listings, including drafts |
| POST `/offers/{service}` | Approved provider/admin | Create, 201 |
| PUT `/offers/{service}/{id}` | Owner/admin with provider checks | Update, 200 |
| DELETE `/offers/{service}/{id}` | Owner/admin | Delete, 204 |
| POST `/photos` | Token | Multipart `photo`, returns `url`, 201 |

Search parameters: `service` (defaults to spaces), `q`, `type`, `lat` and `lng` together, `radiusKm` (1–200; default 10 with coordinates), `minPrice`, `maxPrice`, `page`. `locationName` is a UI label, not a city ID filter: choose a city and send its coordinates. Search uses exact stored coordinates internally, even when preview pins are approximate. Distances calculated by the guest client from those pins are estimates.

Both list responses contain `data: []` and `meta: {total,currentPage,lastPage}`, with 30 records per page. `/me/offers` accepts `service` and `page`; other search filters are not applied there. The service is part of an offer's identity: keep `{service,id}` together for navigation and comparison.

Create/update example for a vehicle:

```json
{
  "title": "Van for local trips",
  "description": "Comfortable van available for local rentals.",
  "type": "van",
  "price": 2500,
  "priceUnit": "day",
  "status": "active",
  "location": {"lat": 11.7753, "lng": 124.8861, "address": "Catbalogan City", "city": "Catbalogan"},
  "photos": [],
  "details": {"brand": "Toyota", "model": "Hiace", "year": 2024}
}
```

PUT uses the same complete payload. Photo URLs come from the upload endpoint; maximum 12 photos, JPEG/PNG/WebP, 5 MB each. Spaces are monthly, haul daily, rakets hourly or per job. `details.brand/model/year` are required for haul. `status` accepts draft/active/reserved for all; rented for spaces/haul; archived for spaces. Legacy stored vehicle/service statuses are mapped by the server. User/owner IDs in request bodies never determine ownership.

## Provider applications

| Method and path | Result |
| --- | --- |
| GET `/me/provider-roles` | `data.statuses` for spaces, haul, raketeer |
| GET `/me/provider-applications/{service}` | Service, role status, and this user's application or null |
| POST `/me/provider-applications/{service}` | Submit multipart application, 201 |
| GET `/provider-documents/{id}/{index}` | Private download, owner or admin only |

All require a token. Submit `displayName`, `phone`, `area`, `description` (20–3000 characters), `confirmed=1`, and 1–5 `documents[]` files (JPG/PNG/WebP/PDF, max 5 MB each). Document URLs use the API path and still need the bearer token. Do not load them as public image URLs or expose the token in a URL.

Applications enter `pending`; rejected applications can be resubmitted. Existing pending/approved applications reject duplicate submission with 422. The existing admin dashboard reviews the same records and updates the same role statuses. A role approval does not approve other services. No self-approval endpoint is added.

## Inquiries and replies

| Method and path | Result |
| --- | --- |
| POST `/offers/{service}/{id}/inquiries` | Send an inquiry, 201; body `{"message":"Is this available next week?"}` |
| GET `/me/inquiries` | Sent and received conversations, 20 per page |
| GET `/inquiries/{id}` | Conversation and paginated messages under `data` |
| POST `/inquiries/{id}/messages` | Reply, 201; body `{"message":"Yes, it is available."}` |

All require a token. Guests receive 401. Only the customer and listing owner can read/reply; unrelated accounts receive 404. Starting an inquiry on one's own listing returns 403; unpublished/deleted listings return 404. Start messages are 5–3000 characters, replies 1–3000. New inquiries are limited to 6 requests/minute; replies to 20/minute, with 429 on excess.

A unique conversation is kept per service/listing/customer. Starting again appends a message to that conversation. Requests do not send email, reserve inventory, create bookings, or charge payments. Conversations persist after a listing is deleted, so participants can finish their discussion; new inquiries are blocked. If ownership changes, the old conversation remains private to its original participants and starting through that listing returns 409.

The detail response contains `inquiry`, `listingAvailable`, and `messages: {data,meta}`. Page 1 contains the latest 30 messages in chronological order; page 2 contains the next older set. Each message has `id`, `senderId`, `body`, and `createdAt`. After sending, reload page 1. There is no realtime push, unread counter, or polling requirement in this release. Refresh the inbox to receive new replies.

Example flow:

1. Browse `/offers?service=spaces` without a token and open a preview.
2. Log in through `/api/auth/login`; store the access token securely.
3. Refetch the selected offer with the token to obtain full details.
4. POST an inquiry; use the returned conversation `data.id` to open the thread.
5. Provider fetches `/me/inquiries` and replies through `/inquiries/{id}/messages`.

Validation failures return Laravel's `message` and field-keyed `errors` with 422. Handle 401 by requesting login, 403 as permission denial, 404 as unavailable, and 429 by waiting before retrying. Do not display success until the POST succeeds.

## Installation and integration status

Run `php artisan migrate` to add `marketplace_inquiries` and `inquiry_messages`. This migration does not clear listings/users. Build the web assets with `npm run build`.

The Flutter main branch inspected for this work (`8366faf1ff7868d14fea7e8da16ad2d15b1adb14`) still returns mock repositories even when `useMockData` is false. This change completes the backend preview/inquiry flow and supplies mobile listing CRUD and provider-application endpoints; it does not switch the Flutter app to networking. Flutter still needs remote repositories, secure token storage, DTO mapping, preview gating, and inquiry screens. Google/Facebook sign-in and the complete mobile email-verification flow are not implemented by this change.

Regression tests: `MarketplaceAccessTest`, `MarketplaceInquiryTest`, `MarketplaceTest`, `ProviderApplicationTest`, and landing/seeder tests. Browser fixtures exercise the guest/full detail layouts and inquiry inbox/thread without representing a live server test.
