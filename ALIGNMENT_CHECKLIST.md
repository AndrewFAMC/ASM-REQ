# Individual Tracking System - File Alignment Checklist

## 📋 Files That Need Modification

### ✅ ALREADY COMPLETED

#### 1. Database Layer
- ✅ `database/migrations/add_individual_tracking_system.sql` - Tables created
- ✅ Database tables: `asset_units`, `tag_units`, `unit_history`
- ✅ Stored procedure: `sp_create_units_for_asset`
- ✅ Views: `v_unit_details`, `v_office_units`

#### 2. API Layer
- ✅ `api/asset_units.php` - Complete unit management API

#### 3. Custodian Interface
- ✅ `custodian/dashboard.php` - Auto-create units on add asset
- ✅ `custodian/dashboard.php` - Unit selection when generating tags
- ✅ `custodian/individual_tracking_enhancement.js` - UI enhancements

#### 4. Office Interface
- ✅ `office/view_assets_detailed.php` - Detailed unit view

---

## 🔧 FILES THAT NEED MODIFICATION

### Priority 1: Critical (Asset Creation)

#### 1. Admin Asset Creation
**File:** `admin/actions/asset_actions.php`
**Function:** `createAsset()` (line 422-507)
**Change Needed:**
```php
// After creating asset, auto-create units if quantity > 1
if ($quantity > 1) {
    // Enable individual tracking
    $stmt = $pdo->prepare("UPDATE assets SET track_individually = TRUE WHERE id = ?");
    $stmt->execute([$assetId]);

    // Create units
    $stmt = $pdo->prepare("CALL sp_create_units_for_asset(?, ?, ?)");
    $stmt->execute([$assetId, $quantity, $createdBy]);
}
```

#### 2. Custodian Actions (Legacy)
**File:** `custodian/actions/custodian_actions.php`
**Function:** Asset creation handler
**Change Needed:**
Same logic as admin - auto-create units for quantity > 1

#### 3. Old Custodian Dashboard
**File:** `custodian_dashboard.php`
**Change Needed:**
Same updates as `custodian/dashboard.php` if still in use

---

### Priority 2: Important (Display & Reporting)

#### 4. Asset Display/Listing Pages
**Files:**
- `admin/admin_dashboard.php` - Show unit count
- `custodian_dashboard.php` - Show unit count
- `office/office_dashboard.php` - Show unit info

**Change Needed:**
Add unit count display:
```php
// In query, add:
LEFT JOIN (
    SELECT asset_id, COUNT(*) as unit_count
    FROM asset_units
    GROUP BY asset_id
) units ON a.id = units.asset_id

// In display:
if ($asset['track_individually']) {
    echo "({$asset['unit_count']} units tracked individually)";
}
```

#### 5. Asset Details/View Pages
**Files:**
- `admin/view_asset.php` (if exists)
- `custodian/view_asset.php` (if exists)

**Change Needed:**
Show list of units when viewing asset details

#### 6. Barcode Lookup
**File:** `api/barcode_lookup.php` (line 26-89)
**Change Needed:**
Include unit information in lookup results:
```php
// Add to query:
LEFT JOIN asset_units u ON a.id = u.asset_id AND u.unit_serial_number LIKE ?

// Return unit info if available
```

---

### Priority 3: Optional (Enhanced Features)

#### 7. Reports
**File:** `api/get_report_data.php`
**Change Needed:**
Add unit-level reporting option

#### 8. Export Functions
**Files:**
- `api/export_depreciation_summary.php`
- `api/export_approval_summary.php`
- `api/export_borrowing_history.php`

**Change Needed:**
Include unit codes in exports

#### 9. Missing Assets
**File:** `api/missing_assets.php`
**Change Needed:**
Allow reporting specific units as missing

#### 10. Asset Transfer
**Files:**
- Any transfer/movement tracking files

**Change Needed:**
Track unit movements, not just asset movements

---

## 📝 DETAILED MODIFICATIONS NEEDED

### File 1: admin/actions/asset_actions.php

