<?php
/**
 * Simple CLI Test for Email Functions
 * Run: php test_email_simple.php
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line.\n");
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email_functions.php';

echo "\n========================================\n";
echo "   EMAIL REMINDER SYSTEM TEST\n";
echo "========================================\n\n";

$testEmail = 'mico.macapugay2004@gmail.com';
$testName = 'Test User';
$testAssetName = 'Test Projector XYZ-123';
$testRequestId = 9999;

echo "Test Configuration:\n";
echo "  Email: {$testEmail}\n";
echo "  Asset: {$testAssetName}\n";
echo "  Request ID: #{$testRequestId}\n\n";

// Test 1: 2-Day Reminder
echo "TEST 1: Sending 2-Day Return Reminder...\n";
$result = sendReturnReminderEmail(
    $pdo,
    $testEmail,
    $testName,
    $testAssetName,
    date('Y-m-d', strtotime('+2 days')),
    2,
    $testRequestId,
    'upcoming'
);
echo $result ? "  ✓ SUCCESS\n" : "  ✗ FAILED\n";

// Test 2: Urgent (Tomorrow) Reminder
echo "\nTEST 2: Sending Urgent (1-Day) Reminder...\n";
$result = sendReturnReminderEmail(
    $pdo,
    $testEmail,
    $testName,
    $testAssetName,
    date('Y-m-d', strtotime('+1 day')),
    1,
    $testRequestId,
    'urgent'
);
echo $result ? "  ✓ SUCCESS\n" : "  ✗ FAILED\n";

// Test 3: Overdue Alert (Borrower)
echo "\nTEST 3: Sending Overdue Alert (3 days)...\n";
$result = sendOverdueAlertEmail(
    $pdo,
    $testEmail,
    $testName,
    $testAssetName,
    date('Y-m-d', strtotime('-3 days')),
    3,
    $testRequestId,
    'borrower'
);
echo $result ? "  ✓ SUCCESS\n" : "  ✗ FAILED\n";

// Check Activity Logs
echo "\n========================================\n";
echo "   RECENT EMAIL ACTIVITY LOGS\n";
echo "========================================\n";

$logStmt = $pdo->prepare("
    SELECT action, description, created_at
    FROM activity_logs
    WHERE action IN ('EMAIL_SENT', 'EMAIL_FAILED')
    ORDER BY created_at DESC
    LIMIT 5
");
$logStmt->execute();
$logs = $logStmt->fetchAll();

foreach ($logs as $log) {
    $status = $log['action'] === 'EMAIL_SENT' ? '✓' : '✗';
    echo "{$status} [{$log['created_at']}] {$log['description']}\n";
}

echo "\n========================================\n";
echo "   TEST COMPLETE\n";
echo "========================================\n\n";
echo "Check your inbox: {$testEmail}\n";
echo "Check spam folder if emails not received.\n\n";
?>
