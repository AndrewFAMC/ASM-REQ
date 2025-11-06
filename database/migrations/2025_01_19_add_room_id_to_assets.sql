-- Migration: Add room_id to assets table
-- Target DB: hcc_asset_management

-- Add room_id column to assets
ALTER TABLE `assets`
  ADD COLUMN IF NOT EXISTS `room_id` INT(11) DEFAULT NULL AFTER `location`,
  ADD INDEX IF NOT EXISTS `idx_assets_room_id` (`room_id`),
  ADD CONSTRAINT `fk_assets_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Verify
-- DESCRIBE `assets`;
-- SELECT COUNT(*) as assets_with_room FROM `assets` WHERE `room_id` IS NOT NULL;
