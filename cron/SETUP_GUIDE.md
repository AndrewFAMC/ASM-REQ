# Asset Notification System - Setup Guide

## Overview

This automated notification system sends email alerts to employees for:
1. **2-Day Warning**: Email notification 2 days before asset is due for return
2. **Overdue Alert**: Daily email notifications for assets past their return date

---

## Files Created

1. **`asset_notification_scheduler.php`** - Main scheduler script that checks and sends notifications
2. **`run_scheduler.bat`** - Windows batch file to run the scheduler
3. **`test_notifications.php`** - Test script for manual testing
4. **`SETUP_GUIDE.md`** - This setup guide

---

## Database Changes

Added new column to `asset_requests` table:
- `two_day_reminder_sent` (TINYINT) - Tracks if 2-day warning was sent

---

## Setup Instructions

### Option 1: Manual Testing (Recommended First)

1. **Test the system manually** to ensure it works:
   ```
   cd C:\xampp\htdocs\AMS-REQ\cron
   php test_notifications.php
   ```

2. **Or test via browser**:
   ```
   http://localhost/AMS-REQ/cron/test_notifications.php?secret=hcc_cron_2024
   ```

3. **Follow the prompts**:
   - Option 1: Create test data
   - Option 2: Run scheduler manually
   - Option 3: View notification history
   - Option 4: Reset reminder flags

---

### Option 2: Automate with Windows Task Scheduler

#### Step-by-Step Instructions:

1. **Open Windows Task Scheduler**:
   - Press `Win + R`
   - Type `taskschd.msc` and press Enter

2. **Create a New Task**:
   - Click "Create Basic Task" in the right panel
   - Name: `HCC Asset Notification Scheduler`
   - Description: `Sends email notifications for asset returns and overdue items`

3. **Set Trigger**:
   - Select "Daily"
   - Start date: Today
   - Time: `08:00 AM` (recommended)
   - Recur every: `1 day`

4. **Set Action**:
   - Action: "Start a program"
   - Program/script: `C:\xampp\htdocs\AMS-REQ\cron\run_scheduler.bat`
   - Click "Next" and "Finish"

5. **Configure Advanced Settings** (Optional but recommended):
   - Right-click the task and select "Properties"
   - Go to "Settings" tab
   - Check "Run task as soon as possible after a scheduled start is missed"
   - Uncheck "Stop the task if it runs longer than: 3 days"
   - Click "OK"

---

### Option 3: Web-based Cron (Alternative)

If you can't use Windows Task Scheduler, you can set up a web-based cron service:

1. **Use a cron service** like:
   - https://cron-job.org
   - https://www.easycron.com

2. **Configure the cron job**:
   - URL: `http://your-domain.com/AMS-REQ/cron/asset_notification_scheduler.php?secret=hcc_cron_2024`
   - Schedule: Daily at 8:00 AM
   - Method: GET

**Note**: This requires your application to be accessible from the internet.

---

## How It Works

### 1. Two-Day Warning System

- **Checks**: Items with `status = 'released'` and `expected_return_date = today + 2 days`
- **Condition**: Only sends if `two_day_reminder_sent = 0`
- **Actions**:
  - Creates in-app notification
  - Sends email using template `getReturnReminderTemplate()`
  - Sets `two_day_reminder_sent = 1`

### 2. Overdue Alert System

- **Checks**: Items with `status = 'released'` and `expected_return_date < today`
- **Actions**:
  - Creates urgent in-app notification (daily)
  - Sends email using template `getOverdueAlertTemplate()`
  - Sets `reminder_sent = 1` (first time only)

### 3. Email Preferences

Users can control their email notifications via:
- **User Preferences**: `notification_preferences` column in `users` table
- **Disable Types**: Users can disable specific notification types
- **Email Toggle**: Users can turn off all email notifications

---

## Testing Checklist

### Before Going Live:

- [ ] Test with sample data using `test_notifications.php`
- [ ] Verify emails are being sent correctly
- [ ] Check email templates look good in Gmail/Outlook
- [ ] Verify 2-day warnings are sent only once
- [ ] Verify overdue alerts work for multiple days
- [ ] Check logs are being created in `/logs/` folder
- [ ] Test with actual user accounts
- [ ] Verify Windows Task Scheduler is running the task

