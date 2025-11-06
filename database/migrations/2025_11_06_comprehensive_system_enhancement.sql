-- ============================================================================
-- COMPREHENSIVE SYSTEM ENHANCEMENT MIGRATION
-- Holy Cross College Asset Management System
-- Date: 2025-11-06
-- Description: Implements professor's recommendations for system enhancement
-- ============================================================================

-- Use the correct database
USE `hcc_asset_management`;

-- Start transaction for safety
START TRANSACTION;

-- ============================================================================
-- 1. UPDATE ASSET STATUS DEFINITIONS
-- ============================================================================
-- Current: 'Active','Inactive','Maintenance','Retired'
-- New: 'Active','Inactive','Damaged','Missing','Under Repair'

ALTER TABLE `assets`
MODIFY COLUMN `status` ENUM('Active','Inactive','Damaged','Missing','Under Repair','Retired')
DEFAULT 'Active'
COMMENT 'Asset status: Active=in use, Inactive=in storage/available, Damaged=needs repair, Missing=lost/untraceable, Under Repair=being fixed, Retired=decommissioned';

-- ============================================================================
-- 2. ADD DEPRECIATION TRACKING TO ASSETS
-- ============================================================================

ALTER TABLE `assets`
ADD COLUMN IF NOT EXISTS `original_value` DECIMAL(15,2) NULL COMMENT 'Original purchase value' AFTER `value`,
ADD COLUMN IF NOT EXISTS `current_value` DECIMAL(15,2) NULL COMMENT 'Current depreciated value' AFTER `original_value`,
ADD COLUMN IF NOT EXISTS `depreciation_rate` DECIMAL(5,2) NULL DEFAULT 0.00 COMMENT 'Annual depreciation rate in percentage' AFTER `current_value`,
ADD COLUMN IF NOT EXISTS `last_depreciation_date` DATE NULL COMMENT 'Last date depreciation was calculated' AFTER `depreciation_rate`;

-- Populate original_value with existing value if NULL
UPDATE `assets` SET `original_value` = `value` WHERE `original_value` IS NULL;
UPDATE `assets` SET `current_value` = `value` WHERE `current_value` IS NULL;

-- ============================================================================
-- 3. ENHANCE ASSET_BORROWINGS TABLE FOR RETURN TRACKING
-- ============================================================================

ALTER TABLE `asset_borrowings`
ADD COLUMN IF NOT EXISTS `actual_return_date` DATETIME NULL COMMENT 'Actual date item was returned' AFTER `return_date`,
ADD COLUMN IF NOT EXISTS `return_status` ENUM('On Time','Returned Late','Overdue','Not Returned') NULL COMMENT 'Return status tracking' AFTER `actual_return_date`,
ADD COLUMN IF NOT EXISTS `overdue_notification_sent` BOOLEAN DEFAULT FALSE COMMENT 'Flag if overdue notification was sent' AFTER `return_status`,
ADD COLUMN IF NOT EXISTS `reminder_sent_date` DATETIME NULL COMMENT 'Date when reminder was sent' AFTER `overdue_notification_sent`,
ADD COLUMN IF NOT EXISTS `days_overdue` INT DEFAULT 0 COMMENT 'Number of days overdue' AFTER `reminder_sent_date`,
ADD COLUMN IF NOT EXISTS `last_known_borrower` VARCHAR(255) NULL COMMENT 'Last person who had the item' AFTER `days_overdue`,
ADD COLUMN IF NOT EXISTS `condition_on_return` TEXT NULL COMMENT 'Condition remarks on return: Complete, Missing parts, Damaged, etc.' AFTER `last_known_borrower`;

-- Update status enum to include more options
ALTER TABLE `asset_borrowings`
MODIFY COLUMN `status` ENUM('active','returned','overdue','not_returned','lost') NOT NULL DEFAULT 'active';

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS `idx_expected_return_date` ON `asset_borrowings` (`expected_return_date`);
CREATE INDEX IF NOT EXISTS `idx_return_status` ON `asset_borrowings` (`return_status`);
CREATE INDEX IF NOT EXISTS `idx_overdue_notification` ON `asset_borrowings` (`overdue_notification_sent`);

