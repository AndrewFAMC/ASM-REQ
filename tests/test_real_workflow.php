<?php
/**
 * Test Real Workflow Email Notifications
 *
 * This tests if emails are sent when using the actual API endpoints
 */

require_once __DIR__ . '/config.php';

echo "=== Testing Real Workflow Email Notifications ===\n\n";

// Check if sendRequestNotificationEmail function exists
if (function_exists('sendRequestNotificationEmail')) {
    echo "✓ sendRequestNotificationEmail function is available\n";
} else {
    echo "✗ sendRequestNotificationEmail function NOT FOUND!\n";
    echo "This is the problem - the function is not loaded.\n";
    exit(1);
}

// Check if we can call it directly
echo "\nTesting direct function call...\n";
try {
    $result = sendRequestNotificationEmail(
        $pdo,
        'andrewbeley7@gmail.com',
        'Test User',
        999,
        'Test Asset',
        'approved',
        'This is a direct function call test'
    );

    if ($result) {
        echo "✓ Direct function call SUCCESS - Email sent\n";
    } else {
        echo "✗ Direct function call FAILED - Email not sent\n";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Check Recent Requests ===\n";
$stmt = $pdo->prepare("
    SELECT id, status, requester_id
    FROM asset_requests
    WHERE status IN ('pending', 'approved_custodian', 'approved_admin')
    ORDER BY request_date DESC
    LIMIT 5
");
$stmt->execute();
$requests = $stmt->fetchAll();

if (count($requests) > 0) {
    echo "Found " . count($requests) . " requests:\n";
    foreach ($requests as $req) {
        echo "  - Request #{$req['id']}: {$req['status']}\n";
    }
} else {
    echo "No pending/approved requests found\n";
}

echo "\nTest completed.\n";
?>
