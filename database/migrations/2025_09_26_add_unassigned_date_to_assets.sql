-- Migration to add unassigned_date column to assets table
ALTER TABLE assets
ADD COLUMN unassigned_date DATE NULL AFTER assignment_date;
