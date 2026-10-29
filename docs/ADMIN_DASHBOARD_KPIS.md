# Admin dashboard metrics

`/admin/dashboard?days=30` accepts 7, 30 or 90 days and remains admin-only.

Current snapshots: all registered accounts (including admins/demo accounts), unique
users with at least one approved provider role, available listings, pending role
requests and pending viewing requests. A user requesting two roles counts twice
in the pending queue, but once in approved providers.

Period activity: new users, listings and viewing requests by `created_at`. The
window includes today so far and the preceding N-1 calendar days, using the app
and database timestamp convention (`config('app.timezone')`, shown on screen).
The comparator is the immediately preceding N calendar days. When its count is
zero, the UI explains the absence of baseline activity instead of dividing by
zero. Counts reflect records still present, not an immutable historical ledger.

Inventory excludes soft-deleted vehicles/services and legacy non-space records
in `listings`. Availability means `active` for spaces and `available` for vehicles
and services. It is not occupancy, revenue or booking conversion. The signup
chart fills days with no registrations with zero and provides a daily-count table.

Provider review links use the existing `/admin/approvals` queue. Pending role
counts include legacy requests without supporting documents. No new database
migration, demo figures, inquiry tables or payment tracking are introduced.

Validation: `php artisan test --filter=AdminDashboardMetricsTest`, `npm run build`,
and `npm run test:frontend`. Refresh reruns the metrics; no background polling.
