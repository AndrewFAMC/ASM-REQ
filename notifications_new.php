<?php
/**
 * Notifications Page - Simplified Version
 */

require_once 'config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: login.php');
    exit;
}

$user = getUserInfo();
$userId = $_SESSION['user_id'];

// Get all notifications directly from database (no API calls)
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY is_read ASC, created_at DESC
    LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM notifications
    WHERE user_id = ?
    AND (expires_at IS NULL OR expires_at > NOW())
");
$countStmt->execute([$userId]);
$totalCount = (int)$countStmt->fetch()['total'];
$totalPages = ceil($totalCount / $limit);

// Get statistics
$stats = [
    'unread' => 0,
    'by_type' => [
        'return_reminder' => 0,
        'overdue_alert' => 0,
        'approval_request' => 0,
        'approval_response' => 0
    ]
];

$unreadStmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$unreadStmt->execute([$userId]);
$stats['unread'] = (int)$unreadStmt->fetch()['count'];

$typeStmt = $pdo->prepare("
    SELECT type, COUNT(*) as count
    FROM notifications
    WHERE user_id = ? AND is_read = FALSE
    GROUP BY type
");
$typeStmt->execute([$userId]);
$typeCounts = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($typeCounts as $type => $count) {
    if (isset($stats['by_type'][$type])) {
        $stats['by_type'][$type] = (int)$count;
    }
}

// Function to get time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// Function to get notification icon
function getNotificationIcon($type) {
    $icons = [
        'return_reminder' => '🔔',
        'overdue_alert' => '⚠️',
        'approval_request' => '📋',
        'approval_response' => '✅',
        'missing_report' => '❌',
        'system_alert' => 'ℹ️'
    ];
    return $icons[$type] ?? '📬';
}

// Function to get notification color
function getNotificationColor($type) {
    $colors = [
        'return_reminder' => 'bg-blue-50 border-l-4 border-blue-500',
        'overdue_alert' => 'bg-red-50 border-l-4 border-red-500',
        'approval_request' => 'bg-yellow-50 border-l-4 border-yellow-500',
        'approval_response' => 'bg-green-50 border-l-4 border-green-500',
        'missing_report' => 'bg-pink-50 border-l-4 border-pink-500',
        'system_alert' => 'bg-indigo-50 border-l-4 border-indigo-500'
    ];
    return $colors[$type] ?? 'bg-gray-50 border-l-4 border-gray-500';
}

// Function to get priority badge
function getPriorityBadge($priority) {
    $badges = [
        'urgent' => '<span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">URGENT</span>',
        'high' => '<span class="px-2 py-1 text-xs font-semibold rounded bg-orange-100 text-orange-800">HIGH</span>',
        'medium' => '<span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">MEDIUM</span>',
        'low' => '<span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">LOW</span>'
    ];
    return $badges[$priority] ?? $badges['medium'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .notification-card { transition: all 0.2s; }
        .notification-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .notification-unread { background-color: #eff6ff; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Notifications</h1>
                        <p class="text-sm text-gray-600 mt-1">Stay updated with your asset management activities</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Notification Bell -->
                        <?php include __DIR__ . '/includes/notification_center.php'; ?>

                        <a href="employee_dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Total Unread -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                            <i class="fas fa-bell text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Unread</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['unread'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Return Reminders -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                            <i class="fas fa-undo text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Return Reminders</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['by_type']['return_reminder'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Overdue Alerts -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Overdue Alerts</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['by_type']['overdue_alert'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Approval Requests -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                            <i class="fas fa-clipboard-check text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Approvals</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['by_type']['approval_request'] + $stats['by_type']['approval_response'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Bar -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?= count($notifications) ?> of <?= $totalCount ?> notifications
                </div>
                <button onclick="markAllAsRead()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-check-double mr-2"></i>Mark All as Read
                </button>
            </div>

            <!-- Notifications List -->
            <?php if (empty($notifications)): ?>
                <div class="bg-white rounded-lg shadow-sm py-16 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="mt-4 text-lg text-gray-600">No notifications found</p>
                    <p class="mt-2 text-sm text-gray-500">You're all caught up!</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-card bg-white rounded-lg shadow-sm <?= getNotificationColor($notif['type']) ?> <?= $notif['is_read'] ? '' : 'notification-unread' ?> p-6 cursor-pointer"
                             data-notification-id="<?= $notif['id'] ?>"
                             data-action-url="<?= htmlspecialchars($notif['action_url'] ?? '', ENT_QUOTES) ?>"
                             onclick="markAsRead(this)">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 text-3xl mr-4">
                                    <?= getNotificationIcon($notif['type']) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?= htmlspecialchars($notif['title']) ?>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">New</span>
                                            <?php endif; ?>
                                        </h3>
                                        <?= getPriorityBadge($notif['priority']) ?>
                                    </div>
                                    <p class="text-gray-700 mb-3"><?= htmlspecialchars($notif['message']) ?></p>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="far fa-clock mr-1"></i>
                                        <span><?= timeAgo($notif['created_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex items-center justify-between bg-white rounded-lg shadow-sm px-6 py-4">
                        <div class="text-sm text-gray-700">
                            Page <?= $page ?> of <?= $totalPages ?>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    async function markAsRead(element) {
        const notificationId = element.dataset.notificationId;
        const actionUrl = element.dataset.actionUrl;
        
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notification_id', notificationId);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('/AMS-REQ/api/notifications.php', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (actionUrl && actionUrl !== '' && actionUrl !== 'null') {
                    window.location.href = actionUrl;
                } else {
                    window.location.reload();
                }
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function markAllAsRead() {
        const result = await Swal.fire({
            title: 'Mark All as Read?',
            text: 'This will mark all notifications as read',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, mark all read',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280'
        });

        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_all_read');
                formData.append('csrf_token', csrfToken);

                const response = await fetch('/AMS-REQ/api/notifications.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'All notifications marked as read',
                        icon: 'success',
                        confirmButtonColor: '#2563eb'
                    }).then(() => window.location.reload());
                } else {
                    throw new Error(data.message || 'Failed to mark all as read');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to mark all as read',
                    icon: 'error',
                    confirmButtonColor: '#2563eb'
                });
            }
        }
    }
    </script>
</body>
</html>