### Testing Commands:

```bash
# Create test data and run scheduler
cd C:\xampp\htdocs\AMS-REQ\cron
php test_notifications.php

# Run scheduler manually
php asset_notification_scheduler.php

# Or via browser
http://localhost/AMS-REQ/cron/test_notifications.php?secret=hcc_cron_2024&action=2
```

---

## Monitoring and Logs

### Log Files

Logs are stored in: `C:\xampp\htdocs\AMS-REQ\logs/`

Example log file: `notification_scheduler_2025-01-08.log`

### What's Logged:

- Number of 2-day reminders sent
- Number of overdue alerts sent
- Any errors encountered
- Execution time and completion status

### Checking Logs:

```bash
# View today's log
type C:\xampp\htdocs\AMS-REQ\logs\notification_scheduler_2025-01-08.log

# Or open in notepad
notepad C:\xampp\htdocs\AMS-REQ\logs\notification_scheduler_2025-01-08.log
```

---

## Troubleshooting

### Emails Not Sending

1. **Check SMTP credentials** in `includes/email_functions.php`:
   - Username: `mico.macapugay2004@gmail.com`
   - Password: App Password (check if still valid)

2. **Check Gmail settings**:
   - Enable "Less secure app access" OR use App Password
   - Check if Gmail is blocking automated emails

3. **Check logs** for error messages:
   ```bash
   type C:\xampp\htdocs\AMS-REQ\logs\notification_scheduler_YYYY-MM-DD.log
   ```

### Task Scheduler Not Running

1. **Check task history**:
   - Open Task Scheduler
   - Find your task
   - Click "History" tab
   - Look for errors

2. **Run manually** to test:
   - Right-click the task
   - Click "Run"
   - Check if it executes

3. **Check permissions**:
   - Task should run with your user account
   - Or run as Administrator

### No Notifications Created

1. **Check database**:
   ```sql
   SELECT * FROM notifications
   WHERE type IN ('return_reminder', 'overdue_alert')
   ORDER BY created_at DESC
   LIMIT 10;
   ```

2. **Check asset_requests**:
   ```sql
   SELECT id, status, expected_return_date, two_day_reminder_sent, reminder_sent
   FROM asset_requests
   WHERE status = 'released';
   ```

3. **Verify users are active**:
   ```sql
   SELECT id, full_name, email, is_active
   FROM users
   WHERE id IN (SELECT requester_id FROM asset_requests WHERE status = 'released');
   ```

---

## Customization

### Change Reminder Days

To change from 2 days to 3 days before:

1. Edit `asset_notification_scheduler.php`
2. Find line: `$twoDayReminderDate = date('Y-m-d', strtotime('+2 days'));`
3. Change to: `$twoDayReminderDate = date('Y-m-d', strtotime('+3 days'));`

### Change Schedule Time

1. Open Windows Task Scheduler
2. Right-click the task
3. Properties → Triggers → Edit
4. Change the time

### Disable Specific Notification Types

Users can disable notifications in their profile:
- Go to Profile Settings
- Notification Preferences
- Toggle email notifications on/off

---

## Security Notes

### Secret Key

The scheduler uses a secret key (`hcc_cron_2024`) for web access:
- Change this in both files if needed
- Found in: `asset_notification_scheduler.php` and `test_notifications.php`

### Recommended Security:

1. **Block web access** to `/cron/` folder via `.htaccess`:
   ```apache
   Order Deny,Allow
   Deny from all
   Allow from 127.0.0.1
   ```

2. **Use Windows Task Scheduler** instead of web cron for better security

---

## Support

For issues or questions:
1. Check logs in `/logs/` folder
2. Run test script to diagnose issues
3. Check email templates in `includes/email_templates.php`
4. Verify SMTP settings in `includes/email_functions.php`

---

## Version History

- **v1.0** (2025-01-08): Initial release
  - 2-day warning notifications
  - Overdue alert notifications
  - Windows Task Scheduler integration
  - Comprehensive testing tools

---

**Last Updated**: January 8, 2025
