<?php
// config.php

// --- Maintenance Mode Check ---
function check_maintenance_mode() {
    $maintenance_file = __DIR__ . '/maintenance.json';
    if (file_exists($maintenance_file)) {
        $status = json_decode(file_get_contents($maintenance_file), true);
        if (isset($status['status']) && $status['status'] === 'active' && time() < $status['end_time']) {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            // Exclude IT portal pages from maintenance mode
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'it_login.php' && $current_page !== 'it_dashboard.php') {
                $message = htmlspecialchars($status['message'] ?? 'We are performing scheduled maintenance. The system will be back online shortly.');
                $remaining_time = $status['end_time'] - time();
                
                if ($isAjax) {
                    http_response_code(503);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'maintenance' => true, 'message' => $message]);
                    exit;
                }

                http_response_code(503); // Service Unavailable
                echo <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Maintenance</title>
<style>
body{background-color:#111;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;text-align:center;animation:fadeIn 1s ease-in-out;}
.container{padding:2rem;}.icon{font-size:3rem;margin-bottom:1rem;}h1{font-size:2rem;margin-bottom:0.5rem;}p{font-size:1rem;color:#ccc;}
#timer{font-size:1.5rem;font-weight:bold;margin-top:1.5rem;color:#ffc107;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
</style></head><body><div class="container">
<div class="icon">⚙️</div><h1>System Under Maintenance</h1><p>{$message}</p>
<div id="timer"></div></div>
<script>
let endTime = {$status['end_time']} * 1000;
function updateTimer(){let now=new Date().getTime();let distance=endTime-now;if(distance<0){document.getElementById('timer').innerHTML='Maintenance complete. Please refresh.';return;}
let h=Math.floor((distance%(1000*60*60*24))/(1000*60*60));let m=Math.floor((distance%(1000*60*60))/(1000*60));let s=Math.floor((distance%(1000*60))/1000);
document.getElementById('timer').innerHTML='Time remaining: '+h.toString().padStart(2,'0')+':'+m.toString().padStart(2,'0')+':'+s.toString().padStart(2,'0');}
setInterval(updateTimer,1000);updateTimer();
</script></body></html>
HTML;
                exit;
            }
        }
    }
}
check_maintenance_mode();

