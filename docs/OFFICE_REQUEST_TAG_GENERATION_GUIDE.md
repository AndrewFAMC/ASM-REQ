# Office Request - Tag Generation Workflow

## Overview

When an office user requests an asset from the custodian, the workflow follows these steps:

1. **Office User** submits request → Status: `pending`
2. **Custodian** reviews and approves → Status: `custodian_review`
3. **Admin** gives final approval → Status: `approved`
4. **Custodian** generates inventory tag (with office pre-selected) → Status: `released`
5. **Office** verifies receipt on dashboard

---

## Key Feature: Pre-Selected Office for Tag Generation

When the custodian generates an inventory tag for an office request:

✅ **The office is automatically pre-selected** (from `requester_office_id`)
✅ **Custodian manually generates the tag number** (maintains control)
✅ **Custodian cannot change which office** (it's predetermined by the request)
✅ **Permanent transfer** (no return expected)

---

## Complete Workflow

### Stage 1: Office User Submits Request

**File:** [office/request_from_custodian.php](../office/request_from_custodian.php)

```php
INSERT INTO asset_requests
(requester_id, asset_id, quantity, purpose, status, campus_id,
 request_source, requester_office_id, request_date)
VALUES (?, ?, ?, ?, 'pending', ?, 'custodian', ?, NOW())
```

**Key fields:**
- `request_source = 'custodian'` - Routes to custodian approval
- `requester_office_id` - Identifies which office is requesting
- `expected_return_date = NULL` - Permanent transfer, no return

**User sees:**
- Asset browsing interface
- No return date field
- "Permanent transfer" messaging

---

### Stage 2: Custodian Reviews

**File:** [custodian/approve_requests.php](../custodian/approve_requests.php)

**API:** `GET /api/requests.php?action=get_pending_requests`

**Custodian sees:**
- All pending requests with `request_source='custodian'`
- Office name displayed (from `requester_office_name`)
- Approve/Reject buttons

**On Approve:**
```php
UPDATE asset_requests
SET status = 'custodian_review',
    custodian_reviewed_by = ?,
    custodian_reviewed_at = NOW()
WHERE id = ?
```

---

### Stage 3: Admin Final Approval

**File:** [admin/approve_requests.php](../admin/approve_requests.php) (or similar)

**API:** `POST /api/requests.php?action=approve_as_admin`

**Admin sees:**
- Requests with status `custodian_review`
- Office name and request details
- Approve/Reject buttons

**On Approve:**
```php
UPDATE asset_requests
SET status = 'approved',
    final_approved_by = ?,
    final_approved_at = NOW()
WHERE id = ?
```

**Important:** Status becomes `'approved'`, **NOT** `'released'` yet!

---

### Stage 4: Custodian Generates Tag ⭐ NEW

**File:** [custodian/release_assets.php](../custodian/release_assets.php)

**What Changed:**
1. Office transfer requests display differently with purple badges
2. Show "Office Transfer" indicator
3. Button says "Generate Tag" instead of "Release Asset"
4. Clicking opens tag generation modal with office pre-selected

#### UI Changes

**Request Card Display:**
```javascript
const isOfficeTransfer = request.requester_office_id != null;

// Shows purple badge: "Office Transfer"
// Shows requester office name in purple
// Info banner: "Permanent Transfer: This asset will be assigned to [Office Name]"
// Button: "Generate Tag" (instead of "Release Asset")
```

**Tag Generation Modal:**
- **Asset name:** (read-only)
- **Office:** Pre-selected, **disabled field** showing office name in purple
- **Tag number:** Auto-generated random tag (e.g., MIS-111125-6730), editable
  - Format: `{OFFICE_PREFIX}-{MMDDYY}-{RANDOM4DIGITS}`
  - Example: `MIS-111125-6730` = MIS Office, November 11, 2025, random 4-digit number
  - Office prefix extracted from office name (e.g., "MIS Office" → "MIS")
  - Ensures uniqueness by checking existing tags
- **Quantity:** From request (read-only)
- **Remarks:** Optional, pre-filled with "Via approved office request #123"

**Tag Number Generation API:**

```javascript
GET /api/requests.php?action=generate_random_tag_number&request_id=123

Response:
{
    success: true,
    tagNumber: 'MIS-111125-6730'
}
```

**Submit Tag API Call:**

```javascript
POST /api/requests.php
{
    action: 'generate_tag_for_office_request',
    request_id: 123,
    tag_number: 'MIS-111125-6730',
    remarks: 'Via approved office request #123',
    csrf_token: '...'
}
```

#### Backend Processing

**API Handler:** `case 'generate_tag_for_office_request'`

**Steps:**

1. **Validate request:**
   - Status must be `'approved'`
   - `requester_office_id` must exist (office request)
   - Tag number must be unique

2. **Create inventory tag:**
```php
INSERT INTO inventory_tags
(tag_number, asset_id, office_id, status, assigned_by_custodian_id,
 inventory_date, remarks, quantity, unit_price, total_value)
VALUES (?, ?, ?, 'Pending Verification', ?, NOW(), ?, ?, 0, 0)
```

3. **Update asset:**
```php
UPDATE assets
SET quantity = quantity - ?,
    assigned_to = ?,
    status = CASE
        WHEN quantity - ? <= 0 THEN 'In Use'
        ELSE status
    END
WHERE id = ?
```

4. **Update request status:**
```php
UPDATE asset_requests
SET status = 'released',
    released_date = NOW(),
    released_by = ?
WHERE id = ?
```

5. **Log activity:**
```php
logActivity($pdo, $assetId, 'ASSIGNED_TO_OFFICE',
    "Asset transferred to office '{$officeName}' via approved request #{$requestId}. Tag: {$tagNumber}");
```

6. **Notify office user:**
```php
createNotification(
    $pdo,
    $requesterId,
    'request_approved',
    'Asset Transfer Completed',
    "Your request has been approved and the asset has been transferred to your office.
     Inventory tag: {$tagNumber}. Please verify receipt on your dashboard."
);
```

**Response:**
```json
{
    "success": true,
    "message": "Inventory tag generated and asset transferred to office",
    "tag_id": 456,
    "tag_number": "HCC-2025-0001"
}
```

---

### Stage 5: Office Verification

**File:** [office/office_dashboard.php](../office/office_dashboard.php)

**Office user sees:**
- New inventory tag in "Pending Verification" status
- Can verify receipt
- Request status shows "Transferred to Office" in My Requests page

---

## Key Differences: Office Transfer vs Employee Borrowing

| Aspect | Office Transfer | Employee Borrowing |
|--------|----------------|-------------------|
| **Request Type** | Permanent | Temporary |
| **Return Expected** | ❌ No | ✅ Yes |
| **On Approval** | Custodian generates tag | Custodian releases directly |
| **Tag Generation** | Manual, office pre-selected | N/A (no tag) |
| **Final Status** | `'released'` = Transferred | `'released'` = In use |
| **Borrowing Record** | ❌ No (uses inventory tag) | ✅ Yes |
| **Office Selection** | Pre-determined by request | N/A |

---

## Files Modified

### 1. custodian/release_assets.php

**Changes:**
- Load only custodian requests: `?request_source=custodian`
- Detect office transfers: `request.requester_office_id != null`
- Show purple badges for office transfers
- Different button: "Generate Tag" vs "Release Asset"
- New method: `generateTagForOffice(requestId)`
- Tag generation modal with pre-selected office

**Lines Modified:**
- Line 307: Added `&request_source=custodian` to API call
- Lines 368-456: Updated `renderRequestCard()` to handle office transfers
- Lines 587-698: Added `generateTagForOffice()` method
- Lines 700-720: Added `generateTagNumber()` helper

---

### 2. api/requests.php

**Changes:**
- Added `get_last_tag_number` endpoint
- Added `generate_tag_for_office_request` endpoint

**New Cases:**

**Case: `get_last_tag_number`** (Lines 1201-1223)
- Returns last tag number for auto-suggestion
- Format: `HCC-{YEAR}-{NUMBER}`

**Case: `generate_tag_for_office_request`** (Lines 1225-1369)
- Validates request is approved and is office transfer
- Creates inventory tag with office pre-assigned
- Updates asset quantity and assignment
- Changes request status to `'released'`
- Logs activity and sends notification

---

## Testing the Complete Flow

### Test Scenario:

1. **Login as office user** (e.g., MIS Office department head)

2. **Submit request:**
   - Navigate to "Request from Custodian"
   - Select an asset (e.g., "Projector")
   - Quantity: 2
   - Purpose: "For conference room setup"
   - Submit

3. **Verify in database:**
```sql
SELECT * FROM asset_requests
WHERE id = [request_id];

-- Should show:
-- status = 'pending'
-- request_source = 'custodian'
-- requester_office_id = [office_id]
-- expected_return_date = NULL
```

4. **Login as custodian:**
   - Navigate to "Approve Requests"
   - Should see request with office name "(MIS Office)"
   - Click "Approve"

5. **Verify status changed:**
```sql
-- status should now be 'custodian_review'
```

6. **Login as admin:**
   - Navigate to admin approval page
   - Should see request pending final approval
   - Click "Approve"

7. **Verify status changed:**
```sql
-- status should now be 'approved'
```

8. **Login as custodian again:**
   - Navigate to "Release Assets"
   - Should see request with purple "Office Transfer" badge
   - Shows: "(MIS Office)" in purple
   - Button says "Generate Tag"
   - Click "Generate Tag"

9. **In tag modal:**
   - Asset name: "Projector" (disabled)
   - Office: "MIS Office" (disabled, purple, pre-selected)
   - Tag number: "HCC-2025-0001" (editable)
   - Quantity: 2 (disabled)
   - Remarks: "Via approved office request #123" (editable)
   - Click "Generate Tag & Transfer"

10. **Verify in database:**
```sql
-- Check inventory_tags
SELECT * FROM inventory_tags WHERE tag_number = 'HCC-2025-0001';
-- Should show:
-- office_id = [MIS office ID]
-- status = 'Pending Verification'
-- assigned_by_custodian_id = [custodian ID]

-- Check asset
SELECT * FROM assets WHERE id = [asset_id];
-- Should show:
-- quantity = (original - 2)
-- assigned_to = [MIS office ID]

-- Check request
SELECT * FROM asset_requests WHERE id = [request_id];
-- Should show:
-- status = 'released'
-- released_date = NOW()
-- released_by = [custodian ID]
```

11. **Login as office user:**
    - Navigate to "My Requests"
    - Should show request with "Transferred to Office" badge
    - Progress tracker at 100% ("Transferred")
    - Navigate to office dashboard
    - Should see new tag in "Pending Verification"

---

## Benefits of This Approach

1. **Control:** Custodian manually generates tags (maintains oversight)
2. **Automation:** Office is pre-selected (reduces errors)
3. **Consistency:** Uses same inventory tag system as manual assignments
4. **Transparency:** Full audit trail from request to transfer
5. **Verification:** Office still verifies receipt (quality control)
6. **Clear Intent:** Purple UI clearly distinguishes office transfers
7. **No Returns:** Permanent transfer concept is clear throughout

---

## Troubleshooting

### Issue: Office transfer requests not showing in release_assets.php

**Check:**
1. ✅ Request status is `'approved'`
2. ✅ Request has `request_source='custodian'`
3. ✅ API URL includes `&request_source=custodian`

### Issue: Office field not pre-selected in tag modal

**Check:**
1. ✅ Request has `requester_office_id` set
2. ✅ API response includes `requester_office_name`
3. ✅ Modal shows office in disabled field

### Issue: "This is not an office transfer request" error

**Check:**
1. ✅ Request has `requester_office_id IS NOT NULL`
2. ✅ Backend query joins offices table

---

## Next Enhancements

**Optional improvements:**

1. **Bulk tag generation** - Generate multiple tags at once for large quantities
2. **Tag printing** - Generate printable tag labels
3. **Transfer history** - Show office's transfer request history
4. **Transfer analytics** - Dashboard showing transfer statistics

---

**Implementation Date:** November 11, 2025
**Status:** ✅ Complete
**Files Modified:** 2 (release_assets.php, requests.php)
**API Endpoints Added:** 2 (get_last_tag_number, generate_tag_for_office_request)
