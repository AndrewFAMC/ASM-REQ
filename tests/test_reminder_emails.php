<?php
/**
 * Test Script for Return Reminder and Overdue Alert Emails
 *
 * Usage: Run from browser: http://localhost/AMS-REQ/test_reminder_emails.php
 *        Or from CLI: php test_reminder_emails.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email_functions.php';

// Check if user is admin/super_admin for security
if (!isLoggedIn()) {
    // Allow manual_run parameter for testing
    if (!isset($_GET['manual_run'])) {
        die("Please log in as admin to run this test.");
    }
}

$user = getUserInfo();
if ($user && !in_array(strtolower($user['role']), ['admin', 'super_admin', 'custodian'])) {
    die("Access denied. Admin/Custodian only.");
}

echo "<!DOCTYPE html><html><head><title>Email Reminder Test</title><style>
body { font-family: Arial; padding: 20px; background: #f4f4f4; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
h1 { color: #333; }
.test-section { background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0071e3; }
.success { color: #10b981; font-weight: bold; }
.error { color: #ef4444; font-weight: bold; }
.info { color: #6b7280; font-size: 14px; }
pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; overflow-x: auto; border-radius: 4px; }
button { background: #0071e3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
button:hover { background: #005bb5; }
</style></head><body><div class='container'>";

echo "<h1>🧪 Email Reminder System Test</h1>";
echo "<p class='info'>This script tests the new multi-stage reminder and escalation email system.</p>";

// Test Configuration
$testEmail = 'mico.macapugay2004@gmail.com'; // Change to your test email
$testName = 'Test User';
$testAssetName = 'Test Projector XYZ-123';
$testRequestId = 9999;

echo "<div class='test-section'>";
echo "<h2>📧 Test Configuration</h2>";
echo "<p><strong>Test Email:</strong> {$testEmail}</p>";
echo "<p><strong>Test Asset:</strong> {$testAssetName}</p>";
echo "<p><strong>Test Request ID:</strong> #{$testRequestId}</p>";
echo "</div>";

// Test 1: Return Reminder Emails (All Stages)
echo "<div class='test-section'>";
echo "<h2>Test 1: Return Reminder Emails (Multi-Stage)</h2>";

$reminderTests = [
    ['days' => 7, 'urgency' => 'advance_notice', 'label' => '7 Days Before (Advance Notice)'],
    ['days' => 2, 'urgency' => 'upcoming', 'label' => '2 Days Before (Upcoming)'],
    ['days' => 1, 'urgency' => 'urgent', 'label' => '1 Day Before (Urgent)'],
    ['days' => 0, 'urgency' => 'today', 'label' => 'Day of Return (Today)']
];

foreach ($reminderTests as $test) {
    $expectedDate = date('Y-m-d', strtotime("+{$test['days']} days"));

    echo "<h3>📅 {$test['label']}</h3>";
    echo "<p class='info'>Expected Return: " . date('F j, Y', strtotime($expectedDate)) . "</p>";

    try {
        $result = sendReturnReminderEmail(
            $pdo,
            $testEmail,
            $testName,
            $testAssetName,
            $expectedDate,
            $test['days'],
            $testRequestId,
            $test['urgency']
        );

        if ($result) {
            echo "<p class='success'>✓ Email sent successfully!</p>";
        } else {
            echo "<p class='error'>✗ Email send failed (check logs)</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
echo "</div>";

// Test 2: Overdue Alert Emails (Different Severity Levels)
echo "<div class='test-section'>";
echo "<h2>Test 2: Overdue Alert Emails (Escalation Levels)</h2>";

$overdueTests = [
    ['days' => 1, 'role' => 'borrower', 'label' => '1 Day Overdue (Borrower)'],
    ['days' => 3, 'role' => 'borrower', 'label' => '3 Days Overdue (Warning)'],
    ['days' => 5, 'role' => 'custodian', 'label' => '5 Days Overdue (Custodian Alert)'],
    ['days' => 10, 'role' => 'admin', 'label' => '10 Days Overdue (Admin Escalation)'],
    ['days' => 35, 'role' => 'admin', 'label' => '35 Days Overdue (Potentially Lost)']
];

foreach ($overdueTests as $test) {
    $expectedDate = date('Y-m-d', strtotime("-{$test['days']} days"));

    echo "<h3>🚨 {$test['label']}</h3>";
    echo "<p class='info'>Expected Return: " . date('F j, Y', strtotime($expectedDate)) . " ({$test['days']} days ago)</p>";

    try {
        $result = sendOverdueAlertEmail(
            $pdo,
            $testEmail,
            $testName . " ({$test['role']})",
            $testAssetName,
            $expectedDate,
            $test['days'],
            $testRequestId,
            $test['role']
        );

        if ($result) {
            echo "<p class='success'>✓ Email sent successfully!</p>";
        } else {
            echo "<p class='error'>✗ Email send failed (check logs)</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
echo "</div>";

// Test 3: Check Activity Logs
echo "<div class='test-section'>";
echo "<h2>Test 3: Activity Logs</h2>";

try {
    $logStmt = $pdo->prepare("
        SELECT action, description, created_at
        FROM activity_logs
        WHERE action IN ('EMAIL_SENT', 'EMAIL_FAILED')
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $logStmt->execute();
    $logs = $logStmt->fetchAll();

    if ($logs) {
        echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
        echo "<tr><th>Action</th><th>Description</th><th>Time</th></tr>";
        foreach ($logs as $log) {
            $actionClass = $log['action'] === 'EMAIL_SENT' ? 'success' : 'error';
            echo "<tr>";
            echo "<td class='{$actionClass}'>" . htmlspecialchars($log['action']) . "</td>";
            echo "<td>" . htmlspecialchars($log['description']) . "</td>";
            echo "<td>" . htmlspecialchars($log['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No email logs found.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error fetching logs: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: System Settings Check
echo "<div class='test-section'>";
echo "<h2>Test 4: System Settings</h2>";

$emailEnabled = getSystemSetting($pdo, 'enable_email_notifications', true);
$reminderDays = getSystemSetting($pdo, 'reminder_days_before', 2);
$autoMissingDays = getSystemSetting($pdo, 'auto_missing_after_days', 60);

echo "<ul>";
echo "<li><strong>Email Notifications:</strong> " . ($emailEnabled ? "<span class='success'>Enabled</span>" : "<span class='error'>Disabled</span>") . "</li>";
echo "<li><strong>Reminder Days Before:</strong> {$reminderDays} days</li>";
echo "<li><strong>Auto-Mark Missing After:</strong> {$autoMissingDays} days</li>";
echo "</ul>";
echo "</div>";

// Test 5: Manual Cron Job Test
echo "<div class='test-section'>";
echo "<h2>Test 5: Manual Cron Job Execution</h2>";
echo "<p class='info'>Click the buttons below to manually trigger the cron functions:</p>";

if (isset($_GET['run_reminders'])) {
    echo "<h3>Running sendReturnReminders()...</h3>";
    try {
        $count = sendReturnReminders($pdo);
        echo "<p class='success'>✓ Sent {$count} reminder(s)</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

if (isset($_GET['run_overdue'])) {
    echo "<h3>Running sendOverdueNotifications()...</h3>";
    try {
        $count = sendOverdueNotifications($pdo);
        echo "<p class='success'>✓ Sent {$count} overdue alert(s)</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<form method='GET' style='display:inline;'>";
echo "<button type='submit' name='run_reminders' value='1'>▶ Run Return Reminders</button>";
echo "</form>";

echo "<form method='GET' style='display:inline;'>";
echo "<button type='submit' name='run_overdue' value='1'>▶ Run Overdue Alerts</button>";
echo "</form>";

echo "</div>";

// Summary
echo "<div class='test-section' style='background: #d1fae5; border-left-color: #10b981;'>";
echo "<h2>✅ Testing Complete</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check your email inbox ({$testEmail}) for test emails</li>";
echo "<li>Verify email templates display correctly</li>";
echo "<li>Check spam folder if emails not received</li>";
echo "<li>Review activity logs above for any errors</li>";
echo "<li>Schedule the cron job in Windows Task Scheduler</li>";
echo "</ol>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>📋 Windows Task Scheduler Setup</h2>";
echo "<pre>";
echo "Task Name: HCC Asset Return Reminders\n";
echo "Program: C:\\xampp\\php\\php.exe\n";
echo "Arguments: \"C:\\xampp\\htdocs\\AMS-REQ\\cron\\check_overdue_assets.php\"\n";
echo "Start in: C:\\xampp\\htdocs\\AMS-REQ\n";
echo "Schedule: Daily at 8:00 AM\n";
echo "Run whether user is logged on or not: ✓\n";
echo "</pre>";
echo "</div>";

echo "</div></body></html>";
?>
