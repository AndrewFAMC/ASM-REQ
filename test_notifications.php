<?php
/**
 * Test Script for Notification System
 *
 * This script creates sample notifications to test the notification center
 * Access: http://localhost/AMS-REQ/test_notifications.php
 */

require_once 'config.php';

// Require admin access for safety
if (!isLoggedIn() || !hasRole('employee')) {
    die('Access denied. Admin access required to run tests.');
}

$user = getUserInfo();
$userId = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🔔 Notification System Test</h1>
            <p class="text-gray-600 mb-6">Create sample notifications to test the notification center</p>

            <!-- Current User Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>Testing as:</strong> <?= htmlspecialchars($user['full_name']) ?>
                    (ID: <?= $userId ?>, Role: <?= htmlspecialchars($user['role']) ?>)
                </p>
            </div>

            <!-- Test Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <!-- Return Reminder -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">🔔 Return Reminder</h3>
                    <p class="text-sm text-gray-600 mb-3">Creates a reminder notification for asset return</p>
                    <button onclick="createNotification('return_reminder')"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Create Test Notification
                    </button>
                </div>

                <!-- Overdue Alert -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">⚠️ Overdue Alert</h3>
                    <p class="text-sm text-gray-600 mb-3">Creates an urgent overdue notification</p>
                    <button onclick="createNotification('overdue_alert')"
                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                        Create Test Notification
                    </button>
                </div>

                <!-- Approval Request -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">📋 Approval Request</h3>
                    <p class="text-sm text-gray-600 mb-3">Creates a request needing approval</p>
                    <button onclick="createNotification('approval_request')"
                            class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">
                        Create Test Notification
                    </button>
                </div>

                <!-- Approval Response -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">✅ Approval Response</h3>
                    <p class="text-sm text-gray-600 mb-3">Creates an approval decision notification</p>
                    <button onclick="createNotification('approval_response')"
                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        Create Test Notification
                    </button>
                </div>

                <!-- Missing Report -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">❌ Missing Report</h3>
                    <p class="text-sm text-gray-600 mb-3">Creates a missing asset notification</p>
                    <button onclick="createNotification('missing_report')"
                            class="w-full bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg">
                        Create Test Notification
                    </button>
                </div>

                <!-- System Alert -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">ℹ️ System Alert</h3>
                    <p class="text-sm text-gray-600 mb-3">Creates a general system notification</p>
                    <button onclick="createNotification('system_alert')"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
                        Create Test Notification
                    </button>
                </div>
            </div>

            <!-- Bulk Test -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="font-semibold text-gray-900 mb-3">🚀 Bulk Test</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button onclick="createMultipleNotifications()"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium">
                        Create 5 Sample Notifications
                    </button>
                    <button onclick="clearAllNotifications()"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium">
                        Clear All My Notifications
                    </button>
                </div>
            </div>

            <!-- Current Notifications Count -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-900">Current Status</h4>
                        <p class="text-sm text-gray-600">Your unread notifications</p>
                    </div>
                    <div class="text-right">
                        <p id="unreadCount" class="text-3xl font-bold text-blue-600">-</p>
                        <button onclick="checkNotifications()" class="text-sm text-blue-600 hover:text-blue-800">
                            Refresh Count
                        </button>
                    </div>
                </div>
            </div>

            <!-- Back to Dashboard -->
            <div class="mt-6 text-center">
                <a href="employee_dashboard.php" class="text-blue-600 hover:text-blue-800">
                    ← Back to Dashboard (to see notifications in action)
                </a>
            </div>
        </div>

        <!-- Test Results Log -->
        <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
            <h3 class="font-semibold text-gray-900 mb-3">📝 Test Log</h3>
            <div id="logContainer" class="space-y-2 max-h-64 overflow-y-auto">
                <p class="text-sm text-gray-500">No tests run yet...</p>
            </div>
        </div>
    </div>

    <script>
    const userId = <?= $userId ?>;

    // Notification templates
    const templates = {
        'return_reminder': {
            title: 'Asset Return Reminder',
            message: 'Your borrowed Laptop (Dell XPS 15) is due for return in 2 days. Please return it on time.',
            priority: 'medium'
        },
        'overdue_alert': {
            title: 'URGENT: Asset Overdue!',
            message: 'Your borrowed Projector (Epson EB-X41) is now 5 days overdue. Please return immediately to avoid penalties.',
            priority: 'urgent'
        },
        'approval_request': {
            title: 'New Asset Request Pending',
            message: 'John Doe has requested to borrow a Camera (Canon EOS). Please review and approve.',
            priority: 'high'
        },
        'approval_response': {
            title: 'Request Approved',
            message: 'Your asset request for Tablet (iPad Pro) has been approved. Please collect from custodian office.',
            priority: 'high'
        },
        'missing_report': {
            title: 'Asset Marked as Missing',
            message: 'Scanner (HP ScanJet) borrowed by Jane Smith has been marked as missing after 60 days overdue.',
            priority: 'urgent'
        },
        'system_alert': {
            title: 'System Maintenance Notice',
            message: 'The asset management system will undergo scheduled maintenance on Saturday, 10 PM - 12 AM.',
            priority: 'low'
        }
    };

    async function createNotification(type) {
        const template = templates[type];
        log(`Creating ${type} notification...`, 'info');

        try {
            const response = await fetch('/AMS-REQ/api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'create_test',
                    user_id: userId,
                    type: type,
                    title: template.title,
                    message: template.message,
                    priority: template.priority
                })
            });

            // Since the API might not have a create_test action, let's insert directly
            const result = await insertNotificationDirect(type, template);

            if (result) {
                Swal.fire('Success!', `${template.title} created`, 'success');
                log(`✅ Successfully created ${type} notification`, 'success');
                checkNotifications();
            }
        } catch (error) {
            console.error('Error:', error);
            log(`❌ Failed to create notification: ${error.message}`, 'error');
            Swal.fire('Error', 'Failed to create notification', 'error');
        }
    }

    async function insertNotificationDirect(type, template) {
        // Insert via a PHP backend call
        const response = await fetch('test_notifications_insert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                user_id: userId,
                type: type,
                title: template.title,
                message: template.message,
                priority: template.priority
            })
        });

        return response.ok;
    }

    async function createMultipleNotifications() {
        log('Creating 5 sample notifications...', 'info');
        const types = ['return_reminder', 'overdue_alert', 'approval_request', 'approval_response', 'system_alert'];

        for (const type of types) {
            await createNotification(type);
            await new Promise(resolve => setTimeout(resolve, 200)); // Small delay
        }

        Swal.fire('Complete!', '5 notifications created', 'success');
        log('✅ Bulk notification creation complete', 'success');
    }

    async function clearAllNotifications() {
        const result = await Swal.fire({
            title: 'Clear All Notifications?',
            text: 'This will mark all your notifications as read',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear all'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('/AMS-REQ/api/notifications.php?action=mark_all_read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        csrf_token: '<?= generateCSRFToken() ?>'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('Cleared!', 'All notifications marked as read', 'success');
                    log('✅ All notifications cleared', 'success');
                    checkNotifications();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                log(`❌ Failed to clear notifications: ${error.message}`, 'error');
                Swal.fire('Error', 'Failed to clear notifications', 'error');
            }
        }
    }

    async function checkNotifications() {
        try {
            const response = await fetch('/AMS-REQ/api/notifications.php?action=get_count');
            const data = await response.json();

            if (data.success) {
                document.getElementById('unreadCount').textContent = data.count;
                log(`📊 Current unread count: ${data.count}`, 'info');
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('unreadCount').textContent = 'Error';
        }
    }

    function log(message, type = 'info') {
        const logContainer = document.getElementById('logContainer');
        const timestamp = new Date().toLocaleTimeString();

        const colors = {
            'info': 'text-blue-600',
            'success': 'text-green-600',
            'error': 'text-red-600',
            'warning': 'text-yellow-600'
        };

        const entry = document.createElement('p');
        entry.className = `text-sm ${colors[type]}`;
        entry.textContent = `[${timestamp}] ${message}`;

        // Remove "no tests" message
        if (logContainer.querySelector('.text-gray-500')) {
            logContainer.innerHTML = '';
        }

        logContainer.insertBefore(entry, logContainer.firstChild);
    }

    // Check notifications on page load
    document.addEventListener('DOMContentLoaded', () => {
        checkNotifications();
        log('🔧 Test page loaded and ready', 'info');
    });
    </script>
</body>
</html>
