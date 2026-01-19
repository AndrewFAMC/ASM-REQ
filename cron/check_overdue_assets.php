<?php
/**
 * Automated Cron Job - Check Overdue Assets & Send Reminders
 *
 * This script should be run periodically (recommended: daily at midnight)
 *
 * XAMPP/Windows Setup:
 * 1. Install Windows Task Scheduler
 * 2. Create a task to run: php "C:\xampp\htdocs\AMS-REQ\cron\check_overdue_assets.php"
 * 3. Schedule: Daily at 12:00 AM
 *
 * Linux/Mac Setup:
 * Add to crontab: 0 0 * * * /usr/bin/php /path/to/AMS-REQ/cron/check_overdue_assets.php
 *
 * REFACTORED: Now uses the new centralized email system
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli' && !isset($_GET['manual_run'])) {
    die("This script can only be run from command line or with ?manual_run=1");
}

// Load configuration
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/email/Email.php';

// Start output logging
$logFile = dirname(__DIR__) . '/logs/overdue_check_' . date('Y-m-d') . '.log';
$startTime = microtime(true);

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    echo $logMessage;

    // Ensure logs directory exists
    $logsDir = dirname($logFile);
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

logMessage("========================================");
logMessage("Starting Overdue Assets Check");
logMessage("========================================");

try {
    // Initialize Email system
    $emailSystem = new Email($pdo);
    $emailEnabled = getSystemSetting($pdo, 'enable_email_notifications', true);

    // Check if overdue checking is enabled
    $overdueCheckEnabled = getSystemSetting($pdo, 'overdue_check_enabled', true);

    if (!$overdueCheckEnabled) {
        logMessage("Overdue checking is disabled in system settings. Exiting.");
        exit(0);
    }

    // Step 1: Check and update overdue borrowings
    logMessage("Step 1: Checking for overdue borrowings...");
    $overdueCount = checkOverdueBorrowings($pdo);
    logMessage("- Updated $overdueCount borrowing(s) to overdue status");

    // Step 2: Send return reminders (REFACTORED)
    logMessage("\nStep 2: Sending return reminders...");
    $reminderCount = sendReturnRemindersRefactored($pdo, $emailSystem, $emailEnabled);
    logMessage("- Sent $reminderCount reminder notification(s)");

    // Step 3: Send overdue notifications (REFACTORED)
    logMessage("\nStep 3: Sending overdue alert notifications...");
    $overdueNotifCount = sendOverdueNotificationsRefactored($pdo, $emailSystem, $emailEnabled);
    logMessage("- Sent $overdueNotifCount overdue alert(s)");

    // Step 4: Check for assets that should be marked as missing
    logMessage("\nStep 4: Checking for assets that should be marked as missing...");
    $autoMissingDays = getSystemSetting($pdo, 'auto_missing_after_days', 60);

    $missingStmt = $pdo->prepare("
        SELECT ab.*, a.asset_name, a.id as asset_id, a.campus_id
        FROM asset_borrowings ab
        JOIN assets a ON ab.asset_id = a.id
        WHERE ab.status = 'overdue'
        AND ab.expected_return_date < DATE_SUB(CURDATE(), INTERVAL ? DAY)
        AND a.status != 'Missing'
    ");
    $missingStmt->execute([$autoMissingDays]);
    $potentiallyMissing = $missingStmt->fetchAll();

    $missingCount = 0;
    foreach ($potentiallyMissing as $item) {
        // Create missing asset report
        $reportStmt = $pdo->prepare("
            INSERT INTO missing_assets_reports
            (asset_id, reported_by, reported_date, last_known_location, last_known_borrower,
             last_known_borrower_contact, description, status, campus_id)
            SELECT
                ?,
                1, -- System user
                NOW(),
                a.location,
                ab.borrower_name,
                ab.borrower_contact,
                CONCAT('Automatically reported: Item overdue for more than ', ?, ' days'),
                'reported',
                a.campus_id
            FROM assets a
            LEFT JOIN asset_borrowings ab ON ab.id = ?
            WHERE a.id = ?
        ");

        $reportStmt->execute([
            $item['asset_id'],
            $autoMissingDays,
            $item['id'],
            $item['asset_id']
        ]);

        // Update asset status to Missing
        $updateAssetStmt = $pdo->prepare("UPDATE assets SET status = 'Missing' WHERE id = ?");
        $updateAssetStmt->execute([$item['asset_id']]);

        // Update borrowing status
        $updateBorrowingStmt = $pdo->prepare("UPDATE asset_borrowings SET status = 'lost' WHERE id = ?");
        $updateBorrowingStmt->execute([$item['id']]);

        // Log activity
        logActivity($pdo, $item['asset_id'], 'STATUS_UPDATED',
            "Asset automatically marked as Missing after being overdue for {$autoMissingDays} days",
            $item['campus_id']
        );

        $missingCount++;
    }
    logMessage("- Marked $missingCount asset(s) as missing");

    // Step 5: Clean up old notifications
    logMessage("\nStep 5: Cleaning up old notifications...");
    $cleanedCount = cleanupNotifications($pdo, 30);
    logMessage("- Cleaned up old notifications");

    // Summary
    $duration = round(microtime(true) - $startTime, 2);
    logMessage("\n========================================");
    logMessage("Summary:");
    logMessage("- Overdue items detected: $overdueCount");
    logMessage("- Return reminders sent: $reminderCount");
    logMessage("- Overdue alerts sent: $overdueNotifCount");
    logMessage("- Assets marked as missing: $missingCount");
    logMessage("- Execution time: {$duration}s");
    logMessage("========================================");
    logMessage("Overdue check completed successfully!\n");

    exit(0);

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

/**
 * Send return reminders using the new centralized email system
 *
 * Reminder stages:
 * - 7 days before: Advance notice
 * - 2 days before: Upcoming return
 * - 1 day before: Urgent reminder
 * - Day of return: Return today
 */
