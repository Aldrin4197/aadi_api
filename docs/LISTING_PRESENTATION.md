# Listing owner and ratings presentation

The landing community/social-proof section and its footer link are hidden when no published testimonials are supplied. Existing configured published reviews can still render in the future. Actual listing reviews remain available independently.

ListingOwner renders the owner's avatar, name, compact approved-role badge and profile link with consistent spacing in customer previews and full listing details. Missing/broken avatars use the existing initials fallback. Public provider listing previews now include ownerAvatarUrl in their explicit allowlist.

RatingStars renders exactly five stars. Display mode supports fractional fills for the average; editor mode uses native radio controls for 1–5, including keyboard navigation and focus visibility. The summary displays the server's average and current count, including a clear zero-rating state. Each individual review displays its own stars. New reviews require an explicit rating; edits load the existing value. Existing rating validation and aggregate queries are unchanged.

Validation: production build and 9 frontend tests passed. Desktop/mobile fixtures verified avatar loading, a 3.5 average/fractional fill and count, star click/arrow-key selection, mocked submission payload, overflow and absence of an empty social-proof section. The server persistence flow was not exercised here. No migration.
