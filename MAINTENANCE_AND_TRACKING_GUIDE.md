# Maintenance and Status Tracking Guide

## System Overview

Your AMS-REQ has **3 levels** of maintenance and status tracking:

1. **Asset Level** - Overall asset status and maintenance
2. **Unit Level** - Individual unit status, condition, and maintenance
3. **History Level** - Complete audit trail of all changes

---

## 1. Individual Unit Status Tracking

### Available Unit Statuses:
- **Available** - Ready to use, in stock
- **In Use** - Currently assigned/borrowed
- **Damaged** - Broken, needs repair
- **Missing** - Cannot be located
- **Under Repair** - Being fixed
- **Disposed** - Removed from inventory

### Condition Ratings:
- **Excellent** - Like new
- **Good** - Normal wear
- **Fair** - Shows wear, still functional
- **Poor** - Degraded, needs attention
- **Non-functional** - Broken, unusable

### Where to Track:
**Table**: `asset_units`

**Fields**:
- `unit_status` - Current status (Available, In Use, etc.)
- `condition_rating` - Physical condition (Excellent, Good, etc.)
- `last_maintenance_date` - When last serviced
- `warranty_expiry` - Warranty end date
- `notes` - Any special notes

### How to View:
1. **Office Users**: `office/view_assets_detailed.php`
   - See all units assigned to your office
   - View status and condition of each unit
   - Report issues on specific units

2. **Custodian/Admin**: `custodian/dashboard.php`
   - View all units across all offices
   - Search by unit code
   - Update status and condition

---

## 2. Asset Maintenance Tracking

### Maintenance Types:
- **Cleaning** - Regular cleaning
- **Repair** - Fix broken items
- **Inspection** - Regular checks
- **Calibration** - Technical adjustments
- **Other** - Custom maintenance

### Maintenance Status:
- **Pending** - Scheduled but not started
- **In Progress** - Currently being worked on
- **Completed** - Finished
- **Cancelled** - No longer needed

### Where to Track:
**Table**: `asset_maintenance`

**Fields**:
- `maintenance_type` - Type of maintenance
- `maintenance_date` - When performed
- `next_maintenance_date` - When next due
- `description` - What was done
- `performed_by` - Who did it
- `cost` - How much it cost
- `status` - Current status
- `notes` - Additional info

### How to Use:

#### View Maintenance History:
```sql
SELECT * FROM asset_maintenance
WHERE asset_id = 1
ORDER BY maintenance_date DESC;
```

#### Schedule Maintenance:
```sql
INSERT INTO asset_maintenance
(asset_id, maintenance_type, maintenance_date, next_maintenance_date, description, status)
VALUES
(1, 'Inspection', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'Annual inspection', 'Pending');
```

#### Complete Maintenance:
```sql
UPDATE asset_maintenance
SET status = 'Completed',
    notes = 'All tests passed'
WHERE id = 123;
```

---

## 3. Unit History (Audit Trail)

### What Gets Tracked:
- Status changes (Available → Damaged)
- Condition changes (Good → Fair)
- Assignments (Assigned to office)
- Repairs (Under Repair → Available)
- Location changes
- Any updates to unit

### Where to Track:
**Table**: `unit_history`

**Fields**:
- `unit_id` - Which unit
- `action` - What happened (e.g., "STATUS_CHANGE", "ASSIGNED")
- `old_value` - Previous value
- `new_value` - New value
- `description` - Details
- `performed_by` - User ID who made change
- `performed_by_name` - User name
- `created_at` - When it happened

### How to View:

#### View History for a Unit:
```sql
SELECT * FROM unit_history
WHERE unit_id = 5
ORDER BY created_at DESC;
```

#### Example History:
```
ID  | Action        | Old Value  | New Value     | Description
----|---------------|------------|---------------|---------------------------
1   | CREATED       | NULL       | Available     | Unit created
2   | ASSIGNED      | Available  | In Use        | Assigned to MIS office
3   | STATUS_CHANGE | Good       | Fair          | Normal wear reported
4   | STATUS_CHANGE | In Use     | Damaged       | Screen cracked, needs repair
5   | STATUS_CHANGE | Damaged    | Under Repair  | Sent to repair shop
6   | STATUS_CHANGE | Under Repair| Available    | Repair completed
```

---

## 4. Current Features Available

### ✅ Office Users Can:
1. **View Asset Details**
   - Go to: `office/view_assets_detailed.php`
   - See all assigned units
   - View status and condition

2. **Report Issues**
   - Click "Report Issue" on any unit
   - System updates unit status
   - Custodian gets notified

3. **Track Asset Location**
   - See which units they have
   - See condition of each unit
   - Full accountability

### ✅ Custodian/Admin Can:
1. **Update Unit Status**
   - Via API: `api/asset_units.php`
   - Change status (Available, Damaged, etc.)
   - Change condition rating

2. **Schedule Maintenance**
   - Add to `asset_maintenance` table
   - Set next maintenance date
   - Track costs

3. **View Maintenance History**
   - See all past maintenance
   - Available in barcode lookup
   - Full history per asset

4. **Quick Scan Update**
   - Page: `custodian/quick_scan_update.php`
   - Scan QR code
   - Update status instantly

---

## 5. How to Use the System

### Scenario 1: Unit Gets Damaged

**Office User Reports**:
1. Goes to detailed asset view
2. Finds the damaged unit (e.g., CHAIR-005)
3. Clicks "Report Issue"
4. Describes: "Leg is broken"
5. Submits

