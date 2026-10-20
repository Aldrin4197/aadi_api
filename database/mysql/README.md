# Aadi application tables for MySQL

`aadi_mysql.sql` contains only these **nine Aadi application tables**:

- `trust_badges`
- `listings`
- `viewing_requests`
- `favorites`
- `saved_searches`
- `listing_views`
- `vehicle_listings`
- `service_listings`
- `provider_applications`

Excluded: `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`, and `migrations`. No migration-history rows, accounts, passwords, tokens, or demo data are included.

## Prerequisite: existing Aadi users table

This is an application-table export, not a complete Laravel database bootstrap. The selected database must already contain Aadi's `users` table. Its `id` must be `CHAR(26)` with a primary or unique index, InnoDB storage, and `utf8mb4_unicode_ci` collation, matching the repository's ULID user identifiers. A default Laravel `BIGINT` user ID will cause foreign-key errors. Do not change existing user ID types blindly.

The full web app also needs Aadi's custom user fields (provider role statuses and `is_admin`) and whichever framework tables its configured authentication, session, queue, and cache drivers use. These remain managed by Laravel's migrations and are intentionally outside this SQL export.

## phpMyAdmin import

1. Select the MySQL database containing the compatible Aadi users table.
2. Confirm that none of the nine tables above already exists.
3. Open **Import**, select `aadi_mysql.sql`, use UTF-8 encoding, and import.

For a command-line import:

```sh
mysql -u YOUR_DB_USER -p YOUR_DATABASE < database/mysql/aadi_mysql.sql
```

There is no `CREATE DATABASE` or `USE` statement, allowing hosting-prefixed database names. There are no destructive statements or disabled foreign-key checks. Stop on any error; MySQL DDL commits implicitly and a failed import can leave partial tables.

## Laravel installation and migrations

For a normal new Aadi installation, configure `.env` with `DB_CONNECTION=mysql` and run `php artisan migrate` **instead of importing this SQL**. This lets Laravel create and track all required tables itself.

Do not import this SQL and then run the complete migration history against the same tables: this export intentionally omits the migration ledger, so Laravel will not know the application tables were imported. Existing installations should use their migrations for updates, not this export. Do not run `migrate:fresh` or `migrate:refresh` to resolve a duplicate-table error; they can destroy existing data.

`mysql.env.example` contains only MySQL connection settings. It does not change session, cache, or queue drivers or remove their dependencies. This export does not transfer SQLite data.

## Validation

All nine application table definitions retain the original snapshot's columns, indexes, and constraints, matching repository migrations through `2026_10_19_000002_create_provider_applications`. All nine CREATE TABLE statements are byte-for-byte identical to definitions that passed MySQL-dialect static parsing in the original snapshot. The standard SET NAMES statement was excluded from that parser check because the parser does not support it. No PHP/MySQL runtime was available, so an actual MySQL import remains untested.
