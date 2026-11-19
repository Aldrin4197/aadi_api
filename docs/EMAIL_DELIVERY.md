# Email delivery (Hostinger)

Aadi sends the "Confirm your email" message, and support ticket replies, through the `info@aadirentals.com` mailbox on Hostinger.

## Settings

Set these in `.env` (the repository ships the non-secret values in `.env.example`):

```
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=info@aadirentals.com
MAIL_PASSWORD=<the mailbox password>
MAIL_FROM_ADDRESS="info@aadirentals.com"
MAIL_FROM_NAME="Aadi"
```

- Port 465 uses implicit TLS, so the scheme is `smtps`. If 465 is blocked, use `MAIL_PORT=587` with `MAIL_SCHEME=smtp` (STARTTLS).
- The username is the full mailbox address, and `MAIL_FROM_ADDRESS` must be that same mailbox. Hostinger rejects other senders.
- Never commit the password. `.env` is ignored by git. After editing it run `php artisan config:clear`.
- Set `APP_URL` to the public `https://` address in production so links in emails are correct.

## Checking it works

```
php artisan aadi:mail-test you@example.com
php artisan aadi:mail-test you@example.com --verification
```

The first sends a plain message, the second sends the real confirmation email. On failure the command prints the exact error. Delivery errors during registration and resend are also written to `storage/logs/laravel.log`; the user sees "could not be sent, try resending" and can retry from the verify page.

## In-app notification emails

After confirming SMTP and queue delivery, set `AADI_NOTIFICATION_EMAIL_ENABLED=true`, clear or refresh the configuration cache, and restart persistent workers. Each new in-app notification then queues one immediate email after the database transaction commits. The email contains a generic notice and a link to Aadi, not the notification text or message content. This includes messages and notifications to administrators, and may result in an additional email for support replies that already have their own email.

The switch defaults to `false` so a deployment does not unexpectedly email existing users. Turning it on does not send emails for older notifications. Queue or mail failures are logged and queued mail is retried up to three times; monitor failed jobs as described in [Hostinger deployment](HOSTINGER_DEPLOYMENT.md). SMTP delivery may still be rejected, delayed, or filtered after acceptance. Consider mailbox quotas and user preferences before enabling high-volume notification mail.

## Deliverability

The domain already publishes Hostinger's SPF and DKIM records plus a DMARC policy (currently `p=none`). Once messages are arriving reliably you can tighten DMARC to `p=quarantine`.

## What the message looks like

Subject: **Confirm your Aadi email address**. It greets the user by name, explains the link expires in 60 minutes and only works for their account, and says it can be ignored if they did not sign up. The wording lives in `AppServiceProvider::boot()`.
