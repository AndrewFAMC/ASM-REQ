# Office Asset Request - Complete Workflow Implementation

## Overview

When an office user requests an asset from the custodian, it's a **permanent transfer** (not a loan). The asset should be assigned to the office via an inventory tag, just like when a custodian manually assigns assets to offices.

---

## ✅ Completed Implementation

### 1. Request Creation (office/request_from_custodian.php)
- ✅ Office users can view available central inventory assets
- ✅ Submit request with quantity and purpose
- ✅ **NO return date** required (permanent transfer)
- ✅ Request stored with `request_source='custodian'` and `requester_office_id` set
- ✅ Status starts as `'pending'`

### 2. My Requests Page (office/my_requests.php)
- ✅ Visual progress tracker with 4 stages:
  1. Submitted
  2. Custodian Review
  3. Admin Approval
  4. **Transferred** (not "Released")
- ✅ Status badges show "Transferred to Office" instead of "In Use"
- ✅ No return date displayed
- ✅ No overdue warnings

### 3. Database Schema
- ✅ `requester_office_id` field added to track office requests
- ✅ Foreign key to `offices` table

---

## ⏳ Remaining Implementation

### Admin Approval Handler

When admin approves an office request (`request_source='custodian'` AND `requester_office_id IS NOT NULL`), the system should:

1. **Create inventory tag** (like custodian manual assignment)
2. **Assign asset to office**
3. **Update request status** to `'released'` (meaning "transferred")
4. **Send notification** to office user

---

## Detailed Workflow

### Current Flow

```
Office User                    Custodian                Admin
     |                              |                      |
     | 1. Submit Request            |                      |
     |----------------------------->|                      |
     |   (status: pending)          |                      |
     |                              |                      |
     |                              | 2. Review & Approve  |
     |                              |--------------------->|
     |                              | (status: custodian_review)
     |                              |                      |
     |                              |           3. Final   |
     |                              |<---------------------|
     |                              |  Approve (status: approved)
     |                              |                      |
     |                     ⚠️ NEEDS IMPLEMENTATION ⚠️      |
     |                              |                      |
     |                              | 4. Generate Tag      |
     |                              | 5. Assign to Office  |
     |                              | 6. Set status='released'
     |                              |                      |
     | 7. Notified (Asset Transferred)                    |
     |<------------------------------|                      |
```

### What Needs to Happen on Admin Approval

When admin approves (`api/requests.php` - `approve_as_admin` action):

```php
// Check if this is an office request (permanent transfer)
if ($request['request_source'] === 'custodian' && $request['requester_office_id']) {
    // This is an office transfer request

    // 1. Generate inventory tag number
    $tagNumber = generateTagNumber($pdo, $campusId);

    // 2. Create inventory tag
    $sql = "INSERT INTO inventory_tags
            (tag_number, asset_id, office_id, status, assigned_by_custodian_id, inventory_date)
            VALUES (?, ?, ?, 'Pending Verification', ?, NOW())";
    executeQuery($pdo, $sql, [
        $tagNumber,
        $request['asset_id'],
        $request['requester_office_id'],
        $custodianId // Need to get custodian user ID
    ]);

    // 3. Update asset: decrease quantity, assign to office
    executeQuery($pdo,
        "UPDATE assets SET quantity = quantity - ?, assigned_to = ? WHERE id = ?",
        [$request['quantity'], $request['requester_office_id'], $request['asset_id']]
    );

    // 4. Update request status to 'released' (meaning transferred)
    executeQuery($pdo,
        "UPDATE asset_requests SET status = 'released', released_date = NOW(), released_by = ? WHERE id = ?",
        [$userId, $requestId]
    );

    // 5. Log activity
    logActivity($pdo, $request['asset_id'], 'ASSIGNED_TO_OFFICE',
        "Asset automatically transferred to office via approved request #{$requestId}");

    // 6. Notify office user
    createNotification($pdo, $request['requester_id'], ...);

} else {
    // Regular employee request - keep existing logic
    // Just update status to 'approved', custodian will manually release
}
```

---

## Key Differences: Office vs Employee Requests

| Aspect | Office Request | Employee Request |
|--------|---------------|------------------|
| **Request Type** | Permanent Transfer | Temporary Loan |
| **Return Required** | ❌ No | ✅ Yes |
| **Expected Return Date** | ❌ Not applicable | ✅ Required |
| **On Approval** | Auto-create inventory tag & assign | Custodian manually releases |
| **Final Status** | `'released'` = Transferred | `'released'` = In use, awaiting return |
| **Asset Assignment** | Assigned to office via inventory tag | Created as borrowing record |
| **Verification** | Office verifies receipt on dashboard | N/A |

---

## Suggested Code Modifications

### File: `api/requests.php`

**Location:** Around line 434 (in `approve_as_admin` case)

**Add this logic after admin approval:**

