-- ============================================================================
-- INDIVIDUAL ASSET TRACKING SYSTEM
-- Migration: Add support for tracking individual asset units
-- Created: 2025-01-12
-- ============================================================================

-- Table: asset_units
-- Purpose: Track individual units of assets for detailed accountability
-- Example: 30 chairs = 30 individual unit records with unique serial numbers
CREATE TABLE IF NOT EXISTS asset_units (
    id INT(11) NOT NULL AUTO_INCREMENT,
    asset_id INT(11) NOT NULL COMMENT 'Reference to parent asset',
    unit_serial_number VARCHAR(100) NOT NULL COMMENT 'Unique serial number for this specific unit',
    unit_code VARCHAR(50) NULL COMMENT 'Short code for easy reference (e.g., CHAIR-001)',
    unit_status ENUM('Available', 'In Use', 'Damaged', 'Missing', 'Under Repair', 'Disposed') DEFAULT 'Available' COMMENT 'Current status of this specific unit',
    condition_rating ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Non-functional') DEFAULT 'Good' COMMENT 'Physical condition of the unit',
    assigned_to_user_id INT(11) NULL COMMENT 'If assigned to specific person',
    location_notes TEXT NULL COMMENT 'Specific location within office/room',
    acquisition_date DATE NULL COMMENT 'Date this unit was acquired',
    warranty_expiry DATE NULL COMMENT 'Warranty expiration for this unit',
    last_maintenance_date DATE NULL COMMENT 'Last time this unit was serviced',
    notes TEXT NULL COMMENT 'Additional notes about this specific unit',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT(11) NULL COMMENT 'User who created this unit record',

    PRIMARY KEY (id),
    UNIQUE KEY unique_unit_serial (unit_serial_number),
    INDEX idx_asset_id (asset_id),
    INDEX idx_unit_status (unit_status),
    INDEX idx_unit_code (unit_code),

    CONSTRAINT fk_asset_units_asset
        FOREIGN KEY (asset_id)
        REFERENCES assets(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_asset_units_assigned_user
        FOREIGN KEY (assigned_to_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_asset_units_created_by
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual asset units for detailed tracking';


-- Table: tag_units
-- Purpose: Link inventory tags to specific asset units
-- Example: Tag MIS-112025-1234 contains CHAIR-001, CHAIR-002... CHAIR-010
CREATE TABLE IF NOT EXISTS tag_units (
    id INT(11) NOT NULL AUTO_INCREMENT,
    tag_id INT(11) NOT NULL COMMENT 'Reference to inventory tag',
    unit_id INT(11) NOT NULL COMMENT 'Reference to specific asset unit',
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this unit was added to the tag',
    removed_at TIMESTAMP NULL COMMENT 'When this unit was removed from the tag',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Is this unit currently part of the tag',
    notes TEXT NULL COMMENT 'Notes about this assignment',

    PRIMARY KEY (id),
    INDEX idx_tag_id (tag_id),
    INDEX idx_unit_id (unit_id),
    INDEX idx_is_active (is_active),
    UNIQUE KEY unique_active_assignment (tag_id, unit_id, is_active),

    CONSTRAINT fk_tag_units_tag
        FOREIGN KEY (tag_id)
        REFERENCES inventory_tags(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_tag_units_unit
        FOREIGN KEY (unit_id)
        REFERENCES asset_units(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Links inventory tags to specific asset units';


-- Table: unit_history
-- Purpose: Track all changes to individual units (movements, status changes, etc.)
CREATE TABLE IF NOT EXISTS unit_history (
    id INT(11) NOT NULL AUTO_INCREMENT,
    unit_id INT(11) NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'CREATED, ASSIGNED, TRANSFERRED, STATUS_CHANGED, DAMAGED, REPAIRED, etc.',
    old_value VARCHAR(255) NULL,
    new_value VARCHAR(255) NULL,
    description TEXT NULL,
    performed_by INT(11) NULL,
    performed_by_name VARCHAR(255) NULL COMMENT 'Cached name for history',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_unit_id (unit_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),

    CONSTRAINT fk_unit_history_unit
        FOREIGN KEY (unit_id)
        REFERENCES asset_units(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_unit_history_user
        FOREIGN KEY (performed_by)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='History log for individual asset units';


-- Modify assets table to add tracking mode flag
ALTER TABLE assets
ADD COLUMN IF NOT EXISTS track_individually BOOLEAN DEFAULT FALSE
COMMENT 'TRUE = track each unit individually, FALSE = bulk quantity tracking';

ALTER TABLE assets
ADD COLUMN IF NOT EXISTS auto_create_units BOOLEAN DEFAULT FALSE
COMMENT 'Automatically create unit records when quantity is added';

-- Add index for faster queries
ALTER TABLE assets ADD INDEX IF NOT EXISTS idx_track_individually (track_individually);


-- ============================================================================
-- SAMPLE DATA: Create some test units for existing assets
-- ============================================================================

-- Example: If you want to enable individual tracking for chairs
-- UPDATE assets SET track_individually = TRUE WHERE asset_name LIKE '%Chair%';

-- Then create units for an asset (example for asset_id = 206 with 30 chairs)
-- This would typically be done via the application interface
/*
INSERT INTO asset_units (asset_id, unit_serial_number, unit_code, unit_status, condition_rating, created_by)
SELECT
    206 as asset_id,
    CONCAT('HCC2501081990-', LPAD(n, 3, '0')) as unit_serial_number,
    CONCAT('CHAIR-', LPAD(n, 3, '0')) as unit_code,
    'Available' as unit_status,
    'Good' as condition_rating,
    1 as created_by
FROM (
    SELECT @row := @row + 1 AS n
    FROM (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
         (SELECT 0 UNION SELECT 1 UNION SELECT 2) t2,
         (SELECT @row := 0) r
) numbers
WHERE n <= 30;
*/

-- ============================================================================
-- VIEWS FOR EASY QUERYING
-- ============================================================================

-- View: v_unit_details
-- Purpose: Get complete details of each unit with asset and tag information
CREATE OR REPLACE VIEW v_unit_details AS
SELECT
    u.id AS unit_id,
    u.unit_serial_number,
    u.unit_code,
    u.unit_status,
    u.condition_rating,
    u.location_notes,
    u.notes AS unit_notes,
    a.id AS asset_id,
    a.asset_name,
    a.serial_number AS asset_serial_number,
    c.category_name,
    tu.tag_id,
    it.tag_number,
    it.office_id,
    o.office_name,
    o.floor,
    cam.campus_name,
    u.created_at AS unit_created_at,
    u.updated_at AS unit_updated_at
FROM asset_units u
INNER JOIN assets a ON u.asset_id = a.id
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN tag_units tu ON u.id = tu.unit_id AND tu.is_active = TRUE
LEFT JOIN inventory_tags it ON tu.tag_id = it.id
LEFT JOIN offices o ON it.office_id = o.id
LEFT JOIN campuses cam ON a.campus_id = cam.id;


-- View: v_office_units
-- Purpose: Show all units assigned to each office
CREATE OR REPLACE VIEW v_office_units AS
SELECT
    o.id AS office_id,
    o.office_name,
    o.floor,
    it.id AS tag_id,
    it.tag_number,
    it.status AS tag_status,
    a.id AS asset_id,
    a.asset_name,
    COUNT(DISTINCT u.id) AS total_units,
    SUM(CASE WHEN u.unit_status = 'Available' THEN 1 ELSE 0 END) AS available_units,
    SUM(CASE WHEN u.unit_status = 'In Use' THEN 1 ELSE 0 END) AS in_use_units,
    SUM(CASE WHEN u.unit_status = 'Damaged' THEN 1 ELSE 0 END) AS damaged_units,
    SUM(CASE WHEN u.unit_status = 'Missing' THEN 1 ELSE 0 END) AS missing_units,
    SUM(CASE WHEN u.condition_rating = 'Excellent' THEN 1 ELSE 0 END) AS excellent_condition,
    SUM(CASE WHEN u.condition_rating = 'Good' THEN 1 ELSE 0 END) AS good_condition,
    SUM(CASE WHEN u.condition_rating = 'Fair' THEN 1 ELSE 0 END) AS fair_condition,
    SUM(CASE WHEN u.condition_rating = 'Poor' THEN 1 ELSE 0 END) AS poor_condition
FROM offices o
LEFT JOIN inventory_tags it ON o.id = it.office_id
LEFT JOIN assets a ON it.asset_id = a.id
LEFT JOIN tag_units tu ON it.id = tu.tag_id AND tu.is_active = TRUE
LEFT JOIN asset_units u ON tu.unit_id = u.id
GROUP BY o.id, o.office_name, o.floor, it.id, it.tag_number, it.status, a.id, a.asset_name;


-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================

-- Procedure: sp_create_units_for_asset
-- Purpose: Automatically create unit records for an asset
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_create_units_for_asset$$

CREATE PROCEDURE sp_create_units_for_asset(
    IN p_asset_id INT,
    IN p_quantity INT,
    IN p_created_by INT
)
BEGIN
    DECLARE v_asset_name VARCHAR(255);
    DECLARE v_serial_prefix VARCHAR(100);
    DECLARE v_counter INT DEFAULT 1;
    DECLARE v_existing_count INT;

    -- Get asset details
    SELECT asset_name, serial_number
    INTO v_asset_name, v_serial_prefix
    FROM assets
    WHERE id = p_asset_id;

    -- Check how many units already exist
    SELECT COUNT(*) INTO v_existing_count
    FROM asset_units
    WHERE asset_id = p_asset_id;

    -- Start counter from existing count + 1
    SET v_counter = v_existing_count + 1;

    -- Create units
    WHILE v_counter <= (v_existing_count + p_quantity) DO
        INSERT INTO asset_units (
            asset_id,
            unit_serial_number,
            unit_code,
            unit_status,
            condition_rating,
            created_by
        ) VALUES (
            p_asset_id,
            CONCAT(v_serial_prefix, '-', LPAD(v_counter, 3, '0')),
            CONCAT(UPPER(LEFT(v_asset_name, 5)), '-', LPAD(v_counter, 3, '0')),
            'Available',
            'Good',
            p_created_by
        );

        SET v_counter = v_counter + 1;
    END WHILE;

    -- Log the creation
    INSERT INTO unit_history (unit_id, action, description, performed_by, performed_by_name)
    SELECT
        id,
        'CREATED',
        CONCAT('Unit created for ', v_asset_name),
        p_created_by,
        (SELECT full_name FROM users WHERE id = p_created_by)
    FROM asset_units
    WHERE asset_id = p_asset_id
    AND id > (SELECT COALESCE(MAX(id), 0) - p_quantity FROM asset_units WHERE asset_id = p_asset_id);

END$$

DELIMITER ;


-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================
SELECT 'Individual Asset Tracking System installed successfully!' AS Status;
SELECT 'Tables created: asset_units, tag_units, unit_history' AS Info;
SELECT 'Views created: v_unit_details, v_office_units' AS Info;
SELECT 'Stored procedures created: sp_create_units_for_asset' AS Info;
