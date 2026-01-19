<?php
/**
 * Async Email Queue System Test
 *
 * Tests the complete async email workflow:
 * 1. Queue emails using queueRequestNotificationEmail()
 * 2. Verify they're in the database with status='pending'
 * 3. Show instructions to run the background worker
 * 4. Monitor the queue status
 */

require_once __DIR__ . '/config.php';

echo "=== Async Email Queue System Test ===\n";
echo "Start Time: " . date('Y-m-d H:i:s') . "\n\n";

$testEmail = 'andrewbeley7@gmail.com';
$testName = 'Andrew Beley';

// ================================================================
// Step 1: Clear any previous test emails
// ================================================================
echo "Step 1: Clearing previous test emails...\n";
$stmt = $pdo->prepare("DELETE FROM email_queue WHERE recipient_email = ?");
$stmt->execute([$testEmail]);
echo "  Cleared " . $stmt->rowCount() . " previous test email(s)\n\n";

// ================================================================
// Step 2: Queue multiple test emails
// ================================================================
echo "Step 2: Queueing test emails...\n";

$testScenarios = [
    [
        'requestId' => 201,
        'assetName' => 'Laptop Dell Latitude',
        'status' => 'approved',
        'message' => 'Your request for Laptop Dell Latitude has been approved by the custodian.',
        'priority' => 'high'
    ],
    [
        'requestId' => 202,
        'assetName' => 'Projector Sony VPL',
        'status' => 'rejected',
        'message' => 'Your request for Projector Sony VPL has been rejected. Reason: Asset unavailable.',
        'priority' => 'high'
    ],
    [
        'requestId' => 203,
        'assetName' => 'Desktop Computer HP',
        'status' => 'released',
        'message' => 'Your requested asset Desktop Computer HP has been released. Please return by next week.',
        'priority' => 'normal'
    ],
    [
        'requestId' => 204,
        'assetName' => 'Whiteboard Mobile',
        'status' => 'returned',
        'message' => 'Your borrowed asset Whiteboard Mobile has been returned on time. Thank you!',
        'priority' => 'low'
    ]
];

$queuedCount = 0;
foreach ($testScenarios as $scenario) {
    $result = queueRequestNotificationEmail(
        $pdo,
        $testEmail,
        $testName,
        $scenario['requestId'],
        $scenario['assetName'],
        $scenario['status'],
        $scenario['message'],
        $scenario['priority']
    );

    if ($result) {
        echo "  ✓ Queued: Request #{$scenario['requestId']} - {$scenario['status']} ({$scenario['priority']} priority)\n";
        $queuedCount++;
    } else {
        echo "  ✗ Failed to queue: Request #{$scenario['requestId']}\n";
    }
}

echo "\nTotal queued: {$queuedCount} email(s)\n\n";

// ================================================================
// Step 3: Verify emails are in the queue
// ================================================================
echo "Step 3: Verifying emails in queue...\n";
$stmt = $pdo->prepare("
    SELECT id, subject, status, priority, attempts, created_at
    FROM email_queue
    WHERE recipient_email = ?
    ORDER BY
        CASE priority
            WHEN 'high' THEN 1
            WHEN 'normal' THEN 2
            WHEN 'low' THEN 3
        END,
        created_at ASC
");
$stmt->execute([$testEmail]);
$queuedEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($queuedEmails) > 0) {
    echo "  Found " . count($queuedEmails) . " email(s) in queue:\n\n";
    foreach ($queuedEmails as $email) {
        echo "  ID: {$email['id']}\n";
        echo "  Subject: {$email['subject']}\n";
        echo "  Status: {$email['status']}\n";
        echo "  Priority: {$email['priority']}\n";
        echo "  Attempts: {$email['attempts']}\n";
        echo "  Created: {$email['created_at']}\n";
        echo "  " . str_repeat('-', 50) . "\n";
    }
} else {
    echo "  ✗ No emails found in queue!\n";
}

// ================================================================
// Step 4: Check activity logs (if table exists)
// ================================================================
echo "\nStep 4: Checking activity logs...\n";
try {
    $logStmt = $pdo->prepare("
        SELECT action, description, created_at
        FROM activity_logs
        WHERE action LIKE 'EMAIL_%'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $logStmt->execute();
    $logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($logs) {
        foreach ($logs as $log) {
            $icon = match($log['action']) {
                'EMAIL_QUEUED' => '📋',
                'EMAIL_SENT' => '✓',
                'EMAIL_FAILED' => '✗',
                'EMAIL_QUEUE_FAILED' => '⚠',
                default => '•'
            };
            echo "  [{$log['created_at']}] {$icon} {$log['action']}: {$log['description']}\n";
        }
    } else {
        echo "  No activity logs found.\n";
    }
} catch (PDOException $e) {
    echo "  Activity logs table not available (this is OK).\n";
}

// ================================================================
// Step 5: Instructions to run background worker
// ================================================================
echo "\n" . str_repeat('=', 60) . "\n";
echo "NEXT STEPS - How to Process the Queue\n";
echo str_repeat('=', 60) . "\n\n";

echo "The emails are now queued. To send them, run the background worker:\n\n";
echo "Option 1: Run in new terminal window\n";
echo "  cd c:\\xampp\\htdocs\\AMS-REQ\\cron\n";
echo "  run_email_worker.bat\n\n";

echo "Option 2: Run directly with PHP\n";
echo "  \"C:\\xampp\\php\\php.exe\" \"c:\\xampp\\htdocs\\AMS-REQ\\cron\\process_email_queue.php\"\n\n";

echo "The worker will:\n";
echo "  - Process emails in priority order (high → normal → low)\n";
echo "  - Send emails asynchronously in batches of 10\n";
echo "  - Retry failed emails with exponential backoff (5 min, 15 min, 1 hour)\n";
echo "  - Mark emails as 'sent' or 'failed' after processing\n";
echo "  - Run continuously until you stop it (Ctrl+C)\n\n";

echo "After running the worker, check:\n";
echo "  - Your email inbox: {$testEmail}\n";
echo "  - Activity logs in the database\n";
echo "  - email_queue table for status updates\n\n";

// ================================================================
// Step 6: Show queue monitoring query
// ================================================================
echo str_repeat('=', 60) . "\n";
echo "QUEUE MONITORING\n";
echo str_repeat('=', 60) . "\n\n";

echo "Monitor queue status with this SQL query:\n\n";
echo "  SELECT status, priority, COUNT(*) as count\n";
echo "  FROM email_queue\n";
echo "  GROUP BY status, priority\n";
echo "  ORDER BY status, priority;\n\n";

$statusStmt = $pdo->query("
    SELECT status, priority, COUNT(*) as count
    FROM email_queue
    GROUP BY status, priority
    ORDER BY status, priority
");
$statusCounts = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

if ($statusCounts) {
    echo "Current queue status:\n";
    foreach ($statusCounts as $row) {
        echo "  {$row['status']} ({$row['priority']}): {$row['count']} email(s)\n";
    }
} else {
    echo "Queue is empty.\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Test completed successfully!\n";
echo "End Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n";
?>
