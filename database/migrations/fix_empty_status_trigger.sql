-- Migration: Fix empty status values in asset_requests
-- Purpose: Prevent empty string status values from being inserted

-- Drop trigger if exists
DROP TRIGGER IF EXISTS before_asset_request_insert;
DROP TRIGGER IF EXISTS before_asset_request_update;

-- Create trigger to prevent empty status on INSERT
DELIMITER $$
CREATE TRIGGER before_asset_request_insert
BEFORE INSERT ON asset_requests
FOR EACH ROW
BEGIN
    -- If status is empty string or NULL, set to 'pending'
    IF NEW.status = '' OR NEW.status IS NULL THEN
        SET NEW.status = 'pending';
    END IF;
END$$
DELIMITER ;

-- Create trigger to prevent empty status on UPDATE
DELIMITER $$
CREATE TRIGGER before_asset_request_update
BEFORE UPDATE ON asset_requests
FOR EACH ROW
BEGIN
    -- If status is being set to empty string or NULL, keep it as 'pending'
    IF NEW.status = '' OR NEW.status IS NULL THEN
        SET NEW.status = 'pending';
    END IF;
END$$
DELIMITER ;

-- Fix any existing empty status values
UPDATE asset_requests
SET status = 'pending'
WHERE status = '' OR status IS NULL;
