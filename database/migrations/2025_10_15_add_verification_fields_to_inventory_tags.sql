-- Migration: Add verification fields to inventory_tags table
-- Target DB: hcc_asset_management

-- Add columns to track who verified the asset and when.
ALTER TABLE `inventory_tags`
  ADD COLUMN `verified_by_user_id` INT(11) NULL DEFAULT NULL AFTER `assigned_by_custodian_id`,
  ADD COLUMN `verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `verified_by_user_id`;

-- Add a foreign key to link back to the users table.
ALTER TABLE `inventory_tags`
  ADD CONSTRAINT `fk_inventory_tags_verified_by`
  FOREIGN KEY (`verified_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;