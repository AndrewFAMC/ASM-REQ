-- Migration: Add created_by field to assets table
-- Target DB: hcc_asset_management
-- This script is idempotent for MySQL 8+/MariaDB (uses IF NOT EXISTS)

-- Add created_by column to assets table
ALTER TABLE assets ADD COLUMN IF NOT EXISTS created_by INT(11) DEFAULT NULL AFTER updated_at;

-- Add foreign key constraint
ALTER TABLE assets ADD CONSTRAINT fk_assets_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add index for performance
CREATE INDEX IF NOT EXISTS idx_assets_created_by ON assets(created_by);

-- Update existing records to set created_by to NULL (since we don't have historical data)
-- This is optional and can be removed if you want to track this going forward only
