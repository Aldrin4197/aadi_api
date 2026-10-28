# Profiles, avatars and provider verification

Run `php artisan migrate` and ensure `php artisan storage:link` is configured for public avatars. Verification files remain on the **local/private** disk; do not link `storage/app/private` to the public web root.

## Personal profile and avatar

Profile includes name, email, phone, city, private address, and public bio. The account page alone receives private profile fields. Address and internal avatar storage path are hidden from default User serialization. Marketplace provider details show the avatar, bio and role approval flag, never supporting documents or the personal address.

Avatar uploads accept JPEG, PNG or WebP, at most 5 MB and 6000px in either dimension. The web editor offers crop positioning, zoom and a preview, and submits a 512px square JPEG. Stored filenames are generated. Replacing/removing an avatar deletes only the previously tracked avatar file after a successful database update; external legacy avatar URLs are not deleted.

Authenticated mobile endpoints: GET/PATCH `/api/v1/profile`, POST `/api/v1/profile/avatar` with multipart `avatar`, DELETE `/api/v1/profile/avatar`. Profile PATCH accepts name/email plus optional phone/bio/city/address, never role flags. Avatar and profile responses are intended for the account owner.

## Role applications

Requirements are product rules in `config/provider_verification.php`, not claims about statutory obligations. Administrators/developers can adjust each role's required types there. BIR registration, business documents and certifications are optional by default; government ID is required for provider applications; landlords also provide property authority; vehicle owners provide OR and CR. Certifications and professional licenses can be supplied for relevant services. Existing generic uploads remain downloadable but may need replacement with typed documents to satisfy current approval rules.

Each application contains individual/business classification, business name where applicable, contact and service area, offering description, ownership/authorization relationship or service category, skills and years of experience. Documents carry a type, optional private reference (property address, vehicle identifier or qualification), and expiry date when applicable. Uploads support JPEG/PNG/WebP/PDF, 5 MB each and 10 files total.

Save draft does not submit for review or grant a provider role. Submitted applications become pending and cannot be edited while under review. Admin approval checks current required document types, file existence and expiry. Admin requests for updates need a reason; applicants can retain existing files and replace only the relevant documents. Approved providers can resubmit updated verification, which returns that role to pending. Expiry is checked on submission/approval; this release does not automatically revoke previously approved roles on expiry.

These are **role-level approvals**, not separate approval records for every vehicle or property. References let reviewers identify the assets in the supporting files, but no per-listing verification badge is awarded. Existing listing publication behavior is not changed by this release.

## Mobile contract

GET/POST `/api/v1/provider-applications/{service}` and GET `/api/v1/provider-documents/{id}/{index}` require Sanctum authentication. Service is spaces, haul or raketeer. GET returns requirements, role status and the current application. POST multipart fields:

- `version`: latest application version when editing; stale saves are rejected
- `action`: draft or submit
- `displayName`, `phone`, `area`, `description`
- `details[applicantType]`: individual/business; optional `businessName`, `relationship`, `serviceCategory`, `skills`, `experienceYears`
- `documents[]`: new files only; `documentMeta[index][type|reference|expiresAt]` describes each new file
- `keepDocuments[]`: indices from the latest application response to retain; omit to remove old files
- `confirmed`: true when submitting

A successful request returns the updated application and new indices. Reload after conflicts; never reuse indices from older application versions. Metadata responses do not disclose storage paths. Downloads are owner/admin-only attachments with private/no-store and nosniff headers. Old unversioned role endpoints are unchanged; mobile clients should adopt these versioned routes for typed verification.

## Validation

Run `php artisan test --filter=ProfileVerificationTest`. Coverage includes avatar replacement/removal, profile privilege-field rejection, private document authorization, role isolation, drafts, expiry rejection, failed-upload cleanup, and retained-file resubmission. The browser can validate crop and form interactions without a backend, but real upload/storage and admin flows require these PHP tests and a configured deployment.
