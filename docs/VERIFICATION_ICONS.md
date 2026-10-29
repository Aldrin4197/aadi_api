# Verification icons and detail-map scrolling

ProviderBadge now renders icons only, with a warm-yellow background/check mark, accessible labels and native hover titles. No visible role text remains. Icons represent Verified User, Verified Landlord, Verified UV Owner and Verified Raketeer.

Verified User means email_verified_at is present. It does not certify identity documents; the accessible label/title says so. Provider icons still depend on their individual admin-approved role states, not email verification. Listing DTOs expose ownerEmailVerified and public profiles expose emailVerified as booleans only. No email address or verification timestamp was added to public data.

The full listing page overrides the reused map wrapper to position:relative, top:auto and a fixed 320px height. Its map stays in document flow above reviews instead of inheriting the search map's sticky positioning. The Explore map is unchanged.

Validation: production build and frontend checks; desktop/mobile browser fixture checks the actual Leaflet container's bounding box at multiple scroll positions and verifies that it stays above reviews. Remote map tiles are blocked in that fixture. Icon count, absence of visible labels, accessible names and yellow styling are checked. Added ListingVerificationBadgeTest for independent email/role states in all three categories; PHP is unavailable here, so run it in CI/local. No migration.
