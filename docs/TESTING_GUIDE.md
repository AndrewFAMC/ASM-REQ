# Testing Guide - AMS Enhancements

## 🧪 How to Test the New Features

This guide will help you test all the newly implemented features.

---

## 1. Testing the Notification System

### A. Test Notification Creation

1. **Access the test page:**
   - Open your browser and go to: `http://localhost/AMS-REQ/test_notifications.php`
   - You must be logged in as an **admin** user

2. **Create test notifications:**
   - Click any of the colored buttons to create different types of notifications:
     - 🔔 Return Reminder (Blue)
     - ⚠️ Overdue Alert (Red)
     - 📋 Approval Request (Yellow)
     - ✅ Approval Response (Green)
     - ❌ Missing Report (Pink)
     - ℹ️ System Alert (Indigo)

3. **Create multiple notifications:**
   - Click "Create 5 Sample Notifications" to create a batch

4. **Check the results:**
   - Look at the bell icon in the header - it should show a red badge with the count
   - Click the bell icon to see the notification dropdown
   - Verify notifications appear with correct colors and icons

### B. Test Notification Bell Icon

1. **View in different dashboards:**
   - Go to Employee Dashboard: `http://localhost/AMS-REQ/employee_dashboard.php`
   - Go to Custodian Dashboard: `http://localhost/AMS-REQ/custodian_dashboard.php`
   - Go to Office Dashboard: `http://localhost/AMS-REQ/office_dashboard.php`
   - Verify the bell icon appears in all dashboards

2. **Test notification interactions:**
   - Click on a notification in the dropdown
   - It should mark as read and the badge count should decrease
   - Test "Mark all as read" button

3. **Test auto-refresh:**
   - Keep a dashboard open
   - Create a notification from another tab
   - Wait up to 30 seconds - the badge should update automatically

### C. Test Full Notifications Page

1. **Access notifications page:**
   - Go to: `http://localhost/AMS-REQ/notifications.php`

2. **Test features:**
   - Verify statistics cards show correct counts
   - Test filters (type, priority, status)
   - Test search functionality
   - Click on notifications to view details
   - Test "Mark All Read" button
   - Test pagination (if you have 20+ notifications)

---

## 2. Testing the Approval Workflow

### A. Test Custodian Approval Dashboard

1. **Access as custodian:**
   - Login as a custodian user
   - Go to: `http://localhost/AMS-REQ/custodian/approve_requests.php`

2. **Verify dashboard displays:**
   - Statistics cards (Pending, Approved Today, Total)
   - Filter options (Status, Date Range, Search)
   - Request list with colored cards

3. **Test if you have requests:**
   - Click "View Details" on any request
   - Verify modal shows complete request information
   - Test "Quick Approve" button
   - Test "Approve" with comments
   - Test "Reject" with reason (required)

### B. Create Test Request (Manual SQL)

If you don't have any requests, create a test request using SQL:

```sql
-- Insert a test asset request
INSERT INTO asset_requests
(user_id, asset_id, campus_id, quantity, purpose, expected_return_date, status, created_at)
VALUES
(1, 1, 1, 1, 'For presentation in classroom', DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending', NOW());
```

Replace the IDs with actual values from your database:
- `user_id` - ID of the user making the request
- `asset_id` - ID of an asset in your database
- `campus_id` - Campus ID

### C. Test Approval Flow

1. **Create a request (as staff/employee)**
2. **Approve as custodian:**
   - Login as custodian
   - Go to approval dashboard
   - Find the pending request
   - Click "Approve"
   - Add optional comments
   - Submit

3. **Verify notification sent:**
   - The requester should receive a notification
   - Admin should receive a notification for next-level approval

4. **Test rejection:**
   - Create another request
   - Reject it with a reason
   - Verify requester gets rejection notification

---

## 3. Testing Database Changes

### A. Verify New Tables

Run this SQL to check all new tables exist:

```sql
SHOW TABLES LIKE 'notifications';
SHOW TABLES LIKE 'borrowing_chain';
SHOW TABLES LIKE 'asset_movement_logs';
SHOW TABLES LIKE 'missing_assets_reports';
SHOW TABLES LIKE 'department_approvers';
SHOW TABLES LIKE 'sms_notifications';
SHOW TABLES LIKE 'email_notifications';
SHOW TABLES LIKE 'system_settings';
```

### B. Verify New Columns

Check if new columns were added:

```sql
-- Check assets table
DESCRIBE assets;
-- Should see: original_value, current_value, depreciation_rate, last_depreciation_date

-- Check asset_borrowings table
DESCRIBE asset_borrowings;
-- Should see: actual_return_date, return_status, overdue_notification_sent

-- Check users table
SELECT DISTINCT role FROM users;
-- Should include 'auditor'
```

