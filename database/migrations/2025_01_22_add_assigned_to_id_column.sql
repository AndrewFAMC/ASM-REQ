-- Migration: Add assigned_to_id to assets table
-- Target DB: hcc_asset_management
-- This script adds a foreign key to the users table for tracking assignments.

-- Step 1: Add the 'assigned_to_id' column to the 'assets' table.
-- Using IF NOT EXISTS for safety, so it can be run multiple times.
ALTER TABLE `assets`
ADD COLUMN IF NOT EXISTS `assigned_to_id` INT(11) NULL DEFAULT NULL AFTER `assigned_email`,
ADD INDEX IF NOT EXISTS `idx_assets_assigned_to_id` (`assigned_to_id`);

-- Step 2: Add the foreign key constraint.
-- We check if the constraint already exists before adding it.
SET @constraint_name = 'fk_assets_assigned_to_user';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND CONSTRAINT_NAME = @constraint_name) = 0,
    'ALTER TABLE `assets` ADD CONSTRAINT `fk_assets_assigned_to_user` FOREIGN KEY (`assigned_to_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;',
    'SELECT "Constraint already exists." AS status;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;