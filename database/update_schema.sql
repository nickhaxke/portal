-- =====================================================================
-- EXECUTIVE E-LIBRARY — DATABASE UPDATE SCRIPT
-- Tarehe: 23 Oktoba 2026
-- Matumizi: Tumia script hii kupitia phpMyAdmin > SQL tab au Import.
-- Inafanya kazi kwenye matoleo yote ya MySQL na MariaDB bila hitilafu.
-- =====================================================================

-- 1. Tengeneza jedwali la libraries (kama halipo)
CREATE TABLE IF NOT EXISTS `libraries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'library',
  `cover_image` varchar(191) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` varchar(20) NOT NULL DEFAULT 'public',
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `libraries_slug_unique` (`slug`),
  KEY `libraries_is_published_index` (`is_published`),
  KEY `libraries_visibility_index` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Weka default library ya kwanza (Executive Core Library)
INSERT IGNORE INTO `libraries` (`id`, `name`, `slug`, `description`, `icon`, `is_published`, `visibility`, `sort_order`, `created_at`, `updated_at`)
VALUES (1, 'Executive Core Library', 'executive-core-library', 'Flagship repository of curated publications on strategic leadership, governance, and institutional wisdom.', 'library', 1, 'public', 1, NOW(), NOW());

-- 3. Ongeza safu wima mpya kwenye books kwa njia salama (Safe Stored Procedure)
DROP PROCEDURE IF EXISTS AddPortalUpgradeColumns;
DELIMITER //
CREATE PROCEDURE AddPortalUpgradeColumns()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'library_id') THEN
        ALTER TABLE `books` ADD COLUMN `library_id` bigint(20) unsigned NULL AFTER `id`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'language') THEN
        ALTER TABLE `books` ADD COLUMN `language` varchar(20) NOT NULL DEFAULT 'en' AFTER `publisher`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'series') THEN
        ALTER TABLE `books` ADD COLUMN `series` varchar(191) NULL AFTER `edition`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'series_number') THEN
        ALTER TABLE `books` ADD COLUMN `series_number` decimal(8,2) NULL AFTER `series`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'isbn13') THEN
        ALTER TABLE `books` ADD COLUMN `isbn13` varchar(30) NULL AFTER `isbn`;
    END IF;
END //
DELIMITER ;

CALL AddPortalUpgradeColumns();
DROP PROCEDURE IF EXISTS AddPortalUpgradeColumns;

-- Weka vitabu vilivyopo viwe chini ya Executive Core Library
UPDATE `books` SET `library_id` = 1 WHERE `library_id` IS NULL;

-- 4. Tengeneza jedwali la tags na book_tag (kama hayapo)
CREATE TABLE IF NOT EXISTS `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `book_tag` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_tag_book_id_tag_id_unique` (`book_id`,`tag_id`),
  KEY `book_tag_book_id_foreign` (`book_id`),
  KEY `book_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `book_tag_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Sajili migration kwenye jedwali la migrations la Laravel ili isirudiwe
INSERT IGNORE INTO `migrations` (`migration`, `batch`) 
SELECT '2026_10_22_065208_create_libraries_and_upgrade_books_and_tags_schema', COALESCE(MAX(`batch`), 0) + 1 
FROM `migrations`;