# Quick Start Guide - Asset Notification System

## 🚀 Quick Test (5 minutes)

### Step 1: Test via Browser
Open your browser and go to:
```
http://localhost/AMS-REQ/cron/test_notifications.php?secret=hcc_cron_2024
```

### Step 2: Create Test Data
- Click or enter option `1`
- This creates sample assets due in 2 days and overdue items

### Step 3: Run Scheduler
- Go back to the test page
- Click or enter option `2`
- Check if emails are sent

### Step 4: Check Results
- View your email inbox for test notifications
- Check logs at: `C:\xampp\htdocs\AMS-REQ\logs\`

---

## ⚙️ Setup Automation (10 minutes)

### Windows Task Scheduler Setup

1. **Open Task Scheduler**
   - Press `Win + R`
   - Type `taskschd.msc`
   - Press Enter

2. **Create Task**
   - Click "Create Basic Task"
   - Name: `HCC Asset Notifications`
   - Description: `Daily asset notification emails`
   - Click Next

3. **Set Trigger**
   - Select "Daily"
   - Time: `08:00 AM`
   - Click Next

4. **Set Action**
   - Select "Start a program"
   - Program: `C:\xampp\htdocs\AMS-REQ\cron\run_scheduler.bat`
   - Click Next, then Finish

5. **Test It**
   - Right-click the task
   - Click "Run"
   - Check logs folder

---

## 📧 What Gets Sent

### 2-Day Warning Email
- **When**: 2 days before return date
- **Who**: Employee with borrowed asset
- **Subject**: "Asset Return Reminder - 2 Days Left"
- **Sent**: Once per asset

### Overdue Alert Email
- **When**: Every day after due date
- **Who**: Employee with overdue asset
- **Subject**: "URGENT: Overdue Asset Return"
- **Sent**: Daily until returned

---

## 📊 Monitoring

### Check Today's Log
```bash
notepad C:\xampp\htdocs\AMS-REQ\logs\notification_scheduler_2025-11-08.log
```

### View Recent Notifications
```
http://localhost/AMS-REQ/cron/test_notifications.php?secret=hcc_cron_2024&action=3
```

### Database Check
```sql
-- See all active released assets
SELECT * FROM asset_requests WHERE status = 'released';

-- See recent notifications
SELECT * FROM notifications
WHERE type IN ('return_reminder', 'overdue_alert')
ORDER BY created_at DESC
LIMIT 10;
```

---

## 🔧 Common Tasks

### Manually Run Scheduler
```bash
cd C:\xampp\htdocs\AMS-REQ\cron
php asset_notification_scheduler.php
```

### Reset Test Data
```
http://localhost/AMS-REQ/cron/test_notifications.php?secret=hcc_cron_2024&action=4
```

### Change Email Settings
Edit: `includes/email_functions.php`
- Line 22-23: SMTP credentials

---

## ❓ Troubleshooting

### No emails received?
1. Check spam/junk folder
2. Verify Gmail App Password is valid
3. Check error logs in `/logs/` folder

### Task not running?
1. Check Windows Task Scheduler history
2. Run batch file manually
3. Check PHP path in batch file

### Need help?
See full guide: `SETUP_GUIDE.md`

---

## 📁 Important Files

| File | Purpose |
|------|---------|
| `asset_notification_scheduler.php` | Main scheduler script |
| `run_scheduler.bat` | Windows batch file |
| `test_notifications.php` | Testing tool |
| `SETUP_GUIDE.md` | Detailed documentation |
| `../logs/` | Log files directory |

---

**Created**: January 8, 2025
**System**: HCC Asset Management
**Version**: 1.0
