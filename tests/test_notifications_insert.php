<?php
/**
 * Test Notification Insertion Helper
 *
 * Inserts test notifications directly into the database
 */

require_once 'config.php';

// Require admin access
if (!isLoggedIn() || !hasRole('employee')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
$type = $_POST['type'] ?? '';
$title = $_POST['title'] ?? '';
$message = $_POST['message'] ?? '';
$priority = $_POST['priority'] ?? 'medium';

if (!$userId || !$type || !$title || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Insert notification directly
    $stmt = $pdo->prepare("
        INSERT INTO notifications
        (user_id, type, title, message, priority, created_at, is_read)
        VALUES (?, ?, ?, ?, ?, NOW(), FALSE)
    ");

    $result = $stmt->execute([$userId, $type, $title, $message, $priority]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification created',
            'notification_id' => $pdo->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to insert notification');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