**Location:** Line 487-507
**Current Code:**
```php
$assetId = $pdo->lastInsertId();

// Log activity
logActivity($pdo, $assetId, 'CREATED', "Asset created: " . $data['asset_name']);

$pdo->commit();
return ['id' => $assetId, 'barcode' => $data['serial_number']];
```

**Updated Code:**
```php
$assetId = $pdo->lastInsertId();
$quantity = $data['quantity'] ?? 1;

// Log activity
logActivity($pdo, $assetId, 'CREATED', "Asset created: " . $data['asset_name']);

// AUTO-CREATE INDIVIDUAL UNITS for quantity > 1
if ($quantity > 1) {
    // Enable individual tracking
    $trackStmt = $pdo->prepare("UPDATE assets SET track_individually = TRUE WHERE id = ?");
    $trackStmt->execute([$assetId]);

    // Create units using stored procedure
    $unitsStmt = $pdo->prepare("CALL sp_create_units_for_asset(?, ?, ?)");
    $unitsStmt->execute([$assetId, $quantity, $createdBy]);

    logActivity($pdo, $assetId, 'UNITS_CREATED', "Created {$quantity} individual units with unique serial numbers");
}

$pdo->commit();
return [
    'id' => $assetId,
    'barcode' => $data['serial_number'],
    'units_created' => $quantity > 1 ? $quantity : 0
];
```

---

### File 2: custodian/actions/custodian_actions.php

**Location:** After asset insertion (around line 32-60)
**Add Same Logic:**
```php
// After asset is created
$assetId = $pdo->lastInsertId();
$quantity = $_POST['quantity'] ?? 1;

// AUTO-CREATE UNITS if quantity > 1
if ($quantity > 1) {
    $pdo->prepare("UPDATE assets SET track_individually = TRUE WHERE id = ?")->execute([$assetId]);
    $pdo->prepare("CALL sp_create_units_for_asset(?, ?, ?)")->execute([$assetId, $quantity, $userId]);
}
```

---

### File 3: Asset Display Views

**Location:** Any file displaying asset lists
**Add to Display:**
```php
<td>
    <?= htmlspecialchars($asset['asset_name']) ?>
    <?php if ($asset['track_individually']): ?>
        <span class="text-xs text-blue-600">
            <i class="fas fa-layer-group"></i>
            <?= $asset['unit_count'] ?? $asset['quantity'] ?> units
        </span>
    <?php endif; ?>
</td>
```

---

### File 4: api/barcode_lookup.php

**Location:** Line 44-69
**Current Query:**
```php
SELECT a.*, c.category_name, cam.campus_name, ...
FROM assets a
WHERE a.campus_id = ? AND (a.barcode LIKE ? OR a.serial_number LIKE ? ...)
```

**Enhanced Query:**
```php
SELECT
    a.*,
    c.category_name,
    cam.campus_name,
    u.id as unit_id,
    u.unit_code,
    u.unit_serial_number,
    u.unit_status,
    u.condition_rating
FROM assets a
LEFT JOIN categories c ON a.category_id = c.id
LEFT JOIN campuses cam ON a.campus_id = cam.id
LEFT JOIN asset_units u ON a.id = u.asset_id
    AND (u.unit_serial_number LIKE ? OR u.unit_code LIKE ?)
WHERE a.campus_id = ?
AND (
    a.barcode LIKE ? OR
    a.serial_number LIKE ? OR
    LOWER(a.asset_name) LIKE ? OR
    u.unit_serial_number LIKE ? OR
    u.unit_code LIKE ?
)
LIMIT 1
```

**Add to Response:**
```php
'unit_info' => [
    'unit_id' => $asset['unit_id'],
    'unit_code' => $asset['unit_code'],
    'unit_serial' => $asset['unit_serial_number'],
    'unit_status' => $asset['unit_status'],
    'condition' => $asset['condition_rating']
]
```

---

## 🔍 VERIFICATION CHECKLIST

After modifications, verify:

### Database
- [ ] `sp_create_units_for_asset` procedure exists and works
- [ ] `asset_units` table has records
- [ ] `track_individually` column exists in assets table

