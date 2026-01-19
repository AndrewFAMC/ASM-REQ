# Email Worker - Web-Based Auto-Start Guide

## Overview

The email worker can now be automatically started through the web interface when admins or custodians log in. No need to manually run batch files!

## How It Works

### Automatic Start on Login

When an **admin** or **custodian** logs in or refreshes their dashboard:

1. The system checks if the email worker is running
2. If NOT running and there are pending emails → **Automatically starts the worker**
3. If already running → Shows status in browser console
4. Every 5 minutes → Checks and restarts if needed

### Files Created

1. **[api/email_worker_manager.php](api/email_worker_manager.php)** - API endpoint to control the worker
2. **[includes/email_worker_autostart.php](includes/email_worker_autostart.php)** - JavaScript that runs on page load

### Installation Complete

The auto-start script has already been added to:
- ✅ [custodian_dashboard.php](custodian_dashboard.php)

## API Endpoints

### Base URL
```
/AMS-REQ/api/email_worker_manager.php
```

### Actions Available

#### 1. **Auto Start** (Recommended)
Automatically starts worker if needed

```javascript
fetch('/AMS-REQ/api/email_worker_manager.php?action=auto_start')
```

**Response:**
```json
{
  "success": true,
  "message": "Email worker auto-started",
  "status": "running",
  "queue_stats": {
    "pending": 5,
    "sent": 10,
    "failed": 0,
    "total": 15
  },
  "auto_started": true
}
```

#### 2. **Manual Start**
Force start the worker

```javascript
fetch('/AMS-REQ/api/email_worker_manager.php?action=start')
```

#### 3. **Stop Worker**
Stop the running worker

```javascript
fetch('/AMS-REQ/api/email_worker_manager.php?action=stop')
```

#### 4. **Check Status**
Get current worker status and queue stats

```javascript
fetch('/AMS-REQ/api/email_worker_manager.php?action=status')
```

**Response:**
```json
{
  "success": true,
  "status": "running",
  "worker_running": true,
  "queue_stats": {
    "pending": 3,
    "sent": 25,
    "failed": 1,
    "total": 29,
    "by_priority": [
      {"status": "pending", "priority": "high", "count": 2},
      {"status": "pending", "priority": "normal", "count": 1}
    ],
    "oldest_pending": "2025-11-09 09:15:23"
  },
  "message": "Email worker is running"
}
```

## How to Use

### Option 1: Automatic (Default)

**Just log in** as admin or custodian. The worker starts automatically if needed.

Check browser console (F12) to see:
```
✓ Email worker auto-started successfully
Pending emails: 5
```

### Option 2: Manual Control via Browser Console

Open browser console (F12) and run:

**Start worker:**
```javascript
fetch('/AMS-REQ/api/email_worker_manager.php?action=start')
  .then(r => r.json())
  .then(data => console.log(data));
```

**Check status:**
```javascript
fetch('/AMS-REQ/api/email_worker_manager.php?action=status')
  .then(r => r.json())
  .then(data => console.log(data));
```

**Stop worker:**
```javascript
fetch('/AMS-REQ/api/email_worker_manager.php?action=stop')
  .then(r => r.json())
  .then(data => console.log(data));
```

### Option 3: Add to Any Page

Add this to any admin/custodian page:

```php
<?php include 'includes/email_worker_autostart.php'; ?>
```

Place it before the closing `</body>` tag.

## Security

- Only **admin**, **super_admin**, and **custodian** roles can control the worker
- Unauthorized users get `403 Forbidden` response
- Session validation required

## Monitoring

### Browser Console Logs

The auto-start script logs to browser console:

```
✓ Email worker auto-started successfully
Pending emails: 3

✓ Email worker status: running
Queue stats: {pending: 3, sent: 10, failed: 0}

⚠ Email worker stopped but emails are pending. Restarting...
```

### Periodic Checks

The script checks worker status every **5 minutes** and auto-restarts if:
- Worker is stopped
- There are pending emails in the queue

## Troubleshooting

### Worker Won't Start

1. **Check browser console** (F12) for errors
2. **Verify permissions** - Only admin/custodian can start worker
3. **Check PHP path** - Ensure `C:\xampp\php\php.exe` exists
4. **Verify worker script** - File should exist at `c:\xampp\htdocs\AMS-REQ\cron\process_email_queue.php`

### Worker Keeps Stopping

1. **Check for PHP errors** in worker output
2. **Verify database connection** is active
3. **Ensure MySQL is running** in XAMPP
4. **Check SMTP credentials** in [email_functions.php](includes/email_functions.php)

### No Emails Being Sent

1. **Check queue** - Run status endpoint to see if emails are queued
2. **Verify worker is running** - Check status endpoint
3. **Check for failed emails** - Look at queue_stats.failed count
4. **Review error messages** in database:
   ```sql
   SELECT id, recipient_email, error_message, attempts
   FROM email_queue
   WHERE status = 'failed';
   ```

## Comparison of Methods

| Method | Auto-Start | Requires Terminal | Runs on Server Restart | Best For |
|--------|-----------|-------------------|----------------------|----------|
| **Web Auto-Start** | ✅ Yes | ❌ No | ❌ No | Development, quick testing |
| **Manual Batch File** | ❌ No | ✅ Yes | ❌ No | Manual control |
| **Windows Service** | ✅ Yes | ❌ No | ✅ Yes | **Production (recommended)** |
| **Task Scheduler** | ✅ Yes | ❌ No | ✅ Yes | Production alternative |

## Production Recommendation

For **production deployment**, use a Windows Service or Task Scheduler instead of web-based auto-start to ensure the worker runs continuously even when no one is logged in.

### Setup Windows Task Scheduler (Recommended for Production)

1. Open Task Scheduler
2. Create New Task
3. **Trigger**: At system startup
4. **Action**: Start program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\AMS-REQ\cron\process_email_queue.php`
5. **Settings**:
   - ✅ Run whether user is logged on or not
   - ✅ Run with highest privileges
   - ✅ If task fails, restart every 1 minute

## Summary

✅ **Auto-start is now enabled** on custodian dashboard
✅ **Worker starts automatically** when admins/custodians log in
✅ **Periodic checks** every 5 minutes ensure worker stays running
✅ **Full API** available for manual control
✅ **Zero manual intervention** required for development

The email worker will now start automatically whenever you log in to the system!