function sendReturnRemindersRefactored($pdo, $emailSystem, $emailEnabled) {
    try {
        // Define reminder stages
        $reminderStages = [
            ['days' => 7, 'urgency' => 'advance_notice', 'priority' => NOTIFICATION_PRIORITY_LOW],
            ['days' => 2, 'urgency' => 'upcoming', 'priority' => NOTIFICATION_PRIORITY_HIGH],
            ['days' => 1, 'urgency' => 'urgent', 'priority' => NOTIFICATION_PRIORITY_HIGH],
            ['days' => 0, 'urgency' => 'today', 'priority' => NOTIFICATION_PRIORITY_URGENT]
        ];

        $totalCount = 0;

        foreach ($reminderStages as $stage) {
            $daysUntil = $stage['days'];
            $urgencyLevel = $stage['urgency'];
            $priority = $stage['priority'];

            // Query asset_requests for released items
            $stmt = $pdo->prepare("
                SELECT
                    ar.id,
                    ar.asset_id,
                    ar.requester_id,
                    ar.expected_return_date,
                    a.asset_name,
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    ar.last_reminder_sent,
                    ar.reminder_count
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.status = 'released'
                AND ar.expected_return_date IS NOT NULL
                AND ar.expected_return_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND (
                    ar.last_reminder_sent IS NULL
                    OR DATE(ar.last_reminder_sent) < CURDATE()
                    OR (? = 0 AND TIME(ar.last_reminder_sent) < TIME(DATE_SUB(NOW(), INTERVAL 4 HOUR)))
                )
            ");
            $stmt->execute([$daysUntil, $daysUntil]);
            $requests = $stmt->fetchAll();

            foreach ($requests as $request) {
                // Create in-app notification
                createNotification($pdo, $request['user_id'], NOTIFICATION_RETURN_REMINDER,
                    $daysUntil == 0 ? 'Return Asset Today' : "Return Reminder ({$daysUntil} days)",
                    "Please return '{$request['asset_name']}' " .
                    ($daysUntil == 0 ? 'TODAY' : "in {$daysUntil} day(s)"),
                    [
                        'related_type' => 'request',
                        'related_id' => $request['id'],
                        'priority' => $priority,
                        'action_url' => '/employee/my_requests.php'
                    ]
                );

                // Send email notification using centralized system
                if ($emailEnabled && $request['email']) {
                    $emailSystem->sendReturnReminder(
                        $request['email'],
                        $request['full_name'],
                        $request['asset_name'],
                        $request['expected_return_date'],
                        $daysUntil,
                        $request['id'],
                        $urgencyLevel
                    );
                }

                // Update reminder tracking
                $reminderCount = (int)($request['reminder_count'] ?? 0) + 1;
                $updateStmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET last_reminder_sent = NOW(), reminder_count = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$reminderCount, $request['id']]);

                $totalCount++;
            }
        }

        return $totalCount;
    } catch (PDOException $e) {
        error_log("Error sending return reminders: " . $e->getMessage());
        return 0;
    }
}

/**
 * Send overdue notifications using the new centralized email system with escalation chain
 *
 * Escalation levels:
 * - Day 1-3: Email to borrower only
 * - Day 4-7: Email to borrower + custodian
 * - Day 8-30: Email to borrower + custodian + admin
 * - Day 30+: Mark as potentially lost
 */
