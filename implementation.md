# Aadi Three-Feature Platform Update

## Summary
Aadi is now positioned as a platform with three product lines:
- Space Rentals
- PUV Rental under the brand name Aadi Haul
- Skills Services under the brand name Aadi Raketeer

Space Rentals remains the main product. Aadi Haul and Aadi Raketeer are isolated subproducts with their own database tables, APIs, and admin pages.

## Admin Additions

### Admin Routes
Added two authenticated admin routes:
- GET /admin/vehicles -> admin.vehicles.index
- GET /admin/services -> admin.services.index

Controller: App\Http\Controllers\Admin\DashboardController

### Admin Pages
Added two dedicated Inertia pages:
- resources/js/Pages/Admin/Vehicles/Index.vue
- resources/js/Pages/Admin/Services/Index.vue

These pages provide:
- Total count badges
- Paginated tables
- Product-specific columns
- Status badges
- Empty-state handling

### Admin Navigation and Dashboard
Updated the admin layout and dashboard to show the new branded product lines:
- Space Rentals
- Aadi Haul
- Aadi Raketeer

Dashboard updates include:
- Product totals for Aadi Haul and Aadi Raketeer
- Quick actions for both subproducts
- Renamed labels in the sidebar and dashboard cards

## Database Context

### Existing Product Isolation
This implementation assumes the following tables already exist:
- listings for Space Rentals
- vehicle_listings for Aadi Haul
- service_listings for Aadi Raketeer

### Sanctum ULID Fix
The API testing exposed a schema issue in Sanctum. Since users use ULIDs, personal_access_tokens.tokenable_id also needs to support ULIDs.

Changes made:
- Updated the original Sanctum migration to use ulidMorphs('tokenable')
- Added a migration for existing databases to convert personal_access_tokens.tokenable_id to CHAR(26)

Without this fix, API register/login token creation fails on MySQL.

### Web Session ULID Fix
Admin panel login also broke for the same root cause. The `sessions.user_id` column was still `bigint unsigned`, but users use ULIDs. This silently prevented the web guard session from persisting the authenticated user, so `/admin/login` would appear to succeed but any subsequent request bounced back to a guest state.

Changes made:
- Updated the original users/sessions migration to use `foreignUlid('user_id')` for the sessions table
- Added a migration for existing databases to convert `sessions.user_id` to `CHAR(26)`

### Admin Login Credentials Fix
The admin login page advertises test credentials (`admin@aadi.test` / `password`), but that account did not exist in the working database, and the default `DatabaseSeeder` only created `test@example.com` with a random factory-generated user.

Changes made in `database/seeders/DatabaseSeeder.php`:
- Seeder now upserts `admin@aadi.test` (name: Aadi Admin, password: `password`)
- Seeder now upserts `test@example.com` (name: Test User, password: `password`) instead of relying on the factory default

Working local admin credentials after running `php artisan db:seed`:
- Email: `admin@aadi.test` / Password: `password`
- Email: `test@example.com` / Password: `password`

Note: The current `Admin\LoginController` does not check for an admin-only role/flag. Any valid `users` record can sign in to `/admin`. Adding a real admin role/permission check is listed under Suggested Next Enhancements.

## API Additions

### Backward-Compatible Endpoints
The generic endpoints remain available:
- /api/vehicles
- /api/services

### Branded Alias Endpoints
Added branded aliases for app integration:

#### Aadi Haul
- GET /api/haul
- POST /api/haul
- GET /api/haul/{id}
- PUT /api/haul/{id}
- DELETE /api/haul/{id}

#### Aadi Raketeer
- GET /api/raketeer
- POST /api/raketeer
- GET /api/raketeer/{id}
- PUT /api/raketeer/{id}
- DELETE /api/raketeer/{id}

### Registration and Approval Flow
Users must first register, then apply for the relevant subproduct role before creating listings.

#### Aadi Haul role flow
- POST /api/auth/register
- POST /api/me/aadi-haul-application
- POST /api/me/aadi-haul-application/approve

#### Aadi Raketeer role flow
- POST /api/auth/register
- POST /api/me/aadi-raketeer-application
- POST /api/me/aadi-raketeer-application/approve

The approval endpoints above are demo/self-approval endpoints for local development and mobile integration testing. Admin-specific approval endpoints with a user ID are also available.

## Verified Working API Payloads

### 1. Register a user
POST /api/auth/register

```json
{
  "name": "Haul Owner",
  "email": "haul.owner@example.com",
  "password": "password123"
}
```

### 2. Apply for Aadi Haul
POST /api/me/aadi-haul-application

```json
{
  "businessName": "Aadi Haul Test Fleet",
  "licenses": ["LTFRB-TEST-001"],
  "inventory": ["Toyota Hiace", "Isuzu Elf"],
  "certificates": ["ORCR", "Insurance"]
}
```

### 3. Approve Aadi Haul locally
POST /api/me/aadi-haul-application/approve

No request body required.

### 4. Create an Aadi Haul listing
POST /api/haul

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

### 5. Apply for Aadi Raketeer
POST /api/me/aadi-raketeer-application

```json
{
  "businessName": "Aadi Raketeer Test Services",
  "skills": ["Electrical Repair", "Home Maintenance"],
  "certifications": ["TESDA NC II"],
  "serviceArea": "Metro Manila",
  "yearsExperience": 6
}
```

### 6. Approve Aadi Raketeer locally
POST /api/me/aadi-raketeer-application/approve

No request body required.

### 7. Create an Aadi Raketeer listing
POST /api/raketeer

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

## Notes for App Integration
- Keep Space Rentals on the existing listings API.
- Use the new branded alias endpoints for the mobile app if you want product names to match UI copy.
- A user can hold multiple roles at once.
- A user must be approved for Aadi Haul before posting a haul listing.
- A user must be approved for Aadi Raketeer before posting a raketeer listing.
- New listing creation responses now refresh from the database so default values like currency and status are returned correctly.

## Validation Performed
- Verified route registration with php artisan route:list --path=api
- Verified Aadi Haul flow end-to-end:
  - register
  - apply
  - approve
  - create listing
- Verified Aadi Raketeer flow end-to-end:
  - register
  - apply
  - approve
  - create listing
- Verified admin/frontend build with npm run build

## Suggested Next Enhancements
- Add admin approval screens for Aadi Haul and Aadi Raketeer instead of relying on demo self-approval.
- Add filters to the new admin pages.
- Add product-specific analytics for Space Rentals vs Aadi Haul vs Aadi Raketeer.
- Add a real `is_admin` role/permission check to `Admin\LoginController` so only designated admin accounts can access `/admin`.
- Update the admin login page's displayed test credentials if they are rotated or removed for production.
