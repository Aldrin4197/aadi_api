# 🚚🛠️ Aadi Haul & Aadi Raketeer - Mobile API Integration Guide

## Overview

Aadi is now a three-product platform. This guide covers the two new product lines added on top of the existing Space Rentals API:

| Product Line | Brand Name | Role Field | Base Resource |
|---|---|---|---|
| PUV / Vehicle Rental | **Aadi Haul** | `vehicleOwnerStatus` | `vehicle_listings` |
| Skills / Service Booking | **Aadi Raketeer** | `serviceProviderStatus` | `service_listings` |

Both are isolated from Space Rentals at the database level (separate tables, separate models, separate controllers) but share the same `users` table, the same Sanctum bearer-token authentication, and the same `/api/auth/*` endpoints.

**Base URL (local dev):** `http://127.0.0.1:8000/api` (or `http://localhost:8000/api`)
**Auth:** Laravel Sanctum, `Authorization: Bearer {token}`
**JSON style:** camelCase keys, no `{"data": ...}` wrapper, ISO-8601 timestamps, ULID string IDs (26 chars)

---

## 1. Role Model

A single user account can hold multiple roles at once:

- `landlordStatus` — Space Rentals (existing)
- `vehicleOwnerStatus` — Aadi Haul
- `serviceProviderStatus` — Aadi Raketeer

Each status is one of: `none`, `pending`, `approved`, `rejected`.

A user **must be `approved`** for the relevant role before they can create a listing in that product line. Registration alone is not enough.

### Role lifecycle
```
register → apply for role (pending) → approve (approved) → create listings
```

---

## 2. Registration & Login (shared)

These are unchanged from the base API and apply to all three product lines.

### Register
```
POST /api/auth/register
Content-Type: application/json
```
```json
{
  "name": "Juan Dela Cruz",
  "email": "juan@example.com",
  "password": "password123"
}
```
Response `201`:
```json
{
  "accessToken": "2|6KSfYk2W5GkbSBpfqmctP8WuRSVIDXXUwz9WVp8sd79f0746",
  "refreshToken": "2|6KSfYk2W5GkbSBpfqmctP8WuRSVIDXXUwz9WVp8sd79f0746",
  "user": {
    "id": "01m1k5tch17xc5fymrbhpcfht5",
    "email": "juan@example.com",
    "name": "Juan Dela Cruz",
    "landlordStatus": "none",
    "vehicleOwnerStatus": "none",
    "serviceProviderStatus": "none",
    "phone": null,
    "avatarUrl": null,
    "badges": [],
    "ratingAverage": 0,
    "ratingCount": null,
    "trustScore": null
  }
}
```

### Login
```
POST /api/auth/login
```
```json
{ "email": "juan@example.com", "password": "password123" }
```
Returns the same `accessToken` / `user` shape as register.

Store `accessToken` and send it as `Authorization: Bearer {accessToken}` on every request below.

### Get current profile
```
GET /api/me
```
Returns the full `UserResource`, including `vehicleOwner` and `serviceProvider` detail objects once a role has been applied for (see section 5).

---

## 3. Aadi Haul (PUV / Vehicle Rental)

### 3.1 Apply to become a Haul Owner
```
POST /api/me/aadi-haul-application
```
```json
{
  "businessName": "Aadi Haul Test Fleet",
  "licenses": ["LTFRB-TEST-001"],
  "inventory": ["Toyota Hiace", "Isuzu Elf"],
  "certificates": ["ORCR", "Insurance"]
}
```
| Field | Type | Required | Notes |
|---|---|---|---|
| `businessName` | string | yes | max 255 |
| `licenses` | string[] | yes | e.g. LTFRB franchise/permit numbers |
| `inventory` | string[] | no | free-form vehicle list |
| `certificates` | string[] | no | free-form doc list |

Sets `vehicleOwnerStatus` to `pending`. Returns the updated `UserResource`.

### 3.2 Approve the Haul application
```
POST /api/me/aadi-haul-application/approve
```
No request body. This is a **self-approval / demo endpoint** for local and mobile-integration testing — sets `vehicleOwnerStatus` to `approved` for the currently authenticated user.

An admin-driven alternative also exists:
```
POST /api/me/aadi-haul-application/approve/{userId}
```
(Requires an admin-flagged user; not yet wired to a real admin role — see Known Limitations.)

