# 🔄 DUAL-FLOW REQUEST SYSTEM

## 📋 Overview

The Asset Management System now supports **TWO separate request workflows**:

1. **Employee → Office (Department)** - For office-specific assets
2. **Employee → Custodian → Admin** - For central/custodian assets

---

## 🎯 The Two Flows

### **Flow 1: Office Request (Direct)**
```
Employee selects "Request from Office"
    ↓
Chooses office (MIS, HM Kitchen, Criminology, etc.)
    ↓
Office Head reviews & approves
    ↓
Status: APPROVED ✅ (Employee can pick up)
```

**Characteristics:**
- ✅ **Single approval** (Office Head only)
- ✅ **Fast** - No admin or custodian needed
- ✅ **Office-specific** inventory
- ✅ Employee can request from **any office**

---

### **Flow 2: Custodian Request (Full Approval)**
```
Employee selects "Request from Custodian"
    ↓
Custodian reviews availability
    ↓
Admin gives final approval
    ↓
Custodian releases asset
    ↓
Status: RELEASED ✅
```

**Characteristics:**
- ✅ **Two approvals** (Custodian + Admin)
- ✅ **Central inventory** managed by custodian
- ✅ **Higher value** or shared assets
- ✅ Full audit trail

---

## 🗄️ Database Changes

### **New Fields in `asset_requests` Table:**

```sql
-- Source tracking
request_source ENUM('custodian', 'office') NOT NULL DEFAULT 'custodian'
  - 'custodian': Goes through full approval (Custodian → Admin)
  - 'office': Goes to office head only

-- Office request fields
target_office_id INT NULL
  - Which office the request is directed to
  - FK to offices.id

office_approved_by INT NULL
  - Office head who approved
  - FK to users.id

office_approved_at DATETIME NULL
  - When office head approved

office_approval_notes TEXT NULL
  - Office head comments

-- New status
status ENUM(..., 'office_review', ...)
  - Added 'office_review' for office-pending requests
```

---

## 📊 Status Flow Charts

### **Office Request Flow:**
```
pending
   ↓ (Employee submits to office)
office_review
   ↓ (Office head approves)
approved ✅
   ↓ (Employee picks up)
(Optional: released → returned)
```

### **Custodian Request Flow:**
```
pending
   ↓ (Custodian reviews)
custodian_review
   ↓ (Admin approves)
approved
   ↓ (Custodian releases)
released
   ↓ (Custodian processes return)
returned ✅
```

---

## 👥 Role Permissions

| Role | Can See | Can Approve | Request Source Filter |
|------|---------|-------------|----------------------|
| **Employee** | Own requests | ❌ | Can create both types |
| **Office** | Office requests | ✅ (office_review → approved) | `request_source='office'` AND `target_office_id=their_office` |
| **Custodian** | Custodian requests | ✅ (pending → custodian_review) | `request_source='custodian'` |
| **Admin** | Custodian requests | ✅ (custodian_review → approved) | No filter (sees all for oversight) |

---

## 🔧 API Endpoints

### **Get Pending Requests (Role-Based)**
```http
GET /api/requests.php?action=get_pending_requests
```

**Filters automatically based on role:**
- **Office:** Shows `status='office_review'` AND `request_source='office'` AND `target_office_id=their_office`
- **Custodian:** Shows `status='pending'` AND `request_source='custodian'`
- **Admin:** Shows `status='custodian_review'`

---

### **Create Request (Employee)**
```http
POST /api/requests.php
{
    "action": "create_request",
    "asset_id": 123,
    "quantity": 1,
    "purpose": "For lab demo",
    "expected_return_date": "2025-01-20",
    "request_source": "office",     // or "custodian"
    "target_office_id": 5            // Required if request_source='office'
}
```

**Validation:**
- If `request_source='office'` → `target_office_id` is REQUIRED
- If `request_source='custodian'` → `target_office_id` is NULL

**Initial Status:**
- Office requests → `status='office_review'`
- Custodian requests → `status='pending'`

---

### **Approve as Office Head**
```http
POST /api/requests.php
{
    "action": "approve_as_office",
    "request_id": 123,
    "comments": "Approved for use" (optional)
}
```

**Requirements:**
- User must have `role='office'`
- Request must have `status='office_review'`
- Request must have `target_office_id=user's office_id`
- Request must have `request_source='office'`

**Result:**
- Status changes to `'approved'`
- Sets `office_approved_by`, `office_approved_at`, `office_approval_notes`
- Sets `approved_by` and `approved_at` (marks as fully approved)
- Notifies requester

---

### **Approve as Custodian** (Unchanged for custodian requests)
```http
POST /api/requests.php
{
    "action": "approve_as_custodian",
    "request_id": 123,
    "comments": "Available" (optional)
}
```

**Requirements:**
- User must have `role='custodian'`
- Request must have `status='pending'`
- Request must have `request_source='custodian'`

**Result:**
- Status changes to `'custodian_review'`
- Notifies admin

---

### **Approve as Admin** (Unchanged for custodian requests)
```http
POST /api/requests.php
{
    "action": "approve_as_admin",
    "request_id": 123,
    "comments": "Final approval" (optional)
}
```

