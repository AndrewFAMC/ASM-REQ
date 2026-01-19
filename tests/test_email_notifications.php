<?php
/**
 * Email Notification Testing Suite
 *
 * Tests all email notification scenarios:
 * 1. Custodian Approval → Next Approver
 * 2. Admin Final Approval → Requester
 * 3. Request Rejection → Requester
 * 4. Asset Release → Requester
 * 5. Asset Return (On-time) → Requester
 * 6. Asset Return (Late) → Requester
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email_functions.php';

// Test configuration
$testEmail = 'andrewbeley7@gmail.com'; // Change this to your test email
$testRecipientName = 'Andrew Beley';

echo "=== Email Notification Testing Suite ===\n";
echo "Test Email: {$testEmail}\n";
echo "Start Time: " . date('Y-m-d H:i:s') . "\n\n";

$results = [];

// ================================================================
// Test 1: Custodian Approval - Email to Next Approver
// ================================================================
echo "Test 1: Custodian Approval Email (Next Approver)\n";
echo str_repeat('-', 60) . "\n";

try {
    $result = sendRequestNotificationEmail(
        $pdo,
        $testEmail,
        $testRecipientName,
        101,
        'Laptop HP ProBook',
        'pending_approval',
        "Request #101 for Laptop HP ProBook has been approved by custodian and requires your approval."
    );

    if ($result) {
        echo "✓ SUCCESS: Email sent successfully\n";
        $results['custodian_approval'] = 'PASS';
    } else {
        echo "✗ FAILED: Email could not be sent\n";
        $results['custodian_approval'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    $results['custodian_approval'] = 'ERROR';
}

echo "\n";
sleep(2); // Delay between emails to avoid rate limiting

// ================================================================
// Test 2: Admin Final Approval - Email to Requester
// ================================================================
echo "Test 2: Admin Final Approval Email (Requester)\n";
echo str_repeat('-', 60) . "\n";

try {
    $result = sendRequestNotificationEmail(
        $pdo,
        $testEmail,
        $testRecipientName,
        102,
        'Projector Epson EB-X41',
        'approved',
        "Your request for Projector Epson EB-X41 has been fully approved! Please coordinate with the custodian for asset pickup."
    );

    if ($result) {
        echo "✓ SUCCESS: Email sent successfully\n";
        $results['admin_approval'] = 'PASS';
    } else {
        echo "✗ FAILED: Email could not be sent\n";
        $results['admin_approval'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    $results['admin_approval'] = 'ERROR';
}

echo "\n";
sleep(2);

// ================================================================
// Test 3: Request Rejection - Email to Requester
// ================================================================
echo "Test 3: Request Rejection Email (Requester)\n";
echo str_repeat('-', 60) . "\n";

try {
    $result = sendRequestNotificationEmail(
        $pdo,
        $testEmail,
        $testRecipientName,
        103,
        'Whiteboard Magnetic',
        'rejected',
        "Your request for Whiteboard Magnetic has been rejected. Reason: Item is currently unavailable for the requested date."
    );

    if ($result) {
        echo "✓ SUCCESS: Email sent successfully\n";
        $results['rejection'] = 'PASS';
    } else {
        echo "✗ FAILED: Email could not be sent\n";
        $results['rejection'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    $results['rejection'] = 'ERROR';
}

echo "\n";
sleep(2);

// ================================================================
// Test 4: Asset Release - Email to Requester
// ================================================================
echo "Test 4: Asset Release Email (Requester)\n";
echo str_repeat('-', 60) . "\n";

try {
    $expectedReturnDate = date('F j, Y', strtotime('+7 days'));
    $result = sendRequestNotificationEmail(
        $pdo,
        $testEmail,
        $testRecipientName,
        104,
        'Desktop Computer Dell OptiPlex',
        'released',
        "Your requested asset 'Desktop Computer Dell OptiPlex' has been released. Please return it by {$expectedReturnDate}."
    );

    if ($result) {
        echo "✓ SUCCESS: Email sent successfully\n";
        $results['release'] = 'PASS';
    } else {
        echo "✗ FAILED: Email could not be sent\n";
        $results['release'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    $results['release'] = 'ERROR';
}

echo "\n";
sleep(2);

// ================================================================
// Test 5: Asset Return On-Time - Email to Requester
// ================================================================
echo "Test 5: Asset Return On-Time Email (Requester)\n";
echo str_repeat('-', 60) . "\n";

try {
    $result = sendRequestNotificationEmail(
        $pdo,
        $testEmail,
        $testRecipientName,
        105,
        'Chair Office Swivel',
        'returned',
        "Your borrowed asset 'Chair Office Swivel' has been returned on time. Condition: good. Thank you!"
    );

    if ($result) {
        echo "✓ SUCCESS: Email sent successfully\n";
        $results['return_ontime'] = 'PASS';
    } else {
        echo "✗ FAILED: Email could not be sent\n";
        $results['return_ontime'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    $results['return_ontime'] = 'ERROR';
}

echo "\n";
sleep(2);

// ================================================================
// Test 6: Asset Return Late - Email to Requester
// ================================================================
echo "Test 6: Asset Return Late Email (Requester)\n";
echo str_repeat('-', 60) . "\n";

try {
    $result = sendRequestNotificationEmail(
        $pdo,
        $testEmail,
        $testRecipientName,
        106,
        'Microphone Wireless',
        'late_return',
        "Your borrowed asset 'Microphone Wireless' has been returned 3 day(s) late. Condition: good. Late return remarks: Delayed due to extended seminar."
    );

    if ($result) {
        echo "✓ SUCCESS: Email sent successfully\n";
        $results['return_late'] = 'PASS';
    } else {
        echo "✗ FAILED: Email could not be sent\n";
        $results['return_late'] = 'FAIL';
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    $results['return_late'] = 'ERROR';
}

echo "\n";

// ================================================================
// Test Summary
// ================================================================
echo str_repeat('=', 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 60) . "\n";

$passed = 0;
$failed = 0;
$errors = 0;

foreach ($results as $test => $result) {
    $icon = match($result) {
        'PASS' => '✓',
        'FAIL' => '✗',
        'ERROR' => '⚠',
        default => '?'
    };

    echo sprintf("%-30s %s %s\n", ucwords(str_replace('_', ' ', $test)), $icon, $result);

    if ($result === 'PASS') $passed++;
    elseif ($result === 'FAIL') $failed++;
    elseif ($result === 'ERROR') $errors++;
}

echo str_repeat('-', 60) . "\n";
echo sprintf("Total Tests: %d | Passed: %d | Failed: %d | Errors: %d\n",
    count($results), $passed, $failed, $errors);
echo str_repeat('=', 60) . "\n";

echo "\nEnd Time: " . date('Y-m-d H:i:s') . "\n";

// Check email logs
echo "\n=== Recent Email Activity Logs ===\n";
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
    foreach ($logs as $log) {
        $icon = $log['action'] === 'EMAIL_SENT' ? '✓' : '✗';
        echo sprintf("[%s] %s %s\n",
            $log['created_at'],
            $icon,
            $log['description']
        );
    }
} else {
    echo "No email activity logs found.\n";
}

echo "\n=== Instructions ===\n";
echo "1. Check your inbox: {$testEmail}\n";
echo "2. Look for 6 test emails with different subjects:\n";
echo "   - Asset Request Pending Approval\n";
echo "   - Asset Request Approved\n";
echo "   - Asset Request Rejected\n";
echo "   - Asset Request Released\n";
echo "   - Asset Request Returned (2 emails)\n";
echo "3. Verify email formatting, branding, and content\n";
echo "4. Check spam folder if emails are not in inbox\n";

if ($passed === count($results)) {
    echo "\n🎉 ALL TESTS PASSED! Email notifications are working correctly.\n";
} elseif ($failed > 0 || $errors > 0) {
    echo "\n⚠ SOME TESTS FAILED! Please check the error logs above.\n";
}

echo "\nTest completed.\n";
?>
