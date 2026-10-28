# Admin navigation and user inventory

The main Inertia AdminLayout now has grouped navigation, a persisted compact desktop sidebar with accessible labels, and a mobile dialog drawer. It includes a role-approval shortcut and a marketplace link. Customer layouts and the legacy standalone admin SPA are unchanged.

`/admin/users` supports a name/email search and shows counts for properties, vehicles and rakets. View listings opens `/admin/users/{user}/listings?service=spaces|haul|raketeer`. Both routes require authentication and EnsureAdmin. Inventory queries are scoped to the selected owner/provider and paginated; property records are restricted to space_rental. Drafts and inactive records are included, soft-deleted records are not.

The dedicated admin inspection modal includes the gallery, full description, precise location, source status, timestamps, record ID, owner email, provider-role approval and an Edit link to the existing authorized editor. It does not use the customer preview layout or introduce new moderation mutations. Public profile access remains a separate link. Existing server policies continue to govern edits.

No migration. Validation: npm run build, npm run test:frontend, and desktop/mobile browser fixture checks passed. Browser checks covered active navigation, compact mode, mobile Escape/focus restoration, preview metadata/edit links and overflow. New AdminUserListingsTest covers authentication, admin-only access, owner isolation, three category counts, unpublished records, search and invalid category input. PHP was unavailable, so backend tests require CI or local execution before merge.

## Category-table inspection and approvals

The `/admin/listings`, `/admin/vehicles`, and `/admin/services` routes now use ListingInspectionController and the shared AdminListingsTable. Clicking a non-interactive part of a row, its photo/title, or the keyboard-accessible Inspect button opens AdminListingPreview. Owner links lead to that owner's admin inventory. The server supplies canonical listing fields plus sourceStatus, updatedAt and a limited adminOwner object (id/name/email). Pagination and unpublished listings are preserved. These routes remain inside auth + EnsureAdmin.

Role Approvals now renders AdminLayout directly and has page-scoped styles. Reviewing, submitting and validation errors remain within the admin shell; provider application submission pages still use the user layout. Decision endpoints and version validation are unchanged.

Follow-up validation: build and 9 frontend tests passed. Browser fixtures cover each category at desktop/mobile, row/button previews, Escape/focus restoration, admin approval layout and mocked decision submission. AdminListingInspectionTest covers category data, unpublished records and non-admin rejection; PHP tests still require CI/local execution.
