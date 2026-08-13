-- ============================================================================
--  Live deployment helper — brings the live database up to the current code.
--
--  WHY THIS FILE EXISTS
--    Deploying code does NOT create tables. Four migrations and one seeder have
--    shipped without reaching live:
--
--      2026_08_10_000100_create_about_section_tables
--      2026_08_10_000200_create_course_schedules_table
--      2026_08_10_000300_add_batch_to_course_enquiries_table
--      2026_08_13_000200_move_schedules_into_their_own_module
--      AboutSectionSeeder   (the four About Us blocks)
--
--    Until those run on live, the home page shows no schedule section and the
--    About Us page shows no Story / Purpose / Features / Approach — both are
--    wrapped in @if(count(...)), so they simply vanish instead of erroring.
--
--  IF YOU HAVE SSH / TERMINAL ACCESS, DON'T USE THIS FILE — run instead:
--      php artisan migrate --force
--      php artisan db:seed --class=AboutSectionSeeder --force
--      php artisan config:clear && php artisan view:clear && php artisan route:clear
--
--  HOW TO USE THIS FILE (phpMyAdmin only):
--    1. Open phpMyAdmin on the live host, select the `ci5_hireminds` database.
--    2. SQL tab → paste this whole file → Go.
--    3. Read section 8 at the bottom — the batches themselves still have to be
--       entered in the panel, under Courses → Schedule.
--
--  Safe to run more than once: every CREATE is IF NOT EXISTS, every ALTER is
--  guarded, the migration rows are only inserted when absent, and the About Us
--  content is only seeded into a section that currently has no rows (so it will
--  never overwrite copy someone has already edited in the panel).
-- ============================================================================


-- ---------------------------------------------------------------------------
-- 1. About Us — the four editable blocks
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `about_sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(40) NOT NULL,
  `label` VARCHAR(255) NULL,
  `title` VARCHAR(255) NULL,
  `lead` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `about_sections_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. About Us — the repeating rows inside each block
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `about_section_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `about_section_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NULL,
  `text` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `tone` VARCHAR(30) NULL,
  `position` VARCHAR(10) NULL,
  `zoom` TINYINT(1) NOT NULL DEFAULT 0,
  `eyes` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `about_section_items_lookup` (`about_section_id`, `is_active`, `display_order`),
  CONSTRAINT `about_section_items_about_section_id_foreign`
    FOREIGN KEY (`about_section_id`) REFERENCES `about_sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. Course schedules — one row per upcoming batch
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `duration` VARCHAR(60) NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `fee` DECIMAL(10,2) NULL,
  `show_fee` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_schedules_lookup` (`course_id`, `is_active`, `start_date`),
  KEY `course_schedules_start_date_index` (`start_date`),
  CONSTRAINT `course_schedules_course_id_foreign`
    FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. Batch timing, and the retirement of courses.schedule_enabled.
--
--    An earlier version of this file added `schedule_enabled` to `courses` — a
--    per-course master switch that lived at the top of the schedule repeater in
--    the course form. Schedules are their own module now, that repeater is gone,
--    and each batch's own `is_active` is the only thing deciding whether it
--    shows. A hidden column silently suppressing rows the admin can see in the
--    listing would be worse than no switch, so it is dropped here if present.
--
--    On a live database that never got the earlier version, both statements are
--    no-ops. (Guarded because MySQL has no "ADD/DROP COLUMN IF [NOT] EXISTS".)
-- ---------------------------------------------------------------------------
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'course_schedules'
      AND COLUMN_NAME  = 'start_time') = 0,
  'ALTER TABLE `course_schedules` ADD COLUMN `start_time` TIME NULL AFTER `duration`, ADD COLUMN `end_time` TIME NULL AFTER `start_time`',
  'SELECT ''course_schedules timing columns already present'''));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'courses'
      AND COLUMN_NAME  = 'schedule_enabled') > 0,
  'ALTER TABLE `courses` DROP COLUMN `schedule_enabled`',
  'SELECT ''courses.schedule_enabled already gone'''));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 5. course_enquiries.batch — which batch an Apply click was about
-- ---------------------------------------------------------------------------
SET @sql := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'course_enquiries'
      AND COLUMN_NAME  = 'batch') = 0,
  'ALTER TABLE `course_enquiries` ADD COLUMN `batch` VARCHAR(255) NULL AFTER `course_name`',
  'SELECT ''course_enquiries.batch already present'''));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 6. Tell Laravel these migrations are already applied, so a later
--    `php artisan migrate` does not try to run them again.
-- ---------------------------------------------------------------------------
SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.name, @batch FROM (
  SELECT '2026_08_10_000100_create_about_section_tables'        AS name
  UNION ALL SELECT '2026_08_10_000200_create_course_schedules_table'
  UNION ALL SELECT '2026_08_10_000300_add_batch_to_course_enquiries_table'
  UNION ALL SELECT '2026_08_13_000200_move_schedules_into_their_own_module'
) AS m
LEFT JOIN `migrations` existing ON existing.`migration` = m.name
WHERE existing.`id` IS NULL;


