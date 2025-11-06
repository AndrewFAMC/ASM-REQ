# Approval Workflow Implementation Plan

## Overview
This document outlines the redesigned approval workflow based on the professor's requirements:
- **Eliminate direct borrowing** - all borrowing must go through a request/approval process
- **Multi-tier approval hierarchy**: Requester → Custodian → Department Head → Admin
- **Automated notifications** at each approval step
- **Full audit trail** of who approved at each level

## Current vs New Workflow

### Current Flow (Direct Borrowing):
```
Staff → Directly borrows asset → Asset becomes unavailable
```

### New Flow (Request-Based):
```
Staff Request
    ↓
Custodian Review & Approve
    ↓
Department Head Approve (if required)
    ↓
Admin Final Approval (for high-value assets)
    ↓
Asset Released to Borrower
    ↓
Borrowing Record Created
```

## Database Schema (Already Created)

The following tables support this workflow:

### `asset_requests` table:
- `id` - Primary key
- `user_id` - Who requested
- `asset_id` - What asset
- `campus_id` - Which campus
- `quantity` - How many
- `purpose` - Why needed
- `expected_return_date` - When to return
- `status` - pending/approved_custodian/approved_department/approved_admin/rejected/released
- `custodian_approved_by` - User ID of custodian approver
- `custodian_approved_at` - Timestamp
- `department_approved_by` - User ID of department head approver
- `department_approved_at` - Timestamp
- `admin_approved_by` - User ID of admin approver
- `admin_approved_at` - Timestamp
- `rejection_reason` - Why rejected (if applicable)
- `created_at`, `updated_at`

### `department_approvers` table:
- `id` - Primary key
- `department_name` - Department name
- `approver_user_id` - Department head user ID
- `campus_id` - Which campus
- `is_active` - Active/inactive

## Implementation Steps

### Step 1: Update Asset Request Creation
- Modify request submission to set initial status as `pending`
- Trigger notification to custodian for review

### Step 2: Custodian Approval Interface
- Create approval dashboard showing pending requests
- Allow approve/reject with comments
- Update status to `approved_custodian`
- Trigger notification to department head (if required) or admin

### Step 3: Department Head Approval (Conditional)
- For high-value assets or specific categories, require department approval
- Department head views requests in their queue
- Approve/reject with comments
- Update status to `approved_department`
- Trigger notification to admin

### Step 4: Admin Final Approval
- Admin reviews all approved requests
- Final approve/reject
- Update status to `approved_admin`
- Create borrowing record
- Update asset availability
- Trigger notification to requester

### Step 5: Asset Release
- Once fully approved, custodian can release the asset
- Status changes to `released`
- Borrowing record is activated
- Expected return date tracking begins

## Notification Triggers

### On Request Submission:
```php
createNotification(
    $pdo,
    $custodianId,
    NOTIFICATION_APPROVAL_REQUEST,
    "New Asset Request",
    "{$requesterName} has requested {$assetName}. Please review.",
    ['related_type' => 'request', 'related_id' => $requestId, 'priority' => 'high']
);
```

### On Custodian Approval:
```php
createNotification(
    $pdo,
    $departmentHeadId,
    NOTIFICATION_APPROVAL_REQUEST,
    "Request Approved by Custodian",
    "Asset request for {$assetName} needs your approval.",
    ['related_type' => 'request', 'related_id' => $requestId, 'priority' => 'medium']
);
```

### On Department Approval:
```php
createNotification(
    $pdo,
    $adminId,
    NOTIFICATION_APPROVAL_REQUEST,
    "Request Approved by Department",
    "Asset request for {$assetName} needs final approval.",
    ['related_type' => 'request', 'related_id' => $requestId, 'priority' => 'medium']
);
```

### On Final Approval:
```php
createNotification(
    $pdo,
    $requesterId,
    NOTIFICATION_APPROVAL_RESPONSE,
    "Request Approved!",
    "Your request for {$assetName} has been approved. Please collect from custodian.",
    ['related_type' => 'request', 'related_id' => $requestId, 'priority' => 'high']
);
```

### On Rejection (at any level):
```php
createNotification(
    $pdo,
    $requesterId,
    NOTIFICATION_APPROVAL_RESPONSE,
    "Request Rejected",
    "Your request for {$assetName} was rejected. Reason: {$reason}",
    ['related_type' => 'request', 'related_id' => $requestId, 'priority' => 'medium']
);
```

## UI Components to Create

### 1. Request Approval Dashboard (Custodian)
- **File**: `custodian/requests.php`
- **Features**:
  - List of pending requests
  - View request details (requester, asset, purpose, quantity, expected return)
  - Approve/Reject buttons
  - Comment/reason input
  - Filter by status, date, asset type

### 2. Department Approval Dashboard
- **File**: `department/requests.php`
- **Features**:
  - List of requests approved by custodian, pending department approval
  - View full approval chain
  - Approve/Reject with comments

### 3. Admin Final Approval Dashboard
- **File**: `admin/requests.php`
- **Features**:
  - List of requests approved by custodian/department, pending admin approval
  - View complete approval history
  - Final approve/reject
  - Bulk approval capability

### 4. Request Status Tracker (Requester View)
- **File**: `staff/my_requests.php`
- **Features**:
  - View all my requests
  - Status indicators with visual progress bar
  - Approval timeline showing who approved and when
  - Cancel pending request option

## Approval Rules Configuration

### System Settings (via `system_settings` table):
- `require_department_approval` - Boolean, default: false
- `require_admin_approval` - Boolean, default: true
- `high_value_threshold` - Decimal, assets above this value require extra approval
- `department_approval_for_categories` - JSON array of category IDs requiring department approval

## API Endpoints

### For Custodian:
- `POST /api/requests.php?action=approve_as_custodian`
- `POST /api/requests.php?action=reject_request`

### For Department Head:
- `POST /api/requests.php?action=approve_as_department`
- `POST /api/requests.php?action=reject_request`

### For Admin:
- `POST /api/requests.php?action=approve_as_admin`
- `POST /api/requests.php?action=final_reject`
- `POST /api/requests.php?action=release_asset`

## Security Considerations

1. **Role Verification**: Ensure only authorized users can approve at each level
2. **Ownership Check**: Custodians can only approve requests for their campus
3. **CSRF Protection**: All approval actions require valid CSRF tokens
4. **Audit Logging**: Log every approval/rejection action
5. **Prevent Double Approval**: Check current status before allowing approval

## Testing Checklist

- [ ] Create request as staff user
- [ ] Verify custodian receives notification
- [ ] Approve as custodian, verify department/admin notified
- [ ] Approve as department (if required), verify admin notified
- [ ] Approve as admin, verify requester notified
- [ ] Test rejection at each level
- [ ] Verify borrowing record created only after full approval
- [ ] Check asset availability updates correctly
- [ ] Verify approval history is visible
- [ ] Test bulk approval for admin

---

**Status**: Implementation in progress
**Last Updated**: 2025-11-06
