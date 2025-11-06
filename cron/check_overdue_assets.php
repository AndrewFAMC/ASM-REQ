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
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli' && !isset($_GET['manual_run'])) {
    die("This script can only be run from command line or with ?manual_run=1");
}

// Load configuration
require_once dirname(__DIR__) . '/config.php';

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

    // Step 2: Send reminder notifications
    logMessage("\nStep 2: Sending return reminders...");
    $reminderCount = sendReturnReminders($pdo);
    logMessage("- Sent $reminderCount reminder notification(s)");

    // Step 3: Send overdue notifications
    logMessage("\nStep 3: Sending overdue alert notifications...");
    $overdueNotifCount = sendOverdueNotifications($pdo);
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
?>
