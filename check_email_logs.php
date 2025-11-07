<?php
require_once 'config.php';

if (!isLoggedIn()) {
    die("Please login first");
}

echo "<h2>Email Notification Debug</h2>";

// Check PHP error log
echo "<h3>PHP Error Log (last 50 lines):</h3>";
$errorLog = "C:/xampp/php/logs/php_error_log";
if (file_exists($errorLog)) {
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -50);
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto;'>";
    foreach ($lastLines as $line) {
        if (stripos($line, 'email') !== false || stripos($line, 'mail') !== false || stripos($line, 'smtp') !== false) {
            echo "<strong style='color: red;'>" . htmlspecialchars($line) . "</strong>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log not found at: $errorLog</p>";
}

// Check recent notifications
echo "<h3>Recent Notifications (last 10):</h3>";
$stmt = $pdo->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$notifications = $stmt->fetchAll();

if ($notifications) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #eee;'><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        $userStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $userStmt->execute([$notif['user_id']]);
        $user = $userStmt->fetch();

        echo "<tr>";
        echo "<td>" . $notif['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name'] ?? 'Unknown') . "<br><small>" . htmlspecialchars($user['email'] ?? '') . "</small></td>";
        echo "<td>" . $notif['type'] . "</td>";
        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
        echo "<td>" . $notif['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No notifications found.</p>";
}

// Check recent requests
echo "<h3>Recent Asset Requests (last 5):</h3>";
$stmt = $pdo->prepare("
    SELECT ar.*, u.full_name as requester_name, u.email as requester_email, a.asset_name
    FROM asset_requests ar
    JOIN users u ON ar.requester_id = u.id
    JOIN assets a ON ar.asset_id = a.id
    ORDER BY ar.created_at DESC
    LIMIT 5
");
$stmt->execute();
$requests = $stmt->fetchAll();

if ($requests) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #eee;'><th>ID</th><th>Requester</th><th>Asset</th><th>Status</th><th>Created</th></tr>";
    foreach ($requests as $req) {
        echo "<tr>";
        echo "<td>" . $req['id'] . "</td>";
        echo "<td>" . htmlspecialchars($req['requester_name']) . "<br><small>" . htmlspecialchars($req['requester_email']) . "</small></td>";
        echo "<td>" . htmlspecialchars($req['asset_name']) . "</td>";
        echo "<td>" . $req['status'] . "</td>";
        echo "<td>" . $req['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No requests found.</p>";
}
?>
