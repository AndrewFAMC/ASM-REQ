# Office-to-Custodian Asset Request Feature

## Overview

This feature allows **office users (department heads)** to request assets from the **custodian's central inventory** for their department/office.

Previously, only employees could request assets. Now, office users can also make requests on behalf of their office.

---

## User Flow

### For Office Users (Department Heads)

```
1. Office user logs in
   ↓
2. Clicks "Request from Custodian" in sidebar
   ↓
3. Views available assets from central inventory
   ↓
4. Selects an asset and fills request form:
   - Quantity
   - Purpose/Justification
   - Expected return date (optional)
   ↓
5. Submits request
   ↓
6. Request goes to custodian for review (status: 'pending')
   ↓
7. Custodian approves → status: 'custodian_review'
   ↓
8. Admin gives final approval → status: 'approved'
   ↓
9. Custodian releases asset → status: 'released'
   ↓
10. Office receives asset
```

---

## Database Changes

### New Field: `requester_office_id`

Added to `asset_requests` table to track when an office user makes a request:

```sql
ALTER TABLE asset_requests
ADD COLUMN requester_office_id INT(11) NULL
COMMENT 'Office ID of requester if requester is an office user'
AFTER requester_id;
```

**Purpose**:
- Differentiates between employee requests and office (department head) requests
- Links the request to the requesting office
- Enables tracking which office made the request

---

## Files Created/Modified

### Created Files

1. **[office/request_from_custodian.php](../office/request_from_custodian.php)**
   - Main page for office users to request assets
   - Displays available assets from custodian
   - Handles request submission
   - AJAX endpoints for loading assets and submitting requests

2. **[office/my_requests.php](../office/my_requests.php)**
   - Request tracking page for office users
   - Shows all requests made by the office user
   - Visual progress tracker with 5 stages
   - Statistics dashboard (In Progress, Approved, Rejected)
   - Detailed view of each request with notes and approval chain

3. **[database/migrations/add_requester_office_field.sql](../database/migrations/add_requester_office_field.sql)**
   - Adds `requester_office_id` field to `asset_requests` table
   - Adds foreign key constraint to `offices` table
   - Adds index for better query performance

4. **[database/migrations/run_migration.php](../database/migrations/run_migration.php)**
   - Simple script to run the migration
   - Can be executed via command line: `php run_migration.php`

### Modified Files

1. **[office/office_dashboard.php](../office/office_dashboard.php)** (Lines 327-335)
   - Added "Request from Custodian" link in sidebar navigation
   - Added "My Requests" link in sidebar navigation
   - Icons: `fa-paper-plane`, `fa-clipboard-list`
   - Positioned below "Approval History" with separator

---

## How It Works

### Asset Availability Query

The system shows only assets that are:
- In the same campus as the office user
- Have status `'Available'` or `'In Storage'`
- Have quantity > 0
- Are NOT assigned to a specific office (office_id IS NULL)
- Belong to central inventory managed by custodian

```sql
WHERE a.campus_id = ?
AND a.status IN ('Available', 'In Storage')
AND a.quantity > 0
AND (a.office_id IS NULL OR a.office_id = 0)
```

### Request Creation

When an office user submits a request:

```php
INSERT INTO asset_requests
(requester_id, asset_id, quantity, purpose, expected_return_date,
 status, campus_id, request_source, requester_office_id, request_date)
VALUES (?, ?, ?, ?, ?, 'pending', ?, 'custodian', ?, NOW())
```

**Key fields:**
- `requester_id`: User ID of the office user
- `request_source`: Always `'custodian'` (not `'office'`)
- `requester_office_id`: Office ID of the requesting office
- `status`: Starts as `'pending'`

---

## Approval Flow

Office-to-custodian requests follow the **standard custodian approval flow**:

```
pending → custodian_review → approved → released → returned
```

### Approvers

1. **Custodian** - Reviews availability and feasibility
2. **Admin** - Gives final approval
3. **Custodian** - Releases the asset

This is the same flow as employee-to-custodian requests.

---

## Differences from Office-to-Office Requests

| Aspect | Office-to-Custodian | Office-to-Office (Employee Flow) |
|--------|---------------------|----------------------------------|
| **Request Source** | `'custodian'` | `'office'` |
| **Requester** | Office user (dept head) | Employee |
| **Approvers** | Custodian + Admin | Office head only |
| **Approval Steps** | 2 (Custodian → Admin) | 1 (Office head) |
| **Inventory Source** | Central/Custodian | Department-specific |
| **Status Flow** | pending → custodian_review → approved → released | pending → office_review → approved |

---

## UI Features

### Request from Custodian Page

**Components:**

1. **Asset Grid**
   - Card-based layout
   - Shows: Asset name, category, location, serial number, quantity
   - Status badge (Available, In Storage)
   - "Request" button on each card

2. **Search/Filter**
   - Real-time search by asset name, category, or serial number
   - Client-side filtering for instant results

3. **Request Modal**
   - Asset details (name, available quantity)
   - Quantity input (with max validation)
   - Purpose textarea (required)
   - Expected return date (optional)
   - Submit/Cancel buttons

4. **Responsive Design**
   - Grid adapts: 1 column (mobile) → 2 columns (tablet) → 3 columns (desktop)
   - Uses Tailwind CSS for styling

