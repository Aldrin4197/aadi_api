-- Aadi application tables only; MySQL 8.0+.
-- Laravel/framework tables and migration history are intentionally excluded.
-- Requires an EXISTING Aadi users table with id CHAR(26), InnoDB,
-- utf8mb4_unicode_ci, and a primary/unique index on id.
-- A stock Laravel users.id BIGINT is NOT compatible with these ULID foreign keys.
-- Import only when the nine application tables below do not exist.
-- No DROP statements or disabled foreign-key checks are used.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

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
