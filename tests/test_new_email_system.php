<?php
/**
 * Test Script for New Email System
 *
 * This script tests all components of the refactored email system:
 * - Email configuration
 * - Email queue operations
 * - Email templates
 * - Backward compatibility
 *
 * Usage: php tests/test_new_email_system.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email/Email.php';
require_once __DIR__ . '/../includes/email_functions.php';

echo "========================================\n";
echo "  NEW EMAIL SYSTEM TEST SUITE\n";
echo "========================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Email Configuration
echo "[TEST 1] Email Configuration...\n";
try {
    if (EmailConfig::$smtp_host === 'smtp.gmail.com' &&
        EmailConfig::$smtp_user === 'mico.macapugay2004@gmail.com' &&
        !empty(EmailConfig::$smtp_pass)) {
        echo "  ✓ SMTP configuration loaded correctly\n";
        echo "  ✓ Host: " . EmailConfig::$smtp_host . "\n";
        echo "  ✓ User: " . EmailConfig::$smtp_user . "\n";
        $testsPassed++;
    } else {
        throw new Exception("Configuration incomplete");
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 2: Email Queue Operations
echo "[TEST 2] Email Queue Operations...\n";
try {
    $queue = new EmailQueue($pdo);

    // Test adding to queue
    $emailId = $queue->add(
        'test@example.com',
        'Test User',
        'Test Email Subject',
        '<html><body>Test email body</body></html>',
        'normal',
        'test',
        999
    );

    if ($emailId !== false) {
        echo "  ✓ Email added to queue (ID: {$emailId})\n";

        // Test retrieving from queue
        $pending = $queue->getPending(1);
        if (!empty($pending) && $pending[0]['id'] == $emailId) {
            echo "  ✓ Email retrieved from queue\n";
            $testsPassed++;

            // Clean up test email
            $stmt = $pdo->prepare("DELETE FROM email_queue WHERE id = ?");
            $stmt->execute([$emailId]);
            echo "  ✓ Test email cleaned up\n";
        } else {
            throw new Exception("Could not retrieve email from queue");
        }
    } else {
        throw new Exception("Could not add email to queue");
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 3: Email Templates
echo "[TEST 3] Email Template Rendering...\n";
try {
    $template = new AccountCreationEmail();
    $html = $template->render('John Doe', 'john@example.com', 'password123');

    if (strpos($html, 'John Doe') !== false &&
        strpos($html, 'john@example.com') !== false &&
        strpos($html, 'password123') !== false &&
        strpos($html, 'We Find Assets') !== false) {
        echo "  ✓ AccountCreationEmail template renders correctly\n";
        $testsPassed++;
    } else {
        throw new Exception("Template rendering incomplete");
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 4: Email Facade Class
echo "[TEST 4] Email Facade Class...\n";
try {
    $email = new Email($pdo);

    // Test queuing an email
    $emailId = $email->sendAccountCreation('test@example.com', 'Test User', 'testpass123');

    if ($emailId !== false) {
        echo "  ✓ Email queued via Email facade class\n";
        $testsPassed++;

        // Clean up
        $stmt = $pdo->prepare("DELETE FROM email_queue WHERE id = ?");
        $stmt->execute([$emailId]);
        echo "  ✓ Test email cleaned up\n";
    } else {
        throw new Exception("Could not queue email via facade");
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 5: Backward Compatibility
echo "[TEST 5] Backward Compatibility (Legacy Functions)...\n";
try {
    // Test old function name
    $result = sendAccountCreationEmail($pdo, 'legacy@example.com', 'Legacy User', 'oldpass123');

    if ($result === true) {
        echo "  ✓ Legacy function sendAccountCreationEmail() works\n";

        // Verify it was queued
        $stmt = $pdo->prepare("SELECT id FROM email_queue WHERE recipient_email = 'legacy@example.com' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $emailId = $stmt->fetchColumn();

        if ($emailId) {
            echo "  ✓ Legacy function uses new queue system internally\n";
            $testsPassed++;

            // Clean up
            $stmt = $pdo->prepare("DELETE FROM email_queue WHERE id = ?");
            $stmt->execute([$emailId]);
            echo "  ✓ Test email cleaned up\n";
        } else {
            throw new Exception("Email not found in queue");
        }
    } else {
        throw new Exception("Legacy function returned false");
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 6: Queue Statistics
echo "[TEST 6] Queue Statistics...\n";
try {
    $email = new Email($pdo);
    $stats = $email->getQueueStats();

    if (isset($stats['total']) && isset($stats['pending']) && isset($stats['sent'])) {
        echo "  ✓ Queue statistics retrieved\n";
        echo "    - Total (24h): {$stats['total']}\n";
        echo "    - Pending: {$stats['pending']}\n";
        echo "    - Sent: {$stats['sent']}\n";
        echo "    - Failed: {$stats['failed']}\n";
        echo "    - High Priority: {$stats['high_priority']}\n";
        $testsPassed++;
    } else {
        throw new Exception("Statistics incomplete");
    }
} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 7: All Email Types
echo "[TEST 7] All Email Types Can Be Queued...\n";
$emailIds = [];
try {
    $email = new Email($pdo);

    // Account Creation
    $id = $email->sendAccountCreation('test1@example.com', 'User 1', 'pass123');
    if ($id) {
        $emailIds[] = $id;
        echo "  ✓ Account creation email queued\n";
    }

    // Asset Request
    $id = $email->sendRequestNotification('test2@example.com', 'User 2', 1, 'Laptop', 'approved', 'Request approved');
    if ($id) {
        $emailIds[] = $id;
        echo "  ✓ Asset request email queued\n";
    }

    // Return Reminder
    $id = $email->sendReturnReminder('test3@example.com', 'User 3', 'Monitor', '2025-12-31', 3, 1, 'upcoming');
    if ($id) {
        $emailIds[] = $id;
        echo "  ✓ Return reminder email queued\n";
    }

    // Overdue Alert
    $id = $email->sendOverdueAlert('test4@example.com', 'User 4', 'Keyboard', '2025-11-01', 10, 1, 'borrower');
    if ($id) {
        $emailIds[] = $id;
        echo "  ✓ Overdue alert email queued\n";
    }

    // Missing Asset
    $id = $email->sendMissingAssetAlert('test5@example.com', 'User 5', 'Mouse', 'M001', 1, 'custodian', [
        'last_known_location' => 'Office 101',
        'description' => 'Test missing asset'
    ]);
    if ($id) {
        $emailIds[] = $id;
        echo "  ✓ Missing asset email queued\n";
    }

    if (count($emailIds) === 5) {
        echo "  ✓ All email types work correctly\n";
        $testsPassed++;
    } else {
        throw new Exception("Not all email types queued successfully");
    }

    // Clean up
    foreach ($emailIds as $id) {
        $stmt = $pdo->prepare("DELETE FROM email_queue WHERE id = ?");
        $stmt->execute([$id]);
    }
    echo "  ✓ Test emails cleaned up\n";

} catch (Exception $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Summary
echo "========================================\n";
echo "  TEST SUMMARY\n";
echo "========================================\n";
echo "Tests Passed: {$testsPassed}\n";
echo "Tests Failed: {$testsFailed}\n";
echo "\n";

if ($testsFailed === 0) {
    echo "🎉 ALL TESTS PASSED! Email system is working correctly.\n\n";
    echo "Next steps:\n";
    echo "1. Start the email queue worker: php cron/process_email_queue.php\n";
    echo "2. Check email queue status in database: SELECT * FROM email_queue;\n";
    echo "3. Test sending a real email by creating a user or making a request\n";
} else {
    echo "⚠️  SOME TESTS FAILED. Please review the errors above.\n";
}

echo "\n";
