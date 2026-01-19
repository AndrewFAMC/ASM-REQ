-- Migration: Add Multi-Stage Reminder and Escalation Tracking Fields
-- Created: 2025-01-09
-- Purpose: Support multi-stage reminders (7d, 2d, 1d, 0d) and escalation tracking

USE hcc_asset_management;

-- Add tracking fields to asset_requests table
ALTER TABLE asset_requests
    ADD COLUMN IF NOT EXISTS last_reminder_sent DATETIME NULL COMMENT 'Last time any reminder was sent',
    ADD COLUMN IF NOT EXISTS reminder_count INT DEFAULT 0 COMMENT 'Total number of reminders sent',
    ADD COLUMN IF NOT EXISTS last_overdue_alert_sent DATETIME NULL COMMENT 'Last time overdue alert was sent',
    ADD COLUMN IF NOT EXISTS overdue_alert_count INT DEFAULT 0 COMMENT 'Total number of overdue alerts sent';

-- Add index for performance on date queries
ALTER TABLE asset_requests
    ADD INDEX IF NOT EXISTS idx_expected_return_date (expected_return_date),
    ADD INDEX IF NOT EXISTS idx_status_return_date (status, expected_return_date);

-- Update existing records to set default values
UPDATE asset_requests
SET
    reminder_count = 0,
    overdue_alert_count = 0
WHERE reminder_count IS NULL OR overdue_alert_count IS NULL;

-- Display current schema
SELECT 'Migration completed successfully!' AS status;
DESCRIBE asset_requests;
