# Getting started

New accounts see an optional four-step modal after registration: about you, interests, preferred area/budget, and review. This includes social-login accounts because the database default applies to newly created users. Email verification remains required wherever the existing application requires it; onboarding does not verify an email or grant a provider role.

Existing accounts are marked skipped by the migration rather than interrupted on their next login. A Getting started / Your preferences button on `/profile` and `/dashboard` opens the modal again. Budget units are explicit (PHP per month, day, hour, or project). Answers are private preferences and do not automatically change map filters or recommendations. Closing or choosing Do this later persists dismissal without storing unsaved answers, even if those answers are invalid.

Provider application URLs retain their existing controllers and submission endpoints. The Inertia resolver presents `Marketplace/ProviderApply` through `Pages/Onboarding/ProviderApplication.vue`, which opens a modal over the provider-role overview. Landlord, UV Owner, and Raketeer each get about-you, offering, documents, and review steps. Document types/requirements come from the existing server configuration, including property authority, OR/CR, certifications, and applicable business documents. Existing draft, revision, private-document, validation, and admin-decision behavior stays authoritative.

Closing a provider modal retains its current answers and files while the page stays mounted. Save draft persists them for another visit. Pending applications are read-only; approved roles can explicitly submit updated verification. The final step requires confirmation before submission.

## Deployment

Run `php artisan migrate` before using onboarding. This adds `users.onboarding_data` (private JSON) and `users.onboarding_status` (pending/skipped/completed). No seeders or new environment variables are required. Rollback removes the stored preferences.

## Validation

- Production build passed; existing frontend suite: 9 passed.
- Chromium component fixtures: personal onboarding at 320px, 390px, and 1440px; checked step progression, answers retained across steps, final payload, close after saving, and no page overflow.
- All three provider flows checked at 390px: required-document checks, multi-file selection/types, review, final confirmation, simulated server validation error with retained answers, close/reopen, and pending read-only state.
- Fixtures stubbed network saves and did not exercise actual email/OAuth, file storage, or database writes.
- Added `tests/Feature/OnboardingTest.php` for authenticated ownership, validation, private serialization, role isolation, and skip/resume behavior. PHP is unavailable in the editing environment, so run `php artisan test --filter=OnboardingTest` and the existing provider feature tests in CI or locally before merge.
- Built from main `22764c8`; no changed files overlap PR #39 or PR #40. Neither PR is merged by this change.
