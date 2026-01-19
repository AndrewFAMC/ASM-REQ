# Email Worker - Complete Guide

## Question: Do I have a .bat file to run the email worker? Is it auto-run when I login to the website?

### Answer:

**YES, you have .bat files** ✅
**NO, it does NOT auto-run when you login** ❌ (but can be enabled)

---

## Available .BAT Files

### 1. Quick Start (Manual Run)
**File**: `tests\run_email_worker.bat`
```batch
Double-click to run the email worker in a console window
```
- Shows real-time processing
- Press Ctrl+C to stop
- Good for testing

### 2. Auto Setup (Task Scheduler)
**File**: `setup_email_worker.bat`
```batch
Right-click → Run as Administrator
```
- Sets up Windows Task Scheduler
- Runs every 1 minute automatically
- Continues after restart
- Runs in background (no console window)

### 3. Remove Auto Setup
**File**: `uninstall_email_worker.bat`
```batch
Removes the scheduled task
```

---

## Current Status

### Email Worker: ✅ **RUNNING**
- Process ID: 6324
- Started manually (not via scheduled task)
- Processing emails successfully

### Auto-Start on Login: ❌ **NOT ENABLED**
- File exists: `includes/email_worker_autostart.php`
- **NOT** included in dashboards yet
- Needs to be added to admin/custodian dashboards

---

## How to Enable Auto-Start on Website Login

### Option 1: Add to Admin Dashboard

Add this to `admin/admin_dashboard.php` (before closing `</body>` tag):
```php
<?php include __DIR__ . '/../includes/email_worker_autostart.php'; ?>
```

### Option 2: Add to Custodian Dashboard

Add this to `custodian/dashboard.php` (before closing `</body>` tag):
```php
<?php include __DIR__ . '/../includes/email_worker_autostart.php'; ?>
```

### What It Does:
✅ Checks if email worker is running when admin/custodian logs in
✅ Auto-starts if there are pending emails
✅ Checks every 5 minutes and restarts if needed
✅ Only runs for admin/custodian roles (not all users)

---

## Recommended Setup

### For Production (Best):

1. **Use Windows Task Scheduler**:
   ```
   Right-click: setup_email_worker.bat
   Select: Run as Administrator
   ```

   **Benefits**:
   - Runs automatically every minute
   - Continues after server restart
   - Runs even when no one is logged in
   - Most reliable

### For Development/Testing:

1. **Manual Run**:
   ```
   Double-click: tests\run_email_worker.bat
   ```

   **Benefits**:
   - See real-time output
   - Easy to stop/restart
   - Good for debugging

### For Web-Based Auto-Start:

1. **Enable in dashboards** (see above)

   **Benefits**:
   - Starts when admin/custodian logs in
   - Auto-restarts if crashed
   - No need for Task Scheduler
   - **Drawback**: Only runs when someone is logged in

---

## Quick Commands

### Check if Worker is Running:
```bash
wmic process where "name='php.exe'" get ProcessId,CommandLine 2>nul
```

### Check Email Queue:
```sql
SELECT COUNT(*) FROM email_queue WHERE status = 'pending';
```

### View Recent Emails:
```sql
SELECT id, recipient_email, subject, status, sent_at
FROM email_queue
ORDER BY created_at DESC
LIMIT 10;
```

### Start Worker Manually:
```bash
cd c:\xampp\htdocs\AMS-REQ\tests
run_email_worker.bat
```

### Check Task Scheduler:
```bash
schtasks /query /tn "AMS Email Worker"
```

---

## Web Interface

### Email Worker Status Page:
**URL**: `http://localhost/AMS-REQ/email_worker_status.php`

**Features**:
- View worker status (running/stopped)
- See queue statistics
- Start/Stop worker
- Auto-start worker
- Real-time monitoring

**Requirements**:
- Must be logged in as Admin or Custodian

---

## API Endpoints

### Check Status:
```
GET /AMS-REQ/api/email_worker_manager.php?action=status
```

### Start Worker:
```
GET /AMS-REQ/api/email_worker_manager.php?action=start
```

### Stop Worker:
```
GET /AMS-REQ/api/email_worker_manager.php?action=stop
```

### Auto-Start (if pending emails):
```
GET /AMS-REQ/api/email_worker_manager.php?action=auto_start
```

---

## Troubleshooting

### Problem: Worker stops after some time
**Solution**: Use Task Scheduler (setup_email_worker.bat)

### Problem: Worker not processing emails
**Check**:
1. Is it running? `wmic process where "name='php.exe'" get CommandLine`
2. Any errors? Check PHP error log
3. SMTP configured? Check `includes/email/EmailConfig.php`

### Problem: Auto-start not working
**Check**:
1. Is autostart included in dashboard?
2. Are you logged in as admin/custodian?
3. Check browser console for errors

### Problem: Task Scheduler not working
**Solutions**:
1. Run as Administrator
2. Check Task Scheduler: `taskschd.msc`
3. Look for "AMS Email Worker" task
4. Check "Last Run Result"

---

## Summary

### ✅ What You Have:
1. Manual .bat file to run worker
2. Setup .bat to install as scheduled task
3. Web interface to manage worker
4. Auto-start script (not enabled yet)
5. API to control worker

### ❌ What's NOT Enabled:
1. Auto-start on website login (needs to be added to dashboards)
2. Task Scheduler (needs manual setup)

### ✅ Current Status:
- Worker IS running (Process 6324)
- Emails ARE being sent
- Everything works manually

### 🎯 Recommendation:
**For production**: Run `setup_email_worker.bat` as Administrator to enable Task Scheduler. This is the most reliable method.

**Optional**: Add auto-start to dashboards for extra safety (will restart if worker crashes).

---

**Last Updated**: 2025-01-12
