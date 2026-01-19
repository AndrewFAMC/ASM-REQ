# Individual Asset Tracking System - Implementation Guide

## 📋 Overview

The Individual Asset Tracking System allows detailed tracking of each physical unit of an asset. Instead of tracking "30 chairs" as one bulk item, you can now track each chair individually (CHAIR-001, CHAIR-002, etc.) with separate serial numbers, statuses, and condition ratings.

**Created:** January 12, 2025
**Version:** 1.0
**Database Migration:** `add_individual_tracking_system.sql`

---

## 🎯 Key Features

### For Custodians:
- ✅ Enable individual tracking for any asset
- ✅ Auto-generate unit records with unique serial numbers
- ✅ Select specific units when assigning to offices
- ✅ Track each unit's status and condition
- ✅ View complete unit history

### For Office Users:
- ✅ View detailed list of all units assigned to your office
- ✅ See real-time status and condition of each unit
- ✅ Report issues with specific units
- ✅ Track which units are damaged, missing, or under repair

---

## 📊 Database Structure

### New Tables Created

#### 1. `asset_units`
Stores individual unit records for each asset.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| asset_id | INT | Parent asset reference |
| unit_serial_number | VARCHAR(100) | Unique serial for this unit |
| unit_code | VARCHAR(50) | Short code (e.g., CHAIR-001) |
| unit_status | ENUM | Available, In Use, Damaged, Missing, Under Repair, Disposed |
| condition_rating | ENUM | Excellent, Good, Fair, Poor, Non-functional |
| assigned_to_user_id | INT | Specific person assignment (optional) |
| location_notes | TEXT | Specific location within office |
| notes | TEXT | Additional notes |
| created_at | TIMESTAMP | Creation date |

#### 2. `tag_units`
Links inventory tags to specific units.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| tag_id | INT | Inventory tag reference |
| unit_id | INT | Asset unit reference |
| assigned_at | TIMESTAMP | Assignment date |
| removed_at | TIMESTAMP | Removal date (if applicable) |
| is_active | BOOLEAN | Currently active assignment |

#### 3. `unit_history`
Tracks all changes to units.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| unit_id | INT | Unit reference |
| action | VARCHAR(50) | CREATED, ASSIGNED, STATUS_CHANGED, etc. |
| old_value | VARCHAR(255) | Previous value |
| new_value | VARCHAR(255) | New value |
| description | TEXT | Description of change |
| performed_by | INT | User who made the change |
| created_at | TIMESTAMP | Change timestamp |

### Modified Table

#### `assets`
Added two new columns:

| Column | Type | Description |
|--------|------|-------------|
| track_individually | BOOLEAN | Enable individual unit tracking |
| auto_create_units | BOOLEAN | Auto-create units when quantity added |

---

## 🚀 How It Works

### Scenario: Custodian Has 20 Chairs

#### Step 1: Enable Individual Tracking

```sql
UPDATE assets
SET track_individually = TRUE
WHERE id = 206;
```

Or use the UI button in the custodian dashboard when generating a tag.

#### Step 2: Create Unit Records

**Option A: Auto-create via stored procedure**
```sql
CALL sp_create_units_for_asset(206, 20, 1);
```

**Option B: Use the API**
```javascript
POST /api/asset_units.php
{
    action: 'create_units',
    asset_id: 206,
    quantity: 20
}
```

This creates 20 records:
- CHAIR-001 (serial: HCC2501081990-001)
- CHAIR-002 (serial: HCC2501081990-002)
- ...
- CHAIR-020 (serial: HCC2501081990-020)

#### Step 3: Assign 10 Chairs to an Office

When the custodian generates an inventory tag:

