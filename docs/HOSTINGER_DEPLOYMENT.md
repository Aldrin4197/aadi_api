# Hostinger deployment and backup plan

## Queue and scheduler

Hostinger shared plans vary. Confirm the plan supports SSH, PHP CLI, cron and the required PHP extensions, and ask Hostinger about job duration and frequency limits. Configure the database queue and its `jobs` and `failed_jobs` tables through the normal migrations. Keep Laravel's project and private storage outside the public web root, which must point only to `public`.

If a persistent worker is supported, use a host-supported process manager to restart it and monitor it. Otherwise, set `AADI_QUEUE_CRON_ENABLED=true` and create one **Custom** cron job in hPanel to run the correct PHP CLI and the project's `artisan schedule:run` every minute. Replace the paths with the actual PHP executable and project directory:

```
/path/to/php /home/account/path-to-aadi/artisan schedule:run
```

See [Hostinger's cron instructions](https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/). Test the command through SSH and inspect its output in hPanel. Cron uses UTC. Do not run both the scheduled worker and a persistent worker unless the deployment is deliberately configured and monitored for multiple workers.

The optional scheduler runs a bounded `queue:work` invocation every minute with an overlap lock, and checks queue health every five minutes. It can add up to a minute of latency and is not equivalent to a continuously running worker. A worker can take longer than its 45-second maximum runtime when processing a job; its job timeout is 60 seconds and requires PHP's `pcntl` extension to be enforced. Keep the queue connection's `retry_after` greater than the worker timeout and confirm the host permits enough execution time. Overlap locks expire after five minutes; use the database cache and monitor interrupted runs. If the host cannot enforce the job timeout, a stuck worker may outlive its lock; use a host-supported process limit or a different hosting setup.

Worker output is appended to `storage/logs/queue-worker.log`; health output goes to `storage/logs/queue-health.log`. The health check logs a warning if a ready job is at least five minutes old or any job failed within the last 24 hours. Run `php artisan aadi:queue-health` and `php artisan queue:failed` to inspect the queue. Review failed jobs before retrying them: retries can resend emails. Protect and rotate these logs. Configure an independent alerting or log-monitoring service to watch for warnings, failed cron runs, disk exhaustion and stopped workers; a health check cannot alert if cron itself stops. These alerts are not configured by the application.

After configuration or code changes, refresh the Laravel configuration cache as appropriate and restart persistent workers with `php artisan queue:restart`. Verify Pusher and SMTP using test accounts, and check logs and failed jobs. Cron cannot be configured from this repository; enable the flag only after hPanel is set up.

## Google Drive backup plan — not yet implemented

Google Drive can be an off-site destination, subject to quota, privacy and access requirements. Before implementation, agree on the backup frequency, retention, recovery objectives, ownership of the Google account and how encryption keys and OAuth credentials will be managed.

1. Back up the database and all required storage, including private provider verification documents and public listing photos. Keep secrets such as `.env`, mailbox passwords and Pusher credentials out of the archive; store disaster-recovery secrets separately in a secure vault.
2. Produce a consistent database export and a corresponding file snapshot, encrypt archives before upload, and store the encryption key separately from Drive. Use a dedicated, minimally scoped Google account and protect access with multifactor authentication.
3. Use a scheduled, non-interactive upload mechanism compatible with the hosting plan; protect and rotate its credentials. Check storage quotas and upload failures, retain multiple versions, and keep an independent copy outside both the hosting account and Drive.
4. Alert on missed or failed backups and periodically restore both database and files into an isolated environment. Verify private documents remain private and the recovered app works. Never test a restore against the live database.

No automatic backup, Drive integration or restore process is configured by this plan.

## Dependency and production review

The local audit on 2026-11-16 reported Composer advisories, including a critical Laravel TokenGuard advisory, and high-severity npm production dependency advisories. An advisory does not by itself establish that a specific Aadi flow is exploitable. No dependency upgrades were applied. Run `composer audit --locked` and `npm audit --omit=dev` again before deployment; update affected packages in a dedicated, tested change and recheck the results. A Composer dry run with minimal changes indicated eight package updates, including Laravel.

Use `APP_ENV=production`, `APP_DEBUG=false` and the real HTTPS `APP_URL`. Keep `.env`, private documents and logs outside the public web root. Confirm HTTPS, email delivery, session settings, file permissions and access control on the deployed host.

Laravel normally reads `.env`. To use `.env.local` or `.env.production`, set `APP_ENV` in the server process environment to `local` or `production` before Laravel starts; Artisan also supports `--env=local` and `--env=production`. Setting `APP_ENV` only inside `.env` does not select another file. Environment variables supplied by the host take precedence over values in the file. Configuration caching skips environment-file loading, so regenerate the cache using the intended environment and restart workers after changes. Keep both environment files untracked and private.

The initial `.env.production` is a copy of the local configuration with only `APP_URL` changed; it is not production-ready. Before deployment, separately configure `APP_ENV=production`, `APP_DEBUG=false`, production database and mail settings, OAuth redirect URIs, and other service credentials and domains. Do not copy local credentials or a local database configuration into production without review.

## Acceptance checks

Run `php artisan migrate` before serving traffic after deploying page management. Admin → Page management controls the web home, Explore and provider profile pages independently; disabling one returns HTTP 503 with a retry hint and removes home or Explore from the sitemap. Admin, login and legal pages remain available. These switches do not disable mobile/API endpoints or related actions. Custom error pages preserve HTTP status codes and do not replace API JSON errors; production should use `APP_DEBUG=false`.

Before launch, use separate customer and provider test accounts to verify requests, replies, notification emails, live updates and fallback behavior. Confirm directions and mobile navigation on real phones. Also verify the documented privacy, authorization and file-upload behavior, email deliverability, backup restoration and queue alerting on the actual hosting plan.
