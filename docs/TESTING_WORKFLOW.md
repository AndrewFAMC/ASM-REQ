# Complete Approval Workflow Testing Guide

## Quick Start

**Testing Dashboard:** `http://localhost/AMS-REQ/test_workflow.php`

This comprehensive guide will help you test the complete asset request and approval workflow system.

---

## Prerequisites

Before testing, ensure you have:

1. **Database Migration Applied** - All new tables and columns must exist
2. **Test Users Created** with different roles:
   - At least 1 employee/staff user
   - At least 1 custodian user
   - At least 1 admin user
3. **Available Assets** - At least 1 asset with status = 'Available'

### Check Prerequisites

Run the automated testing dashboard:
```
http://localhost/AMS-REQ/test_workflow.php
```

Click "Run All Tests" to verify:
- Database schema is correct
- Required users exist
- Available assets exist
- API endpoints are working

---

## Testing Workflow Step-by-Step

### Test 1: Employee Submits Request

**Login as:** Employee/Staff user

**Steps:**
1. Go to employee dashboard: `http://localhost/AMS-REQ/employee_dashboard.php`
2. Click the **"Request Asset"** button in the header (blue button)
3. Fill out the request form:
   - **Asset:** Select any available asset
   - **Quantity:** Enter 1 (or desired quantity)
   - **Purpose:** Enter detailed justification (min 10 characters)
   - **Expected Return Date:** Select a future date
4. Click **"Submit Request"**

**Expected Results:**
- ✓ Success message appears
- ✓ Request ID is generated
- ✓ Custodian receives notification (check notification bell)
- ✓ Request appears in "My Requests" with status "In Progress"
- ✓ Progress timeline shows "Submitted" as completed, "Custodian Review" as current

**Database Check:**
```sql
SELECT * FROM asset_requests ORDER BY created_at DESC LIMIT 1;
-- Should show: status = 'pending', requester_id = [your user id]

SELECT * FROM notifications WHERE type = 'approval_request' ORDER BY created_at DESC LIMIT 1;
-- Should show notification sent to custodian
```

---

### Test 2: Custodian Reviews and Approves

**Login as:** Custodian user

**Steps:**
1. Check notification bell - should show unread notification
2. Go to: `http://localhost/AMS-REQ/custodian/approve_requests.php`
3. Verify:
   - Statistics show "Pending Approval" count = 1 (or more)
   - Request card appears in the list
   - Status shows "Pending" with yellow border
4. Click **"View Details"** on the request
5. Review the request information in the modal
6. Click **"Approve"** button
7. Add optional comments (e.g., "Approved for classroom use")
8. Click **"Approve"** in the modal

**Expected Results:**
- ✓ Success message: "Request approved"
- ✓ Request disappears from pending list (or status changes)
- ✓ Admin receives notification
- ✓ Employee notification created
- ✓ Request status updated to 'approved_custodian'

**Database Check:**
```sql
SELECT status FROM asset_requests WHERE id = [request_id];
-- Should show: status = 'approved_custodian'

SELECT * FROM approval_history WHERE request_id = [request_id];
-- Should show custodian approval record

SELECT * FROM notifications WHERE type = 'approval_request' AND user_id = [admin_id] ORDER BY created_at DESC LIMIT 1;
-- Should show notification sent to admin
```

**Alternative Test - Rejection:**
- Click "Reject" instead of "Approve"
- Enter rejection reason (required)
- Verify employee receives rejection notification
- Check status changes to 'rejected'

---

### Test 3: Admin Final Approval

**Login as:** Admin user

**Steps:**
1. Check notification bell - should show notification from custodian approval
2. Go to: `http://localhost/AMS-REQ/admin/approve_requests.php`
3. Verify:
   - Statistics show "Pending Approval" count
   - Request shows status "Awaiting Final Approval"
   - Yellow badge on request card
4. Click **"View Details"**
5. Review complete approval chain:
   - Should show "Submitted by employee"
   - Should show "Approved by custodian" with comments
6. Click **"Approve & Release Asset"**
7. Add optional comments (e.g., "Final approval granted")
8. Submit approval

