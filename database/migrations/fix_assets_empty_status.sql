-- Migration: Fix empty/null status values in assets table
-- Purpose: Ensure all assets have a valid status value
-- Date: 2025-11-11

-- First, let's check what invalid status values exist
-- (This is for documentation purposes - you can run this separately to see what needs fixing)
-- SELECT id, asset_name, status FROM assets WHERE status IS NULL OR status = '' OR status NOT IN ('Available','Unavailable','Damaged','Missing','Under Repair','Retired');

-- Fix any NULL or empty status values by setting them to 'Available' (the default)
UPDATE assets
SET status = 'Available'
WHERE status IS NULL OR status = '';

-- Create a trigger to prevent empty status on INSERT
DROP TRIGGER IF EXISTS before_asset_insert_status_check;

DELIMITER $$
CREATE TRIGGER before_asset_insert_status_check
BEFORE INSERT ON assets
FOR EACH ROW
BEGIN
    -- If status is empty string or NULL, set to 'Available'
    IF NEW.status = '' OR NEW.status IS NULL THEN
        SET NEW.status = 'Available';
    END IF;
END$$
DELIMITER ;

-- Create a trigger to prevent empty status on UPDATE
DROP TRIGGER IF EXISTS before_asset_update_status_check;

DELIMITER $$
CREATE TRIGGER before_asset_update_status_check
BEFORE UPDATE ON assets
FOR EACH ROW
BEGIN
    -- If status is being set to empty string or NULL, keep it as 'Available'
    IF NEW.status = '' OR NEW.status IS NULL THEN
        SET NEW.status = 'Available';
    END IF;
END$$
DELIMITER ;

-- Log the changes
SELECT 'Assets status values have been fixed and triggers created to prevent future blank statuses' AS message;
