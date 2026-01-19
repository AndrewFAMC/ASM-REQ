# ✅ CORRECTED APPROVAL FLOW - No Department Head

## 📋 **Simplified Approval Workflow**

The approval flow has been corrected to **remove the department head step**. The flow is now:

```
Employee → Custodian → Admin → Custodian (Release)
```

---

## 🔄 **Step-by-Step Flow**

### **1. Employee Creates Request**
- **Status:** `pending`
- **Action:** Employee submits asset request with purpose and expected return date
- **Next:** Custodian reviews

### **2. Custodian Reviews**
- **Status:** `custodian_review` (after custodian approves)
- **Action:** Custodian checks availability and approves/rejects
- **API:** `POST /api/requests.php?action=approve_as_custodian`
- **Next:** Admin for final approval

### **3. Admin Final Approval**
- **Status:** `approved` (after admin approves)
- **Action:** Admin gives final approval
- **API:** `POST /api/requests.php?action=approve_as_admin`
- **Next:** Custodian releases asset

### **4. Custodian Releases Asset**
- **Status:** `released`
- **Action:** Custodian physically hands over asset
- **API:** `POST /api/requests.php?action=release_asset`
- **Next:** Employee uses asset

### **5. Employee Returns Asset**
- **Status:** `returned`
- **Action:** Custodian processes return
- **API:** `POST /api/requests.php?action=return_asset`
- **Complete!**

---

## 📊 **Status Flow Chart**

```
┌──────────────────────────────────────────────────────────────┐
│                    ASSET REQUEST FLOW                         │
└──────────────────────────────────────────────────────────────┘

1. pending
   ↓ (Custodian approves)

2. custodian_review
   ↓ (Admin approves)

3. approved
   ↓ (Custodian releases)

4. released
   ↓ (Custodian processes return)

5. returned ✓

ALTERNATIVE PATHS:
- rejected (from any step by admin or custodian)
- cancelled (by requester before approval)
```

---

## 🔧 **What Was Changed**

### **API File:** `api/requests.php`

#### **1. Role-Based Request Viewing (Line 57-66)**
```php
if ($userRole === 'custodian') {
    $status = 'pending';  // See new requests
} elseif ($userRole === 'admin' || $userRole === 'super_admin') {
    $status = 'custodian_review';  // See custodian-approved requests
}
// NO department head/office role check!
```

#### **2. Custodian Approval (Line 188-197)**
```php
// Update to 'custodian_review' not 'approved_custodian'
SET status = 'custodian_review',
    custodian_reviewed_by = ?,
    custodian_reviewed_at = NOW()

// Notify admin directly (no department head)
```

#### **3. Department Approval Removed (Line 243-247)**
```php
case 'approve_as_department':
    // This endpoint is disabled
    throw new Exception('Department approval is no longer part of the workflow');
```

#### **4. Admin Approval (Line 280-294)**
```php
// Check for 'custodian_review' status
if ($request['status'] !== 'custodian_review') {
    throw new Exception('Request must be reviewed by custodian first');
}

// Update to 'approved' status
SET status = 'approved',
    approved_by = ?,
    approved_at = NOW()
```

#### **5. Release Asset (Line 472-474)**
```php
// Check for 'approved' status (not 'approved_admin')
if ($request['status'] !== 'approved') {
    throw new Exception('Request must be fully approved before release');
}
```

#### **6. Reject Request (Line 378-391)**
```php
// Removed department approver check
// Only custodian (for pending/custodian_review) or admin (any stage)
if (hasRole('admin') || hasRole('super_admin')) {
    $canReject = true;
} elseif (hasRole('custodian') && in_array($request['status'], ['pending', 'custodian_review'])) {
    $canReject = true;
}
```

---

## 🗄️ **Database Status Values**

### **From `asset_requests` Table:**
```sql
status ENUM(
    'pending',           -- Initial submission
    'custodian_review',  -- Custodian approved, waiting for admin
    'department_review', -- NOT USED ANYMORE
    'approved',          -- Admin approved, ready for release
    'rejected',          -- Rejected by custodian or admin
    'released',          -- Asset handed to employee
    'returned',          -- Asset returned
    'cancelled'          -- Cancelled by requester
)
```

---

## 👥 **Who Can Do What**

### **Employee (role = 'employee')**
- ✅ Create requests
- ✅ View own requests
- ✅ Cancel pending requests
- ❌ Cannot approve anything