**Expected Results:**
- ✓ Success message: "Request approved and asset released"
- ✓ Request removed from pending list
- ✓ Employee receives approval notification
- ✓ Asset borrowing record created
- ✓ Request status = 'approved'

**Database Check:**
```sql
SELECT status FROM asset_requests WHERE id = [request_id];
-- Should show: status = 'approved'

SELECT * FROM approval_history WHERE request_id = [request_id];
-- Should show 2 records: custodian + admin approvals

SELECT * FROM asset_borrowings WHERE request_id = [request_id];
-- Should show borrowing record created

SELECT * FROM notifications WHERE type = 'approval_response' AND user_id = [employee_id] ORDER BY created_at DESC LIMIT 1;
-- Should show approval notification to employee
```

---

### Test 4: Employee Tracks Progress

**Login as:** Employee (original requester)

**Steps:**
1. Check notification bell - should have approval notification
2. Click notification - should redirect to My Requests
3. Go to: `http://localhost/AMS-REQ/my_requests.php`
4. Verify visual progress timeline:
   - All 4 steps should show as "completed" (green checkmarks)
   - Timeline progress bar at 100%
   - Status badge shows "Approved" (green)
5. Click **"View Full Details"**
6. Review approval history in modal

**Expected Results:**
- ✓ Progress timeline fully completed
- ✓ All approval steps shown with timestamps
- ✓ Custodian and admin comments visible
- ✓ Status shows "Ready for Collection"

---

## Additional Tests

### Test 5: Bulk Approval (Admin Only)

1. Create multiple requests (repeat Test 1 multiple times)
2. Have custodian approve them all
3. Login as admin
4. Go to admin approval dashboard
5. Check multiple requests using checkboxes
6. Click "Bulk Approve Selected"
7. Verify all selected requests are approved

### Test 6: Request Rejection Flow

**At Custodian Level:**
1. Employee submits request
2. Custodian clicks "Reject"
3. Enters rejection reason
4. Verify:
   - Employee receives rejection notification
   - Request shows red "Rejected" badge
   - Rejection reason displayed in My Requests

**At Admin Level:**
1. Employee submits request
2. Custodian approves
3. Admin clicks "Reject"
4. Enter rejection reason
5. Verify same outcomes as above

### Test 7: Notification System

1. Go to: `http://localhost/AMS-REQ/test_notifications.php`
2. Create different notification types
3. Check bell icon updates
4. Click notifications in dropdown
5. Verify:
   - Badge count updates
   - Clicking marks as read
   - "Mark all as read" works
6. Go to: `http://localhost/AMS-REQ/notifications_new.php`
7. Verify all notifications display correctly

### Test 8: Filter and Search

**Custodian Dashboard:**
- Test status filter (Pending, Approved, Rejected)
- Test search by asset name or requester
- Verify auto-refresh (wait 30 seconds)

**Admin Dashboard:**
- Same filters as custodian
- Test bulk selection
- Verify approval history display

---

## Common Issues & Solutions

### Issue: "No available assets"
**Solution:**
```sql
-- Create a test asset
INSERT INTO assets (asset_name, asset_code, campus_id, status, created_at)
VALUES ('Test Laptop', 'TEST-001', 1, 'Available', NOW());
```

### Issue: "No custodian found for campus"
**Solution:**
```sql
-- Create custodian user or update existing user
UPDATE users SET role = 'custodian', campus_id = 1 WHERE id = [user_id];
```

### Issue: "Invalid CSRF token"
**Solution:**
- Clear browser cookies
- Logout and login again
- Check that sessions are working

### Issue: "Notification not appearing"
**Solution:**
- Check notifications table: `SELECT * FROM notifications WHERE user_id = [user_id]`
- Verify notification API: `http://localhost/AMS-REQ/api/notifications.php?action=get_count`
- Clear browser cache
- Check browser console for JavaScript errors

### Issue: "Request not showing in approval dashboard"
**Solution:**
- Verify request status matches filter
- Check campus_id matches user's campus
- Look at requests API response for errors