### C. Verify Views

Check if database views were created:

```sql
SHOW FULL TABLES WHERE Table_type = 'VIEW';
-- Should see: view_overdue_borrowings, view_assets_depreciation_status,
--             view_missing_assets_summary, view_department_asset_utilization
```

---

## 4. Testing Cron Job (Manual)

### Run the overdue check script manually:

```bash
# Windows (Command Prompt)
cd C:\xampp\htdocs\AMS-REQ\cron
C:\xampp\php\php.exe check_overdue_assets.php

# Linux/Mac
cd /path/to/AMS-REQ/cron
php check_overdue_assets.php
```

### Verify results:

1. Check the log file created in `logs/overdue_check_YYYY-MM-DD.log`
2. Check if any borrowings were marked as overdue
3. Check if notifications were created
4. Check if old notifications were cleaned up

---

## 5. Testing API Endpoints

### Test Notifications API

Use browser or Postman to test:

```
GET http://localhost/AMS-REQ/api/notifications.php?action=get_count
GET http://localhost/AMS-REQ/api/notifications.php?action=get_unread&limit=10
GET http://localhost/AMS-REQ/api/notifications.php?action=get_stats
```

### Test Requests API

```
GET http://localhost/AMS-REQ/api/requests.php?action=get_pending_requests
GET http://localhost/AMS-REQ/api/requests.php?action=get_request&request_id=1
```

---

## 6. Common Issues & Solutions

### Issue: Notification bell not showing
**Solution:**
- Clear browser cache
- Check if Font Awesome CSS is loading
- Verify user is logged in
- Check browser console for errors

### Issue: Notifications not appearing
**Solution:**
- Check if notifications exist in database: `SELECT * FROM notifications WHERE user_id = YOUR_USER_ID`
- Verify API endpoint is accessible
- Check browser console for JavaScript errors
- Verify CSRF token is present

### Issue: No requests in approval dashboard
**Solution:**
- Create a test request manually (see SQL above)
- Verify user has custodian role
- Check campus_id matches

### Issue: Cron job errors
**Solution:**
- Check PHP path is correct
- Verify database connection
- Check write permissions on logs directory
- Review error logs

---

## 7. Expected Results Checklist

After testing, you should be able to confirm:

- [ ] Bell icon appears in all dashboards
- [ ] Badge shows unread notification count
- [ ] Clicking bell opens dropdown with notifications
- [ ] Clicking notification marks it as read
- [ ] Badge count updates automatically
- [ ] Full notifications page works with filters
- [ ] Custodian can view pending requests
- [ ] Custodian can approve requests with comments
- [ ] Custodian can reject requests with reason
- [ ] Approval creates notification for next approver
- [ ] Rejection creates notification for requester
- [ ] Statistics update correctly
- [ ] All new database tables exist
- [ ] All new columns exist
- [ ] Database views are created
- [ ] Cron job executes without errors

---

## 8. Performance Testing

### Test with Multiple Notifications

1. Create 50+ notifications using the bulk test
2. Verify page loads quickly
3. Check if pagination works (20 per page)
4. Test filtering with large dataset

### Test Auto-Polling

1. Keep dashboard open for 5+ minutes
2. Create notifications from another window
3. Verify bell icon updates within 30 seconds
4. Check network tab for API calls

---

## 9. Browser Compatibility

Test in multiple browsers:
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari (if on Mac)
- [ ] Mobile browsers (responsive design)

---

## 10. Security Testing

### Test CSRF Protection

1. Try to submit approval without CSRF token
2. Should see "Invalid CSRF token" error

### Test Role-Based Access

1. Try accessing custodian dashboard as non-custodian
2. Should see "Access denied" error

### Test Session Validation

1. Logout and try to access protected pages
2. Should redirect to login

---

## 📊 Test Report Template

After testing, document your results:

```
Date: ___________
Tester: ___________

Feature: Notification System
Status: [ ] Pass [ ] Fail
Issues: ___________

Feature: Approval Workflow
Status: [ ] Pass [ ] Fail
Issues: ___________

Feature: Database Changes
Status: [ ] Pass [ ] Fail
Issues: ___________

Feature: Cron Jobs
Status: [ ] Pass [ ] Fail
Issues: ___________

Overall Assessment: ___________
```

---

**Happy Testing! 🎉**

If you encounter any issues, check:
1. Browser console for JavaScript errors
2. PHP error logs (`C:\xampp\php\logs\php_error_log` on Windows)
3. Database error logs
4. Network tab in browser dev tools

---

**Last Updated:** 2025-11-06
**Version:** 1.0
