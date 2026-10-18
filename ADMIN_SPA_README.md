# Aadi Admin SPA

A complete Vue 3 Single Page Application for the Aadi Rental Marketplace admin panel.

## Architecture

- **Vue 3** with Composition API
- **Vue Router** for client-side routing
- **Pinia** for state management
- **Tailwind CSS** with Aadi brand colors
- **Laravel** backend with session-based authentication

## Project Structure

```
resources/js/admin/
├── main.js                 # App bootstrap
├── App.vue                 # Root component
├── router/
│   └── index.js           # Vue Router configuration with guards
├── stores/
│   └── auth.js            # Pinia auth store
├── layouts/
│   ├── GuestLayout.vue    # Login page layout
│   └── AdminLayout.vue    # Main admin layout with sidebar
├── views/
│   ├── Login.vue          # Login page
│   ├── Dashboard.vue      # Dashboard with stats
│   ├── Users.vue          # User management
│   ├── Listings.vue       # Listing management
│   ├── Tickets.vue        # Support tickets
│   └── Analytics.vue      # Analytics page
└── components/
    ├── BaseButton.vue     # Reusable button
    ├── BaseInput.vue      # Form input with validation
    ├── StatCard.vue       # Statistics card
    ├── PageHeader.vue     # Page title component
    └── EmptyState.vue     # Empty state placeholder
```

## Features

### Authentication
- Session-based authentication (Laravel sessions)
- Pinia store for auth state management
- Route guards protecting admin pages
- Auto-redirect on login/logout

### Layouts

**GuestLayout** - For login page
- Gradient background (Aadi teal)
- Centered card design
- Full Aadi logo with tagline
- Responsive design

**AdminLayout** - Main admin interface
- Dark teal sidebar (#0B3A38)
- Collapsible navigation
- User profile with logout
- Responsive (mobile hamburger menu)

### Components

**BaseButton**
```vue
<BaseButton variant="primary" size="lg" :loading="false">
  Click Me
</BaseButton>
```
Variants: `primary`, `secondary`, `danger`, `ghost`
Sizes: `sm`, `md`, `lg`

**BaseInput**
```vue
<BaseInput
  v-model="form.email"
  label="Email"
  type="email"
  :error="errors.email"
  required
/>
```

**StatCard**
```vue
<StatCard
  title="Total Users"
  :value="1247"
  color="teal"
  icon="M12 4.354a4..."
/>
```
Colors: `teal`, `orange`, `blue`, `emerald`

### Pages

1. **Login** (`/admin/login`)
   - Email/password form
   - Remember me checkbox
   - Test credentials displayed
   - Error handling

2. **Dashboard** (`/admin/dashboard`)
   - 4 stat cards (Users, Spaces, Bookings, Verifications)
   - Quick action cards
   - Welcome message

3. **Users** (`/admin/users`)
   - Placeholder with EmptyState
   - Ready for CRUD implementation

4. **Listings** (`/admin/listings`)
   - Placeholder with EmptyState
   - Ready for property management

5. **Tickets** (`/admin/tickets`)
   - Placeholder with EmptyState
   - Ready for support system

6. **Analytics** (`/admin/analytics`)
   - Placeholder with EmptyState
   - Ready for charts/reports

## API Endpoints

### Authentication
```
POST   /api/admin/login    - Login with email/password
POST   /api/admin/logout   - Logout current user
GET    /api/admin/me       - Get authenticated user
```

## Routes

### Web Routes
```php
// All admin routes serve the Vue SPA
Route::prefix('admin')->group(function () {
    Route::get('/{any?}', fn() => view('admin.spa'))
        ->where('any', '.*');
});
```

### Vue Routes
```javascript
/admin/login      - Login page (guest only)
/admin            - Redirects to /admin/dashboard
/admin/dashboard  - Dashboard (auth required)
/admin/users      - Users (auth required)
/admin/listings   - Listings (auth required)
/admin/tickets    - Tickets (auth required)
/admin/analytics  - Analytics (auth required)
```

## Development

### Start dev server
```bash
npm run dev
```

### Build for production
```bash
npm run build
```

### Access admin panel
```
http://localhost:8000/admin/login
```

### Test credentials
```
Email: admin@aadi.test
Password: password
```

## Styling

### Brand Colors
- **Primary Teal**: `#1FA6A0` (aadi-teal)
- **Dark Teal**: `#0B3A38` (aadi-teal-800) - Sidebar background
- **Light Teal**: `#C4ECEA` (aadi-teal-100)
- **Accent Orange**: `#F59E42` (aadi-orange)
- **Deep Orange**: `#D9822B` (aadi-orange-500)

### Tailwind Classes
```css
bg-aadi-teal        /* Primary teal background */
text-aadi-teal-800  /* Dark teal text */
border-aadi-orange  /* Orange border */
hover:bg-aadi-teal-50  /* Light teal hover */
```

### Typography
- **Font**: Quicksand (300-700 weights)
- Loaded from Bunny.net CDN (privacy-focused)

## Next Steps

1. **Implement API endpoints** for each admin page
2. **Build CRUD functionality** for users/listings/tickets
3. **Add data tables** with sorting/filtering
4. **Implement analytics** with charts
5. **Add file uploads** for property images
6. **Build notification system** for alerts
7. **Add role-based permissions** (super admin, moderator)

## Notes

- This is a **separate system** from the Inertia.js demo at `/admin-demo`
- Uses **session-based auth** (not Sanctum tokens)
- All admin routes are protected by **route guards**
- Components are **reusable** across all admin pages
- Ready for **production deployment** with `npm run build`
