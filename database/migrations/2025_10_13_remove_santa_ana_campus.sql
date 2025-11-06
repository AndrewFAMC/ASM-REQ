-- Migration: Remove Sta. Ana, Pampanga campus
-- Target DB: hcc_asset_management
-- This script removes the Sta. Ana, Pampanga campus (ID: 3) and all associated data

-- Start transaction for safety
START TRANSACTION;

-- Delete all asset assignments for users in this campus
DELETE FROM asset_assignments WHERE asset_id IN (
    SELECT id FROM assets WHERE campus_id = 3
);

-- Delete all activity log entries for assets in this campus
DELETE FROM activity_log WHERE asset_id IN (
    SELECT id FROM assets WHERE campus_id = 3
);

-- Delete all assets assigned to this campus
DELETE FROM assets WHERE campus_id = 3;

-- Delete all user sessions for users in this campus
DELETE FROM user_sessions WHERE user_id IN (
    SELECT id FROM users WHERE campus_id = 3
);

-- Delete all users assigned to this campus
DELETE FROM users WHERE campus_id = 3;

-- Delete the campus itself
DELETE FROM campuses WHERE id = 3;

-- Commit the transaction
COMMIT;

-- Verify the campus was removed
-- SELECT * FROM campuses WHERE id = 3;
-- Should return no results