1. Opens "Generate Tag" modal
2. Selects office (e.g., Dean's Office)
3. Sets quantity = 10
4. **System shows unit selection interface**
5. Custodian selects specific units (CHAIR-001 through CHAIR-010)
6. Generates tag

**What happens in the database:**

```sql
-- Create inventory tag
INSERT INTO inventory_tags (...) VALUES (...);
-- Returns tag_id = 100

-- Link selected units to tag
INSERT INTO tag_units (tag_id, unit_id, is_active)
VALUES
    (100, 1, TRUE),  -- CHAIR-001
    (100, 2, TRUE),  -- CHAIR-002
    ...
    (100, 10, TRUE); -- CHAIR-010

-- Update unit statuses
UPDATE asset_units
SET unit_status = 'In Use'
WHERE id IN (1, 2, 3, ..., 10);

-- Log each assignment
INSERT INTO unit_history (unit_id, action, description...)
VALUES (...);
```

#### Step 4: Office User Views Assets

Office user navigates to **view_assets_detailed.php** and sees:

```
Assets in Dean's Office:
┌────────────────────────────────────────────┐
│ Chair (10 units)                           │
│ Tag: MIS-112025-1234                       │
│                                            │
│ Units assigned:                            │
│ • CHAIR-001 - Good Condition              │
│ • CHAIR-002 - Good Condition              │
│ • CHAIR-003 - Damaged (reported)          │
│ • CHAIR-004 - Good Condition              │
│ • CHAIR-005 - Good Condition              │
│ • CHAIR-006 - Good Condition              │
│ • CHAIR-007 - Good Condition              │
│ • CHAIR-008 - Good Condition              │
│ • CHAIR-009 - Good Condition              │
│ • CHAIR-010 - Good Condition              │
│                                            │
│ [Report Issue] [View Details] [Print Tag] │
└────────────────────────────────────────────┘
```

---

## 🔧 API Endpoints

### `/api/asset_units.php`

#### Get Units for Asset
```
GET /api/asset_units.php?action=get_units_for_asset&asset_id=206
```

Response:
```json
{
    "success": true,
    "asset": {
        "id": 206,
        "asset_name": "Chair",
        "quantity": 10,
        "track_individually": true
    },
    "units": [
        {
            "id": 1,
            "unit_code": "CHAIR-001",
            "unit_serial_number": "HCC2501081990-001",
            "unit_status": "In Use",
            "condition_rating": "Good",
            "tag_number": "MIS-112025-1234",
            "office_name": "Dean's Office"
        },
        ...
    ],
    "total_units": 20
}
```

#### Get Available Units (not assigned)
```
GET /api/asset_units.php?action=get_available_units&asset_id=206
```

#### Create Units
```
POST /api/asset_units.php
{
    "action": "create_units",
    "asset_id": 206,
    "quantity": 20
}
```

#### Update Unit Status
```
POST /api/asset_units.php
{
    "action": "update_unit",
    "unit_id": 1,
    "unit_status": "Damaged",
    "condition_rating": "Poor",
    "notes": "Broken leg, needs repair"
}
```

#### Get Office Units (for detailed view)
```
GET /api/asset_units.php?action=get_office_units&office_id=5
```

#### Get Unit History
```
GET /api/asset_units.php?action=get_unit_history&unit_id=1
```

#### Enable Individual Tracking
```
POST /api/asset_units.php
{
    "action": "enable_tracking",
    "asset_id": 206,
    "auto_create": true
}
```

---

## 💻 User Interface

### Custodian Dashboard

#### Before (Bulk Tracking):
```
Asset: Chair
Quantity: 20
Serial Number: HCC2501081990

[Assign to Office] → Select quantity → Done
```

#### After (Individual Tracking):
```
Asset: Chair (Individual Tracking Enabled)
Quantity: 20 units available

[Assign to Office] →
    Select Office: Dean's Office
    Quantity: 10

    ┌─ Select Specific Units ────────┐
    │ ☑ CHAIR-001  ☑ CHAIR-002      │
    │ ☑ CHAIR-003  ☑ CHAIR-004      │
    │ ☑ CHAIR-005  ☑ CHAIR-006      │
    │ ☑ CHAIR-007  ☑ CHAIR-008      │
    │ ☑ CHAIR-009  ☑ CHAIR-010      │
    │ ☐ CHAIR-011  ☐ CHAIR-012      │
    └────────────────────────────────┘

    [Select First 10] [Clear Selection]

    Selected: 10 / 10 units ✓
```

### Office Dashboard

**New Page:** `/office/view_assets_detailed.php`

Features:
- 📊 Stats dashboard (Total units, Good condition, Damaged, Missing)
- 📦 Grouped by inventory tags
- 🔍 Detailed unit cards with status and condition
- ⚠️ Report issue functionality
- 📄 Print tags

---

## 📝 Stored Procedures

### `sp_create_units_for_asset`

Automatically creates unit records for an asset.

```sql
CALL sp_create_units_for_asset(asset_id, quantity, created_by_user_id);
```

**Example:**
```sql
CALL sp_create_units_for_asset(206, 20, 1);
```

Creates:
- CHAIR-001 through CHAIR-020
- Serial numbers: HCC2501081990-001 through HCC2501081990-020
- All with status 'Available' and condition 'Good'
- Logs creation in unit_history

---

## 📈 Database Views

### `v_unit_details`
Complete view of all units with asset and tag information.

```sql
SELECT * FROM v_unit_details WHERE office_name = 'Dean\'s Office';
```

### `v_office_units`
Summary of units assigned to each office.

```sql
SELECT * FROM v_office_units WHERE office_id = 5;
```

Returns counts by status and condition rating.

---

## 🔄 Workflow Examples

### Workflow 1: Adding New Equipment with Individual Tracking

```
1. Custodian receives 10 new laptops
2. Create asset: "Dell Laptop" (quantity = 10)
3. Enable individual tracking
4. System auto-creates 10 units:
   - DELLL-001 to DELLL-010
5. Each laptop gets unique serial number
6. Assign 3 laptops to IT Department
   - Select specific units: DELLL-001, DELLL-002, DELLL-003
7. Generate tag and assign
8. IT Department sees 3 laptops with individual codes
```

### Workflow 2: Reporting Damaged Unit

```
1. Office user views assets in detailed view
2. Clicks on unit CHAIR-003
3. Sees modal with unit details
4. Clicks "Report Issue"
5. Selects issue type: "Damaged"
6. Describes: "Broken leg, unsafe to use"
7. Submits report

Database actions:
- UPDATE asset_units SET unit_status = 'Damaged' WHERE id = 3
- INSERT INTO unit_history (unit_id, action, description...)
- Custodian receives notification
```

### Workflow 3: Transferring Units Between Offices

```
1. Dean's Office no longer needs CHAIR-005
2. Custodian removes unit from current tag
3. Adds unit to different tag for Library
4. Database:
   - UPDATE tag_units SET is_active = FALSE, removed_at = NOW() WHERE unit_id = 5
   - INSERT INTO tag_units (tag_id = new_tag_id, unit_id = 5, is_active = TRUE)
   - INSERT INTO unit_history (action = 'TRANSFERRED'...)
5. Both offices see updated unit assignments
```

---

## 🛡️ Data Integrity

### Constraints

1. **Unique Serial Numbers:** Each `unit_serial_number` must be unique across all units
2. **Active Tag Assignment:** A unit can only have ONE active tag assignment at a time
3. **Cascade Deletes:** Deleting an asset deletes all its units and related records
4. **Foreign Keys:** Enforced relationships between tables

### Validation Rules

- Cannot assign more units than available
- Cannot remove a unit that doesn't exist
- Unit serial numbers auto-generated if not provided
- Status changes are logged with timestamps
- User actions are tracked with user IDs

---

## 🧪 Testing

### Test Data Creation

```sql
-- Enable tracking for an existing asset
UPDATE assets SET track_individually = TRUE WHERE id = 206;

-- Create 20 units
CALL sp_create_units_for_asset(206, 20, 1);

-- Verify units created
SELECT * FROM asset_units WHERE asset_id = 206;

-- Assign some units to a tag
INSERT INTO tag_units (tag_id, unit_id, is_active)
SELECT 100, id, TRUE
FROM asset_units
WHERE asset_id = 206
LIMIT 10;

-- Check office view
SELECT * FROM v_office_units WHERE tag_id = 100;
```

---

## 📚 File Structure

### New Files Created:

```
database/migrations/
  └── add_individual_tracking_system.sql         # Database schema

api/
  └── asset_units.php                            # API endpoints

office/
  └── view_assets_detailed.php                   # Office detailed view

custodian/
  └── individual_tracking_enhancement.js         # UI enhancement

docs/
  └── INDIVIDUAL_ASSET_TRACKING_GUIDE.md         # This file
```

### Modified Files:

```
custodian/dashboard.php                          # Added unit selection
  - Line 211-288: Enhanced assign_and_generate_tag action
  - Line 1798: Added enhancement script

assets table                                     # Added tracking columns
  - track_individually column
  - auto_create_units column
```

---

## ⚙️ Configuration Options

### Enable Automatic Unit Creation

```sql
UPDATE assets
SET auto_create_units = TRUE
WHERE category_id IN (
    SELECT id FROM categories
    WHERE category_name IN ('Electronics', 'Furniture', 'Vehicles')
);
```

### Bulk Enable Individual Tracking

```sql
-- Enable for all high-value assets
UPDATE assets
SET track_individually = TRUE
WHERE value > 5000;

-- Enable for specific categories
UPDATE assets
SET track_individually = TRUE
WHERE category_id IN (1, 2, 5); -- Electronics, Vehicles, Lab Equipment
```

---

## 🎓 Best Practices

### When to Use Individual Tracking

✅ **Use For:**
- Electronics (computers, printers, projectors)
- Vehicles
- High-value equipment (>₱5,000)
- Items requiring warranty tracking
- Assets with individual maintenance schedules
- Equipment assigned to specific individuals

❌ **Don't Use For:**
- Office supplies (pens, papers)
- Low-value bulk items (<₱100 each)
- Consumables
- Items without unique identifiers

### Naming Conventions

**Unit Codes:**
- Use 3-5 letter prefix from asset name
- Pad with zeros for consistent sorting
- Examples:
  - Chairs: CHAIR-001, CHAIR-002
  - Laptops: LAPTO-001, LAPTO-002
  - Printers: PRINT-001, PRINT-002

**Serial Numbers:**
- Format: `{AssetSerial}-{UnitNumber}`
- Example: HCC2501081990-001
- Maintains relationship to parent asset
- Ensures global uniqueness

---

## 🔍 Troubleshooting

### Issue: "No units available"

**Cause:** All units are already assigned
**Solution:** Either return some units or create more using `sp_create_units_for_asset`

### Issue: Unit selection disabled

**Cause:** Individual tracking not enabled for asset
**Solution:** Enable tracking via UI or SQL:
```sql
UPDATE assets SET track_individually = TRUE WHERE id = ?;
```

### Issue: Duplicate serial numbers

**Cause:** Manual serial number entry conflicts
**Solution:** Serial numbers are auto-generated. Let the system create them.

### Issue: Unit count mismatch

**Cause:** Units created/deleted without updating asset quantity
**Solution:** Run consistency check:
```sql
SELECT
    a.id,
    a.asset_name,
    a.quantity AS asset_qty,
    COUNT(u.id) AS unit_count,
    a.quantity - COUNT(u.id) AS difference
FROM assets a
LEFT JOIN asset_units u ON a.asset_id = u.id
WHERE a.track_individually = TRUE
GROUP BY a.id
HAVING difference != 0;
```

---

## 📞 Support

For questions or issues:
1. Check this documentation
2. Review database schema in migration file
3. Check API endpoint responses
4. Review browser console for JavaScript errors

---

## 🚀 Future Enhancements

Potential improvements:
- [ ] Barcode scanning for individual units
- [ ] QR codes for each unit
- [ ] Mobile app for unit status updates
- [ ] Bulk unit status updates
- [ ] Unit maintenance scheduling
- [ ] Unit depreciation tracking
- [ ] Transfer workflows between campuses
- [ ] Unit reservation system

---

**Document Version:** 1.0
**Last Updated:** January 12, 2025
**Status:** ✅ Production Ready
