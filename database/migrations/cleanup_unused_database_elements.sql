-- =====================================================
-- DATABASE CLEANUP MIGRATION
-- Holy Cross College Asset Management System
-- Generated: 2025-11-12
-- =====================================================
-- This script removes unused tables, views, and columns
-- identified in the database usage analysis
-- =====================================================

-- BACKUP REMINDER: Always backup your database before running this script!
-- To backup: mysqldump -u root -p hcc_asset_management > backup_before_cleanup.sql

USE hcc_asset_management;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- SECTION 1: REMOVE COMPLETELY UNUSED TABLES
-- =====================================================

-- 1. Remove IT_SUPPORT_USERS table (No references found anywhere)
DROP TABLE IF EXISTS IT_SUPPORT_USERS;
-- Reason: Zero usage across entire codebase, no functionality implemented

-- 2. Remove PASSWORD_RESETS table (Feature not implemented)
DROP TABLE IF EXISTS PASSWORD_RESETS;
-- Reason: No password reset functionality implemented in the system
-- Action Required: If you plan to implement password reset, restore this table

-- =====================================================
-- SECTION 2: REMOVE UNUSED VIEWS
-- =====================================================

-- 3. Remove ASSET_DETAILS view (Never queried)
DROP VIEW IF EXISTS ASSET_DETAILS;
-- Reason: View created but never used in any reports or queries

-- 4. Remove CAMPUS_STATISTICS view (Never queried)
DROP VIEW IF EXISTS CAMPUS_STATISTICS;
-- Reason: View created but never used in any reports or queries

-- =====================================================
-- SECTION 3: REMOVE UNUSED COLUMNS FROM ACTIVE TABLES
-- =====================================================

-- 5. Clean up ASSETS table
-- Remove: inventory_date (never referenced)
ALTER TABLE ASSETS
DROP COLUMN IF EXISTS inventory_date;

-- Remove: supplier (never referenced)
ALTER TABLE ASSETS
DROP COLUMN IF EXISTS supplier;

-- 6. Clean up BUILDINGS table
-- Remove: building_code (never referenced)
ALTER TABLE BUILDINGS
DROP COLUMN IF EXISTS building_code;

-- 7. Clean up ROOMS table
-- Remove: room_code (never referenced)
ALTER TABLE ROOMS
DROP COLUMN IF EXISTS room_code;

-- Remove: room_type (never referenced)
ALTER TABLE ROOMS
DROP COLUMN IF EXISTS room_type;

-- Remove: capacity (never referenced)
ALTER TABLE ROOMS
DROP COLUMN IF EXISTS capacity;

-- 8. Clean up ASSET_ASSIGNMENTS table
-- Remove: assigned_by (has default value but never queried)
ALTER TABLE ASSET_ASSIGNMENTS
DROP COLUMN IF EXISTS assigned_by;

-- =====================================================
-- SECTION 4: OPTIONAL CLEANUP (REVIEW BEFORE ENABLING)
-- =====================================================
-- Uncomment these if you decide to remove these tables after review

-- ASSET_NAMES table - Only INSERT operations, never queried
-- DROP TABLE IF EXISTS ASSET_NAMES;
-- Reason: Only used for INSERT, never displayed or queried
-- Consider: May be intended for future autocomplete feature

-- EMAIL_VERIFICATIONS table - Minimal usage
-- DROP TABLE IF EXISTS EMAIL_VERIFICATIONS;
-- Reason: Only found in config.php, feature partially implemented
-- Action: Either complete the feature or remove this table

-- LOGIN_ATTEMPTS table - Limited usage
-- DROP TABLE IF EXISTS LOGIN_ATTEMPTS;
-- Reason: Only 2 files use it, may not be actively monitored
-- Action: Review if security monitoring uses this data

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Run these after migration to verify cleanup

-- Check remaining tables
-- SHOW TABLES;

-- Check columns in cleaned tables
-- DESCRIBE ASSETS;
-- DESCRIBE BUILDINGS;
-- DESCRIBE ROOMS;
-- DESCRIBE ASSET_ASSIGNMENTS;

-- =====================================================
-- ROLLBACK SCRIPT (Save for emergency)
-- =====================================================

/*
-- To rollback, you would need to restore from backup
-- Or manually recreate the tables:

-- Recreate IT_SUPPORT_USERS
CREATE TABLE IF NOT EXISTS IT_SUPPORT_USERS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permissions TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(id) ON DELETE CASCADE
);

-- Recreate PASSWORD_RESETS
CREATE TABLE IF NOT EXISTS PASSWORD_RESETS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reset_token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(id) ON DELETE CASCADE
);

-- Recreate ASSETS columns
ALTER TABLE ASSETS ADD COLUMN inventory_date DATE AFTER quantity;
ALTER TABLE ASSETS ADD COLUMN supplier VARCHAR(255) AFTER inventory_date;

-- Recreate BUILDINGS columns
ALTER TABLE BUILDINGS ADD COLUMN building_code VARCHAR(20) AFTER building_name;

-- Recreate ROOMS columns
ALTER TABLE ROOMS ADD COLUMN room_code VARCHAR(20) AFTER building_id;
ALTER TABLE ROOMS ADD COLUMN room_type VARCHAR(50) AFTER room_code;
ALTER TABLE ROOMS ADD COLUMN capacity INT AFTER room_type;

-- Recreate ASSET_ASSIGNMENTS columns
ALTER TABLE ASSET_ASSIGNMENTS ADD COLUMN assigned_by INT DEFAULT NULL AFTER assigned_email;

-- Recreate ASSET_DETAILS view
CREATE OR REPLACE VIEW ASSET_DETAILS AS
SELECT
    a.id,
    a.asset_name,
    c.category_name,
    a.status,
    camp.campus_name,
    a.location,
    a.purchase_date,
    a.value,
    a.assigned_to
FROM ASSETS a
LEFT JOIN CATEGORIES c ON a.category_id = c.id
LEFT JOIN CAMPUSES camp ON a.campus_id = camp.id;

-- Recreate CAMPUS_STATISTICS view
CREATE OR REPLACE VIEW CAMPUS_STATISTICS AS
SELECT
    c.id,
    c.campus_name,
    COUNT(a.id) as total_assets,
    SUM(a.value) as total_value
FROM CAMPUSES c
LEFT JOIN ASSETS a ON c.id = a.campus_id
GROUP BY c.id, c.campus_name;
*/

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CLEANUP SUMMARY
-- =====================================================
-- Tables Removed: 2 (IT_SUPPORT_USERS, PASSWORD_RESETS)
-- Views Removed: 2 (ASSET_DETAILS, CAMPUS_STATISTICS)
-- Columns Removed: 7 across 4 tables
-- Total Elements Cleaned: 11
-- =====================================================

-- End of cleanup migration