function sendOverdueNotificationsRefactored($pdo, $emailSystem, $emailEnabled) {
    try {
        // Get overdue asset_requests
        $stmt = $pdo->prepare("
            SELECT
                ar.id,
                ar.asset_id,
                ar.requester_id,
                ar.expected_return_date,
                ar.campus_id,
                ar.last_overdue_alert_sent,
                ar.overdue_alert_count,
                a.asset_name,
                u.id as user_id,
                u.full_name,
                u.email,
                DATEDIFF(CURDATE(), ar.expected_return_date) as days_overdue
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN users u ON ar.requester_id = u.id
            WHERE ar.status = 'released'
            AND ar.expected_return_date < CURDATE()
            AND (
                ar.last_overdue_alert_sent IS NULL
                OR DATE(ar.last_overdue_alert_sent) < CURDATE()
            )
        ");
        $stmt->execute();
        $overdueRequests = $stmt->fetchAll();

        $count = 0;
        foreach ($overdueRequests as $request) {
            $daysOverdue = (int)$request['days_overdue'];

            // Send notification to borrower
            createNotification($pdo, $request['user_id'], NOTIFICATION_OVERDUE_ALERT,
                'Overdue Asset',
                "'{$request['asset_name']}' is {$daysOverdue} day(s) overdue. Please return it immediately.",
                [
                    'related_type' => 'request',
                    'related_id' => $request['id'],
                    'priority' => NOTIFICATION_PRIORITY_URGENT,
                    'action_url' => '/employee/my_requests.php'
                ]
            );

            // Send email to borrower using centralized system
            if ($emailEnabled && $request['email']) {
                $emailSystem->sendOverdueAlert(
                    $request['email'],
                    $request['full_name'],
                    $request['asset_name'],
                    $request['expected_return_date'],
                    $daysOverdue,
                    $request['id'],
                    'borrower'
                );
            }

            // ESCALATION LEVEL 1: Days 4-7 - Notify custodians
            if ($daysOverdue >= 4 && $daysOverdue <= 7) {
                $custodianStmt = $pdo->prepare("
                    SELECT id, email, full_name
                    FROM users
                    WHERE role = 'custodian'
                    AND campus_id = ?
                    AND is_active = 1
                ");
                $custodianStmt->execute([$request['campus_id']]);
                $custodians = $custodianStmt->fetchAll();

                foreach ($custodians as $custodian) {
                    createNotification($pdo, $custodian['id'], NOTIFICATION_OVERDUE_ALERT,
                        'Overdue Asset Alert',
                        "Asset '{$request['asset_name']}' is {$daysOverdue} days overdue. Borrower: {$request['full_name']}",
                        [
                            'related_type' => 'request',
                            'related_id' => $request['id'],
                            'priority' => NOTIFICATION_PRIORITY_HIGH
                        ]
                    );

                    if ($emailEnabled && $custodian['email']) {
                        $emailSystem->sendOverdueAlert(
                            $custodian['email'],
                            $custodian['full_name'],
                            $request['asset_name'],
                            $request['expected_return_date'],
                            $daysOverdue,
                            $request['id'],
                            'custodian'
                        );
                    }
                }
            }

            // ESCALATION LEVEL 2: Days 8+ - Notify admins
            if ($daysOverdue >= 8) {
                $adminStmt = $pdo->prepare("
                    SELECT id, email, full_name
                    FROM users
                    WHERE role IN ('admin', 'super_admin')
                    AND is_active = 1
                ");
                $adminStmt->execute();
                $admins = $adminStmt->fetchAll();

                foreach ($admins as $admin) {
                    createNotification($pdo, $admin['id'], NOTIFICATION_OVERDUE_ALERT,
                        'Overdue Asset Escalation',
                        "ESCALATION: Asset '{$request['asset_name']}' is {$daysOverdue} days overdue. Borrower: {$request['full_name']}",
                        [
                            'related_type' => 'request',
                            'related_id' => $request['id'],
                            'priority' => NOTIFICATION_PRIORITY_URGENT
                        ]
                    );

                    if ($emailEnabled && $admin['email']) {
                        $emailSystem->sendOverdueAlert(
                            $admin['email'],
                            $admin['full_name'],
                            $request['asset_name'],
                            $request['expected_return_date'],
                            $daysOverdue,
                            $request['id'],
                            'admin'
                        );
                    }
                }
            }

            // Update tracking
            $alertCount = (int)($request['overdue_alert_count'] ?? 0) + 1;
            $updateStmt = $pdo->prepare("
                UPDATE asset_requests
                SET last_overdue_alert_sent = NOW(), overdue_alert_count = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$alertCount, $request['id']]);

            $count++;
        }

        return $count;
    } catch (PDOException $e) {
        error_log("Error sending overdue notifications: " . $e->getMessage());
        return 0;
    }
}
?>
