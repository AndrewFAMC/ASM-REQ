-- Migration: Add Offices and Super Admin Role
-- Target DB: hcc_asset_management
-- This script introduces the 'offices' table and updates the 'users' and 'assets' tables to support the new workflow.

START TRANSACTION;

-- 1. Create the 'offices' table
-- This table will store the different departments or offices within a campus.
CREATE TABLE IF NOT EXISTS `offices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `campus_id` INT NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_office_per_campus` (`name`, `campus_id`),
  FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Update the 'users' table
-- Add 'super_admin' and 'office' to the role enum.
-- Add a nullable 'office_id' to link users to an office.
ALTER TABLE `users`
MODIFY COLUMN `role` ENUM('staff', 'custodian', 'admin', 'super_admin', 'office') NOT NULL,
ADD COLUMN `office_id` INT NULL AFTER `campus_id`,
ADD CONSTRAINT `fk_users_office_id` FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE SET NULL;

-- 3. Update the 'assets' table
-- Add a nullable 'office_id' to link assets to a specific office/department.
ALTER TABLE `assets`
ADD COLUMN `office_id` INT NULL AFTER `campus_id`,
ADD CONSTRAINT `fk_assets_office_id` FOREIGN KEY (`office_id`) REFERENCES `offices`(`id`) ON DELETE SET NULL;

-- 4. Update 'asset_requests' table for the new workflow
-- We will add more columns here later to track approvals from Office, Custodian, and Admin.

COMMIT;