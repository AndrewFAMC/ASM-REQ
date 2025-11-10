<?php
/**
 * Office Approval History Page
 * Shows all requests approved/rejected by this office head
 */

require_once dirname(__DIR__) . '/config.php';

// Require login and office role
requireLogin();
if (!hasRole('office')) {
    header('Location: /AMS-REQ/login.php');
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];
$userId = $user['id'];

// Get office information
$officeStmt = $pdo->prepare("
    SELECT o.id, o.office_name, o.section_code
    FROM department_approvers da
    JOIN offices o ON da.office_id = o.id
    WHERE da.approver_user_id = ? AND da.is_active = TRUE
    LIMIT 1
");
$officeStmt->execute([$userId]);
$office = $officeStmt->fetch();

if (!$office) {
    die("Error: You are not assigned as an office approver.");
}

$officeId = $office['id'];
$officeName = $office['office_name'];

// Get statistics
$stats = [
    'total_approved' => 0,
    'total_rejected' => 0,
    'this_month_approved' => 0,
    'this_month_rejected' => 0
];

try {
    // Total approved by this office head
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE office_approved_by = ?
        AND status IN ('approved', 'released', 'returned')
    ");
    $stmt->execute([$userId]);
    $stats['total_approved'] = (int)$stmt->fetch()['count'];

    // Total rejected
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE office_approved_by = ?
        AND status = 'rejected'
    ");
    $stmt->execute([$userId]);
    $stats['total_rejected'] = (int)$stmt->fetch()['count'];

    // This month approved
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE office_approved_by = ?
        AND status IN ('approved', 'released', 'returned')
        AND YEAR(office_approved_at) = YEAR(CURDATE())
        AND MONTH(office_approved_at) = MONTH(CURDATE())
    ");
    $stmt->execute([$userId]);
    $stats['this_month_approved'] = (int)$stmt->fetch()['count'];

    // This month rejected
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE office_approved_by = ?
        AND status = 'rejected'
        AND YEAR(office_approved_at) = YEAR(CURDATE())
        AND MONTH(office_approved_at) = MONTH(CURDATE())
    ");
    $stmt->execute([$userId]);
    $stats['this_month_rejected'] = (int)$stmt->fetch()['count'];

} catch (PDOException $e) {
    error_log("Error fetching approval history statistics: " . $e->getMessage());
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval History - Office Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-released {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-returned {
            background-color: #e0e7ff;
            color: #3730a3;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Department Head</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <!-- Dashboard -->
                <a href="office_dashboard.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-home w-6"></i><span>Dashboard</span>
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-700 my-2"></div>

                <!-- Approve Requests -->
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                </a>

                <!-- Release Assets -->
                <a href="release_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                </a>

                <!-- Accept Returns -->
                <a href="return_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-undo-alt w-6"></i>
                    <span>Accept Returns</span>
                </a>

                <!-- Approval History -->
                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md bg-gray-700 text-white">
                    <i class="fas fa-history w-6"></i>
                    <span>Approval History</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Approval History</h1>
                            <p class="text-sm text-gray-600 mt-1">
                                <i class="fas fa-building text-blue-600 mr-1"></i>
                                Department: <strong><?= htmlspecialchars($officeName) ?></strong>
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Notification Bell -->
                            <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                            <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                                <i class="fas fa-user-circle mr-1"></i> Profile
                            </a>
                            <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Approved</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['total_approved'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Rejected</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['total_rejected'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">This Month Approved</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['this_month_approved'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-times text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">This Month Rejected</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['this_month_rejected'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="all">All Status</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="released">Released</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                            <select id="dateRangeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="year">This Year</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <div class="flex">
                                <input type="text" id="searchInput" placeholder="Search by requester, asset..." class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500">
                                <button onclick="historyManager.clearFilters()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-r-md">
                                    <i class="fas fa-times mr-1"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Request History</h2>
                            <button onclick="historyManager.loadHistory()" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-sync-alt mr-1"></i> Refresh
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div id="loadingState" class="hidden text-center py-8">
                            <i class="fas fa-spinner fa-spin text-3xl text-blue-600 mb-2"></i>
                            <p class="text-gray-600">Loading history...</p>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="hidden text-center py-8">
                            <i class="fas fa-inbox text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600">No approval history found</p>
                        </div>

                        <!-- History List Container -->
                        <div id="historyList" class="space-y-4"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    const csrfToken = <?= json_encode($csrfToken) ?>;

    class HistoryManager {
        constructor() {
            this.history = [];
            this.filteredHistory = [];
            this.init();
        }

        init() {
            this.loadHistory();

            // Setup event listeners
            document.getElementById('statusFilter').addEventListener('change', () => this.applyFilters());
            document.getElementById('dateRangeFilter').addEventListener('change', () => this.applyFilters());
            document.getElementById('searchInput').addEventListener('input', () => this.applyFilters());

            // Auto-refresh every 60 seconds
            setInterval(() => this.loadHistory(), 60000);
        }

        showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('historyList').innerHTML = '';
        }

        showEmpty() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('historyList').innerHTML = '';
        }

        showHistory() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('emptyState').classList.add('hidden');
        }

        async loadHistory() {
            this.showLoading();

            try {
                const response = await fetch('/AMS-REQ/api/requests.php?action=get_office_approval_history');
                const data = await response.json();

                if (data.success && data.requests && data.requests.length > 0) {
                    this.history = data.requests;
                    this.applyFilters();
                } else {
                    this.showEmpty();
                }
            } catch (error) {
                console.error('Error loading history:', error);
                Swal.fire('Error', 'Failed to load approval history', 'error');
                this.showEmpty();
            }
        }

        applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateRangeFilter = document.getElementById('dateRangeFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            this.filteredHistory = this.history.filter(request => {
                // Status filter
                if (statusFilter !== 'all' && request.status !== statusFilter) {
                    return false;
                }

                // Date range filter
                if (dateRangeFilter !== 'all') {
                    const approvedDate = new Date(request.office_approved_at);
                    const now = new Date();

                    switch (dateRangeFilter) {
                        case 'today':
                            if (approvedDate.toDateString() !== now.toDateString()) return false;
                            break;
                        case 'week':
                            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                            if (approvedDate < weekAgo) return false;
                            break;
                        case 'month':
                            if (approvedDate.getMonth() !== now.getMonth() || approvedDate.getFullYear() !== now.getFullYear()) return false;
                            break;
                        case 'year':
                            if (approvedDate.getFullYear() !== now.getFullYear()) return false;
                            break;
                    }
                }

                // Search filter
                if (searchTerm) {
                    const searchableText = `${request.requester_name} ${request.asset_name} ${request.category_name}`.toLowerCase();
                    if (!searchableText.includes(searchTerm)) return false;
                }

                return true;
            });

            this.renderHistory();
        }

        renderHistory() {
            if (this.filteredHistory.length === 0) {
                this.showEmpty();
                return;
            }

            this.showHistory();
            const container = document.getElementById('historyList');
            container.innerHTML = this.filteredHistory.map(request => this.renderHistoryCard(request)).join('');
        }

        renderHistoryCard(request) {
            const approvedDate = new Date(request.office_approved_at);
            const formattedDate = approvedDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            return `
                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-all">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <h3 class="font-semibold text-gray-900 mr-3">${this.escapeHtml(request.asset_name)}</h3>
                                ${this.getStatusBadge(request.status)}
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-2">
                                <div>
                                    <i class="fas fa-user text-blue-600 mr-2"></i>
                                    <span class="font-medium">Requester:</span> ${this.escapeHtml(request.requester_name)}
                                </div>
                                <div>
                                    <i class="fas fa-tag text-blue-600 mr-2"></i>
                                    <span class="font-medium">Category:</span> ${this.escapeHtml(request.category_name)}
                                </div>
                                <div>
                                    <i class="fas fa-calendar text-blue-600 mr-2"></i>
                                    <span class="font-medium">Decision Date:</span> ${formattedDate}
                                </div>
                                <div>
                                    <i class="fas fa-boxes text-blue-600 mr-2"></i>
                                    <span class="font-medium">Quantity:</span> ${request.quantity}
                                </div>
                            </div>

                            ${request.office_review_notes ? `
                                <div class="bg-gray-50 rounded p-2 mt-2 text-sm">
                                    <i class="fas fa-comment text-gray-600 mr-1"></i>
                                    <span class="font-medium">Your Notes:</span>
                                    <p class="text-gray-700 mt-1">${this.escapeHtml(request.office_review_notes)}</p>
                                </div>
                            ` : ''}

                            ${request.status === 'rejected' && request.rejection_reason ? `
                                <div class="bg-red-50 border-l-4 border-red-500 rounded p-2 mt-2 text-sm">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-1"></i>
                                    <span class="font-medium text-red-800">Rejection Reason:</span>
                                    <p class="text-red-700 mt-1">${this.escapeHtml(request.rejection_reason)}</p>
                                </div>
                            ` : ''}
                        </div>

                        <button onclick="historyManager.viewDetails(${request.id})"
                                class="ml-4 bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-eye mr-1"></i> Details
                        </button>
                    </div>
                </div>
            `;
        }

        getStatusBadge(status) {
            const badges = {
                'approved': '<span class="badge badge-approved">Approved</span>',
                'rejected': '<span class="badge badge-rejected">Rejected</span>',
                'released': '<span class="badge badge-released">Released</span>',
                'returned': '<span class="badge badge-returned">Returned</span>'
            };
            return badges[status] || `<span class="badge">${status}</span>`;
        }

        async viewDetails(requestId) {
            try {
                const response = await fetch(`/AMS-REQ/api/requests.php?action=get_request&request_id=${requestId}`);
                const data = await response.json();

                if (data.success) {
                    const request = data.request;
                    const approvedDate = new Date(request.office_approved_at).toLocaleString();

                    Swal.fire({
                        title: 'Request Details',
                        html: `
                            <div class="text-left space-y-3">
                                <div class="border-b pb-2">
                                    <p class="text-sm text-gray-600">Asset</p>
                                    <p class="font-semibold">${this.escapeHtml(request.asset_name)}</p>
                                </div>
                                <div class="border-b pb-2">
                                    <p class="text-sm text-gray-600">Requester</p>
                                    <p class="font-semibold">${this.escapeHtml(request.requester_name)}</p>
                                    <p class="text-xs text-gray-500">${this.escapeHtml(request.requester_email)}</p>
                                </div>
                                <div class="border-b pb-2">
                                    <p class="text-sm text-gray-600">Request Date</p>
                                    <p class="font-semibold">${new Date(request.request_date).toLocaleString()}</p>
                                </div>
                                <div class="border-b pb-2">
                                    <p class="text-sm text-gray-600">Your Decision Date</p>
                                    <p class="font-semibold">${approvedDate}</p>
                                </div>
                                <div class="border-b pb-2">
                                    <p class="text-sm text-gray-600">Purpose</p>
                                    <p class="font-semibold">${this.escapeHtml(request.purpose)}</p>
                                </div>
                                <div class="border-b pb-2">
                                    <p class="text-sm text-gray-600">Expected Return Date</p>
                                    <p class="font-semibold">${new Date(request.expected_return_date).toLocaleDateString()}</p>
                                </div>
                                ${request.office_review_notes ? `
                                    <div class="border-b pb-2">
                                        <p class="text-sm text-gray-600">Your Notes</p>
                                        <p class="font-semibold">${this.escapeHtml(request.office_review_notes)}</p>
                                    </div>
                                ` : ''}
                                ${request.status === 'rejected' && request.rejection_reason ? `
                                    <div class="bg-red-50 p-3 rounded">
                                        <p class="text-sm text-gray-600">Rejection Reason</p>
                                        <p class="font-semibold text-red-800">${this.escapeHtml(request.rejection_reason)}</p>
                                    </div>
                                ` : ''}
                            </div>
                        `,
                        width: '600px',
                        confirmButtonText: 'Close',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            } catch (error) {
                console.error('Error loading request details:', error);
                Swal.fire('Error', 'Failed to load request details', 'error');
            }
        }

        clearFilters() {
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('dateRangeFilter').value = 'all';
            document.getElementById('searchInput').value = '';
            this.applyFilters();
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        }
    }

    // Initialize
    let historyManager;
    document.addEventListener('DOMContentLoaded', () => {
        historyManager = new HistoryManager();
    });
    </script>
</body>
</html>
