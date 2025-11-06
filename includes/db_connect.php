<?php
// Database connection for HCC Asset Management System
// Make sure XAMPP MySQL is running before using this

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = 'localhost';
$dbname = 'hcc_asset_management';
$username = 'root';
$password = ''; // Default XAMPP MySQL password is empty

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // If connection fails, show error message
    die("Connection failed: " . $e->getMessage() .
        "<br><br><strong>Make sure:</strong>" .
        "<br>1. XAMPP is running" .
        "<br>2. MySQL service is started" .
        "<br>3. Database 'hcc_asset_management' exists" .
        "<br>4. Run the SQL schema first");
}

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_id']);
}

function validateSession($pdo) {
    if (!isLoggedIn()) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.full_name, u.email, u.role, u.campus_id
            FROM users u
            JOIN user_sessions s ON u.id = s.user_id
            WHERE s.session_id = ? AND s.expires_at > NOW() AND u.is_active = TRUE
        ");
        $stmt->execute([$_SESSION['session_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            destroySession($pdo);
            return false;
        }

        // Update last activity
        $pdo->prepare("UPDATE user_sessions SET expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE session_id = ?")
             ->execute([$_SESSION['session_id']]);

        return true;

    } catch (PDOException $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

function destroySession($pdo) {
    if (isset($_SESSION['session_id'])) {
        try {
            $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?")
               ->execute([$_SESSION['session_id']]);
        } catch (PDOException $e) {
            error_log("Session destruction error: " . $e->getMessage());
        }
    }

    $_SESSION = [];
    session_destroy();
}

// Helper functions for database operations
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        throw $e;
    }
}

function fetchAll($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetchAll();
}

function fetchOne($pdo, $sql, $params = []) {
    $stmt = executeQuery($pdo, $sql, $params);
    return $stmt->fetch();
}

// Campus mapping for backward compatibility
$campusMapping = [
    'main' => 1,
    'north' => 2
];


$campusNames = [
    1 => 'Sta. Rosa, Nueva Ecija',
    2 => 'Conception, Tarlac'
];


$campusCodes = [
    1 => 'main',
    2 => 'north'
];

?>