### 3.3 Create a Haul (vehicle) listing
Requires `vehicleOwnerStatus == approved`.
```
POST /api/haul
```
```json
{
  "title": "Aadi Haul Test PUV",
  "description": "18-seater PUV for city and provincial trips.",
  "vehicleType": "PUV",
  "brand": "Toyota",
  "model": "Hiace",
  "year": 2022,
  "plateNumber": "ABC-1234",
  "seatingCapacity": 18,
  "fuelType": "Diesel",
  "transmission": "Manual",
  "features": ["Aircon", "GPS"],
  "pricePerDay": 4500,
  "pricePerWeek": 25000,
  "pricePerMonth": 90000,
  "location": "Quezon City",
  "latitude": 14.6760,
  "longitude": 121.0437,
  "images": [],
  "availableFrom": "2026-09-03",
  "availableTo": "2026-12-31"
}
```

| Field | Type | Required |
|---|---|---|
| `title` | string | yes |
| `description` | string | yes |
| `vehicleType` | string | yes |
| `brand` | string | yes |
| `model` | string | yes |
| `year` | integer | yes |
| `plateNumber` | string | no |
| `seatingCapacity` | integer | no |
| `fuelType` | string | no |
| `transmission` | string | no |
| `features` | string[] | no |
| `pricePerDay` | number | yes |
| `pricePerWeek` | number | no |
| `pricePerMonth` | number | no |
| `location` | string | yes |
| `latitude` / `longitude` | number | no |
| `images` | string[] | no |
| `availableFrom` / `availableTo` | date (`YYYY-MM-DD`) | no |

Response `201` (`VehicleListingResource`):
```json
{
  "id": "01m1k5v3y852375wzg2qrsr4jp",
  "ownerId": "01m1k5v3thm4ce7f1kvn0ek56k",
  "ownerName": "Haul Verify",
  "ownerAvatar": null,
  "title": "Aadi Haul Verify PUV",
  "description": "Modern jeepney for urban transport.",
  "vehicleType": "PUV",
  "brand": "Isuzu",
  "model": "NQR",
  "year": 2023,
  "plateNumber": "XYZ-5678",
  "seatingCapacity": 20,
  "fuelType": "Diesel",
  "transmission": "Manual",
  "features": ["Aircon"],
  "pricePerDay": 5200,
  "pricePerWeek": null,
  "pricePerMonth": null,
  "currency": "PHP",
  "location": "Pasig City",
  "latitude": null,
  "longitude": null,
  "status": "available",
  "images": [],
  "viewsCount": 0,
  "ratingAverage": 0,
  "ratingCount": 0,
  "availableFrom": null,
  "availableTo": null,
  "createdAt": "2026-09-03T08:23:22.000000Z",
  "updatedAt": "2026-09-03T08:23:22.000000Z"
}
```

### 3.4 Haul endpoints (full CRUD + search)
```
GET    /api/haul                 - List / search (paginated, 20/page)
GET    /api/haul/{id}            - Show single listing (increments viewsCount)
POST   /api/haul                 - Create (requires approved vehicleOwnerStatus)
PUT    /api/haul/{id}             - Update (owner only)
DELETE /api/haul/{id}             - Delete (owner only)
```
The generic (non-branded) routes `/api/vehicles*` are identical aliases and remain available for backward compatibility — use whichever naming convention matches your app's UI copy.

**Search / filter query params on `GET /api/haul`:**
| Param | Type | Notes |
|---|---|---|
| `vehicleType` | string | exact match |
| `status` | string | `available`, `rented`, `maintenance`, `inactive` |
| `minPrice` / `maxPrice` | number | filters on `pricePerDay` |
| `latitude` / `longitude` | number | enables Haversine distance sort |
| `radius` | number | km, default 50, only used with lat/lng |

When `latitude`/`longitude` are supplied, results are sorted nearest-first and each item includes a `distance` field (km, rounded to 2 decimals).

---

## 4. Aadi Raketeer (Skills / Service Booking)

### 4.1 Apply to become a Raketeer (Service Provider)
```
POST /api/me/aadi-raketeer-application
```
```json
{
  "businessName": "Aadi Raketeer Test Services",
  "skills": ["Electrical Repair", "Home Maintenance"],
  "certifications": ["TESDA NC II"],
  "serviceArea": "Metro Manila",
  "yearsExperience": 6
}
```
| Field | Type | Required |
|---|---|---|
| `businessName` | string | yes |
| `skills` | string[] | yes |
| `certifications` | string[] | no |
| `serviceArea` | string | yes |
| `yearsExperience` | integer | yes |

Sets `serviceProviderStatus` to `pending`.

