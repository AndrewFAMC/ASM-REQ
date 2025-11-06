-- Migration: Create users table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','custodian','staff','user') NOT NULL DEFAULT 'user',
    `campus_id` INT(11) DEFAULT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `profile_picture` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_campus_id` (`campus_id`),

    CONSTRAINT `fk_users_campus_id` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123 hashed)
INSERT INTO `users` (`username`, `email`, `full_name`, `password_hash`, `role`, `campus_id`, `is_active`) VALUES
('admin', 'admin@hcc.edu.ph', 'System Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, TRUE)
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Verify table creation
-- DESCRIBE `users`;
-- SELECT * FROM `users` LIMIT 5;
