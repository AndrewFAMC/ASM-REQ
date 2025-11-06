-- Migration: Create campuses table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Create campuses table
CREATE TABLE IF NOT EXISTS `campuses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `campus_name` VARCHAR(255) NOT NULL,
    `campus_code` VARCHAR(50) NOT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_campus_code` (`campus_code`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default campuses
INSERT INTO `campuses` (`campus_name`, `campus_code`) VALUES
('Sta. Rosa, Nueva Ecija', 'main'),
('Conception, Tarlac', 'north'),
('Santa Ana, Pampanga', 'south')
ON DUPLICATE KEY UPDATE `campus_name` = VALUES(`campus_name`);

-- Verify table creation
-- DESCRIBE `campuses`;
-- SELECT * FROM `campuses`;
