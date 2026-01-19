-- Database Cleanup Script
-- Removes unused tables from AMS-REQ database
-- Created: 2025-01-12
-- IMPORTANT: Backup database before running this script!

USE hcc_asset_management;

-- ====================================
-- BACKUP REMINDER
-- ====================================
-- Run this first:
-- mysqldump -u root hcc_asset_management > backup_before_cleanup_YYYYMMDD.sql

-- ====================================
-- SAFE TO DELETE (No Active References)
-- ====================================

-- Old inventory system (replaced by asset_units)
DROP TABLE IF EXISTS inventory_items;
DROP TABLE IF EXISTS inventory_sessions;

-- Unused features
DROP TABLE IF EXISTS asset_name_brands;
DROP TABLE IF EXISTS asset_receipts;
DROP TABLE IF EXISTS sms_notifications;
DROP TABLE IF EXISTS asset_movement_logs;

-- ====================================
-- REVIEW BEFORE UNCOMMENTING
-- ====================================

-- Maintenance tracking (only 1 comment reference)
-- DROP TABLE IF EXISTS asset_maintenance;

-- Password reset feature (may be needed in future)
-- DROP TABLE IF EXISTS password_resets;

-- Borrowing system (overlaps with asset_requests)
-- Consider migrating data first:
-- INSERT INTO asset_requests SELECT ... FROM asset_borrowings;
-- DROP TABLE IF EXISTS asset_borrowings;

-- ====================================
-- VERIFICATION QUERIES
-- ====================================

-- After cleanup, verify remaining tables
SHOW TABLES;

-- Check table sizes
SELECT
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
    table_rows
FROM information_schema.TABLES
WHERE table_schema = 'hcc_asset_management'
ORDER BY (data_length + index_length) DESC;

-- ====================================
-- RESULT
-- ====================================
-- Expected result: 6 tables removed
-- Expected remaining: ~36 active tables + views
