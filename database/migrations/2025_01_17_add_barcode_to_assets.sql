-- Migration: Add barcode column to assets table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Add barcode column to assets table
ALTER TABLE `assets` ADD COLUMN IF NOT EXISTS `barcode` VARCHAR(255) DEFAULT NULL AFTER `serial_number`;

-- Add index for barcode
CREATE INDEX IF NOT EXISTS `idx_barcode` ON `assets` (`barcode`);

-- Verify column addition
-- DESCRIBE `assets`;
-- SELECT barcode FROM `assets` LIMIT 5;
