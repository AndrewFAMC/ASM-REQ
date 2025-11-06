-- ============================================================================
-- COMPLETE REMAINING ITEMS - Only creates missing tables and views
-- Holy Cross College Asset Management System
-- Date: 2025-11-06
-- ============================================================================

USE `hcc_asset_management`;

START TRANSACTION;

-- ============================================================================
-- CREATE MISSING TABLES (IF NOT EXISTS)
-- ============================================================================

-- 1. Notifications Table
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

-- 2. Borrowing Chain Table
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

-- 3. Asset Movement Logs Table
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

-- 4. Missing Assets Reports Table
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

-- 5. Department Approvers Table
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

-- 6. SMS Notifications Table
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

-- 7. Email Notifications Table
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

-- 8. System Settings Table
DROP TABLE IF EXISTS `system_settings`;

CREATE TABLE `system_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
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
('auto_missing_after_days', '60', 'integer', 'Auto-mark as missing after X days overdue', 'borrowing');

-- ============================================================================
-- CREATE TRIGGERS
-- ============================================================================

DELIMITER ;;

DROP TRIGGER IF EXISTS `trg_calculate_return_status`;;

CREATE TRIGGER `trg_calculate_return_status`
    BEFORE UPDATE ON `asset_borrowings`
    FOR EACH ROW
BEGIN
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

    IF NEW.status IN ('active', 'overdue') THEN
        SET NEW.last_known_borrower = NEW.borrower_name;
    END IF;
END;;

DROP TRIGGER IF EXISTS `trg_asset_location_change`;;

CREATE TRIGGER `trg_asset_location_change`
    AFTER UPDATE ON `assets`
    FOR EACH ROW
BEGIN
    IF (OLD.location != NEW.location) OR
       (OLD.room_id != NEW.room_id OR (OLD.room_id IS NULL AND NEW.room_id IS NOT NULL) OR (OLD.room_id IS NOT NULL AND NEW.room_id IS NULL)) OR
       (OLD.office_id != NEW.office_id OR (OLD.office_id IS NULL AND NEW.office_id IS NOT NULL) OR (OLD.office_id IS NOT NULL AND NEW.office_id IS NULL)) THEN

        INSERT INTO `asset_movement_logs`
        (`asset_id`, `from_location`, `to_location`, `from_room_id`, `to_room_id`, `from_office_id`, `to_office_id`, `movement_type`, `campus_id`)
        VALUES
        (NEW.id, OLD.location, NEW.location, OLD.room_id, NEW.room_id, OLD.office_id, NEW.office_id, 'transfer', NEW.campus_id);
    END IF;
END;;

DROP TRIGGER IF EXISTS `trg_asset_depreciation_init`;;

CREATE TRIGGER `trg_asset_depreciation_init`
    BEFORE INSERT ON `assets`
    FOR EACH ROW
BEGIN
    IF NEW.original_value IS NULL THEN
        SET NEW.original_value = NEW.value;
    END IF;

    IF NEW.current_value IS NULL THEN
        SET NEW.current_value = NEW.value;
    END IF;
END;;

DELIMITER ;

-- ============================================================================
-- CREATE VIEWS
-- ============================================================================

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
    c.campus_name as campus_name,
    a.campus_id
FROM `asset_borrowings` ab
JOIN `assets` a ON ab.asset_id = a.id
JOIN `campuses` c ON a.campus_id = c.id
WHERE ab.status = 'active'
  AND ab.expected_return_date IS NOT NULL
  AND ab.expected_return_date < CURDATE();

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
    c.campus_name as campus_name,
    cat.category_name as category_name
FROM `assets` a
JOIN `campuses` c ON a.campus_id = c.id
JOIN `categories` cat ON a.category_id = cat.id
WHERE a.depreciation_rate > 0
  AND a.status != 'Retired';

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
    c.campus_name as campus_name,
    mar.campus_id
FROM `missing_assets_reports` mar
JOIN `assets` a ON mar.asset_id = a.id
JOIN `users` u ON mar.reported_by = u.id
JOIN `campuses` c ON mar.campus_id = c.id
WHERE mar.status IN ('reported', 'investigating');

CREATE OR REPLACE VIEW `view_department_asset_utilization` AS
SELECT
    o.id as office_id,
    o.office_name as office_name,
    c.campus_name as campus_name,
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
GROUP BY o.id, o.office_name, c.campus_name;

COMMIT;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT '=== MIGRATION COMPLETED SUCCESSFULLY ===' as Status;
SELECT 'New Tables:' as Info;
SELECT COUNT(*) as count FROM `notifications`;
SELECT COUNT(*) as count FROM `borrowing_chain`;
SELECT COUNT(*) as count FROM `asset_movement_logs`;
SELECT COUNT(*) as count FROM `missing_assets_reports`;
SELECT COUNT(*) as count FROM `department_approvers`;
SELECT COUNT(*) as count FROM `system_settings`;

SELECT 'System Settings:' as Info;
SELECT * FROM `system_settings`;

-- ============================================================================
-- END
-- ============================================================================
