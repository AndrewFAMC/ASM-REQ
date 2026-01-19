-- ============================================================================
-- FRESH START: Individual Tracking System
-- This script clears old asset data and prepares for new individual tracking
-- ============================================================================

-- IMPORTANT: BACKUP YOUR DATABASE FIRST!
-- Run this command before executing this script:
-- mysqldump -u root hcc_asset_management > backup_before_fresh_start_$(date +%Y%m%d).sql

-- ============================================================================
-- STEP 1: DISABLE FOREIGN KEY CHECKS (temporarily)
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- STEP 2: CLEAR ASSET-RELATED DATA
-- ============================================================================

-- Clear inventory tags (office assignments)
TRUNCATE TABLE inventory_tags;
SHOW WARNINGS;

-- Clear individual tracking tables
TRUNCATE TABLE tag_units;
SHOW WARNINGS;

TRUNCATE TABLE unit_history;
SHOW WARNINGS;

TRUNCATE TABLE asset_units;
SHOW WARNINGS;

-- Clear asset requests and related data
TRUNCATE TABLE asset_requests;
SHOW WARNINGS;

-- Clear asset activity logs
TRUNCATE TABLE activity_log;
SHOW WARNINGS;

-- Clear asset assignments history
TRUNCATE TABLE asset_assignments;
SHOW WARNINGS;

-- Clear asset scans
TRUNCATE TABLE asset_scans;
SHOW WARNINGS;

-- Clear asset maintenance records
TRUNCATE TABLE asset_maintenance;
SHOW WARNINGS;

-- Clear missing asset reports
TRUNCATE TABLE missing_assets_reports;
SHOW WARNINGS;

-- FINALLY: Clear all assets
TRUNCATE TABLE assets;
SHOW WARNINGS;

-- ============================================================================
-- STEP 3: RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- STEP 4: VERIFY TABLES ARE EMPTY
-- ============================================================================
SELECT 'Assets count:' AS info, COUNT(*) AS count FROM assets;
SELECT 'Asset units count:' AS info, COUNT(*) AS count FROM asset_units;
SELECT 'Inventory tags count:' AS info, COUNT(*) AS count FROM inventory_tags;
SELECT 'Asset requests count:' AS info, COUNT(*) AS count FROM asset_requests;

-- ============================================================================
-- STEP 5: RESET AUTO_INCREMENT (optional - for clean IDs)
-- ============================================================================
ALTER TABLE assets AUTO_INCREMENT = 1;
ALTER TABLE asset_units AUTO_INCREMENT = 1;
ALTER TABLE inventory_tags AUTO_INCREMENT = 1;
ALTER TABLE tag_units AUTO_INCREMENT = 1;
ALTER TABLE unit_history AUTO_INCREMENT = 1;
ALTER TABLE asset_requests AUTO_INCREMENT = 1;
ALTER TABLE activity_log AUTO_INCREMENT = 1;

-- ============================================================================
-- STEP 6: VERIFY INDIVIDUAL TRACKING SYSTEM IS READY
-- ============================================================================
SELECT 'Individual Tracking Tables:' AS info;
SHOW TABLES LIKE '%unit%';

SELECT 'Stored Procedures:' AS info;
SHOW PROCEDURE STATUS WHERE Db = 'hcc_asset_management' AND Name LIKE '%unit%';

SELECT 'Views:' AS info;
SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_hcc_asset_management LIKE '%unit%';

-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================
SELECT '========================================' AS '';
SELECT 'FRESH START COMPLETE!' AS '';
SELECT '========================================' AS '';
SELECT 'All asset data has been cleared.' AS info;
SELECT 'System is ready for individual tracking.' AS info;
SELECT 'You can now start adding assets!' AS info;
SELECT '========================================' AS '';

-- ============================================================================
-- WHAT TO DO NEXT:
-- ============================================================================
-- 1. Add new assets via Custodian Dashboard
-- 2. When quantity > 1, units will be created automatically
-- 3. Every asset will have individual tracking from day 1!
-- ============================================================================
