-- Migration: Add campus_id to activity_log table
-- Target DB: hcc_asset_management

-- Step 1: Add the campus_id column to the activity_log table.
-- It's set to NULL initially to handle existing records.
ALTER TABLE `activity_log`
  ADD COLUMN `campus_id` INT(11) NULL DEFAULT NULL AFTER `performed_by`,
  ADD INDEX `idx_campus_id` (`campus_id`);

-- Step 2: Backfill the campus_id for existing activity logs.
-- This updates logs by joining with the assets table to find the correct campus.
UPDATE `activity_log` al
JOIN `assets` a ON al.asset_id = a.id
SET al.campus_id = a.campus_id
WHERE al.campus_id IS NULL AND al.asset_id IS NOT NULL;

-- Step 3: Add a foreign key constraint for data integrity.
-- This ensures that the campus_id in activity_log refers to a valid campus.
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_activity_log_campus_id` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Verify the changes (optional)
-- DESCRIBE `activity_log`;