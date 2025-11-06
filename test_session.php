<?php
require_once 'config.php';

if (!isLoggedIn() || !validateSession($pdo)) {
    echo "Not logged in or invalid session\n";
    exit;
}

$user = getUserInfo();
echo "User ID: " . $user['id'] . "\n";
echo "Username: " . $user['username'] . "\n";
echo "Full Name: " . $user['full_name'] . "\n";
echo "Role: " . $user['role'] . "\n";
echo "Office ID: " . $user['office_id'] . "\n";
echo "Campus ID: " . $user['campus_id'] . "\n";
echo "CSRF Token: " . ($_SESSION['csrf_token'] ?? 'Not set') . "\n";
?>
