<?php
/**
 * Notifications Page
 *
 * Displays all notifications for the logged-in user with filtering options
 */

require_once 'config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: login.php');
    exit;
}

$user = getUserInfo();
$userId = $_SESSION['user_id'];
$userRole = strtolower($user['role'] ?? '');

// Get notification statistics
$stats = [];
try {
    $statsResponse = file_get_contents(
        'http://' . $_SERVER['HTTP_HOST'] . '/AMS-REQ/api/notifications.php?action=get_stats',
        false,
        stream_context_create([
            'http' => [
                'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE']
            ]
        ])
    );
    $statsData = json_decode($statsResponse, true);
    if ($statsData['success']) {
        $stats = $statsData['stats'];
    }
} catch (Exception $e) {
    error_log('Error loading notification stats: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - HCC Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/sweetalert-config.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .notification-card {
            transition: all 0.2s;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .notification-card.unread {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php
        $sidebarFile = '';
        switch($userRole) {
            case 'admin':
            case 'super_admin':
                $sidebarFile = 'admin/includes/sidebar.php';
                break;
            case 'custodian':
                $sidebarFile = 'custodian/includes/sidebar.php';
                break;
            case 'staff':
            case 'auditor':
                $sidebarFile = 'staff/includes/sidebar.php';
                break;
        }
        if ($sidebarFile && file_exists($sidebarFile)) {
            include $sidebarFile;
        }
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b">
                <div class="px-6 py-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Notifications</h1>
                        <p class="text-sm text-gray-600 mt-1">Stay updated with your asset management activities</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Notification bell -->
                        <?php include 'includes/notification_center.php'; ?>

                        <button id="markAllReadBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center text-sm">
                            <i class="fas fa-check-double mr-2"></i>Mark All Read
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Total Unread -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                                <i class="fas fa-bell text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Unread Notifications</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['unread'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Return Reminders -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-green-600">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                                <i class="fas fa-undo text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Return Reminders</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['by_type']['return_reminder'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Overdue Alerts -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-red-600">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Overdue Alerts</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['by_type']['overdue_alert'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Requests -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-yellow-600">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                                <i class="fas fa-clipboard-check text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Approval Requests</p>
                                <p class="text-2xl font-bold text-gray-900"><?= ($stats['by_type']['approval_request'] ?? 0) + ($stats['by_type']['approval_response'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Type</label>
                            <select id="typeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Types</option>
                                <option value="return_reminder">Return Reminders</option>
                                <option value="overdue_alert">Overdue Alerts</option>
                                <option value="approval_request">Approval Requests</option>
                                <option value="approval_response">Approval Responses</option>
                                <option value="missing_report">Missing Reports</option>
                                <option value="system_alert">System Alerts</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select id="priorityFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Priorities</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="unread">Unread Only</option>
                                <option value="read">Read Only</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="searchInput" placeholder="Search notifications..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button id="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                        <button id="clearFilters" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-times mr-2"></i>Clear
                        </button>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="bg-white rounded-lg shadow-sm">
                    <!-- Loading State -->
                    <div id="loadingState" class="flex items-center justify-center py-16">
                        <svg class="animate-spin h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Notifications Container -->
                    <div id="notificationsContainer" class="hidden divide-y divide-gray-200"></div>

                    <!-- Empty State -->
                    <div id="emptyState" class="hidden py-16 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="mt-4 text-lg text-gray-600">No notifications found</p>
                        <p class="mt-2 text-sm text-gray-500">You're all caught up!</p>
                    </div>

                    <!-- Pagination -->
                    <div id="paginationContainer" class="hidden px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span id="pageInfo"></span>
                            </div>
                            <div class="flex space-x-2">
                                <button id="prevPage" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Previous
                                </button>
                                <button id="nextPage" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Notification Manager
    class NotificationManager {
        constructor() {
            this.currentPage = 1;
            this.itemsPerPage = 20;
            this.totalPages = 1;
            this.notifications = [];
            this.filters = {
                type: '',
                priority: '',
                status: '',
                search: ''
            };

            this.init();
        }

        init() {
            // Load initial notifications
            this.loadNotifications();

            // Event listeners
            document.getElementById('markAllReadBtn').addEventListener('click', () => this.markAllAsRead());
            document.getElementById('applyFilters').addEventListener('click', () => this.applyFilters());
            document.getElementById('clearFilters').addEventListener('click', () => this.clearFilters());
            document.getElementById('prevPage').addEventListener('click', () => this.changePage(-1));
            document.getElementById('nextPage').addEventListener('click', () => this.changePage(1));

            // Search input with debounce
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filters.search = e.target.value;
                    this.applyFilters();
                }, 500);
            });
        }

        async loadNotifications() {
            this.showLoading();

            try {
                const params = new URLSearchParams({
                    action: 'get_all',
                    page: this.currentPage,
                    limit: this.itemsPerPage
                });

                const response = await fetch(`/AMS-REQ/api/notifications.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.notifications = data.notifications;
                    this.totalPages = data.pagination.total_pages;
                    this.renderNotifications();
                    this.updatePagination(data.pagination);
                } else {
                    this.showEmpty();
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                Swal.fire('Error', 'Failed to load notifications', 'error');
                this.showEmpty();
            }
        }

        renderNotifications() {
            const container = document.getElementById('notificationsContainer');

            if (this.notifications.length === 0) {
                this.showEmpty();
                return;
            }

            let filteredNotifications = this.filterNotifications();

            if (filteredNotifications.length === 0) {
                this.showEmpty();
                return;
            }

            container.innerHTML = filteredNotifications.map(notif => this.createNotificationCard(notif)).join('');

            this.showNotifications();

            // Add click handlers
            container.querySelectorAll('.notification-card').forEach(card => {
                card.addEventListener('click', () => {
                    const notifId = card.dataset.id;
                    const actionUrl = card.dataset.actionUrl;
                    this.markAsRead(notifId, actionUrl);
                });
            });
        }

        filterNotifications() {
            return this.notifications.filter(notif => {
                // Type filter
                if (this.filters.type && notif.type !== this.filters.type) return false;

                // Priority filter
                if (this.filters.priority && notif.priority !== this.filters.priority) return false;

                // Status filter
                if (this.filters.status === 'unread' && (notif.is_read === '1' || notif.is_read === 1)) return false;
                if (this.filters.status === 'read' && (notif.is_read === '0' || notif.is_read === 0)) return false;

                // Search filter
                if (this.filters.search) {
                    const searchLower = this.filters.search.toLowerCase();
                    return notif.title.toLowerCase().includes(searchLower) ||
                           notif.message.toLowerCase().includes(searchLower);
                }

                return true;
            });
        }

        createNotificationCard(notif) {
            const isUnread = notif.is_read === '0' || notif.is_read === 0;
            const timeAgo = this.getTimeAgo(notif.created_at);
            const priorityColor = this.getPriorityColor(notif.priority);
            const typeIcon = this.getTypeIcon(notif.type);

            return `
                <div class="notification-card p-6 cursor-pointer ${isUnread ? 'unread' : ''}"
                     data-id="${notif.id}"
                     data-action-url="${notif.action_url || ''}">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full ${this.getTypeBgColor(notif.type)} flex items-center justify-center text-2xl">
                                ${typeIcon}
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">
                                    ${this.escapeHtml(notif.title)}
                                    ${isUnread ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">New</span>' : ''}
                                </h3>
                                <span class="text-xs px-2 py-1 rounded ${priorityColor}">
                                    ${notif.priority.toUpperCase()}
                                </span>
                            </div>
                            <p class="mt-2 text-gray-600">${this.escapeHtml(notif.message)}</p>
                            <div class="mt-3 flex items-center text-sm text-gray-500">
                                <i class="far fa-clock mr-1"></i>
                                <span>${timeAgo}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        getTypeBgColor(type) {
            const colors = {
                'return_reminder': 'bg-blue-100 text-blue-600',
                'overdue_alert': 'bg-red-100 text-red-600',
                'approval_request': 'bg-yellow-100 text-yellow-600',
                'approval_response': 'bg-green-100 text-green-600',
                'missing_report': 'bg-pink-100 text-pink-600',
                'system_alert': 'bg-indigo-100 text-indigo-600'
            };
            return colors[type] || 'bg-gray-100 text-gray-600';
        }

        getTypeIcon(type) {
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

        getPriorityColor(priority) {
            const colors = {
                'urgent': 'bg-red-100 text-red-800',
                'high': 'bg-orange-100 text-orange-800',
                'medium': 'bg-blue-100 text-blue-800',
                'low': 'bg-gray-100 text-gray-800'
            };
            return colors[priority] || 'bg-gray-100 text-gray-800';
        }

        async markAsRead(notificationId, actionUrl = null) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
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
                        this.loadNotifications();
                    }
                }
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        }

        async markAllAsRead() {
            const result = await Swal.fire({
                title: 'Mark All as Read?',
                text: 'This will mark all notifications as read',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, mark all read'
            });

            if (result.isConfirmed) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
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
                        Swal.fire('Success', 'All notifications marked as read', 'success');
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Error marking all as read:', error);
                    Swal.fire('Error', 'Failed to mark all as read', 'error');
                }
            }
        }

        applyFilters() {
            this.filters.type = document.getElementById('typeFilter').value;
            this.filters.priority = document.getElementById('priorityFilter').value;
            this.filters.status = document.getElementById('statusFilter').value;
            this.renderNotifications();
        }

        clearFilters() {
            document.getElementById('typeFilter').value = '';
            document.getElementById('priorityFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('searchInput').value = '';
            this.filters = { type: '', priority: '', status: '', search: '' };
            this.renderNotifications();
        }

        changePage(direction) {
            this.currentPage += direction;
            this.loadNotifications();
        }

        updatePagination(pagination) {
            const container = document.getElementById('paginationContainer');
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');

            if (pagination.total_pages > 1) {
                container.classList.remove('hidden');
                pageInfo.textContent = `Page ${pagination.current_page} of ${pagination.total_pages} (${pagination.total_items} total)`;
                prevBtn.disabled = pagination.current_page === 1;
                nextBtn.disabled = pagination.current_page === pagination.total_pages;
            } else {
                container.classList.add('hidden');
            }
        }

        showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('notificationsContainer').classList.add('hidden');
            document.getElementById('emptyState').classList.add('hidden');
        }

        showNotifications() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('notificationsContainer').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');
        }

        showEmpty() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('notificationsContainer').classList.add('hidden');
            document.getElementById('emptyState').classList.remove('hidden');
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

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationManager = new NotificationManager();
    });
    </script>
</body>
</html>
