# Asynchronous Email Queue System - Quick Start Guide

## Overview

The async email queue system allows the Asset Management System to send emails in the background without blocking the user interface. This dramatically improves performance - approvals complete instantly while emails are sent separately.

## How It Works

1. **User approves a request** → Approval saves to database immediately
2. **Email gets queued** → Email is inserted into `email_queue` table with status='pending'
3. **Background worker processes queue** → Worker script sends emails asynchronously
4. **Automatic retry** → Failed emails are retried 3 times with exponential backoff

## Key Benefits

- **Instant user feedback** - No waiting 3-5 seconds for email to send
- **Better reliability** - Approvals never fail due to email issues
- **Automatic retry** - Failed emails retry automatically (5 min, 15 min, 1 hour)
- **Priority support** - High priority emails (approvals, rejections) send first
- **Failure tracking** - All failed emails are logged for admin review

## Database Schema

The `email_queue` table was created with:

```sql
CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT NULL,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    related_type VARCHAR(50) NULL,
    related_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    next_retry_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL
);
```

## Starting the Email Worker

### Option 1: Windows Batch File (Easiest)
```bash
cd c:\xampp\htdocs\AMS-REQ\cron
run_email_worker.bat
```

### Option 2: Direct PHP Command
```bash
"C:\xampp\php\php.exe" "c:\xampp\htdocs\AMS-REQ\cron\process_email_queue.php"
```

The worker will:
- Process emails in batches of 10
- Prioritize high → normal → low
- Run continuously until stopped (Ctrl+C)
- Sleep for 30 seconds between batches

## Monitoring the Queue

### Check queue status:
```sql
SELECT status, priority, COUNT(*) as count
FROM email_queue
GROUP BY status, priority;
```

### View pending emails:
```sql
SELECT id, recipient_email, subject, priority, attempts, created_at
FROM email_queue
WHERE status = 'pending'
ORDER BY priority, created_at;
```

### View failed emails:
```sql
SELECT id, recipient_email, subject, error_message, attempts
FROM email_queue
WHERE status = 'failed';
```

## Code Changes

### 1. New Queue Function ([includes/email_functions.php](includes/email_functions.php))

```php
queueRequestNotificationEmail($pdo, $recipientEmail, $recipientName,
    $requestId, $assetName, $status, $message, $priority = 'normal')
```

This function queues an email instead of sending it immediately.

### 2. API Updates ([api/requests.php](api/requests.php))

All `sendRequestNotificationEmail()` calls were replaced with `queueRequestNotificationEmail()`:

- Line 205: Custodian approval → Queue email to requester (normal priority)
- Line 415: Admin approval → Queue email to requester (high priority)
- Line 525: Rejection → Queue email to requester (high priority)
- Line 638: Asset release → Queue email to requester (high priority)
- Line 765-777: Asset return → Queue email to requester (high/normal priority based on late return)

### 3. Background Worker ([cron/process_email_queue.php](cron/process_email_queue.php))

The worker script:
- Fetches pending emails from queue
- Sends them using PHPMailer
- Updates status to 'sent' or 'failed'
- Implements retry logic with exponential backoff
- Logs all activities

## Testing

Run the test script to verify the system:

```bash
"C:\xampp\php\php.exe" "c:\xampp\htdocs\AMS-REQ\test_async_email_queue.php"
```

This will:
1. Queue 4 test emails with different priorities
2. Show them in the database
3. Provide instructions to run the worker
4. Display queue monitoring queries

## Retry Logic

| Attempt | Retry Delay |
|---------|-------------|
| 1       | 5 minutes   |
| 2       | 15 minutes  |
| 3       | 1 hour      |
| Failed  | No more retries (marked as 'failed') |

## Priority Levels

- **High**: Approvals, rejections, releases, late returns
- **Normal**: Standard notifications, on-time returns
- **Low**: General reminders, informational emails

## Troubleshooting

### Emails stuck in 'pending' status
- Ensure the background worker is running
- Check worker output for errors
- Verify SMTP credentials in `email_functions.php`

### Emails marked as 'failed'
- Check `error_message` column in email_queue table
- Common issues: SMTP authentication, network problems, invalid email addresses
- You can manually retry by updating `status='pending'` and `attempts=0`

### Worker stops unexpectedly
- Check for PHP errors in terminal output
- Verify database connection is active
- Ensure XAMPP MySQL service is running

## Production Deployment

For production, consider:

1. **Run worker as a service** (Windows Service or Task Scheduler)
2. **Add monitoring** - Alert if queue grows too large
3. **Admin dashboard** - Create UI to view/retry failed emails
4. **Logging** - Enhance logging for better debugging
5. **Multiple workers** - Run multiple workers for high volume

## Files Created/Modified

### Created:
- [database/migrations/create_email_queue_table.sql](database/migrations/create_email_queue_table.sql)
- [cron/process_email_queue.php](cron/process_email_queue.php)
- [cron/run_email_worker.bat](cron/run_email_worker.bat)
- [test_async_email_queue.php](test_async_email_queue.php)
- ASYNC_EMAIL_QUEUE_GUIDE.md (this file)

### Modified:
- [includes/email_functions.php](includes/email_functions.php) - Added `queueRequestNotificationEmail()` function
- [api/requests.php](api/requests.php) - Replaced all sync email calls with async queue calls

## Summary

The asynchronous email queue system is now fully operational. Users will experience instant feedback when approving/rejecting requests, while emails are sent reliably in the background with automatic retry on failure.

**Key Reminder**: Always keep the background worker running in production to process the email queue!
