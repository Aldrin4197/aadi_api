# Verification icons and detail-map scrolling

ProviderBadge uses the shared Aadi badge components across listing details, previews, saved listings, provider profiles and admin inspections. Provider-role badges use the supplied outline role icons with a filled verification check; the email badge is a shape-free outlined user with a filled check. No visible role text remains, and accessible names/tooltips explain each state.

Verified User means email_verified_at is present. It does not certify identity documents; the accessible label/title says so. Provider icons depend on their individual admin-approved role states, not email verification. Neither badge verifies an individual listing. Listing DTOs expose ownerEmailVerified and public profiles expose emailVerified as booleans only. No email address or verification timestamp was added to public data.

The full listing page overrides the reused map wrapper to position:relative, top:auto and a fixed 320px height. Its map stays in document flow above reviews instead of inheriting the search map's sticky positioning. The Explore map is unchanged.

Validation: production build and frontend checks; desktop/mobile browser fixture checks the actual Leaflet container's bounding box at multiple scroll positions and verifies that it stays above reviews. Remote map tiles are blocked in that fixture. Icon count, absence of visible labels and accessible names are checked. Added ListingVerificationBadgeTest for independent email/role states in all three categories.
