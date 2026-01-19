# Email Notification System - Office Requests

## Issue Fixed
**Problem**: When employees request assets from offices, the office users were NOT receiving email notifications - only in-app notifications.

**Solution**: Added email queue integration to send email notifications to office users.

---

## What Was Changed

### File Modified: `api/requests.php`

#### 1. Added EmailQueue Include (Line 14)
```php
require_once dirname(__DIR__) . '/includes/email/EmailQueue.php';
```

#### 2. Enhanced Office Request Notification (Lines 965-1022)
**Before**: Only created in-app notification
**After**: Creates both in-app notification AND queues email

**New Email Features**:
- Subject: "New Asset Request from [Requester Name]"
- Contains full request details:
  - Requester name and email
  - Asset name and quantity
  - Purpose
  - Expected return date
  - Request ID
- "Review Request" button linking to approval page
- High priority in email queue

---

## Email Content Example

```
Subject: New Asset Request from John Doe

New Office Asset Request
You have received a new asset request that requires your approval.

Request Details:
• Requester: John Doe (john.doe@example.com)
• Asset: Test Chair
• Quantity: 5
• Purpose: Office meeting room
• Expected Return: 2025-01-20
• Request ID: #42

Action Required: Please log in to review and approve or reject this request.

[Review Request Button]
```

---

## How It Works

### Flow:
1. **Employee** submits asset request from office inventory
2. **System** creates request in database with status = `office_review`
3. **System** finds all office users for that office
4. **For each office user**:
   - ✅ Creates in-app notification (shows in notification bell)
   - ✅ **NEW**: Queues email to `email_queue` table
5. **Email Worker** processes queue and sends emails

### Email Queue Process:
- Emails are queued with `priority = 'high'`
- Background worker (`cron/process_email_queue.php`) sends emails
- Automatic retry on failure (max 3 attempts)
- Tracks sent/failed status

---

## Email Worker Status

### Check if emails are pending:
```sql
SELECT COUNT(*) FROM email_queue WHERE status = 'pending';
```

### Start Email Worker:
**Option 1 - Run manually:**
```bash
cd c:\xampp\htdocs\AMS-REQ
php cron\process_email_queue.php
```

**Option 2 - Run as scheduled task:**
See: `tests/setup_task_scheduler.bat`

**Option 3 - Web-based worker:**
```bash
cd c:\xampp\htdocs\AMS-REQ\tests
run_email_worker.bat
```

---

## Testing

### Test Email Notification:
1. **Login as Employee** (role: employee, staff, or custodian)
2. **Go to**: Request Assets page
3. **Select**: Request from office (not custodian)
4. **Choose**: An office that has assets
5. **Fill**: Request details
6. **Submit**: Request

### Expected Results:
✅ Request created with status = `office_review`
✅ In-app notification appears for office user
✅ **Email queued** in `email_queue` table
✅ Office user receives email (when worker runs)

### Verify Email Queued:
```sql
SELECT * FROM email_queue
WHERE related_type = 'request'
ORDER BY created_at DESC
LIMIT 1;
```

### Check Email Was Sent:
```sql
SELECT * FROM email_queue
WHERE status = 'sent'
AND related_type = 'request'
ORDER BY sent_at DESC
LIMIT 5;
```

---

## Email Configuration

Email settings are in: `includes/email/EmailConfig.php`

**SMTP Settings**:
- Host: smtp.gmail.com
- Port: 587 (TLS)
- Username: Your Gmail address
- Password: App password (not regular password)

**Queue Settings**:
- Batch size: 10 emails per batch
- Sleep duration: 30 seconds between batches
- Max attempts: 3 retries

---

## Other Notification Types

This same email queue system is used for:
- ✅ Custodian approval requests
- ✅ Department head approvals
- ✅ Admin final approvals
- ✅ Missing asset reports
- ✅ Overdue reminders
- ✅ Return reminders

**Current Fix**: Office request notifications
**Status**: All notification types use the queue system

---

## Troubleshooting

### Problem: Emails not being sent
**Check**:
1. Is email worker running?
2. Are emails in queue? `SELECT * FROM email_queue WHERE status = 'pending'`
3. Check SMTP configuration in `EmailConfig.php`
4. Check error logs: `includes/logs/` or PHP error log

### Problem: Office user not receiving email
**Check**:
1. Office user exists? `SELECT * FROM users WHERE role = 'office' AND office_id = ?`
2. Office user is active? `is_active = TRUE`
3. Office user has valid email? Check `email` field
4. Email address correct? Verify in database

### Problem: Emails stuck in "pending"
**Solution**:
- Restart email worker: `php cron/process_email_queue.php`
- Check failed emails: `SELECT * FROM email_queue WHERE status = 'failed'`
- Review error messages in `last_error` column

---

## Summary

**Status**: ✅ **FIXED**

**What changed**: Office users now receive **email notifications** when assets are requested from their office inventory.

**Files modified**:
- `api/requests.php` - Added email queue integration

**Testing**: Ready to test with real office request workflow

**Next steps**:
1. Ensure email worker is running
2. Test full workflow with office request
3. Verify email is received

---

**Last Updated**: 2025-01-12
**Fixed By**: Claude Code Assistant
