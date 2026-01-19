# Windows Task Scheduler Setup Guide
## HCC Asset Management - Automated Email Reminders

This guide will help you set up the automated reminder system to run daily.

---

## 📋 Prerequisites

Before setting up the task scheduler, verify:

1. ✅ PHP is installed at: `C:\xampp\php\php.exe`
2. ✅ Cron script exists at: `C:\xampp\htdocs\AMS-REQ\cron\check_overdue_assets.php`
3. ✅ Email functions are working (test emails sent successfully)

---

## 🚀 Step-by-Step Setup

### Step 1: Open Task Scheduler

**Method 1 (Quick):**
1. Press `Win + R`
2. Type: `taskschd.msc`
3. Press Enter

**Method 2 (Start Menu):**
1. Click Start
2. Search for "Task Scheduler"
3. Click on "Task Scheduler" app

---

### Step 2: Create New Task

1. In Task Scheduler, click **"Create Task"** (NOT "Create Basic Task")
   - Location: Right sidebar under "Actions"

---

### Step 3: General Tab Settings

Configure the following:

| Setting | Value |
|---------|-------|
| **Name** | `HCC Asset Return Reminders` |
| **Description** | `Automated email reminders for asset returns and overdue alerts` |
| **Security Options** | |
| → When running the task | ☑️ **Run whether user is logged on or not** |
| → Run with highest privileges | ☑️ **Checked** |
| **Configure for** | Windows 10 (or your Windows version) |

![General Tab Settings]

---

### Step 4: Triggers Tab Settings

1. Click **"New..."** button

2. Configure trigger:

| Setting | Value |
|---------|-------|
| **Begin the task** | `On a schedule` |
| **Settings** | `Daily` |
| **Start** | Today's date at `08:00:00 AM` |
| **Recur every** | `1 days` |
| **Advanced settings** | |
| → Enabled | ☑️ **Checked** |

3. Click **"OK"**

**Recommended Schedule:**
- **8:00 AM** - Send morning reminders before work hours
- **Alternative:** 6:00 AM for very early notifications

---

### Step 5: Actions Tab Settings

1. Click **"New..."** button

2. Configure action:

| Setting | Value |
|---------|-------|
| **Action** | `Start a program` |
| **Program/script** | `C:\xampp\php\php.exe` |
| **Add arguments** | `"C:\xampp\htdocs\AMS-REQ\cron\check_overdue_assets.php"` |
| **Start in** | `C:\xampp\htdocs\AMS-REQ` |

⚠️ **IMPORTANT:**
- Use **quotes** around the file path in arguments
- Make sure the paths match your XAMPP installation

3. Click **"OK"**

---

### Step 6: Conditions Tab Settings

**Uncheck these to ensure task runs reliably:**

| Setting | Recommended |
|---------|-------------|
| ☐ Start the task only if the computer is on AC power | **UNCHECKED** |
| ☐ Stop if the computer switches to battery power | **UNCHECKED** |
| ☑️ Wake the computer to run this task | **CHECKED** (optional) |

---

### Step 7: Settings Tab

Configure these options:

| Setting | Recommended |
|---------|-------------|
| ☑️ Allow task to be run on demand | **CHECKED** |
| ☑️ Run task as soon as possible after a scheduled start is missed | **CHECKED** |
| ☑️ If the task fails, restart every | `1 minute` (up to `3 times`) |
| ☐ Stop the task if it runs longer than | **UNCHECKED** (or set to 1 hour) |

---

### Step 8: Save the Task

1. Click **"OK"** at the bottom
2. **Enter your Windows password** when prompted
   - This is required for tasks that run when user is not logged on
3. Task will be created and saved

---

## ✅ Testing the Task

### Test 1: Manual Run

1. In Task Scheduler, find your task in the list
2. Right-click on `HCC Asset Return Reminders`
3. Select **"Run"**
4. Check the **"Last Run Result"** column
   - Should show: `The operation completed successfully. (0x0)`

### Test 2: Check Logs

1. Navigate to: `C:\xampp\htdocs\AMS-REQ\logs\`
2. Open the latest log file: `overdue_check_YYYY-MM-DD.log`
3. Verify output shows:
   ```
   [YYYY-MM-DD HH:MM:SS] Starting Overdue Assets Check
   [YYYY-MM-DD HH:MM:SS] Step 1: Checking for overdue borrowings...
   [YYYY-MM-DD HH:MM:SS] Step 2: Sending return reminders...
   [YYYY-MM-DD HH:MM:SS] Overdue check completed successfully!
   ```

### Test 3: Verify Email Sent

1. Check the database:
   ```sql
   SELECT action, description, created_at
   FROM activity_log
   WHERE action IN ('EMAIL_SENT', 'EMAIL_FAILED')
   ORDER BY created_at DESC
   LIMIT 10;
   ```

2. Or check the test page: `http://localhost/AMS-REQ/test_reminder_emails.php`

---

## 🔍 Troubleshooting

### Problem: Task doesn't run

**Check:**
1. ✅ Task is **Enabled** (right-click task → Enable)
2. ✅ Trigger is **Enabled** (Edit task → Triggers tab)
3. ✅ Windows is running at scheduled time
4. ✅ User account has permissions

