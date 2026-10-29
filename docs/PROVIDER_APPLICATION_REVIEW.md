# Provider verification review

The `/admin/approvals` page now lists pending applications with role/search filters and 12-row client-side pagination. The server still supplies the pending queue. Each Review application button opens the same review modal for Aadi Properties, Aadi UV and Aadi Raket.

The modal contains account/contact information, offering details, role-specific fields, submitted date, required-document checklist, document selector, image/PDF preview, original download, review note and decision. Approval requires an administrator acknowledgement in the UI. The existing server-side administrator middleware, current-revision check, pending-status check, required-document validation, expiry check and file existence check remain authoritative. Upload presence is not described as document authenticity.

`GET /provider-documents/{application}/{index}?preview=1` (and its existing API equivalent) serves only detected JPEG, PNG, WebP or PDF inline. Access remains restricted to the applicant and administrators; responses are private/no-store and nosniff. Unsupported types return 415; original downloads retain their existing authorization. Browsers without PDF preview support can download the original.

Applicant pages now use the three Aadi service names, display draft status and admin feedback on the role overview, expose an approved application's details, and show a live required-document checklist. All form fields are disabled during upload. Pending applications remain read-only; resubmitting an approved application still sends that role back to pending. Required document policy is unchanged in `config/provider_verification.php`.

No database migration or new package is required. Build frontend assets after pulling.

Validation: production build, nine frontend tests, and browser tests at 1440px and 390px. Browser checks cover all three roles, modal opening, image-preview rendering, decision payloads with mocked Inertia posts, Escape closing and horizontal overflow. Backend preview/privacy and all-role approval tests were added but cannot run in the implementation environment because PHP is unavailable. Run `php artisan test --filter=ProviderDocumentPreviewTest` and the existing `ProfileVerificationTest` / `ProviderApplicationTest` before deployment. Live uploaded documents and real database decisions still require server validation.
