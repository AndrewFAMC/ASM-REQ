-- Migration: Create asset_requests table
-- Target DB: hcc_asset_management
-- This table manages asset requests from staff to admin for approval and custodian release

CREATE TABLE IF NOT EXISTS `asset_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asset_id` INT(11) NOT NULL,
    `requested_by` INT(11) NOT NULL,
    `campus_id` INT(11) NOT NULL,
    `category_id` INT(11) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `receipt_code` VARCHAR(50) NOT NULL UNIQUE,
    `status` ENUM('pending', 'approved', 'rejected', 'released', 'returned') NOT NULL DEFAULT 'pending',
    `approved_by` INT(11) DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `released_by` INT(11) DEFAULT NULL,
    `released_date` DATETIME DEFAULT NULL,
    `expected_return_date` DATE DEFAULT NULL,
    `return_date` DATETIME DEFAULT NULL,
    `return_notes` TEXT DEFAULT NULL,
    `admin_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_receipt_code` (`receipt_code`),
    KEY `idx_asset_id` (`asset_id`),
    KEY `idx_requested_by` (`requested_by`),
    KEY `idx_campus_id` (`campus_id`),
    KEY `idx_status` (`status`),
    KEY `idx_approved_by` (`approved_by`),
    KEY `idx_released_by` (`released_by`),

    CONSTRAINT `fk_asset_requests_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asset_requests_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asset_requests_campus_id` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_asset_requests_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_asset_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_asset_requests_released_by` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
