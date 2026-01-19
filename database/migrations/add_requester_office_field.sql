-- Migration: Add requester_office_id field to asset_requests
-- Date: 2025-11-11
-- Purpose: Track when office users (department heads) make requests to custodian

USE hcc_asset_management;

-- Check if column exists before adding
SET @dbname = DATABASE();
SET @tablename = 'asset_requests';
SET @columnname = 'requester_office_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column already exists, skipping...' AS msg;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT(11) NULL COMMENT 'Office ID of requester if requester is an office user' AFTER requester_id;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key constraint if it doesn't exist
SET @fkname = 'fk_requests_requester_office';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (constraint_name = @fkname)
  ) > 0,
  "SELECT 'Foreign key already exists, skipping...' AS msg;",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT ", @fkname, " FOREIGN KEY (requester_office_id) REFERENCES offices(id) ON DELETE SET NULL;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index for better query performance if it doesn't exist
SET @idxname = 'idx_requester_office';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @idxname)
  ) > 0,
  "SELECT 'Index already exists, skipping...' AS msg;",
  CONCAT("CREATE INDEX ", @idxname, " ON ", @tablename, "(requester_office_id);")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Migration complete
SELECT 'Migration completed: add_requester_office_field.sql' as status;