-- ============================================================================
-- 4. CREATE NOTIFICATIONS TABLE (IN-SYSTEM ALERTS)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT 'User who receives the notification',
    `type` ENUM('return_reminder','overdue_alert','approval_request','approval_response','missing_report','system_alert') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `related_type` ENUM('asset','borrowing','request','maintenance') NULL COMMENT 'Type of related entity',
    `related_id` INT(11) NULL COMMENT 'ID of related entity',
    `is_read` BOOLEAN DEFAULT FALSE,
    `read_at` DATETIME NULL,
    `priority` ENUM('low','medium','high','urgent') DEFAULT 'medium',
    `action_url` VARCHAR(500) NULL COMMENT 'URL to take action',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL COMMENT 'When notification becomes irrelevant',

    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_priority` (`priority`),

    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. CREATE BORROWING CHAIN TABLE (SECONDARY LENDING TRACKING)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `borrowing_chain` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `borrowing_id` INT(11) NOT NULL COMMENT 'Original borrowing record',
    `asset_id` INT(11) NOT NULL,
    `from_person` VARCHAR(255) NOT NULL COMMENT 'Person who lent it',
    `to_person` VARCHAR(255) NOT NULL COMMENT 'Person who received it',
    `to_person_contact` VARCHAR(255) NULL,
    `transfer_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expected_return_date` DATE NULL,
    `actual_return_date` DATETIME NULL,
    `status` ENUM('active','returned') DEFAULT 'active',
    `notes` TEXT NULL,
    `recorded_by` INT(11) NULL COMMENT 'User who recorded this transfer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_borrowing_id` (`borrowing_id`),
    KEY `idx_asset_id` (`asset_id`),
    KEY `idx_status` (`status`),
    KEY `idx_transfer_date` (`transfer_date`),

    CONSTRAINT `fk_borrowing_chain_borrowing` FOREIGN KEY (`borrowing_id`) REFERENCES `asset_borrowings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrowing_chain_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrowing_chain_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. CREATE ASSET MOVEMENT LOGS TABLE (LOCATION TRACKING)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `asset_movement_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asset_id` INT(11) NOT NULL,
    `from_location` VARCHAR(255) NULL COMMENT 'Previous location',
    `to_location` VARCHAR(255) NOT NULL COMMENT 'New location',
    `from_room_id` INT(11) NULL,
    `to_room_id` INT(11) NULL,
    `from_office_id` INT(11) NULL,
    `to_office_id` INT(11) NULL,
    `movement_type` ENUM('deployment','transfer','return','audit','maintenance','storage') NOT NULL,
    `moved_by` INT(11) NULL COMMENT 'User who moved the asset',
    `reason` TEXT NULL,
    `moved_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verified_by` INT(11) NULL COMMENT 'User who verified the movement',
    `verified_date` DATETIME NULL,
    `campus_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_asset_id` (`asset_id`),
    KEY `idx_movement_type` (`movement_type`),
    KEY `idx_moved_date` (`moved_date`),
    KEY `idx_campus_id` (`campus_id`),

    CONSTRAINT `fk_movement_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_movement_moved_by` FOREIGN KEY (`moved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_movement_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_movement_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_movement_from_room` FOREIGN KEY (`from_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_movement_to_room` FOREIGN KEY (`to_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_movement_from_office` FOREIGN KEY (`from_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_movement_to_office` FOREIGN KEY (`to_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. CREATE MISSING ASSETS REPORT TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `missing_assets_reports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asset_id` INT(11) NOT NULL,
    `reported_by` INT(11) NOT NULL COMMENT 'User who reported missing',
    `reported_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_known_location` VARCHAR(255) NULL,
    `last_known_borrower` VARCHAR(255) NULL,
    `last_known_borrower_contact` VARCHAR(255) NULL,
    `last_seen_date` DATE NULL,
    `responsible_department` VARCHAR(255) NULL,
    `description` TEXT NOT NULL COMMENT 'Details about the missing asset',
    `status` ENUM('reported','investigating','found','permanently_lost') DEFAULT 'reported',
    `resolution_notes` TEXT NULL,
    `resolved_by` INT(11) NULL,
    `resolved_date` DATETIME NULL,
    `campus_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_asset_id` (`asset_id`),
    KEY `idx_reported_by` (`reported_by`),
    KEY `idx_status` (`status`),
    KEY `idx_reported_date` (`reported_date`),
    KEY `idx_campus_id` (`campus_id`),

    CONSTRAINT `fk_missing_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_missing_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_missing_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_missing_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. CREATE DEPARTMENT APPROVERS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `department_approvers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `office_id` INT(11) NOT NULL COMMENT 'Department/Office',
    `approver_user_id` INT(11) NOT NULL COMMENT 'Department head who approves',
    `approval_level` ENUM('primary','secondary','backup') DEFAULT 'primary',
    `can_approve_requests` BOOLEAN DEFAULT TRUE,
    `can_assign_assets` BOOLEAN DEFAULT TRUE,
    `max_approval_value` DECIMAL(15,2) NULL COMMENT 'Maximum asset value they can approve',
    `is_active` BOOLEAN DEFAULT TRUE,
    `assigned_date` DATE NOT NULL,
    `campus_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_office_approver` (`office_id`, `approver_user_id`),
    KEY `idx_office_id` (`office_id`),
    KEY `idx_approver_user_id` (`approver_user_id`),
    KEY `idx_campus_id` (`campus_id`),

    CONSTRAINT `fk_dept_approver_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dept_approver_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dept_approver_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. ENHANCE ASSET_REQUESTS TABLE FOR NEW APPROVAL WORKFLOW
-- ============================================================================

ALTER TABLE `asset_requests`
ADD COLUMN IF NOT EXISTS `custodian_reviewed_by` INT(11) NULL COMMENT 'Custodian who reviewed first' AFTER `approved_by`,
ADD COLUMN IF NOT EXISTS `custodian_reviewed_at` DATETIME NULL AFTER `custodian_reviewed_by`,
ADD COLUMN IF NOT EXISTS `custodian_review_notes` TEXT NULL AFTER `custodian_reviewed_at`,
ADD COLUMN IF NOT EXISTS `department_approved_by` INT(11) NULL COMMENT 'Department head approval' AFTER `custodian_review_notes`,
ADD COLUMN IF NOT EXISTS `department_approved_at` DATETIME NULL AFTER `department_approved_by`,
ADD COLUMN IF NOT EXISTS `final_approved_by` INT(11) NULL COMMENT 'Final admin approval' AFTER `department_approved_at`,
ADD COLUMN IF NOT EXISTS `final_approved_at` DATETIME NULL AFTER `final_approved_by`,
ADD COLUMN IF NOT EXISTS `condition_remarks` TEXT NULL COMMENT 'Condition: Complete, Missing Ink, Damaged, etc.' AFTER `final_approved_at`,
ADD COLUMN IF NOT EXISTS `approval_level_required` ENUM('department','custodian','admin','all') DEFAULT 'all' AFTER `condition_remarks`;

-- Update status enum to include more workflow stages
ALTER TABLE `asset_requests`
MODIFY COLUMN `status` ENUM('pending','custodian_review','department_review','approved','rejected','released','returned','cancelled') NOT NULL DEFAULT 'pending';

-- Add indexes for new columns
CREATE INDEX IF NOT EXISTS `idx_custodian_reviewed` ON `asset_requests` (`custodian_reviewed_by`);
CREATE INDEX IF NOT EXISTS `idx_dept_approved` ON `asset_requests` (`department_approved_by`);
CREATE INDEX IF NOT EXISTS `idx_final_approved` ON `asset_requests` (`final_approved_by`);

-- Add foreign keys
ALTER TABLE `asset_requests`
ADD CONSTRAINT `fk_request_custodian_reviewer` FOREIGN KEY (`custodian_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_request_dept_approver` FOREIGN KEY (`department_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_request_final_approver` FOREIGN KEY (`final_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ============================================================================
-- 10. ADD NEW USER ROLE: AUDITOR (ROAMING STAFF)
-- ============================================================================