**System Does**:
```sql
-- Update unit status
UPDATE asset_units
SET unit_status = 'Damaged',
    condition_rating = 'Poor',
    notes = 'Leg is broken - reported by office'
WHERE id = 5;

-- Log history
INSERT INTO unit_history
(unit_id, action, old_value, new_value, description, performed_by_name)
VALUES
(5, 'STATUS_CHANGE', 'In Use', 'Damaged', 'Leg is broken', 'John Doe');

-- Notify custodian
INSERT INTO notifications ...
```

**Custodian Receives**:
- Email notification
- In-app notification
- Can see damaged unit in dashboard

### Scenario 2: Send Unit for Repair

**Custodian Actions**:
```sql
-- Update unit status to Under Repair
UPDATE asset_units
SET unit_status = 'Under Repair',
    notes = 'Sent to furniture repair shop on 2025-01-12'
WHERE id = 5;

-- Record maintenance
INSERT INTO asset_maintenance
(asset_id, maintenance_type, maintenance_date, description, status, cost)
VALUES
(1, 'Repair', '2025-01-12', 'Repair broken chair leg', 'In Progress', 150.00);
```

### Scenario 3: Return from Repair

**Custodian Actions**:
```sql
-- Update unit status back to Available
UPDATE asset_units
SET unit_status = 'Available',
    condition_rating = 'Good',
    last_maintenance_date = CURDATE(),
    notes = 'Repaired - new leg installed'
WHERE id = 5;

-- Update maintenance record
UPDATE asset_maintenance
SET status = 'Completed',
    notes = 'Leg replaced successfully'
WHERE id = 123;
```

### Scenario 4: Schedule Preventive Maintenance

**Custodian Sets Up**:
```sql
-- Schedule next maintenance
INSERT INTO asset_maintenance
(asset_id, maintenance_type, maintenance_date, next_maintenance_date, description, status)
VALUES
(1, 'Inspection', '2025-06-01', '2025-12-01', 'Semi-annual inspection', 'Pending');
```

---

## 6. API Endpoints for Maintenance

### Update Unit Status:
```
POST /api/asset_units.php
{
  "action": "update_unit",
  "unit_id": 5,
  "unit_status": "Damaged",
  "condition_rating": "Poor",
  "notes": "Screen cracked"
}
```

### Get Unit Details:
```
GET /api/asset_units.php?action=get_unit&unit_id=5
```

### Get Maintenance History:
```
GET /api/barcode_lookup.php?search=CHAIR-001
Response includes:
- maintenance_history: [...]
- activity_log: [...]
```

---

## 7. Quick Reference SQL Queries

### Find All Damaged Units:
```sql
SELECT
    au.unit_code,
    au.unit_status,
    au.condition_rating,
    a.asset_name,
    o.office_name
FROM asset_units au
JOIN assets a ON au.asset_id = a.id
LEFT JOIN offices o ON au.assigned_to_office = o.id
WHERE au.unit_status = 'Damaged'
ORDER BY au.created_at DESC;
```

### Units Needing Maintenance:
```sql
SELECT
    au.unit_code,
    a.asset_name,
    au.last_maintenance_date,
    DATEDIFF(CURDATE(), au.last_maintenance_date) as days_since_maintenance
FROM asset_units au
JOIN assets a ON au.asset_id = a.id
WHERE au.last_maintenance_date IS NOT NULL
AND DATEDIFF(CURDATE(), au.last_maintenance_date) > 180
ORDER BY au.last_maintenance_date ASC;
```

### Upcoming Scheduled Maintenance:
```sql
SELECT
    am.*,
    a.asset_name
FROM asset_maintenance am
JOIN assets a ON am.asset_id = a.id
WHERE am.status = 'Pending'
AND am.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
ORDER BY am.next_maintenance_date ASC;
```

### Unit Lifecycle Report:
```sql
SELECT
    uh.unit_id,
    au.unit_code,
    uh.action,
    uh.old_value,
    uh.new_value,
    uh.description,
    uh.performed_by_name,
    uh.created_at
FROM unit_history uh
JOIN asset_units au ON uh.unit_id = au.id
WHERE au.unit_code = 'CHAIR-001'
ORDER BY uh.created_at DESC;
```

---

## 8. Reports You Can Generate

### 1. Asset Condition Report:
Shows condition of all assets by office

### 2. Maintenance Cost Report:
Total maintenance costs by asset/category

### 3. Damaged Assets Report:
All units currently damaged or under repair

### 4. Maintenance Schedule:
Upcoming maintenance tasks

### 5. Unit Lifecycle Report:
Complete history of any unit

---

## 9. Best Practices

### For Office Users:
✅ Report issues immediately when noticed
✅ Check unit condition regularly
✅ Keep notes updated
✅ Verify units when receiving

### For Custodians:
✅ Update status promptly
✅ Schedule preventive maintenance
✅ Track all maintenance costs
✅ Review damaged units weekly
✅ Monitor overdue maintenance

### For System:
✅ All changes are logged automatically
✅ Email notifications sent
✅ History preserved forever
✅ Audit trail complete

---

## 10. Summary

### What You Have:
✅ **Individual unit tracking** - Each item tracked separately
✅ **6 status types** - Available, In Use, Damaged, Missing, Under Repair, Disposed
✅ **5 condition ratings** - Excellent, Good, Fair, Poor, Non-functional
✅ **Maintenance tracking** - Schedule, cost, history
✅ **Complete audit trail** - Every change logged
✅ **Office accountability** - Know exactly what each office has
✅ **Automatic notifications** - Email alerts for issues

### What You Can Do:
✅ Track individual items from cradle to grave
✅ Monitor asset health and condition
✅ Schedule and track maintenance
✅ Generate maintenance cost reports
✅ Hold offices accountable for items
✅ Quick status updates via QR scan
✅ Complete history of every item

### System is READY to use! 🎉

---

**Last Updated**: 2025-01-12
**Status**: Fully Operational
