<?php
// Include database configuration
require_once 'config.php';

// Enforce authentication and role-based access (admin only)
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: login.php');
    exit;
}
$user = getUserInfo();
$role = strtolower($user['role'] ?? '');
if ($role !== 'admin') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. This page is for Admin users only.']);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied. This page is for Admin users only.';
    }
    exit;
}

// Bind user campus
$USER_CAMPUS_ID = (int)($user['campus_id'] ?? 0);

// Handle AJAX requests
// The main AJAX handling is now in admin_dashboard.php, which is included by the view.
// This block is kept for handling session expiry on AJAX calls from the main page.