// --- Real-time Maintenance Polling ---
function inject_maintenance_poller() {
    // --- FIX: Do not run this poller on AJAX requests or API endpoints ---
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Check if this is an API endpoint (returns JSON)
    $current_script = $_SERVER['PHP_SELF'] ?? '';
    $isApiEndpoint = strpos($current_script, '/api/') !== false;

    // Check if JSON content type was set
    $headersSet = headers_list();
    $isJsonResponse = false;
    foreach ($headersSet as $header) {
        if (stripos($header, 'Content-Type: application/json') !== false) {
            $isJsonResponse = true;
            break;
        }
    }

    if ($isAjax || $isApiEndpoint || $isJsonResponse || (isset($_POST['action']) && $_SERVER['REQUEST_METHOD'] === 'POST')) {
        return;
    }

    // Don't inject on IT portal pages or the login page itself
    $current_page = basename($_SERVER['PHP_SELF']);
    $excluded_pages = ['it_login.php', 'it_dashboard.php', 'login.php', 'register.php', 'change_password.php'];
    if (in_array($current_page, $excluded_pages)) {
        return;
    }

    // Inject the JavaScript poller at the end of the body
    echo <<<HTML
    <script>
        (function() {
            let maintenanceCheckInterval;
            let countdownInterval;

            function createMaintenanceOverlay(endTime, durationInSeconds) {
                if (document.getElementById('maintenance-overlay')) return;

                const durationMinutes = Math.floor(durationInSeconds / 60);
                const durationSeconds = durationInSeconds % 60;

                const overlay = document.createElement('div');
                overlay.id = 'maintenance-overlay';
                overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(10,10,10,1); color:white; display:flex; justify-content:center; align-items:center; z-index:99999; opacity:0; transition:opacity 0.5s ease; font-family:sans-serif; text-align:center;';
                
                overlay.innerHTML = \`
                    <div id="maintenance-intro" style="display:block; animation:zoomIn 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;">
                        <div style="font-size:10vw; font-weight:bold; color:#ffc107;">\${durationMinutes.toString().padStart(2, '0')}:\${durationSeconds.toString().padStart(2, '0')}</div>
                    </div>
                    <div id="maintenance-main" style="display:none; opacity:0; transition:opacity 0.5s ease 0.2s;">
                        <div style="font-size:3rem; margin-bottom:1rem;">⚙️</div>
                        <h1 style="font-size:2rem; margin-bottom:0.5rem;">System Under Maintenance</h1>
                        <p style="font-size:1rem; color:#ccc;">The system will be back online shortly.</p>
                        <div id="maintenance-timer" style="font-size:1.5rem; font-weight:bold; margin-top:1.5rem; color:#ffc107;"></div>
                    </div>
                    <style>
                        @keyframes zoomIn { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
                    </style>
                \`;
                document.body.appendChild(overlay);
                document.body.style.overflow = 'hidden';

                // Start fade-in
                setTimeout(() => { overlay.style.opacity = '1'; }, 10);

                // Transition from intro to main view
                setTimeout(() => {
                    const intro = document.getElementById('maintenance-intro');
                    const main = document.getElementById('maintenance-main');
                    if (intro) intro.style.display = 'none';
                    if (main) main.style.display = 'block';
                    setTimeout(() => { if(main) main.style.opacity = '1'; }, 10);
                }, 1200); // Show duration for 1.2 seconds

                // Start the countdown timer
                const timerEl = document.getElementById('maintenance-timer');
                function updateTimer() {
                    const now = Math.floor(Date.now() / 1000);
                    const remaining = endTime - now;
                    if (remaining <= 0) {
                        if(timerEl) timerEl.innerHTML = 'Maintenance complete. Please refresh.';
                        clearInterval(countdownInterval);
                        return;
                    }
                    const h = Math.floor(remaining / 3600);
                    const m = Math.floor((remaining % 3600) / 60);
                    const s = remaining % 60;
                    if(timerEl) timerEl.innerHTML = 'Time remaining: ' + h.toString().padStart(2,'0') + ':' + m.toString().padStart(2,'0') + ':' + s.toString().padStart(2,'0');
                }
                countdownInterval = setInterval(updateTimer, 1000);
                updateTimer();
            }

            function removeMaintenanceOverlay() {
                const overlay = document.getElementById('maintenance-overlay');
                if (overlay) {
                    overlay.style.opacity = '0';
                    setTimeout(() => {
                        overlay.remove();
                        document.body.style.overflow = 'auto';
                        clearInterval(countdownInterval);
                    }, 500);
                }
            }

            async function checkStatus() {
                try {
                    const response = await fetch('/AMS-REQ/api/status_check.php?t=' + Date.now());
                    const result = await response.json();
                    if (result.success && result.data.status === 'active' && result.data.end_time > Math.floor(Date.now() / 1000)) {
                        const duration = result.data.end_time - Math.floor(Date.now() / 1000);
                        createMaintenanceOverlay(result.data.end_time, duration);
                        clearInterval(maintenanceCheckInterval); // Stop polling once maintenance is active
                    } else {
                        removeMaintenanceOverlay();
                    }
                } catch (error) {
                    console.error('Maintenance status check failed:', error);
                }
            }
            
            // Start polling
            maintenanceCheckInterval = setInterval(checkStatus, 3000); // Check every 3 seconds
            checkStatus(); // Initial check
        })();
    </script>
HTML;
}

// Register the function to be called before the closing body tag
register_shutdown_function('inject_maintenance_poller');

// Autoload PHPMailer
require_once __DIR__ . '/vendor/autoload.php';

// Include email functions
require_once __DIR__ . '/includes/email_functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
        exit;
    } else {
        die("Connection failed: " . $e->getMessage() .
            "<br><br><strong>Make sure:</strong>" .
            "<br>1. XAMPP is running" .
            "<br>2. MySQL service is started" .
            "<br>3. Database 'hcc_asset_management' exists" .
            "<br>4. Run the SQL schema first");
    }
}

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getUserInfo() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'campus_id' => $_SESSION['campus_id'] ?? null,
        'office_id' => $_SESSION['office_id'] ?? null,
        'profile_picture' => $_SESSION['profile_picture'] ?? null
    ];
}

function hasRole($role) {
    $userRole = $_SESSION['role'] ?? null;
    
    $roleHierarchy = [
        'admin' => 3,
        'manager' => 2,
        'user' => 1
    ];
    
    return ($roleHierarchy[$userRole] ?? 0) >= ($roleHierarchy[$role] ?? 0);
}

function canAccessCampus($campusId) {
    $user = getUserInfo();
    
    // Admin can access all campuses
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Manager/User can only access their assigned campus
    return $user['campus_id'] == $campusId || $user['campus_id'] === null;
}

function validateSession($pdo) {
    // If not logged in according to session, it's invalid.
    if (!isLoggedIn()) return false;

    // Simplified validation: Check if the session ID exists and is not expired.
    // This avoids re-querying all user data on every single page load.
    // The session is populated with all necessary data upon login.
    $stmt = $pdo->prepare("SELECT 1 FROM user_sessions WHERE session_id = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['session_id']]);
    $session_is_valid = $stmt->fetchColumn();

    if ($session_is_valid) {
        return true;
    }

    // If the session is not found in the DB or is expired, destroy it.
    destroySession($pdo);
    return false;
}

function createSession($pdo, $userId) {
    try {
        $sessionId = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Clean up expired sessions
        $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()")->execute();
        
        // Create new session
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $sessionId, $ipAddress, $userAgent, $expiresAt]);
        
        // Get user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['campus_id'] = $user['campus_id'];
        $_SESSION['office_id'] = $user['office_id'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        
        // Check for forced password change
        if (isset($user['force_password_change']) && $user['force_password_change'] == 1) {
            $_SESSION['force_password_change'] = true;
        } else {
            unset($_SESSION['force_password_change']);
        }
        
        // Update last login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
           ->execute([$userId]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Session creation error: " . $e->getMessage());
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

function logLoginAttempt($pdo, $username, $success) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (username, ip_address, success, attempted_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $ipAddress, $success ? 1 : 0]);
    } catch (PDOException $e) {
        error_log("Login attempt logging error: " . $e->getMessage());
    }
}

function isAccountLocked($pdo, $username) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE (username = ? OR ip_address = ?) 
            AND success = FALSE 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$username, $ipAddress]);
        $result = $stmt->fetch();
        
        return ($result['attempts'] ?? 0) >= 5;
        
    } catch (PDOException $e) {
        error_log("Account lock check error: " . $e->getMessage());
        return false;
    }
}

function authenticateUser($pdo, $username, $password) {
    try {
        // TEMPORARILY DISABLED FOR TESTING - Account lockout check
        // Uncomment the lines below to re-enable security restrictions
        /*
        if (isAccountLocked($pdo, $username)) {
            logLoginAttempt($pdo, $username, false);
            return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts. Please try again in 1 minute.'];
        }
        */
        
        // Find user
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, is_active 
            FROM users 
            WHERE (username = ? OR email = ?) AND is_active = TRUE
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logLoginAttempt($pdo, $username, false);
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            logLoginAttempt($pdo, $username, false);
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
        
        // Create session
        if (createSession($pdo, $user['id'])) {
            logLoginAttempt($pdo, $username, true);
            return ['success' => true, 'message' => 'Login successful.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create session. Please try again.'];
        }
        
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'System error. Please try again later.'];
    }
}

// Helper functions for database operations (existing functions)
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

// Activity logging function
function logActivity($pdo, $assetId, $action, $description, $campusId = null) {
    // If campusId is not provided, try to get it from the asset
    if ($campusId === null && $assetId !== null) {
        $asset = fetchOne($pdo, "SELECT campus_id FROM assets WHERE id = ?", [$assetId]);
        if ($asset) {
            $campusId = $asset['campus_id'];
        }
    }

    // If campusId is still null, fall back to the logged-in user's session campus
    if ($campusId === null && isset($_SESSION['campus_id'])) {
        $campusId = $_SESSION['campus_id'];
    }

    $user = getUserInfo();
    $performedBy = $user['full_name'] ?? ($user['username'] ?? 'System');

    $sql = "INSERT INTO activity_log (asset_id, action, description, performed_by, campus_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    executeQuery($pdo, $sql, [
        $assetId, 
        $action, 
        $description, 
        $performedBy, 
        $campusId
    ]);
}

// Email verification function
function generateVerificationCode($pdo, $email) {
    $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    try {
        $stmt = $pdo->prepare("INSERT INTO email_verifications (email, verification_code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $code, $expiresAt]);

        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Verification code for $email: $code");
        }

        return $code;
    } catch (PDOException $e) {
        error_log("Error generating verification code: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// SYSTEM ENHANCEMENT - NEW CONSTANTS AND CONFIGURATIONS
// ============================================================================

// Asset Status Constants
define('ASSET_STATUS_ACTIVE', 'Active');
define('ASSET_STATUS_INACTIVE', 'Inactive');
define('ASSET_STATUS_DAMAGED', 'Damaged');
define('ASSET_STATUS_MISSING', 'Missing');
define('ASSET_STATUS_UNDER_REPAIR', 'Under Repair');
define('ASSET_STATUS_RETIRED', 'Retired');

// Asset Request Status Constants
define('REQUEST_STATUS_PENDING', 'pending');
define('REQUEST_STATUS_CUSTODIAN_REVIEW', 'custodian_review');
define('REQUEST_STATUS_DEPARTMENT_REVIEW', 'department_review');
define('REQUEST_STATUS_APPROVED', 'approved');
define('REQUEST_STATUS_REJECTED', 'rejected');
define('REQUEST_STATUS_RELEASED', 'released');
define('REQUEST_STATUS_RETURNED', 'returned');
define('REQUEST_STATUS_CANCELLED', 'cancelled');

// Borrowing Status Constants
define('BORROWING_STATUS_ACTIVE', 'active');
define('BORROWING_STATUS_RETURNED', 'returned');
define('BORROWING_STATUS_OVERDUE', 'overdue');
define('BORROWING_STATUS_NOT_RETURNED', 'not_returned');
define('BORROWING_STATUS_LOST', 'lost');

// Return Status Constants
define('RETURN_STATUS_ON_TIME', 'On Time');
define('RETURN_STATUS_RETURNED_LATE', 'Returned Late');
define('RETURN_STATUS_OVERDUE', 'Overdue');
define('RETURN_STATUS_NOT_RETURNED', 'Not Returned');

// Notification Type Constants
define('NOTIFICATION_RETURN_REMINDER', 'return_reminder');
define('NOTIFICATION_OVERDUE_ALERT', 'overdue_alert');
define('NOTIFICATION_APPROVAL_REQUEST', 'approval_request');
define('NOTIFICATION_APPROVAL_RESPONSE', 'approval_response');
define('NOTIFICATION_MISSING_REPORT', 'missing_report');
define('NOTIFICATION_SYSTEM_ALERT', 'system_alert');

// Notification Priority Constants
define('NOTIFICATION_PRIORITY_LOW', 'low');
define('NOTIFICATION_PRIORITY_MEDIUM', 'medium');
define('NOTIFICATION_PRIORITY_HIGH', 'high');
define('NOTIFICATION_PRIORITY_URGENT', 'urgent');

// User Role Constants
define('ROLE_STAFF', 'staff');
define('ROLE_CUSTODIAN', 'custodian');
define('ROLE_ADMIN', 'admin');
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_OFFICE', 'office');
define('ROLE_AUDITOR', 'auditor');

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

// CSRF Protection Functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Debug mode configuration
define('DEBUG_MODE', false); // Set to true for debugging purposes

// Google OAuth configuration check
function isGoogleOAuthConfigured() {
    // Stub function - implement actual Google OAuth configuration check
    // For now, return false to indicate not configured
    return false;
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Clean up expired sessions periodically
if (rand(1, 100) <= 5) { // 5% chance to run cleanup
    try {
        $pdo->prepare("CALL CleanExpiredSessions()")->execute();
    } catch (PDOException $e) {
        // Silently handle cleanup errors
    }
}

// ============================================================================
// SYSTEM SETTINGS FUNCTIONS
// ============================================================================

/**
 * Get a system setting value
 */
function getSystemSetting($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();

        if (!$result) {
            return $default;
        }

        // Convert based on type
        switch ($result['setting_type']) {
            case 'integer':
                return (int)$result['setting_value'];
            case 'boolean':
                return strtolower($result['setting_value']) === 'true';
            case 'json':
                return json_decode($result['setting_value'], true);
            default:
                return $result['setting_value'];
        }
    } catch (PDOException $e) {
        error_log("Error getting system setting: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set a system setting value
 */
function setSystemSetting($pdo, $key, $value, $type = 'string', $description = null) {
    try {
        // Convert value based on type
        switch ($type) {
            case 'boolean':
                $value = $value ? 'true' : 'false';
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }

        $userId = $_SESSION['user_id'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, setting_type, description, updated_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                setting_type = VALUES(setting_type),
                description = COALESCE(VALUES(description), description),
                updated_by = VALUES(updated_by)
        ");

        return $stmt->execute([$key, $value, $type, $description, $userId]);
    } catch (PDOException $e) {
        error_log("Error setting system setting: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// NOTIFICATION SYSTEM FUNCTIONS
// ============================================================================

/**
 * Create a notification for a user
 */
function createNotification($pdo, $userId, $type, $title, $message, $options = []) {
    try {
        $relatedType = $options['related_type'] ?? null;
        $relatedId = $options['related_id'] ?? null;
        $priority = $options['priority'] ?? NOTIFICATION_PRIORITY_MEDIUM;
        $actionUrl = $options['action_url'] ?? null;
        $expiresAt = $options['expires_at'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_type, related_id, priority, action_url, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $userId,
            $type,
            $title,
            $message,
            $relatedType,
            $relatedId,
            $priority,
            $actionUrl,
            $expiresAt
        ]);

        return $result ? $pdo->lastInsertId() : false;
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications for a user
 */
function getUnreadNotifications($pdo, $userId, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications
            WHERE user_id = ?
            AND is_read = FALSE
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY priority DESC, created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting unread notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM notifications
            WHERE user_id = ?
            AND is_read = FALSE
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error getting notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($pdo, $notificationId, $userId = null) {
    try {
        $sql = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ?";
        $params = [$notificationId];

        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsAsRead($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = ? AND is_read = FALSE");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete old/expired notifications
 */
function cleanupNotifications($pdo, $daysOld = 30) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM notifications
            WHERE (expires_at IS NOT NULL AND expires_at < NOW())
            OR (is_read = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY))
        ");
        return $stmt->execute([$daysOld]);
    } catch (PDOException $e) {
        error_log("Error cleaning up notifications: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// OVERDUE DETECTION FUNCTIONS
// ============================================================================

/**
 * Check for overdue borrowings and update status
 */
function checkOverdueBorrowings($pdo) {
    try {
        // Update status to overdue for items past expected return date
        $stmt = $pdo->prepare("
            UPDATE asset_borrowings
            SET status = 'overdue'
            WHERE status = 'active'
            AND expected_return_date IS NOT NULL
            AND expected_return_date < CURDATE()
        ");
        $stmt->execute();

        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error checking overdue borrowings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Send reminder notifications for upcoming return dates (Multi-Stage with Email)
 *
 * Reminder stages:
 * - 7 days before: Advance notice
 * - 2 days before: Upcoming return
 * - 1 day before: Urgent reminder
 * - Day of return: Return today
 */
function sendReturnReminders($pdo) {
    try {
        require_once __DIR__ . '/includes/email_functions.php';

        $emailEnabled = getSystemSetting($pdo, 'enable_email_notifications', true);

        // Define reminder stages
        $reminderStages = [
            ['days' => 7, 'urgency' => 'advance_notice', 'priority' => NOTIFICATION_PRIORITY_LOW],
            ['days' => 2, 'urgency' => 'upcoming', 'priority' => NOTIFICATION_PRIORITY_HIGH],
            ['days' => 1, 'urgency' => 'urgent', 'priority' => NOTIFICATION_PRIORITY_HIGH],
            ['days' => 0, 'urgency' => 'today', 'priority' => NOTIFICATION_PRIORITY_URGENT]
        ];

        $totalCount = 0;

        foreach ($reminderStages as $stage) {
            $daysUntil = $stage['days'];
            $urgencyLevel = $stage['urgency'];
            $priority = $stage['priority'];

            // Query asset_requests for released items
            $stmt = $pdo->prepare("
                SELECT
                    ar.id,
                    ar.asset_id,
                    ar.requester_id,
                    ar.expected_return_date,
                    a.asset_name,
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    ar.last_reminder_sent,
                    ar.reminder_count
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.status = 'released'
                AND ar.expected_return_date IS NOT NULL
                AND ar.expected_return_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND (
                    ar.last_reminder_sent IS NULL
                    OR DATE(ar.last_reminder_sent) < CURDATE()
                    OR (? = 0 AND TIME(ar.last_reminder_sent) < TIME(DATE_SUB(NOW(), INTERVAL 4 HOUR)))
                )
            ");
            $stmt->execute([$daysUntil, $daysUntil]);
            $requests = $stmt->fetchAll();

            foreach ($requests as $request) {
                // Create in-app notification
                createNotification($pdo, $request['user_id'], NOTIFICATION_RETURN_REMINDER,
                    $daysUntil == 0 ? 'Return Asset Today' : "Return Reminder ({$daysUntil} days)",
                    "Please return '{$request['asset_name']}' " .
                    ($daysUntil == 0 ? 'TODAY' : "in {$daysUntil} day(s)"),
                    [
                        'related_type' => 'request',
                        'related_id' => $request['id'],
                        'priority' => $priority,
                        'action_url' => '/employee/my_requests.php'
                    ]
                );

                // Send email notification if enabled
                if ($emailEnabled && $request['email']) {
                    sendReturnReminderEmail(
                        $pdo,
                        $request['email'],
                        $request['full_name'],
                        $request['asset_name'],
                        $request['expected_return_date'],
                        $daysUntil,
                        $request['id'],
                        $urgencyLevel
                    );
                }

                // Update reminder tracking
                $reminderCount = (int)($request['reminder_count'] ?? 0) + 1;
                $updateStmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET last_reminder_sent = NOW(), reminder_count = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$reminderCount, $request['id']]);

                $totalCount++;
            }
        }

        return $totalCount;
    } catch (PDOException $e) {
        error_log("Error sending return reminders: " . $e->getMessage());
        return 0;
    }
}

/**
 * Send overdue notifications with escalation chain
 *
 * Escalation levels:
 * - Day 1-3: Email to borrower only
 * - Day 4-7: Email to borrower + custodian
 * - Day 8-30: Email to borrower + custodian + admin
 * - Day 30+: Mark as potentially lost
 */
function sendOverdueNotifications($pdo) {
    try {
        require_once __DIR__ . '/includes/email_functions.php';

        $emailEnabled = getSystemSetting($pdo, 'enable_email_notifications', true);

        // Get overdue asset_requests
        $stmt = $pdo->prepare("
            SELECT
                ar.id,
                ar.asset_id,
                ar.requester_id,
                ar.expected_return_date,
                ar.campus_id,
                ar.last_overdue_alert_sent,
                ar.overdue_alert_count,
                a.asset_name,
                u.id as user_id,
                u.full_name,
                u.email,
                DATEDIFF(CURDATE(), ar.expected_return_date) as days_overdue
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN users u ON ar.requester_id = u.id
            WHERE ar.status = 'released'
            AND ar.expected_return_date < CURDATE()
            AND (
                ar.last_overdue_alert_sent IS NULL
                OR DATE(ar.last_overdue_alert_sent) < CURDATE()
            )
        ");
        $stmt->execute();
        $overdueRequests = $stmt->fetchAll();

        $count = 0;
        foreach ($overdueRequests as $request) {
            $daysOverdue = (int)$request['days_overdue'];

            // Send notification to borrower
            createNotification($pdo, $request['user_id'], NOTIFICATION_OVERDUE_ALERT,
                'Overdue Asset',
                "'{$request['asset_name']}' is {$daysOverdue} day(s) overdue. Please return it immediately.",
                [
                    'related_type' => 'request',
                    'related_id' => $request['id'],
                    'priority' => NOTIFICATION_PRIORITY_URGENT,
                    'action_url' => '/employee/my_requests.php'
                ]
            );

            // Send email to borrower
            if ($emailEnabled && $request['email']) {
                sendOverdueAlertEmail(
                    $pdo,
                    $request['email'],
                    $request['full_name'],
                    $request['asset_name'],
                    $request['expected_return_date'],
                    $daysOverdue,
                    $request['id'],
                    'borrower'
                );
            }

            // ESCALATION LEVEL 1: Days 4-7 - Notify custodians
            if ($daysOverdue >= 4 && $daysOverdue <= 7) {
                $custodianStmt = $pdo->prepare("
                    SELECT id, email, full_name
                    FROM users
                    WHERE role = 'custodian'
                    AND campus_id = ?
                    AND is_active = 1
                ");
                $custodianStmt->execute([$request['campus_id']]);
                $custodians = $custodianStmt->fetchAll();

                foreach ($custodians as $custodian) {
                    createNotification($pdo, $custodian['id'], NOTIFICATION_OVERDUE_ALERT,
                        'Overdue Asset Alert',
                        "Asset '{$request['asset_name']}' is {$daysOverdue} days overdue. Borrower: {$request['full_name']}",
                        [
                            'related_type' => 'request',
                            'related_id' => $request['id'],
                            'priority' => NOTIFICATION_PRIORITY_HIGH
                        ]
                    );

                    if ($emailEnabled && $custodian['email']) {
                        sendOverdueAlertEmail(
                            $pdo,
                            $custodian['email'],
                            $custodian['full_name'],
                            $request['asset_name'],
                            $request['expected_return_date'],
                            $daysOverdue,
                            $request['id'],
                            'custodian'
                        );
                    }
                }
            }

            // ESCALATION LEVEL 2: Days 8+ - Notify admins
            if ($daysOverdue >= 8) {
                $adminStmt = $pdo->prepare("
                    SELECT id, email, full_name
                    FROM users
                    WHERE role IN ('admin', 'super_admin')
                    AND is_active = 1
                ");
                $adminStmt->execute();
                $admins = $adminStmt->fetchAll();

                foreach ($admins as $admin) {
                    createNotification($pdo, $admin['id'], NOTIFICATION_OVERDUE_ALERT,
                        'Overdue Asset Escalation',
                        "ESCALATION: Asset '{$request['asset_name']}' is {$daysOverdue} days overdue. Borrower: {$request['full_name']}",
                        [
                            'related_type' => 'request',
                            'related_id' => $request['id'],
                            'priority' => NOTIFICATION_PRIORITY_URGENT
                        ]
                    );

                    if ($emailEnabled && $admin['email']) {
                        sendOverdueAlertEmail(
                            $pdo,
                            $admin['email'],
                            $admin['full_name'],
                            $request['asset_name'],
                            $request['expected_return_date'],
                            $daysOverdue,
                            $request['id'],
                            'admin'
                        );
                    }
                }
            }

            // Update tracking
            $alertCount = (int)($request['overdue_alert_count'] ?? 0) + 1;
            $updateStmt = $pdo->prepare("
                UPDATE asset_requests
                SET last_overdue_alert_sent = NOW(), overdue_alert_count = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$alertCount, $request['id']]);

            $count++;
        }

        return $count;
    } catch (PDOException $e) {
        error_log("Error sending overdue notifications: " . $e->getMessage());
        return 0;
    }
}

?>