**Solution:**
- Right-click task → **Properties** → Check all settings
- Ensure "Run whether user is logged on or not" is checked

---

### Problem: Task runs but does nothing

**Check:**
1. ✅ PHP path is correct: `C:\xampp\php\php.exe`
2. ✅ Script path is correct: `C:\xampp\htdocs\AMS-REQ\cron\check_overdue_assets.php`
3. ✅ Paths are wrapped in quotes in "Add arguments"

**Test manually:**
```batch
cd C:\xampp\htdocs\AMS-REQ
C:\xampp\php\php.exe cron\check_overdue_assets.php
```

---

### Problem: "The operator or administrator has refused the request"

**Solution:**
- Run Task Scheduler as Administrator
- Right-click Task Scheduler → "Run as administrator"

---

### Problem: Task runs but emails not sent

**Check:**
1. ✅ Email settings in `includes/email_functions.php`
2. ✅ Gmail app password is correct
3. ✅ System setting: `enable_email_notifications = true`

**Test emails manually:**
```batch
php C:\xampp\htdocs\AMS-REQ\test_email_simple.php
```

---

## 📊 What the Task Does Daily

Every day at 8:00 AM, the task will:

1. ✅ **Check for assets due in 7 days** → Send "Advance Notice" emails
2. ✅ **Check for assets due in 2 days** → Send "Upcoming Return" emails
3. ✅ **Check for assets due in 1 day** → Send "Urgent" emails
4. ✅ **Check for assets due TODAY** → Send "Return Today" emails
5. ✅ **Check for overdue assets** → Send escalation emails:
   - Day 1-3: Email to borrower
   - Day 4-7: Email to borrower + custodian
   - Day 8+: Email to borrower + custodian + admin
6. ✅ **Auto-mark assets as missing** if overdue > 60 days
7. ✅ **Clean up old notifications**
8. ✅ **Log all activity** to `logs/overdue_check_YYYY-MM-DD.log`

---

## 🎯 Alternative: Run Every 12 Hours

If you want more frequent checks:

1. Edit the task → **Triggers** tab
2. Change **"Recur every"** to `1 days`
3. Add **second trigger**:
   - Click "New"
   - Set time to `8:00 PM`
   - Click OK

Now it runs at **8:00 AM** and **8:00 PM** daily.

---

## 📝 Viewing Task History

1. In Task Scheduler, select your task
2. Click **"History"** tab at the bottom
3. View all executions, errors, and results

**Enable History if disabled:**
1. Click "Task Scheduler (Local)" in left sidebar
2. Right panel → **"Enable All Tasks History"**

---

## ⚙️ Advanced: Run on System Startup

To run the task when Windows starts:

1. Edit task → **Triggers** tab
2. Click **"New..."**
3. Set **"Begin the task"** to `At startup`
4. Click OK

⚠️ **Note:** This will run ONCE at startup, in addition to daily schedule.

---

## 🔒 Security Best Practices

1. ✅ Use a dedicated service account (optional)
2. ✅ Limit script permissions to read-only on sensitive files
3. ✅ Store email password in environment variables (future enhancement)
4. ✅ Monitor logs regularly for suspicious activity

---

## 📞 Need Help?

**Common Commands:**

**Check if XAMPP PHP is working:**
```batch
C:\xampp\php\php.exe -v
```

**Test the cron script manually:**
```batch
cd C:\xampp\htdocs\AMS-REQ
C:\xampp\php\php.exe cron\check_overdue_assets.php
```

**Check Task Scheduler logs:**
1. Open Event Viewer: `Win + R` → `eventvwr.msc`
2. Navigate to: **Applications and Services Logs** → **Microsoft** → **Windows** → **TaskScheduler** → **Operational**

---

## ✅ Success Checklist

- [ ] Task created in Task Scheduler
- [ ] Trigger set to run daily at 8:00 AM
- [ ] Action configured with correct PHP and script paths
- [ ] Conditions and Settings tabs configured
- [ ] Manual test run successful
- [ ] Log file created in `logs/` directory
- [ ] Emails sent and logged in database
- [ ] Task history enabled and showing success

---

**🎉 Once complete, your automated reminder system is ACTIVE!**

Borrowers will now receive timely reminders, and overdue assets will be escalated automatically.

---

## 📄 Quick Reference Card

**Task Name:** `HCC Asset Return Reminders`

**Schedule:** Daily at 8:00 AM

**Command:**
```
Program: C:\xampp\php\php.exe
Arguments: "C:\xampp\htdocs\AMS-REQ\cron\check_overdue_assets.php"
Start in: C:\xampp\htdocs\AMS-REQ
```

**Logs:** `C:\xampp\htdocs\AMS-REQ\logs\overdue_check_YYYY-MM-DD.log`

**Manual Run:**
```batch
cd C:\xampp\htdocs\AMS-REQ
C:\xampp\php\php.exe cron\check_overdue_assets.php
```

---

**Last Updated:** November 9, 2025
**Version:** 1.0
**Author:** HCC Asset Management System