### Issue: Column name mismatch (user_id vs requester_id)
**Solution:**
The system handles both column names. If you get SQL errors:
```sql
-- Check which column exists
DESCRIBE asset_requests;

-- If using requester_id, the code will auto-detect and use it
-- No changes needed
```

---

## Test Checklist

Use this checklist to track your testing progress:

### Database & Setup
- [ ] All required tables exist
- [ ] Employee user created
- [ ] Custodian user created
- [ ] Admin user created
- [ ] At least 1 available asset exists

### Request Submission
- [ ] Request form loads correctly
- [ ] Asset dropdown shows available assets
- [ ] Form validation works (future date, min purpose length)
- [ ] Request submits successfully
- [ ] Custodian receives notification

### Custodian Approval
- [ ] Approval dashboard loads
- [ ] Pending requests display
- [ ] Request details modal works
- [ ] Approve with comments works
- [ ] Reject with reason works
- [ ] Admin receives notification after approval

### Admin Approval
- [ ] Admin dashboard loads
- [ ] Requests pending final approval display
- [ ] Complete approval chain visible
- [ ] Final approval works
- [ ] Asset borrowing record created
- [ ] Employee receives approval notification

### Progress Tracking
- [ ] My Requests page loads
- [ ] Visual timeline displays correctly
- [ ] All approval stages show
- [ ] Progress updates in real-time
- [ ] Approval history modal works

### Notifications
- [ ] Bell icon shows unread count
- [ ] Dropdown displays recent notifications
- [ ] Clicking notification marks as read
- [ ] Mark all as read works
- [ ] Auto-refresh updates badge
- [ ] Full notifications page works

### Edge Cases
- [ ] Multiple simultaneous requests
- [ ] Bulk approval
- [ ] Rejection at each level
- [ ] Expired/old requests
- [ ] Cross-campus restrictions

---

## Performance Testing

### Load Test
1. Create 50+ test requests
2. Verify dashboards load quickly
3. Check pagination works
4. Test filtering with large dataset

### Concurrent Users
1. Have multiple users submit requests simultaneously
2. Verify no race conditions
3. Check approval queue integrity

---

## Security Testing

### CSRF Protection
- Try submitting without CSRF token - should fail
- Try reusing old CSRF token - should fail

### Role-Based Access
- Try accessing custodian dashboard as employee - should deny
- Try accessing admin dashboard as custodian - should deny

### Session Validation
- Logout and try accessing protected pages - should redirect
- Try manipulating session data - should be detected

---

## Clean Up Test Data

After testing, clean up:

**Using Test Dashboard:**
```
http://localhost/AMS-REQ/test_workflow.php
```
Click "Clean Test Data" to remove test notifications and requests from last hour.

**Manual SQL:**
```sql
-- Delete test notifications
DELETE FROM notifications WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Delete test requests (adjust as needed)
DELETE FROM asset_requests WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND status IN ('pending', 'rejected');

-- Delete test approval history
DELETE FROM approval_history WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

---

## Reporting Issues

If you encounter bugs, document:
1. **Steps to reproduce**
2. **Expected behavior**
3. **Actual behavior**
4. **Browser console errors** (F12 → Console tab)
5. **Database state** (relevant SQL queries)
6. **Screenshots** if applicable

Check:
- Browser: Chrome DevTools Console (F12)
- PHP Errors: `C:\xampp\php\logs\php_error_log`
- MySQL Errors: Check query results
- Network: DevTools → Network tab for API responses

---

## Success Criteria

The workflow is working correctly when:

✓ Employee can submit request
✓ Custodian receives notification
✓ Custodian can approve/reject
✓ Admin receives notification after custodian approval
✓ Admin can give final approval
✓ Employee receives final approval notification
✓ Progress timeline updates at each stage
✓ Approval history is complete
✓ Asset borrowing record created after approval
✓ No SQL errors or JavaScript errors
✓ All CRUD operations work
✓ Role-based access control enforced
✓ CSRF protection active

---

**Last Updated:** 2025-11-06
**Version:** 1.0

For additional help, check the implementation docs at `docs/IMPLEMENTATION_SUMMARY.md`
