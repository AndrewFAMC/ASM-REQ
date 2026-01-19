-- Fix assignment trigger to allow office assignments without email
-- Drop the existing trigger
DROP TRIGGER IF EXISTS update_asset_assignment_history;

-- Modify asset_assignments table to allow NULL for assigned_email
ALTER TABLE asset_assignments
MODIFY COLUMN assigned_email VARCHAR(255) NULL DEFAULT NULL;

-- Recreate the trigger with NULL handling
DELIMITER //
CREATE TRIGGER update_asset_assignment_history
AFTER UPDATE ON assets
FOR EACH ROW
BEGIN
    -- If assignment changed
    IF (OLD.assigned_to IS NULL AND NEW.assigned_to IS NOT NULL) OR
       (OLD.assigned_to IS NOT NULL AND NEW.assigned_to IS NULL) OR
       (OLD.assigned_to != NEW.assigned_to) THEN

        -- Close previous assignment if exists
        UPDATE asset_assignments
        SET unassigned_date = CURDATE()
        WHERE asset_id = NEW.id AND unassigned_date IS NULL;

        -- Create new assignment if assigned
        IF NEW.assigned_to IS NOT NULL THEN
            INSERT INTO asset_assignments (
                asset_id,
                assigned_to,
                assigned_email,
                assignment_date,
                assigned_to_id
            )
            VALUES (
                NEW.id,
                NEW.assigned_to,
                NEW.assigned_email,  -- Can be NULL for office assignments
                COALESCE(NEW.assignment_date, CURDATE()),
                NEW.assigned_to_id   -- Will be NULL for office assignments
            );
        END IF;
    END IF;
END//
DELIMITER ;
