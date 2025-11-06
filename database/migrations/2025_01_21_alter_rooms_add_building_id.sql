-- Migration: Alter rooms table to add building_id and drop building column
-- Target DB: hcc_asset_management

-- Add building_id column to rooms
ALTER TABLE `rooms`
  ADD COLUMN IF NOT EXISTS `building_id` INT(11) DEFAULT NULL AFTER `campus_id`,
  ADD INDEX IF NOT EXISTS `idx_rooms_building_id` (`building_id`),
  ADD CONSTRAINT `fk_rooms_building_id` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Note: If there is existing data in 'building' column, you may need to:
-- 1. Insert corresponding buildings into buildings table
-- 2. Update rooms set building_id = (select id from buildings where building_name = rooms.building and campus_id = rooms.campus_id)
-- For now, assuming no data or manual migration

-- Drop the old building column
ALTER TABLE `rooms` DROP COLUMN IF EXISTS `building`;

-- Verify
-- DESCRIBE `rooms`;
-- SELECT COUNT(*) as rooms_count FROM `rooms`;
