-- Migration: Add profile_picture to users table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Add profile_picture column to users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `profile_picture` VARCHAR(255) NULL AFTER `campus_id`;

-- Optional: index for profile_picture lookups (if needed)
-- ALTER TABLE `users`
--   ADD INDEX IF NOT EXISTS `idx_users_profile_picture` (`profile_picture`);

-- Existing rows will have NULL for profile_picture.
-- You can update existing users as needed. Example:
-- UPDATE `users` SET `profile_picture` = 'default.jpg' WHERE `profile_picture` IS NULL;

-- Verify
-- SELECT id, username, profile_picture FROM `users` LIMIT 20;
