<?php
/**
 * Notification Center Component
 *
 * A bell icon dropdown that displays unread notifications
 * Requires: Font Awesome, Tailwind CSS, jQuery
 */

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    return;
}

$userId = $_SESSION['user_id'];
?>

<!-- Notification Center -->
<div class="relative notification-center-container">
    <!-- Bell Icon with Badge -->
    <button id="notificationBell" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        <!-- Notification Badge -->
        <span id="notificationBadge" class="hidden absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[20px]">
            0
        </span>
    </button>

    <!-- Notification Dropdown -->
    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-[600px] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
            <button id="markAllRead" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                Mark all read
            </button>
        </div>

        <!-- Loading State -->
        <div id="notificationLoading" class="flex items-center justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Notification List -->
        <div id="notificationList" class="hidden overflow-y-auto flex-1 max-h-[450px]">
            <!-- Notifications will be dynamically loaded here -->
        </div>

        <!-- Empty State -->
        <div id="notificationEmpty" class="hidden py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p class="mt-2 text-gray-500">No notifications</p>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            <a href="/AMS-REQ/notifications.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium text-center block">
                View all notifications
            </a>
        </div>
    </div>
</div>

<!-- Notification Center Styles -->
<style>
.notification-center-container {
    display: inline-block;
}

#notificationDropdown {
    min-width: 384px;
    max-width: 384px;
}

@media (max-width: 640px) {
    #notificationDropdown {
        position: fixed;
        right: 1rem;
        left: 1rem;
        min-width: auto;
        max-width: none;
    }
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f9fafb;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background-color: #eff6ff;
}

.notification-item.unread:hover {
    background-color: #dbeafe;
}

.notification-priority-urgent {
    border-left: 4px solid #dc2626;
}

.notification-priority-high {
    border-left: 4px solid #f59e0b;
}

.notification-priority-medium {
    border-left: 4px solid #3b82f6;
}

.notification-priority-low {
    border-left: 4px solid #6b7280;
}

