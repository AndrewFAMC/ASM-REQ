-- Migration: Create rooms table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Create rooms table
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `room_name` VARCHAR(255) NOT NULL,
    `room_code` VARCHAR(50) NOT NULL,
    `campus_id` INT(11) NOT NULL,
    `building` VARCHAR(255) DEFAULT NULL,
    `floor` VARCHAR(50) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_code_campus` (`room_code`, `campus_id`),
    KEY `idx_campus_id` (`campus_id`),
    KEY `idx_is_active` (`is_active`),

    CONSTRAINT `fk_rooms_campus_id` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rooms_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify table creation
-- DESCRIBE `rooms`;
-- SELECT COUNT(*) as room_count FROM `rooms`;
