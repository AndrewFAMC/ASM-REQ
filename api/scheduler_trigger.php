<?php
/**
 * Scheduler Trigger API
 *
 * This API endpoint triggers the notification scheduler remotely
 * Can be called by cron services, webhooks, or external schedulers
 *
 * Usage:
 * GET/POST: /api/scheduler_trigger.php?api_key=YOUR_SECRET_KEY
 *
 * Security: Requires API key authentication
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Configuration
define('API_SECRET_KEY', 'hcc_scheduler_api_2024_secure'); // Change this in production!
define('ALLOWED_IPS', ['127.0.0.1', '::1']); // Add allowed IPs here, or use ['*'] for all

// Check if request is allowed
$requestMethod = $_SERVER['REQUEST_METHOD'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Verify API key
$apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? '';

if ($apiKey !== API_SECRET_KEY) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Invalid API key',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Optional: Check IP whitelist (comment out if not needed)
/*
if (!in_array('*', ALLOWED_IPS) && !in_array($clientIP, ALLOWED_IPS)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden - IP not whitelisted',
        'ip' => $clientIP,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
*/

// Log the API call
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$apiLogFile = $logDir . '/api_scheduler_calls.log';
$logEntry = sprintf(
    "[%s] API called from IP: %s | Method: %s\n",
    date('Y-m-d H:i:s'),
    $clientIP,
    $requestMethod
);
file_put_contents($apiLogFile, $logEntry, FILE_APPEND);

// Start output buffering to capture scheduler output
ob_start();

try {
    // Include the scheduler
    $schedulerPath = __DIR__ . '/../cron/asset_notification_scheduler.php';

    if (!file_exists($schedulerPath)) {
        throw new Exception('Scheduler file not found');
    }

    // Execute the scheduler
    include $schedulerPath;

    // Capture output
    $schedulerOutput = ob_get_clean();

    // Parse the output for statistics (optional)
    preg_match('/2-Day Reminders Sent: (\d+)/', $schedulerOutput, $reminders);
    preg_match('/Overdue Alerts Sent: (\d+)/', $schedulerOutput, $overdue);
    preg_match('/Errors: (\d+)/', $schedulerOutput, $errors);

    $stats = [
        'two_day_reminders' => isset($reminders[1]) ? (int)$reminders[1] : 0,
        'overdue_alerts' => isset($overdue[1]) ? (int)$overdue[1] : 0,
        'errors' => isset($errors[1]) ? (int)$errors[1] : 0
    ];

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Scheduler executed successfully',
        'statistics' => $stats,
        'execution_time' => date('Y-m-d H:i:s'),
        'client_ip' => $clientIP,
        'output_preview' => substr($schedulerOutput, 0, 500) . '...'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    ob_end_clean();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // Log error
    $errorLog = sprintf(
        "[%s] ERROR: %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    );
    file_put_contents($apiLogFile, $errorLog, FILE_APPEND);
}
?>
