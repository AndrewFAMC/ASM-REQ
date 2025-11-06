-- Migration: Create asset_borrowings table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Create asset_borrowings table
CREATE TABLE IF NOT EXISTS `asset_borrowings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asset_id` INT(11) NOT NULL,
    `borrower_name` VARCHAR(255) NOT NULL,
    `borrower_type` ENUM('Teacher','Student') NOT NULL,
    `borrower_contact` VARCHAR(255) DEFAULT NULL,
    `expected_return_date` DATE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `borrowed_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active','returned') NOT NULL DEFAULT 'active',
    `return_date` DATETIME DEFAULT NULL,
    `return_notes` TEXT DEFAULT NULL,
    `recorded_by` VARCHAR(255) DEFAULT 'Staff User',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_asset_id` (`asset_id`),
    KEY `idx_status` (`status`),
    KEY `idx_borrower_type` (`borrower_type`),
    KEY `idx_borrowed_date` (`borrowed_date`),

    CONSTRAINT `fk_asset_borrowings_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index to prevent multiple active borrowings for the same asset
CREATE UNIQUE INDEX IF NOT EXISTS `idx_unique_active_borrowing` ON `asset_borrowings` (`asset_id`) WHERE `status` = 'active';

-- Add trigger to update return_date when status changes to returned
DELIMITER ;;

CREATE TRIGGER IF NOT EXISTS `trg_asset_borrowings_return_date`
    BEFORE UPDATE ON `asset_borrowings`
    FOR EACH ROW
BEGIN
    IF NEW.status = 'returned' AND OLD.status = 'active' THEN
        SET NEW.return_date = NOW();
    END IF;
END;;

DELIMITER ;

-- Verify table creation
-- DESCRIBE `asset_borrowings`;
-- SELECT COUNT(*) as borrowing_count FROM `asset_borrowings`;
