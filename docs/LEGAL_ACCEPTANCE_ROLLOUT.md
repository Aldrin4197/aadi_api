# Terms and verification acceptance foundation

This PR prepares legal-document review and versioned acceptance. It does not activate a Trust Score, change badges, publish approved legal terms, or alter the current provider approval criteria.

## Default behavior

`AADI_LEGAL_ENABLED` defaults to false. Registration and provider workflows remain unchanged. The bundled Terms, Privacy Notice, and three provider declarations are DRAFTS with explicit unresolved policy markers. Draft documents cannot be accepted or publicly read. An authenticated administrator can review `/legal/accept?preview=1` and add `&context=spaces`, `haul`, or `raketeer` to review provider declarations.

## Before release

Confirm the operator's legal name, business address, support/privacy contacts, minimum age, transaction/payment/refund model, report/appeal process, and any mediation service. Complete the actual privacy inventory, legal bases, processors, transfers, retention periods, and rights-request procedures. Obtain Philippine legal review; the draft is not a compliance certification. Preserve statutory rights rather than promising blanket platform immunity.

Official review references:
- National Privacy Commission, Data Privacy Act: https://privacy.gov.ph/data-privacy-act/
- National Privacy Commission, implementing rules: https://privacy.gov.ph/implementing-rules-regulations-data-privacy-act-2012/
- DTI, Internet Transactions Act: https://ecommerce.dti.gov.ph/internet-transactions-act-of-2023/
- DTI, implementing rules: https://ecommerce.dti.gov.ph/implementing-rules-and-regulations/

These references informed the separation of terms, notice acknowledgment, verification, and trust representations. They do not establish that the unfinished draft satisfies every obligation applicable to Aadi.

## Activation

1. Run `php artisan migrate` to create `legal_acceptances`.
2. Create a reviewed, complete versioned bundle under `resources/legal`; retain old files. Give it a new version, effective date, complete operator details, and status `published`. Remove all `[CONFIRM ...]` markers only after resolving them.
3. Update `config/legal.php` to point to the approved bundle. Test in staging before enabling `AADI_LEGAL_ENABLED=true`; refresh config cache as appropriate.
4. If enabled with an unpublished/incomplete bundle, protected account operations return 503 rather than silently collecting acceptance of a draft. Public browsing and logout remain available. Disable the flag to restore existing behavior while resolving configuration.

## Enabled web flow

The middleware is independent of email and social authentication: signed-in users without current acceptance are prompted before protected account actions. Accounts may already exist at that point; this PR uses a post-sign-in gate, not a registration-form checkbox. Existing users are also prompted for an unaccepted version. Email verification remains separate.

A native modal offers Terms and Privacy Notice sections, separate unchecked acknowledgments, and an additional declaration for each provider role. A user can decline and return to public browsing or log out. Profile access and account deletion remain accessible. Admin operations are exempt to allow legal review and incident handling. Optional onboarding from PR #41 is deferred on the agreement page.

Before opening/submitting a provider application, users accept the account agreement and the role-specific declaration. No uploaded documents are accepted by the agreement endpoint. A stale provider form submitted after a policy update is redirected for review; its upload is not replayed. Return navigation only remembers local GET paths, never POST bodies.

`/legal/terms` is a public, read-only view when publication and enforcement are enabled. `/legal/accept` requires authentication. Backend validation requires explicit acknowledgments; client-side required checkboxes are not the enforcement boundary.

## Records and versioning

Each record contains the authenticated user ID, context, version, SHA-256 digest, exact JSON snapshot, and acceptance timestamp. User IDs, timestamps, and document text are server-derived. Duplicate acceptance is idempotent and preserves the original timestamp. Reusing an accepted version with changed text is rejected; publish a new version instead. No IP/device fingerprint is collected by this feature. Account deletion cascades to these records: review that retention behavior before release.

Notice acknowledgment is not blanket consent to marketing or unrelated processing. No marketing consent is collected here. A provider declaration is not proof of identity, role approval, or a score contribution.

## Scope and validation

This is web acceptance only; mobile/API policy endpoints and enforcement require a separate coordinated rollout before claiming platform-wide enforcement.

Production build and 9 existing frontend tests passed. Chromium fixture checks passed at 320px, 390px, and 1440px for explicit required checkboxes, provider context, simulated error retention, and no horizontal overflow. Draft preview/public-read views had no acceptance button. Fixture saves were stubbed.

Added `tests/Feature/LegalAcceptanceTest.php` for draft protection, guest access, explicit acknowledgment, ownership, duplicate handling, stale versions, account-before-role gating, and changed-text version protection. PHP and Composer dependencies are unavailable here, so run `php artisan test --filter=LegalAcceptanceTest` and the registration/provider suites in CI before merge.

New independent branch from main; does not include or modify PR #39, #40, or #41 features. No PR is merged by creating this one.
