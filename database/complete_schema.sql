-- ============================================================================
-- HCC Asset Management System - Complete Database Schema
-- Database: hcc_asset_management
-- Created: 2025
-- ============================================================================

-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS hcc_asset_management;
CREATE DATABASE hcc_asset_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hcc_asset_management;

-- ============================================================================
-- TABLE: campuses
-- Stores campus/location information
-- ============================================================================
CREATE TABLE campuses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campus_name VARCHAR(100) NOT NULL,
    campus_code VARCHAR(20) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_campus_code (campus_code),
    KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: users
-- Stores all system users (employees, custodians, admins)
-- ============================================================================
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('employee', 'custodian', 'admin', 'super_admin', 'staff', 'auditor') DEFAULT 'employee',
    campus_id INT(11) DEFAULT NULL,
    phone VARCHAR(20),
    notification_preferences JSON DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    force_password_change TINYINT(1) DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_email (email),
    KEY idx_role (role),
    KEY idx_campus (campus_id),
    KEY idx_is_active (is_active),
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: categories
-- Asset categories (Computers, Furniture, etc.)
-- ============================================================================
CREATE TABLE categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_category_name (category_name),
    KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: assets
-- Main assets table
-- ============================================================================
CREATE TABLE assets (
    id INT(11) NOT NULL AUTO_INCREMENT,
    asset_name VARCHAR(200) NOT NULL,
    category_id INT(11) NOT NULL,
    campus_id INT(11) DEFAULT NULL,
    serial_number VARCHAR(100),
    asset_code VARCHAR(100),
    brand VARCHAR(100),
    model VARCHAR(100),
    quantity INT(11) DEFAULT 1,
    unit_price DECIMAL(12,2) DEFAULT 0.00,
    acquisition_date DATE,
    warranty_expiry DATE,
    status ENUM('Available', 'In Use', 'Under Maintenance', 'Damaged', 'Disposed') DEFAULT 'Available',
    description TEXT,
    location VARCHAR(200),
    assigned_to INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_category (category_id),
    KEY idx_campus (campus_id),
    KEY idx_status (status),
    KEY idx_assigned_to (assigned_to),
    KEY idx_serial_number (serial_number),
    KEY idx_asset_code (asset_code),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: asset_requests
-- Asset request/borrowing system
-- ============================================================================
CREATE TABLE asset_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    requester_id INT(11) NOT NULL,
    asset_id INT(11) NOT NULL,
    campus_id INT(11) DEFAULT NULL,
    quantity INT(11) DEFAULT 1,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    purpose TEXT,
    status ENUM('pending', 'custodian_review', 'department_review', 'approved', 'rejected', 'released', 'returned') DEFAULT 'pending',

    -- Approval tracking
    custodian_reviewed_by INT(11) DEFAULT NULL,
    custodian_reviewed_at DATETIME DEFAULT NULL,
    custodian_notes TEXT,

    final_approved_by INT(11) DEFAULT NULL,
    final_approved_at DATETIME DEFAULT NULL,
    admin_notes TEXT,

    -- Rejection tracking
    rejection_reason TEXT,
    rejected_by INT(11) DEFAULT NULL,
    rejected_at DATETIME DEFAULT NULL,

    -- Release/Return tracking
    released_at DATETIME DEFAULT NULL,
    released_by INT(11) DEFAULT NULL,
    release_notes TEXT,

    expected_return_date DATE DEFAULT NULL,
    returned_at DATETIME DEFAULT NULL,
    returned_by INT(11) DEFAULT NULL,
    return_condition ENUM('good', 'fair', 'damaged') DEFAULT NULL,
    return_notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_requester (requester_id),
    KEY idx_asset (asset_id),
    KEY idx_campus (campus_id),
    KEY idx_status (status),
    KEY idx_request_date (request_date),
    KEY idx_custodian_reviewer (custodian_reviewed_by),
    KEY idx_final_approver (final_approved_by),

    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE SET NULL,
    FOREIGN KEY (custodian_reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (final_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (released_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: notifications
-- User notification system
-- ============================================================================
CREATE TABLE notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    type ENUM('return_reminder', 'overdue_alert', 'approval_request', 'approval_response', 'missing_report', 'system_alert') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_type ENUM('asset', 'borrowing', 'request', 'maintenance') DEFAULT NULL,
    related_id INT(11) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME DEFAULT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    action_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,

    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_type (type),
    KEY idx_is_read (is_read),
    KEY idx_priority (priority),
    KEY idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: activity_logs
-- System activity logging
-- ============================================================================
CREATE TABLE activity_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    asset_id INT(11) DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_asset (asset_id),
    KEY idx_action (action),
    KEY idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: department_approvers
-- Department-level approval configuration
-- ============================================================================
CREATE TABLE department_approvers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    campus_id INT(11) NOT NULL,
    approver_user_id INT(11) NOT NULL,
    department_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_campus (campus_id),
    KEY idx_approver (approver_user_id),
    KEY idx_is_active (is_active),

    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: system_settings
-- System configuration settings
-- ============================================================================
CREATE TABLE system_settings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY unique_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: sessions
-- User session management
-- ============================================================================
CREATE TABLE sessions (
    id VARCHAR(128) NOT NULL,
    user_id INT(11) DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_last_activity (last_activity),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: asset_movement_logs
-- Tracks physical movement of assets between locations
-- ============================================================================
CREATE TABLE asset_movement_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    asset_id INT(11) NOT NULL,
    from_location VARCHAR(255) DEFAULT NULL,
    to_location VARCHAR(255) NOT NULL,
    from_room_id INT(11) DEFAULT NULL,
    to_room_id INT(11) DEFAULT NULL,
    from_office_id INT(11) DEFAULT NULL,
    to_office_id INT(11) DEFAULT NULL,
    movement_type ENUM('deployment', 'transfer', 'return', 'audit', 'maintenance', 'storage') NOT NULL,
    moved_by INT(11) DEFAULT NULL,
    reason TEXT,
    moved_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_by INT(11) DEFAULT NULL,
    verified_date DATETIME DEFAULT NULL,
    campus_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_asset (asset_id),
    KEY idx_movement_type (movement_type),
    KEY idx_moved_by (moved_by),
    KEY idx_verified_by (verified_by),
    KEY idx_campus (campus_id),
    KEY idx_moved_date (moved_date),

    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (moved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: borrowing_chain
-- Tracks asset transfer chains between people (sub-borrowing)
-- ============================================================================
CREATE TABLE borrowing_chain (
    id INT(11) NOT NULL AUTO_INCREMENT,
    borrowing_id INT(11) NOT NULL,
    asset_id INT(11) NOT NULL,
    from_person VARCHAR(255) NOT NULL,
    to_person VARCHAR(255) NOT NULL,
    to_person_contact VARCHAR(255) DEFAULT NULL,
    transfer_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE DEFAULT NULL,
    actual_return_date DATETIME DEFAULT NULL,
    status ENUM('active', 'returned') DEFAULT 'active',
    notes TEXT,
    recorded_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_borrowing (borrowing_id),
    KEY idx_asset (asset_id),
    KEY idx_transfer_date (transfer_date),
    KEY idx_status (status),
    KEY idx_recorded_by (recorded_by),

    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: email_notifications
-- Email notification queue and history
-- ============================================================================
CREATE TABLE email_notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('return_reminder', 'overdue_alert', 'approval_request', 'approval_response', 'account_creation', 'general') NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at DATETIME DEFAULT NULL,
    error_message TEXT,
    related_type VARCHAR(50) DEFAULT NULL,
    related_id INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_type (type),
    KEY idx_status (status),
    KEY idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: missing_assets_reports
-- Reports and tracking of missing/lost assets
-- ============================================================================
CREATE TABLE missing_assets_reports (
    id INT(11) NOT NULL AUTO_INCREMENT,
    asset_id INT(11) NOT NULL,
    reported_by INT(11) NOT NULL,
    reported_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_known_location VARCHAR(255) DEFAULT NULL,
    last_known_borrower VARCHAR(255) DEFAULT NULL,
    last_known_borrower_contact VARCHAR(255) DEFAULT NULL,
    last_seen_date DATE DEFAULT NULL,
    responsible_department VARCHAR(255) DEFAULT NULL,
    description TEXT NOT NULL,
    status ENUM('reported', 'investigating', 'found', 'permanently_lost') DEFAULT 'reported',
    resolution_notes TEXT,
    resolved_by INT(11) DEFAULT NULL,
    resolved_date DATETIME DEFAULT NULL,
    campus_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_asset (asset_id),
    KEY idx_reported_by (reported_by),
    KEY idx_reported_date (reported_date),
    KEY idx_status (status),
    KEY idx_resolved_by (resolved_by),
    KEY idx_campus (campus_id),

    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TABLE: sms_notifications
-- SMS notification queue and history
-- ============================================================================
CREATE TABLE sms_notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('return_reminder', 'overdue_alert', 'approval_notification', 'general') NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    sent_at DATETIME DEFAULT NULL,
    delivered_at DATETIME DEFAULT NULL,
    error_message TEXT,
    provider_response TEXT,
    related_type VARCHAR(50) DEFAULT NULL,
    related_id INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_type (type),
    KEY idx_status (status),
    KEY idx_created_at (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SAMPLE DATA - Campuses
-- ============================================================================
INSERT INTO campuses (campus_name, campus_code, address, contact_person, contact_number, is_active) VALUES
('Main Campus', 'MAIN', 'Holy Cross Colleges Main Campus, City', 'Admin Office', '123-4567', 1),
('Annex Campus', 'ANNEX', 'Holy Cross Colleges Annex, City', 'Annex Office', '123-4568', 1),
('Extension Campus', 'EXT', 'Holy Cross Colleges Extension, City', 'Extension Office', '123-4569', 1);

-- ============================================================================
-- SAMPLE DATA - Categories
-- ============================================================================
INSERT INTO categories (category_name, description, is_active) VALUES
('Computers & Laptops', 'Desktop computers, laptops, and tablets', 1),
('Office Equipment', 'Printers, scanners, copiers', 1),
('Furniture', 'Desks, chairs, cabinets', 1),
('Audio/Visual Equipment', 'Projectors, speakers, microphones', 1),
('Networking Equipment', 'Routers, switches, access points', 1),
('Mobile Devices', 'Smartphones, tablets', 1),
('Tools & Equipment', 'Hand tools, power tools', 1),
('Vehicles', 'Company vehicles', 1);

-- ============================================================================
-- SAMPLE DATA - Users
-- Password: password123 (hashed with PASSWORD_BCRYPT)
-- ============================================================================
INSERT INTO users (full_name, email, password, role, campus_id, phone, is_active, force_password_change) VALUES
-- Super Admin
('System Administrator', 'admin@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1, '123-0001', 1, 0),

-- Admins
('John Admin', 'john.admin@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '123-0002', 1, 0),
('Maria Santos', 'maria.admin@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 2, '123-0003', 1, 0),

-- Custodians
('Pedro Custodian', 'pedro.custodian@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'custodian', 1, '123-0004', 1, 0),
('Ana Custodian', 'ana.custodian@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'custodian', 2, '123-0005', 1, 0),

-- Employees/Staff
('Juan Dela Cruz', 'juan.employee@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '123-0006', 1, 0),
('Lisa Garcia', 'lisa.employee@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 1, '123-0007', 1, 0),
('Mark Reyes', 'mark.employee@hcc.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 2, '123-0008', 1, 0);

-- ============================================================================
-- SAMPLE DATA - Assets
-- ============================================================================
INSERT INTO assets (asset_name, category_id, campus_id, serial_number, asset_code, brand, model, quantity, unit_price, acquisition_date, status, location) VALUES
-- Computers
('Dell Laptop', 1, 1, 'DL2024001', 'HCC-COMP-001', 'Dell', 'Latitude 5420', 5, 45000.00, '2024-01-15', 'Available', 'IT Department'),
('HP Desktop Computer', 1, 1, 'HP2024001', 'HCC-COMP-002', 'HP', 'EliteDesk 800', 10, 35000.00, '2024-01-20', 'Available', 'Computer Lab'),
('MacBook Pro', 1, 2, 'MBP2024001', 'HCC-COMP-003', 'Apple', 'MacBook Pro 14"', 3, 95000.00, '2024-02-01', 'Available', 'Design Department'),

-- Office Equipment
('HP LaserJet Printer', 2, 1, 'HPP2024001', 'HCC-PRINT-001', 'HP', 'LaserJet Pro M404', 8, 12000.00, '2024-01-10', 'Available', 'Admin Office'),
('Canon Scanner', 2, 1, 'CS2024001', 'HCC-SCAN-001', 'Canon', 'ImageFORMULA DR-C225', 3, 18000.00, '2024-01-25', 'Available', 'Records Office'),

-- Audio/Visual
('Epson Projector', 4, 1, 'EP2024001', 'HCC-PROJ-001', 'Epson', 'EB-X41', 15, 22000.00, '2023-12-15', 'Available', 'AV Room'),
('Wireless Microphone Set', 4, 1, 'WM2024001', 'HCC-MIC-001', 'Shure', 'BLX288/PG58', 5, 15000.00, '2024-01-05', 'Available', 'Auditorium'),

-- Furniture
('Office Desk', 3, 1, NULL, 'HCC-DESK-001', 'Ikea', 'Bekant', 50, 8000.00, '2023-11-01', 'Available', 'Faculty Offices'),
('Office Chair', 3, 1, NULL, 'HCC-CHAIR-001', 'Herman Miller', 'Aeron', 50, 12000.00, '2023-11-01', 'Available', 'Faculty Offices'),

-- Networking
('Cisco Router', 5, 1, 'CR2024001', 'HCC-NET-001', 'Cisco', 'RV340', 5, 25000.00, '2024-02-10', 'In Use', 'Server Room'),
('TP-Link Access Point', 5, 1, 'TP2024001', 'HCC-NET-002', 'TP-Link', 'EAP245', 20, 4500.00, '2024-02-15', 'Available', 'IT Storage');

-- ============================================================================
-- SAMPLE DATA - System Settings
-- ============================================================================
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('require_department_approval', '0', 'boolean', 'Require department head approval for asset requests'),
('require_admin_approval', '1', 'boolean', 'Require admin approval for asset requests'),
('default_request_duration_days', '30', 'number', 'Default duration for asset requests in days'),
('enable_email_notifications', '1', 'boolean', 'Enable email notifications system-wide'),
('system_name', 'HCC Asset Management System', 'string', 'System display name'),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode');

-- ============================================================================
-- INDEXES OPTIMIZATION
-- ============================================================================

-- Additional composite indexes for common queries
ALTER TABLE assets ADD INDEX idx_category_status (category_id, status);
ALTER TABLE assets ADD INDEX idx_campus_status (campus_id, status);
ALTER TABLE asset_requests ADD INDEX idx_status_date (status, request_date);
ALTER TABLE notifications ADD INDEX idx_user_unread (user_id, is_read, created_at);
ALTER TABLE activity_logs ADD INDEX idx_user_date (user_id, created_at);

-- ============================================================================
-- VIEWS FOR COMMON QUERIES
-- ============================================================================

-- Available assets view
CREATE OR REPLACE VIEW view_available_assets AS
SELECT
    a.*,
    c.category_name,
    cam.campus_name
FROM assets a
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN campuses cam ON a.campus_id = cam.id
WHERE a.status = 'Available' AND a.quantity > 0;

-- Pending requests view
CREATE OR REPLACE VIEW view_pending_requests AS
SELECT
    ar.*,
    u.full_name AS requester_name,
    u.email AS requester_email,
    a.asset_name,
    a.category_id,
    c.category_name,
    cam.campus_name
FROM asset_requests ar
INNER JOIN users u ON ar.requester_id = u.id
INNER JOIN assets a ON ar.asset_id = a.id
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN campuses cam ON ar.campus_id = cam.id
WHERE ar.status IN ('pending', 'custodian_review', 'department_review');

-- User statistics view
CREATE OR REPLACE VIEW view_user_statistics AS
SELECT
    u.id AS user_id,
    u.full_name,
    u.email,
    u.role,
    COUNT(DISTINCT ar.id) AS total_requests,
    SUM(CASE WHEN ar.status = 'approved' THEN 1 ELSE 0 END) AS approved_requests,
    SUM(CASE WHEN ar.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_requests,
    SUM(CASE WHEN ar.status = 'pending' THEN 1 ELSE 0 END) AS pending_requests
FROM users u
LEFT JOIN asset_requests ar ON u.id = ar.requester_id
GROUP BY u.id, u.full_name, u.email, u.role;

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================
SELECT 'Database schema created successfully!' AS status,
       (SELECT COUNT(*) FROM campuses) AS campuses,
       (SELECT COUNT(*) FROM users) AS users,
       (SELECT COUNT(*) FROM categories) AS categories,
       (SELECT COUNT(*) FROM assets) AS assets,
       (SELECT COUNT(TABLE_NAME) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'hcc_asset_management') AS total_tables;
