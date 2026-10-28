# Uploaded listing photo delivery

Listing uploads previously returned the public disk URL based on APP_URL and /storage. Missing public/storage links, stale hosts or HTTP URLs on HTTPS deployments could leave valid uploaded files inaccessible.

Uploads now return `/media/listing-photos/{owner}/{filename}` as an absolute URL on the current request origin. A Laravel route streams JPEG, PNG and WebP files from the public disk without a storage symlink. The handler restricts owner/filename syntax, checks the file MIME type, and cannot serve private verification documents or arbitrary paths. These are public listing photos, as before; this does not add private draft-photo access control.

Listing.photos, VehicleListing.images and ServiceListing.images resolve old `/storage/listing-photos/...` URLs to the new route when the corresponding file exists on the public disk. This fixes DTOs, admin views and legacy API serialization without changing database values or gallery order. External image URLs remain unchanged unless they match the uploaded-photo namespace and an actual local file. New media URLs are refreshed against the current request origin. Files that are missing from storage cannot be recovered by URL normalization.

Deployment: merge/pull and run `php artisan optimize:clear`. No migration or re-upload is needed for existing files. Storage/app/public must still be writable. Correct trusted proxy/HTTPS configuration remains required when running behind a proxy. The public media route must be forwarded to Laravel by the web server.

Validation: production build and git diff --check passed. ListingPhotoTest covers upload/readback, legacy URL normalization across all three models, unchanged database values/external URLs, missing files, invalid MIME types and guest upload rejection. PHP is unavailable in this environment; run `php artisan test --filter=ListingPhotoTest` in CI or locally. The user's deployed broken image has not been reproduced; these changes address the identified storage-link/URL dependency.
