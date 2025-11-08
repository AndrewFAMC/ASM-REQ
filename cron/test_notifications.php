<?php
/**
 * Test Notification System
 *
 * This script allows you to manually test the notification system
 * without waiting for the actual scheduler
 *
 * Usage: php test_notifications.php
 * Or access via browser: http://localhost/AMS-REQ/cron/test_notifications.php?secret=hcc_cron_2024
 */

// Allow both CLI and web access for testing
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['secret']) || $_GET['secret'] !== 'hcc_cron_2024') {
        die('Access denied. This script must be run from command line or with valid secret key.');
    }
    echo "<pre>";
}

// Include configuration
require_once __DIR__ . '/../config.php';

echo "=== Asset Notification System - TEST MODE ===\n";
echo "Execution time: " . date('Y-m-d H:i:s') . "\n\n";

// =========================================================================
// Check current status of asset requests
// =========================================================================
echo "--- Current Asset Requests Status ---\n";

$stmt = $pdo->prepare("
    SELECT
        ar.id,
        ar.status,
        ar.expected_return_date,
        ar.two_day_reminder_sent,
        ar.reminder_sent,
        a.asset_name,
        u.full_name,
        u.email,
        DATEDIFF(ar.expected_return_date, CURDATE()) as days_until_due
    FROM asset_requests ar
    JOIN assets a ON ar.asset_id = a.id
    JOIN users u ON ar.requester_id = u.id
    WHERE ar.status = 'released'
    ORDER BY ar.expected_return_date ASC
    LIMIT 20
");

$stmt->execute();
$requests = $stmt->fetchAll();

echo "Found " . count($requests) . " active released asset(s)\n\n";

if (count($requests) > 0) {
    echo str_pad("ID", 5) . str_pad("User", 25) . str_pad("Asset", 30) . str_pad("Due Date", 15) . str_pad("Days", 8) . str_pad("2D Sent", 10) . "Overdue Sent\n";
    echo str_repeat("-", 110) . "\n";

    foreach ($requests as $req) {
        $daysText = $req['days_until_due'] >= 0 ? "in {$req['days_until_due']}d" : "OVERDUE " . abs($req['days_until_due']) . "d";
        echo str_pad($req['id'], 5) .
             str_pad(substr($req['full_name'], 0, 24), 25) .
             str_pad(substr($req['asset_name'], 0, 29), 30) .
             str_pad($req['expected_return_date'], 15) .
             str_pad($daysText, 8) .
             str_pad($req['two_day_reminder_sent'] ? 'Yes' : 'No', 10) .
             ($req['reminder_sent'] ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "No active released assets found.\n";
}

echo "\n";

// =========================================================================
// Test Options
// =========================================================================
echo "--- Test Options ---\n";
echo "What would you like to test?\n";
echo "1. Create test data (items due in 2 days and overdue items)\n";
echo "2. Run scheduler manually (send actual notifications)\n";
echo "3. View notification history\n";
echo "4. Reset reminder flags (for re-testing)\n";
echo "\n";

// For CLI, prompt user
if (php_sapi_name() === 'cli') {
    echo "Enter option number (1-4): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
} else {
    // For web access, check GET parameter
    $line = $_GET['action'] ?? '';
}

echo "\n";

switch ($line) {
    case '1':
        echo "=== Creating Test Data ===\n";
        createTestData($pdo);
        break;

    case '2':
        echo "=== Running Scheduler Manually ===\n";
        include __DIR__ . '/asset_notification_scheduler.php';
        break;

    case '3':
        echo "=== Notification History ===\n";
        viewNotificationHistory($pdo);
        break;

    case '4':
        echo "=== Resetting Reminder Flags ===\n";
        resetReminderFlags($pdo);
        break;

    default:
        echo "No action selected. Exiting.\n";
        break;
}

echo "\n=== Test Complete ===\n";

if (php_sapi_name() !== 'cli') {
    echo "</pre>";
}

// =========================================================================
// Helper Functions
// =========================================================================

function createTestData($pdo) {
    try {
        // Find a user and asset for testing
        $userStmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'employee' AND is_active = 1 LIMIT 1");
        $user = $userStmt->fetch();

        $assetStmt = $pdo->query("SELECT id, asset_name FROM assets WHERE status = 'Active' LIMIT 1");
        $asset = $assetStmt->fetch();

        if (!$user || !$asset) {
            echo "Error: No active user or asset found for testing.\n";
            return;
        }

        echo "Using User: {$user['full_name']} (ID: {$user['id']})\n";
        echo "Using Asset: {$asset['asset_name']} (ID: {$asset['id']})\n\n";

        // Create test request due in 2 days
        $dueDateTwoDays = date('Y-m-d', strtotime('+2 days'));
        $stmt = $pdo->prepare("
            INSERT INTO asset_requests
            (asset_id, requester_id, campus_id, quantity, purpose, expected_return_date, status, request_date)
            VALUES (?, ?, 1, 1, 'Testing 2-day reminder notification', ?, 'released', NOW())
        ");
        $stmt->execute([$asset['id'], $user['id'], $dueDateTwoDays]);
        echo "✓ Created test request due in 2 days (ID: " . $pdo->lastInsertId() . ")\n";

        // Create test request that is overdue
        $dueDateOverdue = date('Y-m-d', strtotime('-3 days'));
        $stmt = $pdo->prepare("
            INSERT INTO asset_requests
            (asset_id, requester_id, campus_id, quantity, purpose, expected_return_date, status, request_date)
            VALUES (?, ?, 1, 1, 'Testing overdue notification', ?, 'released', NOW())
        ");
        $stmt->execute([$asset['id'], $user['id'], $dueDateOverdue]);
        echo "✓ Created test request that is overdue by 3 days (ID: " . $pdo->lastInsertId() . ")\n";

        echo "\nTest data created successfully!\n";
        echo "You can now run option 2 to test the scheduler.\n";

    } catch (Exception $e) {
        echo "Error creating test data: " . $e->getMessage() . "\n";
    }
}

function viewNotificationHistory($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT
                n.id,
                n.user_id,
                u.full_name,
                n.type,
                n.title,
                n.is_read,
                n.created_at
            FROM notifications n
            JOIN users u ON n.user_id = u.id
            WHERE n.type IN ('return_reminder', 'overdue_alert')
            ORDER BY n.created_at DESC
            LIMIT 20
        ");

        $notifications = $stmt->fetchAll();

        if (count($notifications) > 0) {
            echo "Recent return/overdue notifications:\n\n";
            echo str_pad("ID", 6) . str_pad("User", 25) . str_pad("Type", 20) . str_pad("Read", 6) . "Created At\n";
            echo str_repeat("-", 100) . "\n";

            foreach ($notifications as $notif) {
                echo str_pad($notif['id'], 6) .
                     str_pad(substr($notif['full_name'], 0, 24), 25) .
                     str_pad($notif['type'], 20) .
                     str_pad($notif['is_read'] ? 'Yes' : 'No', 6) .
                     $notif['created_at'] . "\n";
            }
        } else {
            echo "No return/overdue notifications found in history.\n";
        }

    } catch (Exception $e) {
        echo "Error viewing notification history: " . $e->getMessage() . "\n";
    }
}

function resetReminderFlags($pdo) {
    try {
        $stmt = $pdo->query("
            UPDATE asset_requests
            SET two_day_reminder_sent = 0, reminder_sent = 0
            WHERE status = 'released'
        ");

        $count = $stmt->rowCount();
        echo "✓ Reset reminder flags for {$count} request(s).\n";
        echo "You can now re-test the scheduler.\n";

    } catch (Exception $e) {
        echo "Error resetting flags: " . $e->getMessage() . "\n";
    }
}
?>
