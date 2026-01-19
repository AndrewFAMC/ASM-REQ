-- Migration: Add request source tracking for dual-flow system
-- Date: 2025-01-10
-- Purpose: Support Employee->Office and Employee->Custodian request flows

USE hcc_asset_management;

-- Add fields to track request source and target office
ALTER TABLE asset_requests
ADD COLUMN request_source ENUM('custodian', 'office') NOT NULL DEFAULT 'custodian' COMMENT 'Where the request is directed: custodian (central) or office (department)' AFTER status,
ADD COLUMN target_office_id INT(11) NULL COMMENT 'If request_source=office, which office is being requested from' AFTER request_source,
ADD COLUMN office_approved_by INT(11) NULL COMMENT 'Office head who approved the request' AFTER target_office_id,
ADD COLUMN office_approved_at DATETIME NULL COMMENT 'When office head approved' AFTER office_approved_by,
ADD COLUMN office_approval_notes TEXT NULL COMMENT 'Office head approval comments' AFTER office_approved_at;

-- Add foreign key for target_office_id
ALTER TABLE asset_requests
ADD CONSTRAINT fk_requests_target_office
FOREIGN KEY (target_office_id) REFERENCES offices(id) ON DELETE SET NULL;

-- Add foreign key for office_approved_by
ALTER TABLE asset_requests
ADD CONSTRAINT fk_requests_office_approver
FOREIGN KEY (office_approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add index for better query performance
CREATE INDEX idx_request_source ON asset_requests(request_source);
CREATE INDEX idx_target_office ON asset_requests(target_office_id);
CREATE INDEX idx_office_approved ON asset_requests(office_approved_by);

-- Update existing status enum to add 'office_review' status
ALTER TABLE asset_requests
MODIFY COLUMN status ENUM(
    'pending',
    'custodian_review',
    'office_review',
    'department_review',
    'approved',
    'rejected',
    'released',
    'returned',
    'cancelled'
) NOT NULL DEFAULT 'pending';

-- Migration complete
SELECT 'Migration completed: add_request_source_fields.sql' as status;
