-- Migration: Create buildings table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Create buildings table
CREATE TABLE IF NOT EXISTS `buildings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `building_name` VARCHAR(255) NOT NULL,
    `campus_id` INT(11) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_building_name_campus` (`building_name`, `campus_id`),
    KEY `idx_campus_id` (`campus_id`),
    KEY `idx_is_active` (`is_active`),

    CONSTRAINT `fk_buildings_campus_id` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_buildings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify table creation
-- DESCRIBE `buildings`;
-- SELECT COUNT(*) as building_count FROM `buildings`;
