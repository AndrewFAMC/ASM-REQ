<?php
require_once 'config.php';

// Check if email notification constants are defined
echo "<h2>Email Notification Debug</h2>";

echo "<h3>1. Constants Check:</h3>";
echo "NOTIFICATION_APPROVAL_REQUEST = " . (defined('NOTIFICATION_APPROVAL_REQUEST') ? NOTIFICATION_APPROVAL_REQUEST : 'NOT DEFINED') . "<br>";

echo "<h3>2. Email Template Functions:</h3>";
echo "getApprovalRequestTemplate exists: " . (function_exists('getApprovalRequestTemplate') ? 'YES' : 'NO') . "<br>";
echo "sendNotificationEmail exists: " . (function_exists('sendNotificationEmail') ? 'YES' : 'NO') . "<br>";

echo "<h3>3. Test Email Send:</h3>";
if (isLoggedIn()) {
    $user = getUserInfo();
    echo "Logged in as: " . htmlspecialchars($user['full_name']) . " (" . htmlspecialchars($user['email']) . ")<br>";

    if (isset($_GET['send_test'])) {
        $htmlBody = getApprovalRequestTemplate(
            $user['full_name'],
            'Test Requester',
            'Test Asset Name',
            2,
            'TEST-123',
            'custodian'
        );

        $success = sendNotificationEmail($user['email'], $user['full_name'], 'Test Approval Request', $htmlBody);

        if ($success) {
            echo "<div style='color: green; font-weight: bold;'>✓ Email sent successfully to " . htmlspecialchars($user['email']) . "</div>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>✗ Failed to send email</div>";
        }
    } else {
        echo "<a href='?send_test=1' style='padding: 10px 20px; background: blue; color: white; text-decoration: none; border-radius: 5px;'>Send Test Email</a>";
    }
} else {
    echo "Not logged in. <a href='login.php'>Login first</a>";
}

echo "<h3>4. Check Recent Notifications:</h3>";
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $notifications = $stmt->fetchAll();

    if ($notifications) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Title</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>" . $notif['id'] . "</td>";
            echo "<td>" . $notif['user_id'] . "</td>";
            echo "<td>" . $notif['type'] . "</td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . $notif['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No notifications found.";
    }
}
?>
