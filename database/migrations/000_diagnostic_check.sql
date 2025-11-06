-- ============================================================================
-- DIAGNOSTIC CHECK - Run this FIRST to see your current table structure
-- ============================================================================

USE `hcc_asset_management`;

-- Check campuses table structure
DESCRIBE `campuses`;

-- Check categories table structure
DESCRIBE `categories`;

-- Check offices table structure
DESCRIBE `offices`;

-- Check users table structure
DESCRIBE `users`;

-- Check assets table structure
DESCRIBE `assets`;

-- Check asset_borrowings table structure
DESCRIBE `asset_borrowings`;

-- Check asset_requests table structure
DESCRIBE `asset_requests`;

-- Check asset_maintenance table structure
DESCRIBE `asset_maintenance`;

-- Show sample data from campuses
SELECT * FROM `campuses` LIMIT 2;

-- Show sample data from categories
SELECT * FROM `categories` LIMIT 2;

-- Show current asset statuses in use
SELECT DISTINCT status FROM `assets`;

-- Show current user roles
SELECT DISTINCT role FROM `users`;

-- ============================================================================
-- Copy the output and share it so I can create a perfect migration
-- ============================================================================
