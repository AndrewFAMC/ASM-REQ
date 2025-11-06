# Automated Cron Jobs Setup Guide

This directory contains automated scripts that should run periodically to maintain system functionality.

## Scripts

### 1. `check_overdue_assets.php`
**Purpose:** Checks for overdue borrowings, sends reminder notifications, and marks items as missing

**Features:**
- Detects and updates overdue borrowings
- Sends return reminders (2 days before due date)
- Sends urgent overdue alerts
- Auto-marks items as "Missing" after X days overdue
- Cleans up old notifications
- Generates daily log files

**Recommended Schedule:** Daily at midnight (00:00)

---

## Setup Instructions

### Option 1: Windows Task Scheduler (XAMPP)

#### Step 1: Open Task Scheduler
1. Press `Win + R`
2. Type `taskschd.msc` and press Enter

#### Step 2: Create Basic Task
1. Click **"Create Basic Task"** in the right panel
2. Name: `AMS Overdue Check`
3. Description: `Daily check for overdue assets and send notifications`
4. Click **Next**

#### Step 3: Set Trigger
1. Select **"Daily"**
2. Start date: Today
3. Start time: `12:00:00 AM` (midnight)
4. Recur every: `1` days
5. Click **Next**

#### Step 4: Set Action
1. Select **"Start a program"**
2. Program/script: `C:\xampp\php\php.exe`
3. Add arguments: `"C:\xampp\htdocs\AMS-REQ\cron\check_overdue_assets.php"`
4. Click **Next** → **Finish**

#### Step 5: Advanced Settings (Optional)
1. Right-click the task → **Properties**
2. **General** tab:
   - Check: "Run whether user is logged on or not"
   - Check: "Run with highest privileges"
3. **Settings** tab:
   - Check: "Run task as soon as possible after a scheduled start is missed"

---

### Option 2: Manual/Testing Run

#### Via Command Line
```cmd
cd C:\xampp\htdocs\AMS-REQ\cron
C:\xampp\php\php.exe check_overdue_assets.php
```

#### Via Web Browser (For Testing Only)
```
http://localhost/AMS-REQ/cron/check_overdue_assets.php?manual_run=1
```

---

### Option 3: Linux/Mac Crontab

```bash
# Edit crontab
crontab -e

# Add this line (runs daily at midnight)
0 0 * * * /usr/bin/php /path/to/AMS-REQ/cron/check_overdue_assets.php

# Or with logging
0 0 * * * /usr/bin/php /path/to/AMS-REQ/cron/check_overdue_assets.php >> /path/to/logs/cron.log 2>&1
```

---

## Configuration

### System Settings (via database or admin panel)

| Setting Key | Default | Description |
|------------|---------|-------------|
| `overdue_check_enabled` | `true` | Enable/disable overdue checking |
| `reminder_days_before` | `2` | Days before due date to send reminder |
| `auto_missing_after_days` | `60` | Days overdue before marking as missing |
| `enable_email_notifications` | `true` | Send email notifications |
| `enable_sms_notifications` | `false` | Send SMS notifications |

---

## Logs

Logs are stored in: `C:\xampp\htdocs\AMS-REQ\logs\`

**Log files format:** `overdue_check_YYYY-MM-DD.log`

### Sample Log Output:
```
[2025-11-06 00:00:01] ========================================
[2025-11-06 00:00:01] Starting Overdue Assets Check
[2025-11-06 00:00:01] ========================================
[2025-11-06 00:00:01] Step 1: Checking for overdue borrowings...
[2025-11-06 00:00:02] - Updated 5 borrowing(s) to overdue status
[2025-11-06 00:00:02] Step 2: Sending return reminders...
[2025-11-06 00:00:03] - Sent 8 reminder notification(s)
[2025-11-06 00:00:03] Step 3: Sending overdue alert notifications...
[2025-11-06 00:00:04] - Sent 5 overdue alert(s)
[2025-11-06 00:00:04] Step 4: Checking for assets that should be marked as missing...
[2025-11-06 00:00:04] - Marked 1 asset(s) as missing
[2025-11-06 00:00:04] Step 5: Cleaning up old notifications...
[2025-11-06 00:00:04] - Cleaned up old notifications
[2025-11-06 00:00:04] ========================================
[2025-11-06 00:00:04] Summary:
[2025-11-06 00:00:04] - Overdue items detected: 5
[2025-11-06 00:00:04] - Return reminders sent: 8
[2025-11-06 00:00:04] - Overdue alerts sent: 5
[2025-11-06 00:00:04] - Assets marked as missing: 1
[2025-11-06 00:00:04] - Execution time: 3.42s
[2025-11-06 00:00:04] ========================================
[2025-11-06 00:00:04] Overdue check completed successfully!
```

---

## Troubleshooting

### Script Not Running?
1. **Check PHP path:**
   ```cmd
   C:\xampp\php\php.exe -v
   ```

2. **Test manually:**
   ```cmd
   cd C:\xampp\htdocs\AMS-REQ\cron
   C:\xampp\php\php.exe check_overdue_assets.php
   ```

3. **Check Task Scheduler history:**
   - Task Scheduler → View → Show All Running Tasks
   - Check the task's **History** tab

### Permission Errors?
- Ensure the `logs` directory is writable
- Run Task Scheduler with administrator privileges

### No Notifications Sent?
- Check `system_settings` table for correct configuration
- Verify users have valid email addresses
- Check the logs for error messages

---

## Additional Cron Jobs (Future)

You can add more automated tasks in this directory:

- `send_email_reminders.php` - Email-specific reminders
- `generate_reports.php` - Daily/weekly automated reports
- `backup_database.php` - Automated database backups
- `calculate_depreciation.php` - Monthly depreciation calculations

---

## Monitoring

### Check Last Run
Query the logs directory or check `activity_log` table for automated actions.

### Email Notifications on Failure
You can modify the scripts to send admin emails if errors occur.

---

## Support

For issues or questions:
1. Check the log files in `/logs`
2. Test the script manually first
3. Verify system settings in the database
4. Check PHP error logs: `C:\xampp\php\logs\php_error_log`

---

**Created:** 2025-11-06
**Last Updated:** 2025-11-06
**Version:** 1.0