```php
case 'approve_as_admin':
    // ... existing validation code ...

    $pdo->beginTransaction();

    try {
        // Existing admin approval updates
        $stmt = $pdo->prepare("
            UPDATE asset_requests
            SET status = 'approved',
                final_approved_by = ?,
                final_approved_at = NOW(),
                admin_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$userId, $adminNotes, $requestId]);

        // 🆕 NEW: Check if this is an office transfer request
        if ($request['request_source'] === 'custodian' && $request['requester_office_id']) {
            // Auto-transfer asset to office

            // 1. Generate tag number
            $tagPrefix = "HCC-" . date('Y') . "-";
            $lastTag = fetchOne($pdo,
                "SELECT tag_number FROM inventory_tags
                 WHERE tag_number LIKE ?
                 ORDER BY id DESC LIMIT 1",
                [$tagPrefix . '%']
            );

            if ($lastTag) {
                $lastNum = (int)substr($lastTag['tag_number'], strlen($tagPrefix));
                $newNum = $lastNum + 1;
            } else {
                $newNum = 1;
            }
            $tagNumber = $tagPrefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);

            // 2. Create inventory tag
            $insertTag = $pdo->prepare("
                INSERT INTO inventory_tags
                (tag_number, asset_id, office_id, status, assigned_by_custodian_id,
                 inventory_date, remarks)
                VALUES (?, ?, ?, 'Pending Verification',
                        (SELECT id FROM users WHERE role='custodian' AND campus_id = ? LIMIT 1),
                        NOW(), ?)
            ");
            $insertTag->execute([
                $tagNumber,
                $request['asset_id'],
                $request['requester_office_id'],
                $request['campus_id'],
                "Auto-assigned via approved request #{$requestId}"
            ]);
            $tagId = $pdo->lastInsertId();

            // 3. Update asset quantity and assignment
            $updateAsset = $pdo->prepare("
                UPDATE assets
                SET quantity = quantity - ?, assigned_to = ?
                WHERE id = ? AND quantity >= ?
            ");
            $updated = $updateAsset->execute([
                $request['quantity'],
                $request['requester_office_id'],
                $request['asset_id'],
                $request['quantity']
            ]);

            if (!$updated || $updateAsset->rowCount() === 0) {
                throw new Exception('Insufficient asset quantity available');
            }

            // 4. Update request to 'released' status
            $updateReq = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'released',
                    released_date = NOW(),
                    released_by = ?
                WHERE id = ?
            ");
            $updateReq->execute([$userId, $requestId]);

            // 5. Log activity
            logActivity($pdo, $request['asset_id'], 'ASSIGNED_TO_OFFICE',
                "Asset transferred to office #{$request['requester_office_id']} via approved request #{$requestId}. Tag: {$tagNumber}");

            // 6. Notify office user
            createNotification(
                $pdo,
                $request['requester_id'],
                NOTIFICATION_REQUEST_APPROVED,
                "Asset Transfer Approved",
                "Your request has been approved and the asset has been transferred to your office. Please verify receipt on your dashboard.",
                [
                    'related_type' => 'request',
                    'related_id' => $requestId,
                    'priority' => 'high',
                    'action_url' => '/AMS-REQ/office/office_dashboard.php'
                ]
            );
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Request approved successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    break;
```

---

## Testing Checklist

### ✅ Already Tested
- [x] Office user can view available assets
- [x] Office user can submit request (no return date)
- [x] Request appears in custodian pending queue
- [x] Request shows in office "My Requests" with 4-stage progress

### ⏳ To Be Tested (After Implementation)
- [ ] Custodian approves office request → status becomes 'custodian_review'
- [ ] Admin approves office request → inventory tag auto-created
- [ ] Asset quantity decreases correctly
- [ ] Asset shows as assigned to office
- [ ] Office dashboard shows pending verification
- [ ] Office user can verify receipt
- [ ] Request status shows as "Transferred" in My Requests
- [ ] Progress tracker shows 100% complete

---

## Benefits of This Approach

1. **Consistency**: Uses same inventory tag system as manual custodian assignments
2. **Accountability**: Full audit trail from request to transfer
3. **Verification**: Office still needs to verify receipt (quality control)
4. **No Returns**: Clearly distinguished from employee borrowing
5. **Automated**: Reduces manual work for custodian after admin approval

---

## Alternative Approach (Not Recommended)

Instead of auto-creating tags on admin approval, we could:
- Show approved office requests in custodian's "Release Assets" page
- Custodian manually creates tags and assigns

**Why not recommended:**
- More manual work
- Inconsistent with "permanent transfer" concept
- Office users waiting longer for assets

---

## Next Steps

1. **Implement** the auto-transfer logic in `api/requests.php`
2. **Test** the complete workflow end-to-end
3. **Update** release_assets.php to exclude office transfer requests (they're auto-handled)
4. **Document** for future maintenance

---

**Date Created:** November 11, 2025
**Status:** Partially Implemented - Awaiting Admin Approval Handler
**Priority:** High