-- ============================================================================
-- 7. About Us content — the same copy AboutSectionSeeder writes.
--
--    The section row is created if missing; its items are inserted ONLY when
--    that section currently has none, so re-running this never clobbers copy
--    edited from the panel. Image paths point at artwork already in
--    /public/assets, so they resolve without uploading anything.
-- ============================================================================

-- -------- 7a. OUR STORY -----------------------------------------------------
INSERT INTO `about_sections` (`key`, `label`, `title`, `lead`, `image`, `is_active`, `created_at`, `updated_at`)
VALUES ('story', 'Our Story', NULL, NULL, NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

SET @story := (SELECT `id` FROM `about_sections` WHERE `key` = 'story');
SET @n     := (SELECT COUNT(*) FROM `about_section_items` WHERE `about_section_id` = @story);

INSERT INTO `about_section_items`
  (`about_section_id`, `title`, `text`, `image`, `tone`, `position`, `zoom`, `eyes`, `display_order`, `is_active`, `created_at`, `updated_at`)
SELECT @story, s.title, s.text, s.image, NULL, NULL, s.zoom, 0, s.display_order, 1, NOW(), NOW()
FROM (
  SELECT 'It All Started with a Simple Mission' AS title,
         'HireMinds Academy was founded with a clear purpose—to bridge the gap between traditional education and real industry expectations. We recognized that many learners possessed academic knowledge but lacked the practical skills needed to build successful careers. This vision inspired us to create training programs that focus on hands-on learning, expert mentorship, and career readiness from day one.' AS text,
         'assets/images/about-page/people-1.webp' AS image, 0 AS zoom, 0 AS display_order
  UNION ALL SELECT 'Learning That Matches Industry Needs',
         'Every course at HireMinds is carefully designed around current industry requirements rather than outdated academic models. Our learners gain practical experience through real-world projects, interactive classroom sessions, case studies, and expert guidance. By focusing on skills that employers actively seek, we help students build confidence while preparing them for professional challenges and workplace expectations.',
         'assets/images/about-page/people-2.webp', 0, 1
  UNION ALL SELECT 'Guiding Every Step of the Career Journey',
         'Our responsibility goes beyond delivering quality training. We support learners throughout their career journey with personalized mentorship, resume building, interview preparation, communication skills, and placement assistance. Every student receives the guidance needed to confidently transition from learning to employment, ensuring they are prepared for opportunities in today''s competitive job market.',
         'assets/images/about-page/people-3.webp', 0, 2
  -- people-4.webp ships with a baked-in shadow border, so zoom = 1 crops past it
  UNION ALL SELECT 'Building Careers, Creating Impact',
         'Today, HireMinds Academy continues to empower students, graduates, career switchers, and working professionals through industry-focused education. Every successful placement, completed project, and learner achievement reflects our commitment to creating meaningful career opportunities. As industries continue to evolve, we remain dedicated to helping learners develop future-ready skills that support long-term professional growth and success.',
         'assets/images/about-page/people-4.webp', 1, 3
  UNION ALL SELECT 'Shaping the Future of Professional Learning',
         'As technology and industries continue to evolve, HireMinds Academy remains committed to delivering future-ready education that adapts to changing workforce demands. We continuously update our programs, strengthen industry partnerships, and introduce innovative learning experiences that help learners stay competitive. Our journey doesn''t end with a certificate—it begins with building confident professionals ready to make a lasting impact in their careers.',
         'assets/images/about-page/people-5.webp', 0, 4
) AS s
WHERE @n = 0;

-- -------- 7b. OUR PURPOSE ---------------------------------------------------
INSERT INTO `about_sections` (`key`, `label`, `title`, `lead`, `image`, `is_active`, `created_at`, `updated_at`)
VALUES ('purpose', 'Our Purpose', 'Driven by Purpose. Guided by Vision.',
        'Everything we do is built around one goal—to equip learners with practical skills, inspire confidence, and create opportunities that lead to meaningful careers and long-term success.',
        'assets/images/about-page/our-purpose.webp', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

SET @purpose := (SELECT `id` FROM `about_sections` WHERE `key` = 'purpose');
SET @n       := (SELECT COUNT(*) FROM `about_section_items` WHERE `about_section_id` = @purpose);

INSERT INTO `about_section_items`
  (`about_section_id`, `title`, `text`, `image`, `tone`, `position`, `zoom`, `eyes`, `display_order`, `is_active`, `created_at`, `updated_at`)
SELECT @purpose, s.title, s.text, NULL, s.tone, NULL, 0, 0, s.display_order, 1, NOW(), NOW()
FROM (
  SELECT 'Our Vision !' AS title,
         'To deliver practical, industry-focused training that empowers individuals with job-ready skills, builds confidence through hands-on learning, and prepares them for long-term career success. We are committed to bridging the gap between academic knowledge and industry expectations by providing expert-led instruction, real-world projects, continuous mentorship, and career guidance. At the same time, today''s fast-changing business environment.' AS text,
         'vision' AS tone, 0 AS display_order
  UNION ALL SELECT 'Our Mission !',
         'To empower individuals and organizations by building skilled, confident, and future-ready professionals through practical, industry-focused learning experiences. We envision creating a workforce that embraces innovation, adapts to emerging technologies, and thrives in an evolving job market. By fostering continuous learning, professional excellence, and career growth, we aim to strengthening organizations across industries.',
         'mission', 1
) AS s
WHERE @n = 0;

-- -------- 7c. OUR FEATURES --------------------------------------------------
INSERT INTO `about_sections` (`key`, `label`, `title`, `lead`, `image`, `is_active`, `created_at`, `updated_at`)
VALUES ('features', 'Our Features', 'Shaping Future-Ready Professionals',
        'At HireMinds Academy, we combine expert guidance, practical training, and career support to help learners achieve their professional goals.',
        NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

SET @features := (SELECT `id` FROM `about_sections` WHERE `key` = 'features');
SET @n        := (SELECT COUNT(*) FROM `about_section_items` WHERE `about_section_id` = @features);

INSERT INTO `about_section_items`
  (`about_section_id`, `title`, `text`, `image`, `tone`, `position`, `zoom`, `eyes`, `display_order`, `is_active`, `created_at`, `updated_at`)
SELECT @features, s.title, s.text, NULL, s.tone, NULL, 0, s.eyes, s.display_order, 1, NOW(), NOW()
FROM (
  SELECT 'Who We Are' AS title, NULL AS text, 'purple' AS tone, 1 AS eyes, 0 AS display_order
  UNION ALL SELECT 'Industry-Focused Training', 'Learn with a curriculum designed around real industry requirements.', 'red',    0, 1
  UNION ALL SELECT 'Hands-On Learning',         'Learn by doing with projects and practical exercises.',                'peach',  0, 2
  UNION ALL SELECT 'Career Support',            'Get guidance, interview preparation, and placement assistance.',       'yellow', 0, 3
) AS s
WHERE @n = 0;

-- -------- 7d. OUR APPROACH --------------------------------------------------
INSERT INTO `about_sections` (`key`, `label`, `title`, `lead`, `image`, `is_active`, `created_at`, `updated_at`)
VALUES ('approach', 'Our Approach', 'Our Learning Approach',
        'From classroom sessions to career guidance, every step of our training is designed to prepare learners for real-world opportunities.',
        'assets/images/about-page/our-approach-square.webp', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

SET @approach := (SELECT `id` FROM `about_sections` WHERE `key` = 'approach');
SET @n        := (SELECT COUNT(*) FROM `about_section_items` WHERE `about_section_id` = @approach);

INSERT INTO `about_section_items`
  (`about_section_id`, `title`, `text`, `image`, `tone`, `position`, `zoom`, `eyes`, `display_order`, `is_active`, `created_at`, `updated_at`)
SELECT @approach, s.title, NULL, NULL, s.tone, s.position, 0, 0, s.display_order, 1, NOW(), NOW()
FROM (
  SELECT 'Industry-Aligned Curriculum' AS title, 'blue'   AS tone, 'lt' AS position, 0 AS display_order
  UNION ALL SELECT 'Hands-On Projects',   'pink',   'lm', 1
  UNION ALL SELECT 'Classroom Learning',  'beige',  'lb', 2
  UNION ALL SELECT 'Hands-On Projects',   'green',  'rt', 3
  UNION ALL SELECT 'Career Guidance',     'yellow', 'rm', 4
  UNION ALL SELECT 'Placement Support',   'blue',   'rb', 5
) AS s
WHERE @n = 0;


-- ============================================================================
-- 8. AFTER RUNNING THIS — two things still have to be done in the panel
--
--  a) BATCHES. Unlike About Us, these are real business data: this file cannot
--     invent start dates and fees. Admin → Courses → Schedule → "Add Schedule":
--     choose the category, then the course inside it, then the dates, duration,
--     daily timing (optional) and fee. "Show Fee" off — or no fee entered —
--     prints "Contact for Fee" rather than ₹0.
--
--     The website lists a batch until it FINISHES (CourseSchedule::scopeUpcoming)
--     — one that has already started but is still running stays listed, badged
--     "Running" in the panel. A batch with no end date is the exception: there is
--     nothing to expire it against, so it drops off the day it starts.
--
--     Quick check that a batch is live-visible:
--       SELECT c.name, c.is_active, s.start_date, s.end_date, s.is_active
--       FROM course_schedules s JOIN courses c ON c.id = s.course_id
--       ORDER BY COALESCE(s.end_date, s.start_date);
--     A row appears on the site when c.is_active = 1 AND s.is_active = 1
--     AND COALESCE(s.end_date, s.start_date) >= CURDATE().
--     The listing header states the same number ("N showing on the website").
--
--  b) MODULE ACCESS. "Schedule" is a new module. The main admin already has it;
--     any sub-admin who should manage batches needs it ticked under
--     Admin → Users → (edit) → Module Access → Courses → Schedule.
--
--  (The reel cards need nothing: each one now shows a frame of its own clip
--   while it waits, so there is no cover image to upload.)
-- ============================================================================
