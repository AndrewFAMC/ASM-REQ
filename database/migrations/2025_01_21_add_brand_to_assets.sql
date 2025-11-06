-- Migration: Add brand field to assets table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Add brand column to assets table
ALTER TABLE `assets` ADD COLUMN IF NOT EXISTS `brand` VARCHAR(255) NULL DEFAULT NULL AFTER `asset_name`;

-- You can run this script in your database management tool (like phpMyAdmin) to update the table structure.