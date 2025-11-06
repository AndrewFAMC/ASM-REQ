-- Add force_password_change column to users table
ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 1 AFTER is_active;