-- Update users table role enum to include 'auditor'
ALTER TABLE `users`
MODIFY COLUMN `role` ENUM('staff','custodian','admin','super_admin','office','auditor') NOT NULL DEFAULT 'staff';

-- ============================================================================
-- 11. ADD DEPARTMENT_ID TO USERS IF NOT EXISTS
-- ============================================================================

ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `department_id` INT(11) NULL COMMENT 'User department for approval hierarchy' AFTER `office_id`;

-- Add index and foreign key
CREATE INDEX IF NOT EXISTS `idx_users_department` ON `users` (`department_id`);

-- ============================================================================
-- 12. ENHANCE ASSET_MAINTENANCE TABLE
-- ============================================================================

ALTER TABLE `asset_maintenance`
ADD COLUMN IF NOT EXISTS `cost` DECIMAL(15,2) NULL COMMENT 'Repair/maintenance cost' AFTER `notes`,
ADD COLUMN IF NOT EXISTS `performed_by` VARCHAR(255) NULL COMMENT 'Technician or company' AFTER `cost`,
ADD COLUMN IF NOT EXISTS `next_maintenance_date` DATE NULL COMMENT 'Scheduled next maintenance' AFTER `performed_by`,
ADD COLUMN IF NOT EXISTS `maintenance_type` ENUM('preventive','corrective','inspection','calibration') NULL AFTER `next_maintenance_date`;