.notification-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.notification-type-return_reminder { background-color: #dbeafe; color: #1e40af; }
.notification-type-overdue_alert { background-color: #fee2e2; color: #991b1b; }
.notification-type-approval_request { background-color: #fef3c7; color: #92400e; }
.notification-type-approval_response { background-color: #d1fae5; color: #065f46; }
.notification-type-missing_report { background-color: #fce7f3; color: #9f1239; }
.notification-type-system_alert { background-color: #e0e7ff; color: #3730a3; }

/* Bell shake animation for new notifications */
@keyframes bellShake {
    0%, 100% { transform: rotate(0deg); }
    10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
    20%, 40%, 60%, 80% { transform: rotate(10deg); }
}

.bell-shake {
    animation: bellShake 0.5s ease-in-out;
}

/* Pulse animation for badge */
@keyframes badgePulse {
    0%, 100% { transform: translate(50%, -50%) scale(1); }
    50% { transform: translate(50%, -50%) scale(1.1); }
}

.badge-pulse {
    animation: badgePulse 2s infinite;
}
</style>

<!-- Notification Center JavaScript -->
<script>
// Notification Center Manager
class NotificationCenter {
    constructor() {
        this.bell = document.getElementById('notificationBell');
        this.badge = document.getElementById('notificationBadge');
        this.dropdown = document.getElementById('notificationDropdown');
        this.list = document.getElementById('notificationList');
        this.loading = document.getElementById('notificationLoading');
        this.empty = document.getElementById('notificationEmpty');
        this.markAllReadBtn = document.getElementById('markAllRead');

        this.isOpen = false;
        this.unreadCount = 0;
        this.pollInterval = null;

        this.init();
    }

    init() {
        // Click handlers
        this.bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        this.markAllReadBtn.addEventListener('click', () => {
            this.markAllAsRead();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.dropdown.contains(e.target) && !this.bell.contains(e.target)) {
                this.close();
            }
        });

        // Initial load
        this.updateBadge();

        // Poll for new notifications every 30 seconds
        this.startPolling();
    }

    startPolling() {
        this.pollInterval = setInterval(() => {
            this.updateBadge();
        }, 30000); // 30 seconds
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.dropdown.classList.remove('hidden');
        this.loadNotifications();
    }

    close() {
        this.isOpen = false;
        this.dropdown.classList.add('hidden');
    }

    async updateBadge() {
        try {
            const response = await fetch('/AMS-REQ/api/notifications.php?action=get_count');
            const data = await response.json();

            if (data.success) {
                const newCount = parseInt(data.count);

                // Trigger animation if count increased
                if (newCount > this.unreadCount) {
                    this.bell.classList.add('bell-shake');
                    this.badge.classList.add('badge-pulse');
                    setTimeout(() => {
                        this.bell.classList.remove('bell-shake');
                        this.badge.classList.remove('badge-pulse');
                    }, 500);
                }

                this.unreadCount = newCount;

                if (this.unreadCount > 0) {
                    this.badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                    this.badge.classList.remove('hidden');
                } else {
                    this.badge.classList.add('hidden');
                }
            }
        } catch (error) {
            console.error('Error updating notification badge:', error);
        }
    }

    async loadNotifications() {
        this.loading.classList.remove('hidden');
        this.list.classList.add('hidden');
        this.empty.classList.add('hidden');

        try {
            const response = await fetch('/AMS-REQ/api/notifications.php?action=get_unread&limit=20');
            const data = await response.json();

            this.loading.classList.add('hidden');

            if (data.success && data.notifications.length > 0) {
                this.renderNotifications(data.notifications);
                this.list.classList.remove('hidden');
            } else {
                this.empty.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.loading.classList.add('hidden');
            this.empty.classList.remove('hidden');
        }
    }

    renderNotifications(notifications) {
        this.list.innerHTML = notifications.map(notif => this.createNotificationHTML(notif)).join('');

        // Add click handlers
        this.list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const notifId = item.dataset.id;
                const actionUrl = item.dataset.actionUrl;
                this.markAsRead(notifId, actionUrl);
            });
        });
    }

    createNotificationHTML(notif) {
        const isUnread = notif.is_read === '0' || notif.is_read === 0;
        const timeAgo = this.getTimeAgo(notif.created_at);
        const icon = this.getNotificationIcon(notif.type);

        return `
            <div class="notification-item ${isUnread ? 'unread' : ''} notification-priority-${notif.priority}"
                 data-id="${notif.id}"
                 data-action-url="${notif.action_url || ''}">
                <div class="flex items-start space-x-3">
                    <div class="notification-icon notification-type-${notif.type}">
                        ${icon}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            ${this.escapeHtml(notif.title)}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            ${this.escapeHtml(notif.message)}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            ${timeAgo}
                        </p>
                    </div>
                    ${isUnread ? '<div class="flex-shrink-0"><span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span></div>' : ''}
                </div>
            </div>
        `;
    }

    getNotificationIcon(type) {
        const icons = {
            'return_reminder': '🔔',
            'overdue_alert': '⚠️',
            'approval_request': '📋',
            'approval_response': '✅',
            'missing_report': '❌',
            'system_alert': 'ℹ️'
        };
        return icons[type] || '📬';
    }

    async markAsRead(notificationId, actionUrl = null) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notification_id', notificationId);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('/AMS-REQ/api/notifications.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateBadge();

                // Navigate to action URL if provided
                if (actionUrl && actionUrl !== '' && actionUrl !== 'null') {
                    window.location.href = actionUrl;
                } else {
                    // Just refresh the notification list
                    this.loadNotifications();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const formData = new FormData();
            formData.append('action', 'mark_all_read');
            formData.append('csrf_token', csrfToken);

            const response = await fetch('/AMS-REQ/api/notifications.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateBadge();
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    getTimeAgo(datetime) {
        const now = new Date();
        const past = new Date(datetime);
        const diffMs = now - past;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

        return past.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notification center when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.notificationCenter = new NotificationCenter();
});
</script>
