-- Migration: Add borrowable fields to inventory_tags table
-- Target DB: hcc_asset_management

ALTER TABLE `inventory_tags`
  ADD COLUMN `is_borrowable` BOOLEAN NOT NULL DEFAULT FALSE AFTER `status`,
  ADD COLUMN `borrowable_quantity` INT(11) NOT NULL DEFAULT 0 AFTER `is_borrowable`;