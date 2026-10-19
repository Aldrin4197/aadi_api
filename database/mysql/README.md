# Aadi MySQL database

`aadi_mysql.sql` is an empty-database schema snapshot for MySQL 8.0 or newer. It matches the 19 migrations in `database/migrations` through `2026_10_19_000002_create_provider_applications`. It includes 19 tables, ULID relationships, indexes, JSON fields, provider applications, and Laravel's migration history. It contains no user accounts, passwords, API tokens, or sample listings.

## Import with phpMyAdmin

1. Create a new empty database (for example `aadi`) with `utf8mb4_unicode_ci` collation. On shared hosting, create it using the hosting database panel and use the assigned database name and user.
2. Select that database, open **Import**, choose `aadi_mysql.sql`, keep UTF-8 encoding, and run the import.
3. Configure the application's `.env` using the values in `mysql.env.example`, replacing the database name, username, and password with your own.
4. From the application directory run:

```sh
php artisan config:clear
php artisan migrate:status
php artisan migrate
```

The supplied migrations should show as **Ran**. `migrate` applies only migrations added after this snapshot. Do not import this file and then run `migrate:fresh`, `migrate:refresh`, or baseline rollbacks; these rebuild or remove tables and can destroy data.

For a fresh application only, generate an application key if `APP_KEY` is empty:

```sh
php artisan key:generate
php artisan storage:link
```

Keep an existing `APP_KEY` unchanged. The public storage link is for listing photos; provider verification documents remain on the private local disk.

## Import from the MySQL command line

Create/select your empty database first, then import (the `-p` option prompts for your password):

```sh
mysql -u YOUR_DB_USER -p aadi < database/mysql/aadi_mysql.sql
```

If your account may create databases, you can run this in the MySQL client before importing:

```sql
CREATE DATABASE aadi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The SQL file deliberately has no `CREATE DATABASE` or `USE` statement, so phpMyAdmin can import into hosting-prefixed database names. It also has no `DROP TABLE`, `IF NOT EXISTS`, or disabled foreign-key checks: importing into a populated database should fail rather than silently mixing schemas. Stop on an import error; MySQL DDL is not transactional, so a failed import can leave partial tables. Fix the cause and restart in a new empty database.

## Existing installations

Do not import this snapshot into an existing database. Set `DB_CONNECTION=mysql` only when that installation actually uses MySQL, back up its data, and use `php artisan migrate` for schema updates. This SQL does not transfer existing SQLite data.

Alternatively, for a new empty MySQL database with PHP dependencies installed, configure `.env` and run `php artisan migrate` directly instead of importing SQL. Migrations remain the source of truth.

## First administrator

Register your own account through `/register`, then promote that exact account in phpMyAdmin:

```sql
UPDATE users SET is_admin = 1 WHERE email = 'YOUR_REGISTERED_EMAIL';
```

Replace the email with your actual registered email and confirm exactly one row was matched. You can then use `/admin/login` and `/admin/dashboard`. Provider approvals remain separate from admin access. No default administrator or shared password is supplied. Do not run the existing demo seeders for a production setup.

## Validation

The snapshot was checked against the repository's migration table/column inventory and foreign-key targets. All 19 CREATE TABLE statements and the migration-history INSERT passed MySQL-dialect static parsing (the parser does not support the standard SET NAMES statement, so that statement was excluded from this check). A PHP/MySQL runtime was unavailable in the generation environment, so an actual MySQL import and Laravel integration run remain to be performed in your environment. The snapshot does not add unimplemented Google/Facebook identity storage or invent new application features.
