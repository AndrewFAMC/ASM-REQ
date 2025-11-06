<?php
// Include database configuration
require_once 'config.php';

// Destroy the session
destroySession($pdo);

// Set a session variable for logout success message
session_start();
$_SESSION['logout_success'] = true;

// Redirect to login page
header('Location: login.php');
exit;
?>