### 4.2 Approve the Raketeer application
```
POST /api/me/aadi-raketeer-application/approve
```
No request body. Self-approval/demo endpoint — sets `serviceProviderStatus` to `approved`.

Admin-driven alternative:
```
POST /api/me/aadi-raketeer-application/approve/{userId}
```

### 4.3 Create a Raketeer (service) listing
Requires `serviceProviderStatus == approved`.
```
POST /api/raketeer
```
```json
{
  "title": "Aadi Raketeer Test Electrician",
  "description": "On-call electrical repair and preventive maintenance.",
  "serviceType": "Electrical Repair",
  "serviceCategory": "Home Services",
  "skillsRequired": ["Wiring", "Breaker Replacement"],
  "priceType": "fixed",
  "priceAmount": 1500,
  "serviceArea": "Quezon City",
  "availabilitySchedule": {
    "monday": "08:00-17:00",
    "tuesday": "08:00-17:00"
  },
  "responseTimeMinutes": 60,
  "estimatedDurationMinutes": 180,
  "images": [],
  "certifications": ["TESDA NC II"]
}
```

| Field | Type | Required |
|---|---|---|
| `title` | string | yes |
| `description` | string | yes |
| `serviceType` | string | yes |
| `serviceCategory` | string | no |
| `skillsRequired` | string[] | no |
| `priceType` | enum | yes — `fixed`, `hourly`, `per_project` |
| `priceAmount` | number | yes |
| `serviceArea` | string | yes |
| `availabilitySchedule` | object | no — free-form day → hours map |
| `responseTimeMinutes` | integer | no |
| `estimatedDurationMinutes` | integer | no |
| `images` | string[] | no |
| `certifications` | string[] | no |

Response `201` (`ServiceListingResource`):
```json
{
  "id": "01m1k5tw5a8fj1xq6ndrg63bvg",
  "providerId": "01m1k5tw1n0vyrhykxbyn0k1be",
  "providerName": "Raketeer Provider",
  "providerAvatar": null,
  "title": "Aadi Raketeer Test Electrician",
  "description": "On-call electrical repair and preventive maintenance.",
  "serviceType": "Electrical Repair",
  "serviceCategory": "Home Services",
  "skillsRequired": ["Wiring", "Breaker Replacement"],
  "priceType": "fixed",
  "priceAmount": 1500,
  "currency": "PHP",
  "serviceArea": "Quezon City",
  "availabilitySchedule": { "monday": "08:00-17:00", "tuesday": "08:00-17:00" },
  "responseTimeMinutes": 60,
  "estimatedDurationMinutes": 180,
  "status": "available",
  "images": [],
  "certifications": ["TESDA NC II"],
  "viewsCount": 0,
  "ratingAverage": 0,
  "ratingCount": 0,
  "completionCount": 0,
  "createdAt": "2026-09-03T08:23:14.000000Z",
  "updatedAt": "2026-09-03T08:23:14.000000Z"
}
```

### 4.4 Raketeer endpoints (full CRUD + search)
```
GET    /api/raketeer               - List / search (paginated, 20/page)
GET    /api/raketeer/{id}          - Show single listing (increments viewsCount)
POST   /api/raketeer               - Create (requires approved serviceProviderStatus)
PUT    /api/raketeer/{id}          - Update (owner only)
DELETE /api/raketeer/{id}          - Delete (owner only)
```
The generic routes `/api/services*` are identical aliases.

**Search / filter query params on `GET /api/raketeer`:**
| Param | Type | Notes |
|---|---|---|
| `serviceType` | string | exact match |
| `serviceCategory` | string | exact match |
| `status` | string | `available`, `busy`, `inactive` |
| `priceType` | string | `fixed`, `hourly`, `per_project` |
| `minPrice` / `maxPrice` | number | filters on `priceAmount` |
| `serviceArea` | string | partial/LIKE match |

Note: Raketeer search does not currently support lat/lng distance sorting (service area is a free-text field, not geocoded).

---

## 5. `/api/me` Response Shape With Roles Applied

Once a user has applied for one or both roles, `GET /api/me` (and the `user` object returned by register/login) includes extra detail blocks:

