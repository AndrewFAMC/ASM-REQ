<?php
/**
 * Asset Notification Scheduler
 *
 * This script checks for assets that need notifications:
 * 1. Send 2-day warning before due date
 * 2. Send overdue alerts for items past due date
 *
 * Run this script daily via Windows Task Scheduler or cron job
 *
 * Usage: php asset_notification_scheduler.php
 */

// Prevent web access - only allow CLI execution
if (php_sapi_name() !== 'cli') {
    // Allow web access for testing purposes, but require a secret key
    if (!isset($_GET['secret']) || $_GET['secret'] !== 'hcc_cron_2024') {
        die('Access denied. This script must be run from command line or with valid secret key.');
    }
}

// Start output buffering for logging
ob_start();

// Include configuration
require_once __DIR__ . '/../config.php';

echo "=== Asset Notification Scheduler Started ===\n";
echo "Execution time: " . date('Y-m-d H:i:s') . "\n\n";

// Track statistics
$stats = [
    'two_day_reminders_sent' => 0,
    'overdue_alerts_sent' => 0,
    'errors' => 0
];

try {
    // =========================================================================
    // STEP 1: Send 2-day warnings for items due in 2 days
    // =========================================================================
    echo "--- Checking for items due in 2 days ---\n";

    $twoDayReminderDate = date('Y-m-d', strtotime('+2 days'));

    $stmt = $pdo->prepare("
        SELECT
            ar.id,
            ar.requester_id,
            ar.asset_id,
            ar.expected_return_date,
            ar.quantity,
            a.asset_name,
            u.id as user_id,
            u.full_name,
            u.email
        FROM asset_requests ar
        JOIN assets a ON ar.asset_id = a.id
        JOIN users u ON ar.requester_id = u.id
        WHERE ar.status = 'released'
        AND ar.expected_return_date = ?
        AND ar.two_day_reminder_sent = 0
        AND u.is_active = 1
    ");

    $stmt->execute([$twoDayReminderDate]);
    $twoDayItems = $stmt->fetchAll();

    echo "Found " . count($twoDayItems) . " item(s) due in 2 days\n";

    foreach ($twoDayItems as $item) {
        try {
            echo "Processing request #{$item['id']} for {$item['full_name']}...\n";

            // Create in-app notification
            $notificationId = createNotification(
                $pdo,
                $item['user_id'],
                NOTIFICATION_RETURN_REMINDER,
                'Return Reminder - 2 Days Left',
                "Your borrowed asset '{$item['asset_name']}' is due in 2 days. Please prepare to return it by " . date('F j, Y', strtotime($item['expected_return_date'])),
                [
                    'related_type' => 'asset_request',
                    'related_id' => $item['id'],
                    'priority' => NOTIFICATION_PRIORITY_HIGH,
                    'action_url' => '/employee/my_requests.php',
                    'asset_name' => $item['asset_name'],
                    'return_date' => date('F j, Y', strtotime($item['expected_return_date'])),
                    'days_remaining' => 2,
                    'request_id' => $item['id']
                ]
            );

            if ($notificationId) {
                // Mark as sent
                $updateStmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET two_day_reminder_sent = 1
                    WHERE id = ?
                ");
                $updateStmt->execute([$item['id']]);

                $stats['two_day_reminders_sent']++;
                echo "  ✓ Notification sent successfully\n";
            } else {
                echo "  ✗ Failed to create notification\n";
                $stats['errors']++;
            }

        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }

    echo "\n";

    // =========================================================================
    // STEP 2: Send overdue alerts for items past due date
    // =========================================================================
    echo "--- Checking for overdue items ---\n";

    $today = date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            ar.id,
            ar.requester_id,
            ar.asset_id,
            ar.expected_return_date,
            ar.quantity,
            ar.reminder_sent,
            a.asset_name,
            u.id as user_id,
            u.full_name,
            u.email,
            DATEDIFF(?, ar.expected_return_date) as days_overdue
        FROM asset_requests ar
        JOIN assets a ON ar.asset_id = a.id
        JOIN users u ON ar.requester_id = u.id
        WHERE ar.status = 'released'
        AND ar.expected_return_date < ?
        AND u.is_active = 1
    ");

    $stmt->execute([$today, $today]);
    $overdueItems = $stmt->fetchAll();

    echo "Found " . count($overdueItems) . " overdue item(s)\n";

    foreach ($overdueItems as $item) {
        try {
            // Send overdue notification daily (not just once)
            // But we can limit frequency to avoid spam - e.g., once per day
            echo "Processing overdue request #{$item['id']} for {$item['full_name']} ({$item['days_overdue']} days overdue)...\n";

            // Create urgent in-app notification
            $notificationId = createNotification(
                $pdo,
                $item['user_id'],
                NOTIFICATION_OVERDUE_ALERT,
                'URGENT: Overdue Asset Return',
                "Your borrowed asset '{$item['asset_name']}' is {$item['days_overdue']} day(s) OVERDUE! Please return it immediately to avoid penalties.",
                [
                    'related_type' => 'asset_request',
                    'related_id' => $item['id'],
                    'priority' => NOTIFICATION_PRIORITY_URGENT,
                    'action_url' => '/employee/my_requests.php',
                    'asset_name' => $item['asset_name'],
                    'return_date' => date('F j, Y', strtotime($item['expected_return_date'])),
                    'days_overdue' => $item['days_overdue'],
                    'request_id' => $item['id']
                ]
            );

            if ($notificationId) {
                // Mark reminder as sent (general flag)
                if (!$item['reminder_sent']) {
                    $updateStmt = $pdo->prepare("
                        UPDATE asset_requests
                        SET reminder_sent = 1
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$item['id']]);
                }

                $stats['overdue_alerts_sent']++;
                echo "  ✓ Overdue alert sent successfully\n";
            } else {
                echo "  ✗ Failed to create notification\n";
                $stats['errors']++;
            }

        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $stats['errors']++;
        }
    }

    echo "\n";

    // =========================================================================
    // STEP 3: Summary and Cleanup
    // =========================================================================
    echo "=== Execution Summary ===\n";
    echo "2-Day Reminders Sent: {$stats['two_day_reminders_sent']}\n";
    echo "Overdue Alerts Sent: {$stats['overdue_alerts_sent']}\n";
    echo "Errors: {$stats['errors']}\n";
    echo "Completion time: " . date('Y-m-d H:i:s') . "\n";

    // Optional: Clean up old notifications (older than 30 days and read)
    cleanupNotifications($pdo, 30);

} catch (Exception $e) {
    echo "\n!!! CRITICAL ERROR !!!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Get output and log it
$output = ob_get_clean();
echo $output;

// Save to log file
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$logFile = $logDir . '/notification_scheduler_' . date('Y-m-d') . '.log';
file_put_contents($logFile, $output, FILE_APPEND);

echo "\nLog saved to: $logFile\n";
?>