### **Custodian (role = 'custodian')**
- ✅ View pending requests (`status = 'pending'`)
- ✅ Approve requests → Changes to `custodian_review`
- ✅ Reject pending or custodian_review requests
- ✅ Release approved assets → Changes to `released`
- ✅ Process returns → Changes to `returned`
- ❌ Cannot give final approval

### **Admin (role = 'admin' or 'super_admin')**
- ✅ View custodian-reviewed requests (`status = 'custodian_review'`)
- ✅ Final approval → Changes to `approved`
- ✅ Reject at any stage
- ❌ Cannot release assets (custodian does this)

### **Office (role = 'office') - REMOVED FROM FLOW**
- ❌ No longer part of approval workflow
- They can still manage office inventory
- But they DON'T approve asset requests

---

## 📝 **API Endpoints**

### **For Custodians:**

#### Get Pending Requests
```http
GET /api/requests.php?action=get_pending_requests
Returns: status = 'pending'
```

#### Approve Request
```http
POST /api/requests.php
{
    "action": "approve_as_custodian",
    "request_id": 123,
    "comments": "Approved" (optional)
}
Result: status → 'custodian_review'
```

#### Release Asset
```http
POST /api/requests.php
{
    "action": "release_asset",
    "request_id": 123
}
Requirement: status must be 'approved'
Result: status → 'released'
```

---

### **For Admins:**

#### Get Custodian-Reviewed Requests
```http
GET /api/requests.php?action=get_pending_requests
Returns: status = 'custodian_review'
```

#### Final Approval
```http
POST /api/requests.php
{
    "action": "approve_as_admin",
    "request_id": 123,
    "comments": "Final approval" (optional)
}
Requirement: status must be 'custodian_review'
Result: status → 'approved'
```

---

### **For Both:**

#### Reject Request
```http
POST /api/requests.php
{
    "action": "reject_request",
    "request_id": 123,
    "reason": "Not available" (required)
}
Result: status → 'rejected'
```

---

## ⚠️ **Important Notes**

### **Department Approvers Table:**
The `department_approvers` table still exists but is **NOT used** in the approval workflow.
- Office users can still be assigned as "approvers"
- But they have **NO role** in the request approval process
- The table may be used for other purposes (office management, etc.)

### **Status Progression:**
```
pending → custodian_review → approved → released → returned
         ↓                    ↓
      rejected             rejected
```

### **Notifications:**
1. **Employee creates request** → Custodian notified
2. **Custodian approves** → Admin notified (NOT department head)
3. **Admin approves** → Employee notified + Custodian notified to release
4. **Custodian releases** → Employee notified
5. **Any rejection** → Employee notified

---

## 🧪 **Testing Checklist**

### ✅ **Test 1: Complete Flow**
1. Employee creates request → Status: `pending`
2. Custodian approves → Status: `custodian_review`
3. Admin approves → Status: `approved`
4. Custodian releases → Status: `released`
5. Custodian processes return → Status: `returned`

### ✅ **Test 2: Rejection by Custodian**
1. Employee creates request → Status: `pending`
2. Custodian rejects → Status: `rejected`

### ✅ **Test 3: Rejection by Admin**
1. Employee creates request → Status: `pending`
2. Custodian approves → Status: `custodian_review`
3. Admin rejects → Status: `rejected`

### ✅ **Test 4: Wrong Status**
1. Try to release asset with status = `custodian_review` → Should fail
2. Try to admin approve with status = `pending` → Should fail
3. Try to custodian approve already approved request → Should fail

---

## 📂 **Files Modified**

### ✅ Modified:
1. **`api/requests.php`** - Complete approval flow rewrite
   - Line 57-66: Role-based filtering
   - Line 188-197: Custodian approval
   - Line 243-247: Department approval disabled
   - Line 280-294: Admin approval
   - Line 378-391: Reject permissions
   - Line 472-474: Release asset check

### ❌ Deprecated/Not Used:
1. **`office/approve_requests.php`** - Department head approval UI (not used)
2. **`department_approvers` table** - Still exists but not in workflow
3. **Department approval API endpoint** - Throws error

---

## 🎯 **Summary**

✅ **Approval flow simplified:** Employee → Custodian → Admin → Release
✅ **No department head involvement**
✅ **Status names corrected:** Using database enum values
✅ **Clear role permissions:** Each role knows what they can do
✅ **Proper error messages:** Shows current status when action fails

The system now follows a straightforward **two-level approval** process:
1. **Custodian** verifies availability and feasibility
2. **Admin** gives final authorization
3. **Custodian** physically releases the asset

---

**Date:** <?= date('Y-m-d H:i:s') ?>
**Version:** 2.0 - Simplified Flow
