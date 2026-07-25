-- ============================================================================
--  Live deployment helper — creates the `events` and `success_stories` tables
--  on a server where you cannot run `php artisan migrate` (e.g. phpMyAdmin only).
--
--  HOW TO USE (phpMyAdmin):
--    1. Open phpMyAdmin on the live host and select the `ci5_hireminds` database.
--    2. Go to the "SQL" tab, paste this whole file, and click "Go".
--
--  It is safe to run once. It also records the two migrations in Laravel's
--  `migrations` table, so a future `php artisan migrate` will NOT try to
--  re-create them. The seed rows at the bottom are OPTIONAL — delete that
--  section if you'd rather add all content yourself from the admin panel.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Upcoming Events
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `speaker` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL DEFAULT 'Live Event',
  `price` VARCHAR(255) NULL,
  `link` VARCHAR(255) NULL,
  `image` VARCHAR(255) NOT NULL,
  `tone` VARCHAR(255) NOT NULL DEFAULT 'purple',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `show_home` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_is_active_sort_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. Student Success Stories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `success_stories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) NULL,
  `salary` VARCHAR(255) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `tone` VARCHAR(255) NOT NULL DEFAULT 'olive',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `show_home` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `success_stories_is_active_sort_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. Tell Laravel these migrations are already applied
-- ---------------------------------------------------------------------------
SET @batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_07_25_000000_create_events_table', @batch),
  ('2026_07_25_000100_create_success_stories_table', @batch);

-- ---------------------------------------------------------------------------
-- 4. OPTIONAL seed content (the same placeholder cards as the original design).
--    Delete this whole section if you prefer to add everything from the admin
--    panel instead. Image files ship in /public/assets, so these paths resolve.
-- ---------------------------------------------------------------------------
INSERT INTO `events` (`speaker`, `title`, `type`, `price`, `link`, `image`, `tone`, `sort_order`, `is_active`, `show_home`, `created_at`, `updated_at`) VALUES
  ('Rochelle Fernandez', 'Learn about no-code tools',    'Live Event', '₹499/-', '#', 'assets/images/events/person-2.webp', 'purple', 0, 1, 1, NOW(), NOW()),
  ('Regina Phalange',    'Nail your interviews',         'Live Event', '₹499/-', '#', 'assets/images/events/person-3.webp', 'teal',   1, 1, 1, NOW(), NOW()),
  ('Rachel Bennett',     'Sell your first product online','Live Event','₹499/-', '#', 'assets/images/events/person-1.webp', 'green',  2, 1, 1, NOW(), NOW());

INSERT INTO `success_stories` (`name`, `role`, `salary`, `image`, `tone`, `sort_order`, `is_active`, `show_home`, `created_at`, `updated_at`) VALUES
  ('Aayushman Pravin', 'Software Developer', '9.0', 'assets/images/success-story/person-1.webp', 'olive',  0, 1, 1, NOW(), NOW()),
  ('Aayushman Pravin', 'Software Developer', '9.0', 'assets/images/success-story/person-2.webp', 'teal',   1, 1, 1, NOW(), NOW()),
  ('Aayushman Pravin', 'Software Developer', '9.0', 'assets/images/success-story/person-3.webp', 'green',  2, 1, 1, NOW(), NOW()),
  ('Aayushman Pravin', 'Software Developer', '9.0', 'assets/images/success-story/person-4.webp', 'violet', 3, 1, 1, NOW(), NOW());
