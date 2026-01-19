-- Migration: Fix borrowing_chain table to support asset requests
-- Date: 2025-01-12
-- Description: Make borrowing_id nullable and add request_id to support transfers for both borrowings and requests

-- Step 1: Drop the foreign key constraint on borrowing_id
ALTER TABLE `borrowing_chain`
DROP FOREIGN KEY IF EXISTS `fk_borrowing_chain_borrowing`;

-- Step 2: Modify borrowing_id to allow NULL
ALTER TABLE `borrowing_chain`
MODIFY COLUMN `borrowing_id` INT(11) NULL COMMENT 'Original borrowing record (NULL if tracking a request)';

-- Step 3: Add request_id column if it doesn't exist
ALTER TABLE `borrowing_chain`
ADD COLUMN IF NOT EXISTS `request_id` INT(11) NULL COMMENT 'Asset request ID (if transfer is for a request)' AFTER `borrowing_id`;

-- Step 4: Add index on request_id
ALTER TABLE `borrowing_chain`
ADD INDEX IF NOT EXISTS `idx_request_id` (`request_id`);

-- Step 5: Re-add foreign key constraint on borrowing_id (allowing NULL)
ALTER TABLE `borrowing_chain`
ADD CONSTRAINT `fk_borrowing_chain_borrowing`
FOREIGN KEY (`borrowing_id`) REFERENCES `asset_borrowings` (`id`) ON DELETE CASCADE;

-- Step 6: Add foreign key constraint on request_id
ALTER TABLE `borrowing_chain`
ADD CONSTRAINT `fk_borrowing_chain_request`
FOREIGN KEY (`request_id`) REFERENCES `asset_requests` (`id`) ON DELETE CASCADE;

-- Step 7: Add check constraint to ensure either borrowing_id or request_id is provided
-- Note: MySQL 8.0.16+ supports CHECK constraints
-- For older versions, this will be handled in application logic
ALTER TABLE `borrowing_chain`
ADD CONSTRAINT `chk_borrowing_or_request`
CHECK ((`borrowing_id` IS NOT NULL) OR (`request_id` IS NOT NULL));
