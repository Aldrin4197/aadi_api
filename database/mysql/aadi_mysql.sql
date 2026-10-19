-- Aadi MySQL schema snapshot, 2026-10-20.
-- Source: aadi_api main df384012b5e5c3d6beb07b72d9cf31601db0fd22.
-- Target: MySQL 8.0+, InnoDB, utf8mb4_unicode_ci.
-- Select an EMPTY database in phpMyAdmin before importing this file.
-- No DROP, database creation, user accounts, or demo records are included.
-- Do not import over an existing installation; use php artisan migrate there.
-- MySQL DDL commits implicitly. Stop on the first error; do not force continuation.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` CHAR(26) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `landlord_status` VARCHAR(255) NOT NULL DEFAULT 'none',
  `vehicle_owner_status` ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
  `vehicle_owner_business_name` VARCHAR(255) NULL,
  `vehicle_owner_licenses` JSON NULL,
  `vehicle_owner_inventory` JSON NULL,
  `vehicle_owner_certificates` JSON NULL,
  `service_provider_status` ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
  `service_provider_business_name` VARCHAR(255) NULL,
  `service_provider_skills` JSON NULL,
  `service_provider_certifications` JSON NULL,
  `service_provider_service_area` VARCHAR(255) NULL,
  `service_provider_years_experience` INT NULL,
  `phone` VARCHAR(255) NULL,
  `avatar_url` VARCHAR(255) NULL,
  `rating_average` DECIMAL(3,2) NOT NULL DEFAULT 0,
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `trust_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` CHAR(26) NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` SMALLINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` VARCHAR(255) NOT NULL,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` CHAR(26) NOT NULL,
  `name` TEXT NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trust_badges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` CHAR(26) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `granted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trust_badges_user_id_type_unique` (`user_id`,`type`),
  KEY `trust_badges_status_index` (`status`),
  CONSTRAINT `trust_badges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `listings` (
  `id` CHAR(26) NOT NULL,
  `owner_id` CHAR(26) NOT NULL,
  `owner_name` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(255) NOT NULL DEFAULT 'space_rental',
  `type` VARCHAR(255) NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `pricing_type` VARCHAR(255) NOT NULL DEFAULT 'fixed',
  `currency` VARCHAR(255) NOT NULL DEFAULT '₱',
  `lat` DECIMAL(10,7) NOT NULL,
  `lng` DECIMAL(10,7) NOT NULL,
  `address` VARCHAR(255) NULL,
  `city` VARCHAR(255) NULL,
  `photos` JSON NULL,
  `amenities` JSON NULL,
  `attributes` JSON NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'active',
  `property_verification` VARCHAR(255) NOT NULL DEFAULT 'none',
  `rating_average` DECIMAL(3,2) NOT NULL DEFAULT 0,
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `listings_owner_id_foreign` (`owner_id`),
  KEY `listings_status_type_index` (`status`,`type`),
  KEY `listings_lat_lng_index` (`lat`,`lng`),
  KEY `listings_category_status_index` (`category`,`status`),
  CONSTRAINT `listings_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `viewing_requests` (
  `id` CHAR(26) NOT NULL,
  `listing_id` CHAR(26) NOT NULL,
  `listing_title` VARCHAR(255) NOT NULL,
  `tenant_id` CHAR(26) NOT NULL,
  `tenant_name` VARCHAR(255) NOT NULL,
  `owner_id` CHAR(26) NOT NULL,
  `owner_name` VARCHAR(255) NOT NULL,
  `requested_at` TIMESTAMP NOT NULL,
  `proposed_at` TIMESTAMP NULL DEFAULT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `viewing_requests_listing_id_foreign` (`listing_id`),
  KEY `viewing_requests_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `viewing_requests_owner_id_status_index` (`owner_id`,`status`),
  CONSTRAINT `viewing_requests_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `viewing_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `viewing_requests_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favorites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` CHAR(26) NOT NULL,
  `listing_id` CHAR(26) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favorites_user_id_listing_id_unique` (`user_id`,`listing_id`),
  KEY `favorites_listing_id_foreign` (`listing_id`),
  CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `saved_searches` (
  `id` CHAR(26) NOT NULL,
  `user_id` CHAR(26) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `query` JSON NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_searches_user_id_index` (`user_id`),
  CONSTRAINT `saved_searches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `listing_views` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` CHAR(26) NOT NULL,
  `user_id` CHAR(26) NULL,
  `ip_address` VARCHAR(255) NULL,
  `viewed_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `listing_views_listing_id_viewed_at_index` (`listing_id`,`viewed_at`),
  KEY `listing_views_user_id_foreign` (`user_id`),
  CONSTRAINT `listing_views_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `listing_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vehicle_listings` (
  `id` CHAR(26) NOT NULL,
  `owner_id` CHAR(26) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `vehicle_type` VARCHAR(255) NOT NULL,
  `brand` VARCHAR(255) NOT NULL,
  `model` VARCHAR(255) NOT NULL,
  `year` INT NOT NULL,
  `plate_number` VARCHAR(255) NULL,
  `seating_capacity` INT NULL,
  `fuel_type` VARCHAR(255) NULL,
  `transmission` VARCHAR(255) NULL,
  `features` JSON NULL,
  `price_per_day` DECIMAL(10,2) NOT NULL,
  `price_per_week` DECIMAL(10,2) NULL,
  `price_per_month` DECIMAL(10,2) NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'PHP',
  `location` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10,7) NULL,
  `longitude` DECIMAL(10,7) NULL,
  `status` ENUM('available','rented','maintenance','inactive') NOT NULL DEFAULT 'available',
  `images` JSON NULL,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `rating_average` DECIMAL(3,2) NOT NULL DEFAULT 0,
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `available_from` DATE NULL,
  `available_to` DATE NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_listings_owner_id_index` (`owner_id`),
  KEY `vehicle_listings_vehicle_type_index` (`vehicle_type`),
  KEY `vehicle_listings_status_index` (`status`),
  KEY `vehicle_listings_latitude_longitude_index` (`latitude`,`longitude`),
  CONSTRAINT `vehicle_listings_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `service_listings` (
  `id` CHAR(26) NOT NULL,
  `provider_id` CHAR(26) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `service_type` VARCHAR(255) NOT NULL,
  `service_category` VARCHAR(255) NULL,
  `skills_required` JSON NULL,
  `price_type` ENUM('fixed','hourly','per_project') NOT NULL DEFAULT 'fixed',
  `price_amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'PHP',
  `service_area` VARCHAR(255) NOT NULL,
  `availability_schedule` JSON NULL,
  `response_time_minutes` INT NULL,
  `estimated_duration_minutes` INT NULL,
  `status` ENUM('available','busy','inactive') NOT NULL DEFAULT 'available',
  `images` JSON NULL,
  `certifications` JSON NULL,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `rating_average` DECIMAL(3,2) NOT NULL DEFAULT 0,
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `completion_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `latitude` DECIMAL(10,7) NULL,
  `longitude` DECIMAL(10,7) NULL,
  PRIMARY KEY (`id`),
  KEY `service_listings_provider_id_index` (`provider_id`),
  KEY `service_listings_service_type_index` (`service_type`),
  KEY `service_listings_status_index` (`status`),
  KEY `service_listings_latitude_longitude_index` (`latitude`,`longitude`),
  CONSTRAINT `service_listings_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `provider_applications` (
  `id` CHAR(26) NOT NULL,
  `user_id` CHAR(26) NOT NULL,
  `service` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `display_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `area` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `documents` JSON NOT NULL,
  `review_note` TEXT NULL,
  `reviewer_id` CHAR(26) NULL,
  `submitted_at` TIMESTAMP NOT NULL,
  `decided_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_applications_user_id_service_unique` (`user_id`,`service`),
  KEY `provider_applications_reviewer_id_foreign` (`reviewer_id`),
  CONSTRAINT `provider_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_applications_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record only after all CREATE statements succeeded.
-- Snapshot baseline: existing migrations are represented in the schema above.
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_08_10_083342_create_personal_access_tokens_table', 1),
('2026_08_10_083714_add_aadi_fields_to_users_table', 1),
('2026_08_10_083736_create_trust_badges_table', 1),
('2026_08_10_083737_create_listings_table', 1),
('2026_08_10_083738_create_viewing_requests_table', 1),
('2026_08_10_083739_create_favorites_table', 1),
('2026_08_10_083740_create_saved_searches_table', 1),
('2026_08_10_083741_create_listing_views_table', 1),
('2026_08_31_141500_add_category_to_listings_table', 1),
('2026_08_31_143246_add_service_provider_roles_to_users_table', 1),
('2026_08_31_150507_create_vehicle_listings_table', 1),
('2026_08_31_150508_create_service_listings_table', 1),
('2026_09_03_082500_alter_personal_access_tokens_tokenable_id_to_ulid', 1),
('2026_09_03_083100_alter_sessions_user_id_to_ulid', 1),
('2026_10_19_000001_add_marketplace_fields', 1),
('2026_10_19_000002_create_provider_applications', 1);