### Asset Creation
- [ ] Admin creates asset qty=30 → 30 units auto-created ✓
- [ ] Custodian creates asset qty=30 → 30 units auto-created ✓
- [ ] Success message shows unit count

### Asset Display
- [ ] Asset lists show unit count for tracked items
- [ ] Unit count is accurate
- [ ] Icons/badges display correctly

### Tag Generation
- [ ] Custodian can select specific units when assigning
- [ ] Selected units link to tag correctly
- [ ] Unit status updates to "In Use"

### Office View
- [ ] Office users see detailed unit list
- [ ] Can click on individual units
- [ ] Can report issues on specific units

### Barcode/Search
- [ ] Can search by unit serial number
- [ ] Can search by unit code
- [ ] Unit info appears in search results

### Reports
- [ ] Reports show unit-level data (if implemented)
- [ ] Export includes unit information (if implemented)

---

## 📊 MIGRATION PLAN

### Phase 1: Core Functionality (DONE)
- ✅ Database tables
- ✅ API endpoints
- ✅ Custodian creation UI
- ✅ Office view UI

### Phase 2: Admin & Legacy Support (TODO)
- [ ] Admin asset creation
- [ ] Legacy custodian dashboard
- [ ] Old action handlers

### Phase 3: Display Enhancement (TODO)
- [ ] Asset list views
- [ ] Detail views
- [ ] Dashboard cards

### Phase 4: Search & Lookup (TODO)
- [ ] Barcode lookup
- [ ] Asset search
- [ ] Quick scan

### Phase 5: Reporting (OPTIONAL)
- [ ] Unit-level reports
- [ ] Export enhancements
- [ ] Analytics

---

## 🛠️ QUICK FIX SCRIPT

For batch updating existing assets to have units:

```sql
-- Enable tracking for all assets with quantity > 1
UPDATE assets
SET track_individually = TRUE
WHERE quantity > 1
AND track_individually = FALSE;

-- Create units for existing assets (run carefully!)
-- Example for a specific asset:
CALL sp_create_units_for_asset(206, 30, 1);

-- Or use a cursor to process all:
-- (Create a stored procedure for this)
```

---

## 📞 TESTING PROCEDURE

### Test Case 1: New Asset Creation
```
1. Admin/Custodian creates asset:
   - Name: "Test Chair"
   - Quantity: 25
2. Verify:
   ✓ Asset created with ID
   ✓ 25 units in asset_units table
   ✓ Units named TEST-001 through TEST-025
   ✓ Success message shows "25 units created"
```

### Test Case 2: Tag Generation
```
1. Custodian assigns 10 units to office
2. Verify:
   ✓ Unit selection UI appears
   ✓ 25 units available
   ✓ Select 10 units
   ✓ Tag created
   ✓ 10 units linked to tag
   ✓ Unit status = "In Use"
```

### Test Case 3: Office View
```
1. Office user logs in
2. Navigate to detailed view
3. Verify:
   ✓ See 10 units assigned
   ✓ Each unit shows code and status
   ✓ Can click unit for details
   ✓ Can report issue
```

---

## 📁 FILE SUMMARY

### Files to Modify: 10 files

**Critical (Must Do):**
1. ✅ custodian/dashboard.php (DONE)
2. ⏳ admin/actions/asset_actions.php
3. ⏳ custodian/actions/custodian_actions.php

**Important (Should Do):**
4. ⏳ admin/admin_dashboard.php
5. ⏳ custodian_dashboard.php (legacy)
6. ⏳ office/office_dashboard.php
7. ⏳ api/barcode_lookup.php

**Optional (Nice to Have):**
8. ⏳ api/get_report_data.php
9. ⏳ api/export_*.php files
10. ⏳ api/missing_assets.php

### Estimated Time:
- Critical files: 30-45 minutes
- Important files: 1-2 hours
- Optional files: 2-3 hours
- **Total: 3-6 hours for complete alignment**

---

**Status:** Phase 1 Complete, Phase 2 Pending
**Last Updated:** January 12, 2025