**Requirements:**
- User must have `role='admin'` or `role='super_admin'`
- Request must have `status='custodian_review'`
- Request must have `request_source='custodian'`

**Result:**
- Status changes to `'approved'`
- Notifies custodian to release
- Notifies requester

---

### **Reject Request**
```http
POST /api/requests.php
{
    "action": "reject_request",
    "request_id": 123,
    "reason": "Not available" (required)
}
```

**Who can reject:**
- **Admin:** Can reject any request at any stage
- **Custodian:** Can reject `request_source='custodian'` with `status='pending'` or `'custodian_review'`
- **Office:** Can reject `request_source='office'` with `status='office_review'` from their office

---

## 📝 Employee Request Interface (To Be Implemented)

### **Step 1: Choose Request Source**
```
┌─────────────────────────────────────────┐
│  Where would you like to request from?  │
├─────────────────────────────────────────┤
│                                          │
│  [📦 Request from Office/Department]    │
│  Quick approval from your department     │
│                                          │
│  [🏢 Request from Custodian (Central)]  │
│  For central inventory & shared assets  │
│                                          │
└─────────────────────────────────────────┘
```

### **Step 2a: If Office Selected**
```
┌─────────────────────────────────────────┐
│  Select Office/Department:               │
├─────────────────────────────────────────┤
│  ○ MIS (Management Information System)  │
│  ○ HM Kitchen (Hotel Management)        │
│  ○ Criminology Crime Lab                │
│  ○ Engineering Workshop                 │
│  ○ Nursing Laboratory                   │
└─────────────────────────────────────────┘

Then show available assets from that office
```

### **Step 2b: If Custodian Selected**
```
Show central inventory assets directly
```

---

## 🎨 Office Dashboard Updates (To Be Implemented)

### **Office Head Dashboard** (`office/approve_requests.php`)

**Should show:**
1. **Pending Requests from Employees**
   - Filter: `request_source='office'` AND `target_office_id=their_office` AND `status='office_review'`
   - Show requester name, asset, purpose, date
   - Action buttons: Approve / Reject

2. **Recently Approved**
   - Show recent approvals by this office head

3. **Statistics**
   - Pending office requests
   - Approved today
   - Total this month

---

## 🔍 Key Differences Between Flows

| Aspect | Office Request | Custodian Request |
|--------|----------------|-------------------|
| **Approvers** | 1 (Office Head) | 2 (Custodian + Admin) |
| **Speed** | Fast (single approval) | Slower (two approvals) |
| **Inventory** | Office-specific | Central/Custodian |
| **Use Case** | Department equipment | Shared/high-value assets |
| **Status Flow** | pending → office_review → approved | pending → custodian_review → approved → released |
| **Employee Choice** | Can request from ANY office | Always central |
| **Release** | Direct pickup from office | Custodian releases |

---

## 🧪 Testing Checklist

### ✅ Office Request Flow
1. Employee creates request with `request_source='office'` and `target_office_id=5` (MIS)
2. Request status should be `'office_review'`
3. MIS office head logs in, sees the request
4. Office head approves → Status becomes `'approved'`
5. Employee gets notification, can pick up

### ✅ Custodian Request Flow
1. Employee creates request with `request_source='custodian'`
2. Request status should be `'pending'`
3. Custodian approves → Status becomes `'custodian_review'`
4. Admin approves → Status becomes `'approved'`
5. Custodian releases → Status becomes `'released'`
6. Custodian processes return → Status becomes `'returned'`

### ✅ Permission Tests
1. Office head should NOT see custodian requests
2. Custodian should NOT see office requests
3. Office head can only see requests directed to THEIR office
4. Office head from MIS cannot approve HM Kitchen requests

---

## 📂 Files Modified

### ✅ Database:
- `database/migrations/add_request_source_fields.sql` (NEW)
  - Added `request_source`, `target_office_id`, `office_approved_by`, `office_approved_at`, `office_approval_notes`
  - Added `'office_review'` to status enum

### ✅ API:
- `api/requests.php` (UPDATED)
  - Line 61-70: Added office role filtering
  - Line 76-89: Added request_source filtering in WHERE clause
  - Line 91-117: Updated query to include office fields
  - Line 266-345: Added `approve_as_office` endpoint
  - Line 476-494: Updated reject permissions for dual flow

### ⏳ To Be Created:
- Employee request UI with source selection
- Office approval interface updates

---

## 💡 Implementation Notes

### **Why Two Flows?**
1. **Efficiency:** Office requests don't need full approval chain
2. **Autonomy:** Office heads can manage their own inventory
3. **Flexibility:** Employees can choose based on need
4. **Scalability:** Each office manages its assets independently

### **Design Decisions:**
1. **Employee chooses source first** - Clear intent, better UX
2. **Office approvals are final** - No admin needed for office assets
3. **Custodian requests still need admin** - Higher oversight for central assets
4. **Employees can request from any office** - Maximum flexibility

---

**Date:** <?= date('Y-m-d H:i:s') ?>
**Version:** 3.0 - Dual-Flow System
**Status:** API Complete, UI Pending
