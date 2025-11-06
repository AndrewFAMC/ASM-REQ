<?php
require_once 'config.php';

// Unset only IT admin session variables
unset($_SESSION['it_admin_logged_in']);
unset($_SESSION['it_admin_email']);
unset($_SESSION['it_admin_login_time']);

// Redirect to the IT login page
header('Location: it_login.php');
exit;