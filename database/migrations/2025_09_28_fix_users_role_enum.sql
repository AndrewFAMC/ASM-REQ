-- Migration: Fix users role enum to include 'user' instead of 'manager'
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Modify role column enum to include 'user' and remove 'manager' if present
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','custodian','staff','user') NOT NULL DEFAULT 'user';

-- Optional: index for role lookups (if not exists)
ALTER TABLE `users`
  ADD INDEX IF NOT EXISTS `idx_users_role` (`role`);

-- Verify
-- SELECT id, username, email, role FROM `users` LIMIT 20;
