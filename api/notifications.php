<?php
/**
 * Notifications API Endpoint
 *
 * Handles all notification-related operations:
 * - Get unread notifications
 * - Get notification count
 * - Mark as read
 * - Mark all as read
 * - Get notification history
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication
requireLogin();

header('Content-Type: application/json');

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        // Get unread notifications
        case 'get_unread':
            $limit = (int)($_GET['limit'] ?? 10);
            $notifications = getUnreadNotifications($pdo, $userId, $limit);

            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
            break;

        // Get unread count only
        case 'get_count':
            $count = getUnreadNotificationCount($pdo, $userId);

            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;

        // Get all notifications (with pagination)
        case 'get_all':
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;

            $stmt = $pdo->prepare("
                SELECT * FROM notifications
                WHERE user_id = ?
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY is_read ASC, created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            $notifications = $stmt->fetchAll();

            // Get total count
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) as total FROM notifications
                WHERE user_id = ?
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $countStmt->execute([$userId]);
            $totalCount = $countStmt->fetch()['total'];

            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalCount / $limit),
                    'total_items' => (int)$totalCount,
                    'items_per_page' => $limit
                ]
            ]);
            break;

        // Mark single notification as read
        case 'mark_read':
            $notificationId = (int)($_POST['notification_id'] ?? 0);

            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }

            $success = markNotificationAsRead($pdo, $notificationId, $userId);

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]);
            break;

        // Mark all notifications as read
        case 'mark_all_read':
            $success = markAllNotificationsAsRead($pdo, $userId);

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'All notifications marked as read' : 'Failed to mark all notifications as read'
            ]);
            break;

        // Delete a notification
        case 'delete':
            $notificationId = (int)($_POST['notification_id'] ?? 0);

            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }

            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $success = $stmt->execute([$notificationId, $userId]);

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Notification deleted' : 'Failed to delete notification'
            ]);
            break;

        // Get notification by ID
        case 'get_single':
            $notificationId = (int)($_GET['notification_id'] ?? 0);

            if (!$notificationId) {
                throw new Exception('Notification ID is required');
            }

            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
            $notification = $stmt->fetch();

            if (!$notification) {
                throw new Exception('Notification not found');
            }

            // Auto-mark as read when viewed
            markNotificationAsRead($pdo, $notificationId, $userId);

            echo json_encode([
                'success' => true,
                'notification' => $notification
            ]);
            break;

        // Get notification stats
        case 'get_stats':
            $stats = [
                'unread' => getUnreadNotificationCount($pdo, $userId)
            ];

            // Get counts by type
            $typeStmt = $pdo->prepare("
                SELECT type, COUNT(*) as count
                FROM notifications
                WHERE user_id = ? AND is_read = FALSE
                GROUP BY type
            ");
            $typeStmt->execute([$userId]);
            $typeCounts = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $stats['by_type'] = [
                'return_reminder' => (int)($typeCounts[NOTIFICATION_RETURN_REMINDER] ?? 0),
                'overdue_alert' => (int)($typeCounts[NOTIFICATION_OVERDUE_ALERT] ?? 0),
                'approval_request' => (int)($typeCounts[NOTIFICATION_APPROVAL_REQUEST] ?? 0),
                'approval_response' => (int)($typeCounts[NOTIFICATION_APPROVAL_RESPONSE] ?? 0),
                'missing_report' => (int)($typeCounts[NOTIFICATION_MISSING_REPORT] ?? 0),
                'system_alert' => (int)($typeCounts[NOTIFICATION_SYSTEM_ALERT] ?? 0)
            ];

            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
