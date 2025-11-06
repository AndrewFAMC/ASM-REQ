-- Migration: Add role to users table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Add role column to users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `role` ENUM('admin','custodian','staff','user') NOT NULL DEFAULT 'user' AFTER `email`;

-- Optional: index for role lookups
ALTER TABLE `users`
  ADD INDEX IF NOT EXISTS `idx_users_role` (`role`);

-- Existing rows will automatically receive the default value 'user'.
-- You may promote specific users to roles as needed. Examples:
-- UPDATE `users` SET `role`='admin' WHERE `username`='admin' OR `email`='admin@hcc.edu.ph';
-- UPDATE `users` SET `role`='custodian' WHERE `username` IN ('custodian1', 'custodian2');
-- UPDATE `users` SET `role`='staff' WHERE `username` IN ('staff1', 'staff2');

-- Verify
-- SELECT id, username, email, role FROM `users` LIMIT 20;