---

### My Requests Page

**Components:**

1. **Statistics Dashboard**
   - Three cards showing: In Progress, Approved, Rejected
   - Color-coded borders (blue, green, red)
   - Large numbers for quick scanning

2. **Alert Banner**
   - Shows if office has unreturned assets
   - Red alert for overdue items
   - Yellow alert for assets in use

3. **Request Cards**
   - Color-coded left border based on status
   - Request header with asset name and submission date
   - Status badge (In Progress, Approved, Rejected, In Use, Completed)
   - Office name tag showing which office made the request

4. **Request Details Grid**
   - Asset code, quantity, campus, expected return date
   - Purpose/justification text
   - Approver names (custodian, admin)

5. **Visual Progress Tracker**
   - 5-stage timeline: Submitted → Custodian Review → Admin Approval → Released → Returned
   - Animated current step (pulsing blue circle)
   - Completed steps (green with checkmarks)
   - Shows approver names under each stage
   - Progress line fills based on completion percentage

6. **Notes Section**
   - Blue info box showing custodian/admin notes
   - Visible only if notes exist
   - Helpful for understanding approval decisions

7. **Rejection Display**
   - Red alert box for rejected requests
   - Shows rejection reason and date
   - Replaces progress tracker when rejected

---

## Notifications

### When Request is Created

**Notify:**
- Custodian (new pending request)
- Requester (confirmation)

**Activity Log:**
```
Office user {name} from {office_name} requested {quantity} unit(s) from custodian.
Purpose: {purpose}
```

---

## Security & Validation

### Request Validation

- ✅ CSRF token verification
- ✅ Authentication check (must be logged in)
- ✅ Role check (must be office user)
- ✅ Quantity validation (must be > 0 and ≤ available)
- ✅ Campus verification (asset must be in same campus)
- ✅ Required fields check (asset_id, quantity, purpose)

### Database Constraints

- Foreign key: `requester_office_id` → `offices.id` (ON DELETE SET NULL)
- Index on `requester_office_id` for query performance

---

## API Endpoints

### Get Custodian Assets

**Endpoint:** `POST office/request_from_custodian.php`

**Action:** `get_custodian_assets`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "asset_name": "Projector - Epson EB-X41",
      "serial_number": "ABC123",
      "status": "Available",
      "quantity": 5,
      "category_name": "Electronics",
      "campus_name": "Sta. Rosa Campus",
      "location": "Central Storage"
    }
  ]
}
```

### Submit Request

**Endpoint:** `POST office/request_from_custodian.php`

**Action:** `submit_request`

**Parameters:**
- `asset_id` (int, required)
- `quantity` (int, required)
- `purpose` (text, required)
- `expected_return_date` (date, optional)

**Response:**
```json
{
  "success": true,
  "message": "Request submitted successfully! The custodian will review your request.",
  "request_id": 456
}
```

---

## Testing Checklist

### ✅ Basic Flow

1. Log in as office user (department head)
2. Navigate to "Request from Custodian"
3. Verify available assets are displayed
4. Search for a specific asset
5. Click "Request" on an asset
6. Fill in request form with valid data
7. Submit request
8. Verify success message appears
9. Verify request appears in custodian's pending list

### ✅ Validation Tests

1. Try requesting quantity > available (should fail)
2. Try submitting without purpose (should fail)
3. Try accessing page as non-office user (should fail)
4. Verify CSRF token validation

### ✅ Approval Flow

1. Office user submits request
2. Custodian logs in, sees request in "Approve Requests"
3. Custodian approves → status becomes 'custodian_review'
4. Admin logs in, sees request
5. Admin approves → status becomes 'approved'
6. Custodian releases asset → status becomes 'released'
7. Office receives asset

---

## Future Enhancements

**Possible improvements:**

1. **Bulk Requests**: Allow requesting multiple assets at once
2. **Request History**: Show office's past requests
3. **Request Templates**: Save common request patterns
4. **Asset Reservation**: Reserve asset before formal request
5. **Approval Notifications**: Real-time notifications for approval status changes
6. **Request Analytics**: Dashboard showing request statistics per office

---

## Migration Instructions

To deploy this feature to a new database:

1. Run the migration:
   ```bash
   php database/migrations/run_migration.php
   ```

2. Or manually execute the SQL:
   ```bash
   mysql -u root -p hcc_asset_management < database/migrations/add_requester_office_field.sql
   ```

3. Verify the field was added:
   ```sql
   DESCRIBE asset_requests;
   ```

   Should show `requester_office_id INT(11) NULL` after `requester_id`

---

## Support & Troubleshooting

### Common Issues

**Issue**: "No assets available from custodian"
- **Cause**: No assets in central inventory (all assigned to offices)
- **Solution**: Ensure some assets have `office_id = NULL` and status = 'Available'

**Issue**: Request not appearing in custodian's queue
- **Cause**: `request_source` not set to 'custodian'
- **Solution**: Check the INSERT query in request_from_custodian.php

**Issue**: Foreign key constraint fails
- **Cause**: Invalid office_id in requester_office_id
- **Solution**: Verify user has valid office_id in session

---

**Implementation Date:** November 11, 2025
**Version:** 1.0
**Status:** ✅ Complete and Tested
