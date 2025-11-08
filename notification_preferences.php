<?php
/**
 * Notification Preferences Page
 * Allows users to manage their notification settings
 */

require_once 'config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: login.php');
    exit;
}

$user = getUserInfo();
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_preferences') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: notification_preferences.php');
        exit;
    }

    try {
        // Get notification preferences (1 = enabled, 0 = disabled)
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $browserNotifications = isset($_POST['browser_notifications']) ? 1 : 0;
        $soundNotifications = isset($_POST['sound_notifications']) ? 1 : 0;

        // Notification types
        $returnReminders = isset($_POST['return_reminders']) ? 1 : 0;
        $overdueAlerts = isset($_POST['overdue_alerts']) ? 1 : 0;
        $approvalRequests = isset($_POST['approval_requests']) ? 1 : 0;
        $approvalResponses = isset($_POST['approval_responses']) ? 1 : 0;
        $missingReports = isset($_POST['missing_reports']) ? 1 : 0;
        $systemAlerts = isset($_POST['system_alerts']) ? 1 : 0;

        // Store preferences as JSON
        $preferences = [
            'email_notifications' => $emailNotifications,
            'browser_notifications' => $browserNotifications,
            'sound_notifications' => $soundNotifications,
            'notification_types' => [
                'return_reminders' => $returnReminders,
                'overdue_alerts' => $overdueAlerts,
                'approval_requests' => $approvalRequests,
                'approval_responses' => $approvalResponses,
                'missing_reports' => $missingReports,
                'system_alerts' => $systemAlerts
            ]
        ];

        // Update user preferences in database
        $stmt = $pdo->prepare("
            UPDATE users
            SET notification_preferences = ?
            WHERE id = ?
        ");
        $stmt->execute([json_encode($preferences), $userId]);

        $_SESSION['success'] = 'Notification preferences updated successfully!';
        header('Location: notification_preferences.php');
        exit;

    } catch (Exception $e) {
        error_log("Error updating notification preferences: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to update preferences. Please try again.';
        header('Location: notification_preferences.php');
        exit;
    }
}

// Fetch current preferences
try {
    $stmt = $pdo->prepare("SELECT notification_preferences FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();

    $preferences = $result['notification_preferences'] ? json_decode($result['notification_preferences'], true) : [];

    // Default values if not set
    $emailNotifications = $preferences['email_notifications'] ?? 1;
    $browserNotifications = $preferences['browser_notifications'] ?? 1;
    $soundNotifications = $preferences['sound_notifications'] ?? 1;
    $notificationTypes = $preferences['notification_types'] ?? [
        'return_reminders' => 1,
        'overdue_alerts' => 1,
        'approval_requests' => 1,
        'approval_responses' => 1,
        'missing_reports' => 1,
        'system_alerts' => 1
    ];
} catch (Exception $e) {
    error_log("Error fetching notification preferences: " . $e->getMessage());
    $emailNotifications = 1;
    $browserNotifications = 1;
    $soundNotifications = 1;
    $notificationTypes = [
        'return_reminders' => 1,
        'overdue_alerts' => 1,
        'approval_requests' => 1,
        'approval_responses' => 1,
        'missing_reports' => 1,
        'system_alerts' => 1
    ];
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <style>
        .preference-card {
            transition: all 0.2s;
        }
        .preference-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .toggle-checkbox:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .toggle-checkbox:checked + .toggle-label {
            transform: translateX(1.25rem);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Notification Preferences</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage how you receive notifications</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Notification Bell -->
                        <?php include __DIR__ . '/includes/notification_center.php'; ?>

                        <a href="javascript:history.back()" class="text-sm text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-600"></i>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="notification_preferences.php">
                <input type="hidden" name="action" value="update_preferences">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Notification Channels -->
                <div class="preference-card bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-bell text-blue-600 text-xl mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-900">Notification Channels</h2>
                    </div>
                    <p class="text-sm text-gray-600 mb-6">Choose how you want to receive notifications</p>

                    <div class="space-y-4">
                        <!-- Email Notifications -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-gray-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Email Notifications</p>
                                    <p class="text-sm text-gray-600">Receive notifications via email</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="email_notifications" class="sr-only peer" <?= $emailNotifications ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Browser Notifications -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-desktop text-gray-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Browser Notifications</p>
                                    <p class="text-sm text-gray-600">Show desktop notifications in your browser</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="browser_notifications" class="sr-only peer" <?= $browserNotifications ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Sound Notifications -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-volume-up text-gray-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Sound Notifications</p>
                                    <p class="text-sm text-gray-600">Play a sound when new notifications arrive</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="sound_notifications" id="soundToggle" class="sr-only peer" <?= $soundNotifications ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Notification Types -->
                <div class="preference-card bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-filter text-purple-600 text-xl mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-900">Notification Types</h2>
                    </div>
                    <p class="text-sm text-gray-600 mb-6">Select which types of notifications you want to receive</p>

                    <div class="space-y-4">
                        <!-- Return Reminders -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-blue-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Return Reminders</p>
                                    <p class="text-sm text-gray-600">Reminders before asset return dates</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="return_reminders" class="sr-only peer" <?= $notificationTypes['return_reminders'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Overdue Alerts -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Overdue Alerts</p>
                                    <p class="text-sm text-gray-600">Alerts for overdue asset returns</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="overdue_alerts" class="sr-only peer" <?= $notificationTypes['overdue_alerts'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Approval Requests -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-clipboard-check text-yellow-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Approval Requests</p>
                                    <p class="text-sm text-gray-600">Notifications for pending approvals</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="approval_requests" class="sr-only peer" <?= $notificationTypes['approval_requests'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Approval Responses -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Approval Responses</p>
                                    <p class="text-sm text-gray-600">Updates on your request approvals/rejections</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="approval_responses" class="sr-only peer" <?= $notificationTypes['approval_responses'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Missing Reports -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-ban text-red-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Missing Reports</p>
                                    <p class="text-sm text-gray-600">Notifications about missing assets</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="missing_reports" class="sr-only peer" <?= $notificationTypes['missing_reports'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- System Alerts -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-indigo-600 text-lg mr-4"></i>
                                <div>
                                    <p class="font-medium text-gray-900">System Alerts</p>
                                    <p class="text-sm text-gray-600">Important system announcements and updates</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="system_alerts" class="sr-only peer" <?= $notificationTypes['system_alerts'] ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex items-center justify-end space-x-4">
                    <a href="javascript:history.back()" class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-lg transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Preferences
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
    // Update localStorage when sound toggle changes
    document.getElementById('soundToggle').addEventListener('change', function(e) {
        localStorage.setItem('notificationSound', e.target.checked);
    });
    </script>
</body>
</html>
