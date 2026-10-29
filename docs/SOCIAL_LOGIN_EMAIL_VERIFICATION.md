# Social login and email verification

## Deploy

1. Pull this PR and run `php artisan migrate`. This adds `social_accounts`; it does not backfill verification or change provider approval statuses.
2. Set `APP_URL` to the real HTTPS origin and configure your existing `MAIL_*` settings with a working mail provider. `MAIL_MAILER=log` does not deliver email. Keep credentials server-side, never in `VITE_*` variables.
3. Configure the providers below, then run `php artisan optimize:clear` and `npm run build`.
4. Run `php artisan test --filter=Auth` with PHP 8.3+ and the project dependencies before deployment. The implementation environment did not include PHP, so backend tests have not been executed there.

### Google

Create a Web application OAuth client in Google Cloud. Register the exact redirect URI, for example `https://your-aadi-domain/auth/google/callback`, and configure the consent screen / test users / publishing status appropriate to your project.

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://your-aadi-domain/auth/google/callback
```

### Facebook

Configure Facebook Login for your Meta app and register `https://your-aadi-domain/auth/facebook/callback` as a valid OAuth redirect URI. Configure the app's required privacy / data deletion settings and permissions before making it available beyond test users. Choose a currently supported Graph API version from your app dashboard; the integration deliberately has no guessed default.

```dotenv
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=https://your-aadi-domain/auth/facebook/callback
FACEBOOK_GRAPH_VERSION=
```

Set the version in `vNN.N` format. The Facebook button is hidden until all four values are configured; Google requires its three values. No credentials have been added to the repository.

## Behavior

- Google / Facebook buttons on login and registration use full browser redirects, not Inertia requests. They create a new ordinary User account or sign in the same saved provider identity.
- Existing accounts with a matching email are **not automatically linked**. Sign in with the existing password (or reset it), then use Profile & settings → Social sign-in → Connect. This flow requires password confirmation and binds the callback to the same signed-in user. It cannot transfer a provider identity from another user or silently replace an existing connection. Connecting does not change the Aadi email or verify it. Disconnect / replacement UI is not included.
- Provider identity IDs are stored; access tokens, refresh tokens and OAuth client secrets are not stored in the database. State is random, session-bound, expires after 10 minutes and is consumed once. Google also uses PKCE. Failed provider responses never expose/log their response bodies.
- Google can establish email verification only when it returns `email_verified=true` and either a Gmail address or a hosted-domain claim. Other Google addresses and Facebook addresses must follow Aadi's email link. Missing provider email prompts email registration. Subsequent provider logins do not overwrite an account's email, avatar, role or verification state.
- New password registrations send the same Laravel email verification notification. Signup opens `/verify-email`, with resend, profile correction and continued public exploration. An unavailable mail provider leaves the account available and offers resend.
- Existing email/password login remains available. Existing routes with `verified` middleware now enforce verification. Public browsing and public listing previews remain available. This PR does not add a new blanket verification restriction to all marketplace mutations or admin routes.
- Email verification controls the Verified User badge only; landlord, vehicle-owner and Raketeer approvals still require admin review.
- Email links retain Laravel's signature, expiry, current-user ID and current-email hash checks. Open links while signed into the same Aadi account; otherwise log in first. Changing email invalidates the old link and resets verification via the existing profile update flow.
- Social-only users receive an unpredictable password, never a shared/demo password. To set a usable password for password-protected account operations, use the existing Forgot password flow.

## Mobile API

`POST /api/auth/register` keeps its token response and adds `verificationEmailSent`. UserResource responses add `emailVerified`.

Authenticated Sanctum routes:

- `GET /api/v1/email-verification`: `{ "emailVerified": false }`
- `POST /api/v1/email-verification/resend`: sends the same verification email; limited to six attempts per minute. Already-verified users do not receive another message; delivery failure returns 503.

Verification links open the web flow. Native Flutter social SDK integration / exchanging mobile provider tokens for Sanctum tokens is **not implemented** here; never send arbitrary Google/Facebook tokens to the web callback or treat client-submitted profile information as authenticated identity.

## Release checks

Use provider test accounts to exercise new Google signup, returning Google login, Facebook signup + email link, denied consent, missing Facebook email, repeated/expired state, existing-email collision, expired email link, resend, changed-email invalidation and a mobile registration. Confirm mail arrives and callback URLs match the deployed domain exactly. OAuth credentials and live mail were not available for end-to-end validation.

Sources: [Google OIDC](https://developers.google.com/identity/openid-connect/openid-connect), [Google UserInfo reference](https://developers.google.com/identity/openid-connect/reference), [Meta manual login flow](https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow/), [Laravel email verification](https://laravel.com/framework/docs/13.x/verification). Meta documentation could not be fetched in the implementation session (429); verify your app's supported version and settings during release checks.