-- Add index for maintenance type
CREATE INDEX IF NOT EXISTS `idx_maintenance_type` ON `asset_maintenance` (`maintenance_type`);
CREATE INDEX IF NOT EXISTS `idx_next_maintenance` ON `asset_maintenance` (`next_maintenance_date`);

-- ============================================================================
-- 13. CREATE SMS NOTIFICATION LOG TABLE (OPTIONAL)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `sms_notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('return_reminder','overdue_alert','approval_notification','general') NOT NULL,
    `status` ENUM('pending','sent','failed','delivered') DEFAULT 'pending',
    `sent_at` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `error_message` TEXT NULL,
    `provider_response` TEXT NULL,
    `related_type` VARCHAR(50) NULL,
    `related_id` INT(11) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`),
    KEY `idx_created_at` (`created_at`),

    CONSTRAINT `fk_sms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. CREATE EMAIL NOTIFICATION LOG TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `email_notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(500) NOT NULL,
    `body` TEXT NOT NULL,
    `type` ENUM('return_reminder','overdue_alert','approval_request','approval_response','account_creation','general') NOT NULL,
    `status` ENUM('pending','sent','failed') DEFAULT 'pending',
    `sent_at` DATETIME NULL,
    `error_message` TEXT NULL,
    `related_type` VARCHAR(50) NULL,
    `related_id` INT(11) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`),
    KEY `idx_created_at` (`created_at`),

    CONSTRAINT `fk_email_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. CREATE SYSTEM SETTINGS TABLE (FOR CONFIGURATION)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `setting_type` ENUM('string','integer','boolean','json','date') DEFAULT 'string',
    `description` TEXT NULL,
    `category` VARCHAR(50) NULL COMMENT 'Group settings by category',
    `is_public` BOOLEAN DEFAULT FALSE COMMENT 'Can be accessed by non-admins',
    `updated_by` INT(11) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_setting_key` (`setting_key`),
    KEY `idx_category` (`category`),

    CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('reminder_days_before', '2', 'integer', 'Days before return date to send reminder', 'notifications'),
('overdue_check_enabled', 'true', 'boolean', 'Enable automatic overdue checking', 'notifications'),
('require_department_approval', 'true', 'boolean', 'Require department head approval for requests', 'workflow'),
('require_custodian_review', 'true', 'boolean', 'Require custodian review before admin approval', 'workflow'),
('allow_direct_borrowing', 'false', 'boolean', 'Allow direct borrowing without request process', 'workflow'),
('depreciation_method', 'straight_line', 'string', 'Depreciation calculation method', 'finance'),
('enable_sms_notifications', 'false', 'boolean', 'Enable SMS notifications', 'notifications'),
('enable_email_notifications', 'true', 'boolean', 'Enable email notifications', 'notifications'),
('max_borrowing_days', '30', 'integer', 'Maximum days an asset can be borrowed', 'borrowing'),
('auto_missing_after_days', '60', 'integer', 'Auto-mark as missing after X days overdue', 'borrowing')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- ============================================================================
-- 16. CREATE TRIGGERS FOR AUTOMATION
-- ============================================================================

-- Trigger: Auto-update return_status when borrowing is returned
DELIMITER ;;

DROP TRIGGER IF EXISTS `trg_calculate_return_status`;;

CREATE TRIGGER `trg_calculate_return_status`
    BEFORE UPDATE ON `asset_borrowings`
    FOR EACH ROW
BEGIN
    -- When status changes to returned, calculate if it was late
    IF NEW.status = 'returned' AND OLD.status = 'active' THEN
        SET NEW.actual_return_date = NOW();

        IF NEW.expected_return_date IS NOT NULL THEN
            IF DATE(NEW.actual_return_date) > NEW.expected_return_date THEN
                SET NEW.return_status = 'Returned Late';
                SET NEW.days_overdue = DATEDIFF(DATE(NEW.actual_return_date), NEW.expected_return_date);
            ELSE
                SET NEW.return_status = 'On Time';
                SET NEW.days_overdue = 0;
            END IF;
        END IF;
    END IF;

    -- Update last_known_borrower
    IF NEW.status IN ('active', 'overdue') THEN
        SET NEW.last_known_borrower = NEW.borrower_name;
    END IF;
END;;

DELIMITER ;

-- Trigger: Auto-create movement log when asset location changes
DELIMITER ;;

DROP TRIGGER IF EXISTS `trg_asset_location_change`;;

CREATE TRIGGER `trg_asset_location_change`
    AFTER UPDATE ON `assets`
    FOR EACH ROW
BEGIN
    -- If location, room_id, or office_id changed, log it
    IF (OLD.location != NEW.location) OR
       (OLD.room_id != NEW.room_id OR (OLD.room_id IS NULL AND NEW.room_id IS NOT NULL) OR (OLD.room_id IS NOT NULL AND NEW.room_id IS NULL)) OR
       (OLD.office_id != NEW.office_id OR (OLD.office_id IS NULL AND NEW.office_id IS NOT NULL) OR (OLD.office_id IS NOT NULL AND NEW.office_id IS NULL)) THEN

        INSERT INTO `asset_movement_logs`
        (`asset_id`, `from_location`, `to_location`, `from_room_id`, `to_room_id`, `from_office_id`, `to_office_id`, `movement_type`, `campus_id`)
        VALUES
        (NEW.id, OLD.location, NEW.location, OLD.room_id, NEW.room_id, OLD.office_id, NEW.office_id, 'transfer', NEW.campus_id);
    END IF;
END;;

DELIMITER ;

-- Trigger: Update original_value when value changes on new assets
DELIMITER ;;

DROP TRIGGER IF EXISTS `trg_asset_depreciation_init`;;

CREATE TRIGGER `trg_asset_depreciation_init`
    BEFORE INSERT ON `assets`
    FOR EACH ROW
BEGIN
    -- Set original_value to value on creation
    IF NEW.original_value IS NULL THEN
        SET NEW.original_value = NEW.value;
    END IF;

    IF NEW.current_value IS NULL THEN
        SET NEW.current_value = NEW.value;
    END IF;
END;;

DELIMITER ;

-- ============================================================================
-- 17. CREATE VIEWS FOR REPORTING
-- ============================================================================

-- View: Overdue Borrowings
CREATE OR REPLACE VIEW `view_overdue_borrowings` AS
SELECT
    ab.id as borrowing_id,
    ab.asset_id,
    a.asset_name,
    a.barcode,
    ab.borrower_name,
    ab.borrower_contact,
    ab.expected_return_date,
    DATEDIFF(CURDATE(), ab.expected_return_date) as days_overdue,
    ab.status,
    ab.overdue_notification_sent,
    ab.recorded_by,
    c.name as campus_name,
    a.campus_id
FROM `asset_borrowings` ab
JOIN `assets` a ON ab.asset_id = a.id
JOIN `campuses` c ON a.campus_id = c.id
WHERE ab.status = 'active'
  AND ab.expected_return_date IS NOT NULL
  AND ab.expected_return_date < CURDATE();

-- View: Assets needing depreciation update
CREATE OR REPLACE VIEW `view_assets_depreciation_status` AS
SELECT
    a.id,
    a.asset_name,
    a.original_value,
    a.current_value,
    a.depreciation_rate,
    a.purchase_date,
    a.last_depreciation_date,
    TIMESTAMPDIFF(MONTH, a.purchase_date, CURDATE()) as months_since_purchase,
    TIMESTAMPDIFF(MONTH, COALESCE(a.last_depreciation_date, a.purchase_date), CURDATE()) as months_since_last_calc,
    c.name as campus_name,
    cat.name as category_name
FROM `assets` a
JOIN `campuses` c ON a.campus_id = c.id
JOIN `categories` cat ON a.category_id = cat.id
WHERE a.depreciation_rate > 0
  AND a.status != 'Retired';

-- View: Missing Assets Summary
CREATE OR REPLACE VIEW `view_missing_assets_summary` AS
SELECT
    mar.id as report_id,
    a.id as asset_id,
    a.asset_name,
    a.barcode,
    a.serial_number,
    a.value as asset_value,
    mar.last_known_location,
    mar.last_known_borrower,
    mar.reported_date,
    mar.status as investigation_status,
    u.full_name as reported_by_name,
    c.name as campus_name,
    mar.campus_id
FROM `missing_assets_reports` mar
JOIN `assets` a ON mar.asset_id = a.id
JOIN `users` u ON mar.reported_by = u.id
JOIN `campuses` c ON mar.campus_id = c.id
WHERE mar.status IN ('reported', 'investigating');

-- View: Department Utilization Report
CREATE OR REPLACE VIEW `view_department_asset_utilization` AS
SELECT
    o.id as office_id,
    o.name as office_name,
    c.name as campus_name,
    COUNT(DISTINCT a.id) as total_assets,
    SUM(a.current_value) as total_value,
    COUNT(DISTINCT CASE WHEN a.status = 'Active' THEN a.id END) as active_assets,
    COUNT(DISTINCT CASE WHEN a.status = 'Damaged' THEN a.id END) as damaged_assets,
    COUNT(DISTINCT CASE WHEN a.status = 'Missing' THEN a.id END) as missing_assets,
    COUNT(DISTINCT ab.id) as total_borrowings,
    COUNT(DISTINCT CASE WHEN ab.status = 'overdue' THEN ab.id END) as overdue_borrowings
FROM `offices` o
JOIN `campuses` c ON o.campus_id = c.id
LEFT JOIN `assets` a ON a.office_id = o.id
LEFT JOIN `asset_borrowings` ab ON ab.asset_id = a.id
GROUP BY o.id, o.name, c.name;

-- ============================================================================
-- COMMIT TRANSACTION
-- ============================================================================

COMMIT;

-- ============================================================================
-- VERIFICATION QUERIES (OPTIONAL - RUN AFTER MIGRATION)
-- ============================================================================

-- Check new tables
-- SELECT COUNT(*) as notifications_count FROM `notifications`;
-- SELECT COUNT(*) as borrowing_chain_count FROM `borrowing_chain`;
-- SELECT COUNT(*) as movement_logs_count FROM `asset_movement_logs`;
-- SELECT COUNT(*) as missing_reports_count FROM `missing_assets_reports`;
-- SELECT COUNT(*) as dept_approvers_count FROM `department_approvers`;

-- Check updated columns
-- DESCRIBE `assets`;
-- DESCRIBE `asset_borrowings`;
-- DESCRIBE `asset_requests`;
-- DESCRIBE `users`;

-- Check system settings
-- SELECT * FROM `system_settings`;

-- Check views
-- SELECT * FROM `view_overdue_borrowings` LIMIT 5;
-- SELECT * FROM `view_assets_depreciation_status` LIMIT 5;

-- ============================================================================
-- ROLLBACK SCRIPT (USE ONLY IF NEEDED TO UNDO CHANGES)
-- ============================================================================

/*
-- WARNING: This will remove all the changes made by this migration!

DROP VIEW IF EXISTS `view_department_asset_utilization`;
DROP VIEW IF EXISTS `view_missing_assets_summary`;
DROP VIEW IF EXISTS `view_assets_depreciation_status`;
DROP VIEW IF EXISTS `view_overdue_borrowings`;

DROP TRIGGER IF EXISTS `trg_asset_depreciation_init`;
DROP TRIGGER IF EXISTS `trg_asset_location_change`;
DROP TRIGGER IF EXISTS `trg_calculate_return_status`;

DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `email_notifications`;
DROP TABLE IF EXISTS `sms_notifications`;
DROP TABLE IF EXISTS `department_approvers`;
DROP TABLE IF EXISTS `missing_assets_reports`;
DROP TABLE IF EXISTS `asset_movement_logs`;
DROP TABLE IF EXISTS `borrowing_chain`;
DROP TABLE IF EXISTS `notifications`;

ALTER TABLE `users` MODIFY COLUMN `role` ENUM('staff','custodian','admin','super_admin','office') NOT NULL DEFAULT 'staff';
ALTER TABLE `assets` MODIFY COLUMN `status` ENUM('Active','Inactive','Maintenance','Retired') DEFAULT 'Active';
*/

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