```json
{
  "id": "01m1k5tch17xc5fymrbhpcfht5",
  "email": "haul.owner@example.com",
  "name": "Haul Owner",
  "landlordStatus": "none",
  "vehicleOwnerStatus": "approved",
  "serviceProviderStatus": "pending",
  "phone": null,
  "avatarUrl": null,
  "badges": [],
  "ratingAverage": 0,
  "ratingCount": 0,
  "trustScore": 0,
  "vehicleOwner": {
    "businessName": "Aadi Haul Test Fleet",
    "licenses": ["LTFRB-TEST-001"],
    "inventory": ["Toyota Hiace", "Isuzu Elf"],
    "certificates": ["ORCR", "Insurance"]
  },
  "serviceProvider": {
    "businessName": "Aadi Raketeer Test Services",
    "skills": ["Electrical Repair", "Home Maintenance"],
    "certifications": ["TESDA NC II"],
    "serviceArea": "Metro Manila",
    "yearsExperience": 6
  }
}
```

`vehicleOwner` / `serviceProvider` are omitted entirely (not `null`) when the corresponding status is `none`. Check for key presence, not just truthiness, if your JSON parser treats missing keys differently from `null`.

---

## 6. Error Responses

| Status | When | Example |
|---|---|---|
| `401` | Missing/invalid bearer token | `{"message":"Unauthenticated."}` |
| `403` | Creating a listing without an approved role | `{"message":"You must be an approved vehicle owner to create vehicle listings."}` |
| `403` | Updating/deleting a listing you don't own | `{"message":"You do not have permission to update this listing."}` |
| `400` | Re-applying for a role you already applied for | `{"message":"You have already applied or are already a vehicle owner."}` |
| `400` | Approving with no pending application | `{"message":"No pending Aadi Haul application found."}` |
| `422` | Validation failure | standard Laravel `{"message": "...", "errors": {...}}` |
| `404` | Listing ID not found | standard Laravel not-found JSON |

---

## 7. Quick Reference — All New Endpoints

```
# Role application (shared auth, per-product)
POST   /api/me/aadi-haul-application
POST   /api/me/aadi-haul-application/approve
POST   /api/me/aadi-haul-application/approve/{userId}     (admin)
POST   /api/me/aadi-raketeer-application
POST   /api/me/aadi-raketeer-application/approve
POST   /api/me/aadi-raketeer-application/approve/{userId} (admin)

# Aadi Haul listings
GET    /api/haul
GET    /api/haul/{id}
POST   /api/haul
PUT    /api/haul/{id}
DELETE /api/haul/{id}

# Aadi Raketeer listings
GET    /api/raketeer
GET    /api/raketeer/{id}
POST   /api/raketeer
PUT    /api/raketeer/{id}
DELETE /api/raketeer/{id}

# Generic aliases (identical behavior, non-branded naming)
GET|POST|PUT|DELETE /api/vehicles[/{id}]
GET|POST|PUT|DELETE /api/services[/{id}]
```

---

## 8. cURL Smoke Test (copy/paste)

```bash
BASE=http://127.0.0.1:8000/api

# 1. Register
curl -s -X POST $BASE/auth/register -H 'Content-Type: application/json' \
  -d '{"name":"Haul Owner","email":"haul.demo@example.com","password":"password123"}'

# 2. Grab accessToken from the response, then:
TOKEN="paste-token-here"

# 3. Apply for Aadi Haul
curl -s -X POST $BASE/me/aadi-haul-application \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"businessName":"Demo Fleet","licenses":["LTFRB-0001"],"inventory":["Hiace"],"certificates":["ORCR"]}'

# 4. Approve locally
curl -s -X POST $BASE/me/aadi-haul-application/approve -H "Authorization: Bearer $TOKEN"

# 5. Create a listing
curl -s -X POST $BASE/haul \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"title":"Demo PUV","description":"Test","vehicleType":"PUV","brand":"Toyota","model":"Hiace","year":2022,"pricePerDay":4000,"location":"Manila"}'
```
Repeat the same pattern with `aadi-raketeer-application` and `/api/raketeer` for the Skills Services flow.

---

## 9. Known Limitations / Notes for the App Team

- The `.../approve` (no `{userId}`) endpoints are **self-approval demo endpoints**. They exist so mobile/local integration testing isn't blocked on a real admin panel. Do not ship these to production without gating them behind a real admin check or removing them.
- Raketeer listings do not currently support map/distance-based search — only Haul (vehicle) listings do.
- `currency` on both Haul and Raketeer listings defaults to `"PHP"` and is not currently settable per-request.
- Multi-role accounts are fully supported: a single user can be an approved Landlord, Haul Owner, and Raketeer at the same time.
- Space Rentals endpoints (`/api/listings`) are unchanged and unaffected by this update.
