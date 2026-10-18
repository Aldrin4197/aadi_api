# Aadi API Implementation Status

## ✅ FULLY IMPLEMENTED

All endpoints from API_CONTRACT.md are already implemented and working.

### API Features

✅ **camelCase JSON** - All API Resources output camelCase  
✅ **Laravel Sanctum** - Bearer token authentication  
✅ **Distance-based search** - Haversine formula with sorting  
✅ **ISO-8601 dates** - All timestamps in ISO format  
✅ **Enum values** - Exact string matching (boardingHouse, etc.)  
✅ **Authorization** - Owner-only for listing CRUD  
✅ **Error messages** - User-safe messages on all errors  

---

## Endpoints

### Auth (`/api/auth/*`)
- ✅ `POST /auth/register` - Create account, returns AuthSession
- ✅ `POST /auth/login` - Login, returns AuthSession  
- ✅ `POST /auth/logout` - Revoke token (requires auth)

### Me (`/api/me`)
- ✅ `GET /me` - Get current user with badges
- ✅ `POST /me/landlord-application` - Apply for landlord status
- ✅ `POST /me/landlord-application/approve` - Demo self-approval

### Listings (`/api/listings`)
- ✅ `GET /listings` - Search with distance (Haversine), filters by type/price/status
- ✅ `GET /listings/{id}` - Get single listing (tracks view)
- ✅ `POST /listings` - Create listing (auth required)
- ✅ `PUT /listings/{id}` - Update listing (owner only)
- ✅ `DELETE /listings/{id}` - Delete listing (owner only)
- ✅ `GET /listings/{id}/analytics` - Get views, favorites, conversion rate

### Users (`/api/users`)
- ✅ `GET /users/{id}` - Get user profile (public)
- ✅ `GET /users/{ownerId}/listings` - Get listings by owner

### Viewing Requests (`/api/me/viewings`)
- ✅ `GET /me/viewings` - List all viewing requests (tenant or owner)
- ✅ `POST /me/viewings` - Create viewing request
- ✅ `PUT /me/viewings/{id}` - Update status (authorize by role)
- ✅ `DELETE /me/viewings/{id}` - Cancel viewing request

### Favorites (`/api/me/favorites`)
- ✅ `GET /me/favorites` - List favorited listing IDs
- ✅ `PUT /me/favorites/{listingId}` - Add to favorites
- ✅ `DELETE /me/favorites/{listingId}` - Remove from favorites

### Saved Searches (`/api/me/saved-searches`)
- ✅ `GET /me/saved-searches` - List saved searches
- ✅ `POST /me/saved-searches` - Save a search
- ✅ `DELETE /me/saved-searches/{id}` - Delete saved search

---

## Database Schema

All tables match API_CONTRACT.md:

- ✅ `users` - ULID IDs, landlord_status enum, trust metrics
- ✅ `trust_badges` - Badge types and statuses
- ✅ `listings` - ULID IDs, lat/lng, enums, JSON columns
- ✅ `viewing_requests` - Status workflow, snapshots
- ✅ `favorites` - User-listing pivot
- ✅ `saved_searches` - JSON query storage
- ✅ `listing_views` - Analytics tracking

---

## API Resources (camelCase output)

All resources convert snake_case DB columns to camelCase JSON:

```php
// UserResource
{
  "id": "ulid",
  "email": "user@example.com",
  "name": "User Name",
  "landlordStatus": "approved",  // ← camelCase
  "phone": "+63...",
  "avatarUrl": "https://...",    // ← camelCase
  "badges": [...],
  "ratingAverage": 4.5,          // ← camelCase
  "ratingCount": 10,             // ← camelCase
  "trustScore": 85               // ← camelCase
}

// ListingResource
{
  "id": "ulid",
  "ownerId": "ulid",             // ← camelCase
  "ownerName": "Owner",          // ← camelCase
  "title": "...",
  "type": "boardingHouse",       // ← exact enum string
  "location": {
    "lat": 14.6042,
    "lng": 120.9822,
    "address": "...",
    "city": "..."
  },
  "propertyVerification": "verified",  // ← camelCase
  "ratingAverage": 4.6,          // ← camelCase
  "createdAt": "2026-08-10T12:00:00.000Z"  // ← ISO-8601
}
```

---

## Testing the API

### 1. Register a user
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "tenant@aadi.test",
    "password": "password123",
    "name": "Test Tenant"
  }'
```

**Response:**
```json
{
  "accessToken": "1|abc...",
  "refreshToken": "1|abc...",
  "user": {
    "id": "...",
    "email": "tenant@aadi.test",
    "name": "Test Tenant",
    "landlordStatus": "none",
    "badges": [],
    "ratingAverage": 0,
    "ratingCount": 0,
    "trustScore": 0
  }
}
```

### 2. Apply for landlord status
```bash
curl -X POST http://127.0.0.1:8000/api/me/landlord-application \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 3. Approve landlord (demo)
```bash
curl -X POST http://127.0.0.1:8000/api/me/landlord-application/approve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 4. Create a listing
```bash
curl -X POST http://127.0.0.1:8000/api/listings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cozy Bedspace near UST",
    "description": "Perfect for students",
    "type": "bedspace",
    "price": 3500,
    "lat": 14.6091,
    "lng": 120.9897,
    "address": "España Blvd",
    "city": "Manila",
    "photos": ["https://example.com/photo.jpg"],
    "amenities": ["Wi-Fi", "Aircon", "Security"],
    "status": "active"
  }'
```

### 5. Search listings
```bash
curl "http://127.0.0.1:8000/api/listings?lat=14.6042&lng=120.9822&radiusKm=5&type=bedspace&maxPrice=5000" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Returns:** Array of listings sorted by distance (Haversine)

### 6. Get listing analytics
```bash
curl http://127.0.0.1:8000/api/listings/{id}/analytics \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "views": 312,
  "interested": 28,
  "viewingRequests": 4,
  "inquiries": 0,
  "conversionRate": 0.0128
}
```

---

## Admin Panel (Separate System)

Admin routes use **web session auth** (not Sanctum):

- ✅ `GET /admin/login` - Admin login page (Inertia)
- ✅ `POST /admin/login` - Admin authentication
- ✅ `GET /admin/dashboard` - Admin dashboard with stats
- ✅ `GET /admin/users` - User management (placeholder)
- ✅ `GET /admin/listings` - Listing management (placeholder)
- ✅ `GET /admin/tickets` - Support tickets (placeholder)
- ✅ `GET /admin/analytics` - Analytics (placeholder)

---

## Next Steps for Flutter Integration

Per API_CONTRACT.md Section 10:

1. **Configure app** - Set `apiBaseUrl: 'http://127.0.0.1:8000/api'`
2. **Create Dio client** - Add Bearer token interceptor
3. **Implement Api*Repository** - Parse camelCase JSON to Dart models
4. **Wire providers** - Switch from mock to API repositories
5. **Test endpoints** - All endpoints are ready and working!

---

## Validation

All endpoints follow API_CONTRACT.md specifications:
- ✅ camelCase JSON keys
- ✅ Exact enum strings (boardingHouse, emailVerified, etc.)
- ✅ ISO-8601 UTC dates
- ✅ String IDs (ULID)
- ✅ Bearer token auth
- ✅ Distance-sorted search
- ✅ User-safe error messages
- ✅ Authorization checks (owner-only updates)
