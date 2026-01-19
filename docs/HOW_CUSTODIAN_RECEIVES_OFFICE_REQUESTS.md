# How Custodians Receive Office Asset Requests

## Overview

When an office user submits a request for assets from the custodian, the request **automatically appears** in the custodian's "Approve Requests" page. No manual intervention or configuration needed!

---

## Request Flow

```
Office User                         Custodian Dashboard
     |                                      |
     | 1. Submit request                   |
     |   - From: office/request_from_      |
     |     custodian.php                   |
     |   - request_source='custodian'      |
     |   - status='pending'                |
     |   - requester_office_id set         |
     |------------------------------------>|
     |                                      |
     |                    2. Auto-appears in|
     |                       "Approve       |
     |                        Requests"     |
     |                                      |
     |                    3. Custodian      |
     |                       reviews &      |
     |                       approves       |
     |<-------------------------------------|
```

---

## Where Custodians See Office Requests

### Page: `custodian/approve_requests.php`

**Navigation:** Custodian Panel → **Approve Requests**

**What it shows:**
- All pending requests with `request_source='custodian'`
- Includes both:
  - ✅ Employee requests (individual borrowing)
  - ✅ **Office requests** (permanent transfer)

---

## How to Identify Office Requests

Office requests can be distinguished by:

1. **Requester Office Name** - Shows which office is requesting
   - Field: `requester_office_name` (from `requester_office_id`)
   - Example: "MIS Office", "HM Kitchen", "Criminology Lab"

2. **Purpose** - Often mentions department needs
   - Example: "For department computer lab expansion"

3. **No Expected Return Date** - Office transfers are permanent
   - `expected_return_date` is NULL

---

## API Endpoint

### GET `/api/requests.php?action=get_pending_requests`

**For Custodian Role:**

**Query Logic:**
```sql
SELECT ...
FROM asset_requests ar
...
LEFT JOIN offices requester_office ON ar.requester_office_id = requester_office.id
WHERE ar.campus_id = ?
  AND ar.status = 'pending'
  AND ar.request_source = 'custodian'  -- FILTERS TO CUSTODIAN REQUESTS ONLY
ORDER BY ar.request_date DESC
```

**Response includes:**
```json
{
  "success": true,
  "requests": [
    {
      "id": 123,
      "asset_name": "Desktop Computer",
      "quantity": 5,
      "requester_name": "John Doe",
      "requester_office_name": "MIS Office",  // 🆕 IDENTIFIES OFFICE REQUEST
      "purpose": "For department computer lab",
      "request_date": "2025-11-11 10:30:00",
      "status": "pending",
      "request_source": "custodian",
      "requester_office_id": 12,
      ...
    }
  ]
}
```

---

## Custodian Approval Process

### Step 1: View Request
1. Custodian navigates to **"Approve Requests"**
2. Sees all pending requests (including office requests)
3. Can identify office requests by `requester_office_name`

### Step 2: Review Details
- Asset name and quantity
- Requesting office name
- Purpose/justification
- Campus information

### Step 3: Approve or Reject

**If Approved:**
```
Status: pending → custodian_review
Next: Goes to Admin for final approval
```

**If Rejected:**
```
Status: pending → rejected
Office user notified with reason
```

---

## What Makes Office Requests Different

| Aspect | Employee Request | Office Request |
|--------|------------------|----------------|
| **Requester Type** | Individual employee | Office/Department |
| **Purpose** | Personal/temporary use | Department operation |
| **Return Expected** | ✅ Yes | ❌ No (permanent transfer) |
| **expected_return_date** | Required | NULL |
| **requester_office_id** | NULL | Set (e.g., 12) |
| **Final Outcome** | Borrowing record created | Asset assigned to office via inventory tag |

---

## UI Display Example

When viewing requests in custodian dashboard, office requests could show:

```
┌────────────────────────────────────────────────────────────┐
│ Desktop Computer (Qty: 5)                          PENDING │
├────────────────────────────────────────────────────────────┤
│ Requested by: John Doe (MIS Office)               🏢       │
│ Date: Nov 11, 2025 10:30 AM                                │
│ Purpose: For department computer lab expansion             │
│                                                             │
│ [View Details] [Approve] [Reject]                          │
└────────────────────────────────────────────────────────────┘
```

The 🏢 icon or "Office" badge could indicate it's an office request.

---

## Auto-Filtering Logic

The API automatically filters based on the logged-in user's role:

```php
// In api/requests.php

if ($userRole === 'custodian') {
    $status = 'pending';
    $whereClause .= " AND ar.request_source = 'custodian'";
}
```

This means:
- ✅ Custodians only see requests directed to them
- ✅ Office requests (`request_source='custodian'`) automatically appear
- ✅ Employee-to-office requests (`request_source='office'`) are hidden

---

## Statistics on Dashboard

The custodian dashboard shows:

1. **Pending Approval** - Count of all pending requests (including office)
2. **Approved Today** - Requests approved today
3. **Total This Month** - All requests this month

Office requests are included in these counts!

---

## Testing the Flow

### Test Scenario:

1. **Office User Actions:**
   - Log in as office user
   - Navigate to "Request from Custodian"
   - Select an asset (e.g., "Projector")
   - Enter quantity: 2
   - Enter purpose: "For conference room"
   - Submit request

2. **Expected Result:**
   - Request created with:
     - `request_source = 'custodian'`
     - `status = 'pending'`
     - `requester_office_id = 12` (office ID)

3. **Custodian View:**
   - Log in as custodian
   - Navigate to "Approve Requests"
   - **Should see** the office request in the list
   - Shows: "Requested by: [Name] (MIS Office)"

4. **Custodian Approves:**
   - Click "Approve" button
   - Status changes to `'custodian_review'`

5. **Admin Approves:**
   - Log in as admin
   - Navigate to admin approval page
   - Approve the request
   - **Auto-creates inventory tag** and assigns to office

---

## Troubleshooting

### Issue: Office requests not showing in custodian dashboard

**Check:**
1. ✅ Request has `request_source='custodian'` (not 'office')
2. ✅ Request has `status='pending'`
3. ✅ Custodian and requester are on same campus
4. ✅ API query includes `LEFT JOIN offices requester_office`

### Issue: Can't distinguish office vs employee requests

**Solution:**
- Check `requester_office_id` field
- If NOT NULL → it's an office request
- Display office name using `requester_office_name`

---

## Next Steps for Enhancement

### Optional UI Improvements:

1. **Add visual indicator** for office requests
   ```html
   <?php if ($request['requester_office_id']): ?>
       <span class="badge bg-purple-100 text-purple-800">
           <i class="fas fa-building"></i> Office Request
       </span>
   <?php endif; ?>
   ```

2. **Add filter** to show only office requests
   ```javascript
   // In approve_requests.php
   <select id="requestTypeFilter">
       <option value="all">All Requests</option>
       <option value="employee">Employee Only</option>
       <option value="office">Office Only</option>
   </select>
   ```

3. **Show transfer note** in details modal
   ```
   ⚠️ This is a permanent transfer request. Asset will be assigned to the requesting office (no return expected).
   ```

---

## Summary

✅ **Office requests automatically appear** in custodian's "Approve Requests"
✅ **No additional configuration** needed
✅ **Filtered by API** based on `request_source='custodian'`
✅ **Distinguished by** `requester_office_name` field
✅ **Same approval flow** as employee requests (Custodian → Admin)
✅ **Final step different:** Auto-assigns to office instead of creating borrowing

---

**Date:** November 11, 2025
**Status:** ✅ Fully Functional
**API Updated:** Added `requester_office_name` to query
