<?php
/**
 * Custodian Approval Dashboard
 *
 * Allows custodians to review and approve/reject asset requests
 */

require_once '../config.php';

// Check authentication and role
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$role = strtolower($user['role'] ?? '');

if ($role !== 'custodian' && $role !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This page is for Custodian users only.';
    exit;
}

$campusId = $user['campus_id'];

// Get statistics for release/return badges
$campusId = $user['campus_id'];
$pendingReleaseCount = 0;
$pendingReturnCount = 0;
$overdueCount = 0;

try {
    // Count pending releases (approved but not yet released)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'approved' AND campus_id = ?");
    $stmt->execute([$campusId]);
    $pendingReleaseCount = (int)$stmt->fetchColumn();

    // Count pending returns (released but not yet returned)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'released' AND campus_id = ?");
    $stmt->execute([$campusId]);
    $pendingReturnCount = (int)$stmt->fetchColumn();

    // Count overdue returns
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'released' AND campus_id = ? AND expected_return_date < CURDATE()");
    $stmt->execute([$campusId]);
    $overdueCount = (int)$stmt->fetchColumn();

    // Count missing assets reports (active investigations)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM missing_assets_reports WHERE campus_id = ? AND status IN ('reported', 'investigating')");
    $stmt->execute([$campusId]);
    $missingAssetsCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching custodian statistics: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Requests - Custodian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/sweetalert-config.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .request-card {
            transition: all 0.2s;
        }
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-approved { background-color: #d1fae5; color: #065f46; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
      <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Custodian Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <!-- Simplified Navigation -->
                <a href="#" onclick="showTab('manage-assets')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-box-open w-6"></i><span>Manage Assets</span>
                </a>
                <a href="#" onclick="showTab('offices')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Offices</span>
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-700 my-2"></div>

                <!-- Quick Scan Update -->
                <a href="quick_scan_update.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-barcode-read w-6"></i>
                    <span>Quick Scan Update</span>
                </a>

                <!-- Approve Requests -->
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                </a>

                <!-- Release Assets -->
                <a href="release_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                    <?php if ($pendingReleaseCount > 0): ?>
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingReleaseCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Return Assets -->
                <a href="return_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-undo w-6"></i>
                    <span>Return Assets</span>
                    <?php if ($pendingReturnCount > 0): ?>
                        <span class="ml-auto <?= $overdueCount > 0 ? 'bg-red-500' : 'bg-yellow-500' ?> text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingReturnCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-700 my-2"></div>

                <!-- Missing Assets -->
                <a href="missing_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-search w-6"></i>
                    <span>Missing Assets</span>
                    <?php if ($missingAssetsCount > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $missingAssetsCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Approval History -->
                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
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
                            <h1 class="text-2xl font-semibold text-gray-900">Approve Asset Requests</h1>
                            <p class="text-sm text-gray-600 mt-1">Review and approve pending asset requests</p>
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

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Pending Requests -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Pending Approval</p>
                                <p id="pendingCount" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Today -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                                <i class="fas fa-check text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Approved Today</p>
                                <p id="approvedTodayCount" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Requests -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                                <i class="fas fa-list text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total This Month</p>
                                <p id="totalCount" class="text-2xl font-bold text-gray-900">0</p>
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
                                <option value="pending">Pending Only</option>
                                <option value="all">All Requests</option>
                                <option value="approved_custodian">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                            <select id="dateFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="all">All Time</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="searchInput" placeholder="Search by requester, asset..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
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

                <!-- Requests List -->
                <div class="bg-white rounded-lg shadow-sm">
                    <!-- Loading State -->
                    <div id="loadingState" class="flex items-center justify-center py-16">
                        <svg class="animate-spin h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Requests Container -->
                    <div id="requestsContainer" class="hidden divide-y divide-gray-200"></div>

                    <!-- Empty State -->
                    <div id="emptyState" class="hidden py-16 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-4 text-lg text-gray-600">No requests found</p>
                        <p class="mt-2 text-sm text-gray-500">All requests have been processed</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="requestModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900">Request Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div id="modalContent" class="p-6">
                <!-- Content will be loaded dynamically -->
            </div>

            <div id="modalActions" class="p-6 border-t border-gray-200 bg-gray-50">
                <!-- Action buttons will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
    // Request Manager
    class RequestManager {
        constructor() {
            this.requests = [];
            this.filteredRequests = [];
            this.currentRequest = null;
            this.csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            this.init();
        }

        init() {
            // Load initial data
            this.loadRequests();

            // Event listeners
            document.getElementById('applyFilters').addEventListener('click', () => this.applyFilters());
            document.getElementById('clearFilters').addEventListener('click', () => this.clearFilters());

            // Search with debounce
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => this.applyFilters(), 500);
            });

            // Auto-refresh every 30 seconds
            setInterval(() => this.loadRequests(), 30000);
        }

        async loadRequests() {
            this.showLoading();

            try {
                const response = await fetch('/AMS-REQ/api/requests.php?action=get_pending_requests');
                const data = await response.json();

                if (data.success) {
                    this.requests = data.requests;
                    this.updateStatistics();
                    this.applyFilters();
                } else {
                    this.showEmpty();
                }
            } catch (error) {
                console.error('Error loading requests:', error);
                Swal.fire('Error', 'Failed to load requests', 'error');
                this.showEmpty();
            }
        }

        updateStatistics() {
            // Pending count
            const pending = this.requests.filter(r => r.status === 'pending').length;
            document.getElementById('pendingCount').textContent = pending;

            // Approved today
            const today = new Date().toISOString().split('T')[0];
            const approvedToday = this.requests.filter(r =>
                r.status === 'approved_custodian' &&
                r.custodian_reviewed_at &&
                r.custodian_reviewed_at.startsWith(today)
            ).length;
            document.getElementById('approvedTodayCount').textContent = approvedToday;

            // Total this month
            const thisMonth = new Date().toISOString().slice(0, 7);
            const totalMonth = this.requests.filter(r =>
                r.request_date && r.request_date.startsWith(thisMonth)
            ).length;
            document.getElementById('totalCount').textContent = totalMonth;
        }

        applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            this.filteredRequests = this.requests.filter(request => {
                // Status filter
                if (statusFilter !== 'all' && request.status !== statusFilter) {
                    return false;
                }

                // Date filter
                if (request.request_date) {
                    const requestDate = new Date(request.request_date);
                    const now = new Date();
                    if (dateFilter === 'today') {
                        if (requestDate.toDateString() !== now.toDateString()) return false;
                    } else if (dateFilter === 'week') {
                        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                        if (requestDate < weekAgo) return false;
                    } else if (dateFilter === 'month') {
                        if (requestDate.getMonth() !== now.getMonth() ||
                            requestDate.getFullYear() !== now.getFullYear()) return false;
                    }
                }

                // Search filter
                if (searchTerm) {
                    const searchableText = `${request.requester_name} ${request.asset_name} ${request.purpose}`.toLowerCase();
                    if (!searchableText.includes(searchTerm)) return false;
                }

                return true;
            });

            this.renderRequests();
        }

        renderRequests() {
            const container = document.getElementById('requestsContainer');

            if (this.filteredRequests.length === 0) {
                this.showEmpty();
                return;
            }

            container.innerHTML = this.filteredRequests.map(request => this.createRequestCard(request)).join('');
            this.showRequests();

            // Add click handlers
            container.querySelectorAll('.view-request-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const requestId = btn.dataset.requestId;
                    this.viewRequest(requestId);
                });
            });
        }

        createRequestCard(request) {
            const statusBadge = this.getStatusBadge(request.status);
            const urgency = this.getUrgency(request.expected_return_date);
            const timeAgo = this.getTimeAgo(request.created_at);

            return `
                <div class="request-card p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">${this.escapeHtml(request.asset_name)}</h3>
                                ${statusBadge}
                                ${urgency ? `<span class="badge" style="background-color: #fee2e2; color: #991b1b;">${urgency}</span>` : ''}
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div>
                                    <p class="text-sm text-gray-600">Requester</p>
                                    <p class="font-medium text-gray-900">${this.escapeHtml(request.requester_name)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Quantity</p>
                                    <p class="font-medium text-gray-900">${request.quantity}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Category</p>
                                    <p class="font-medium text-gray-900">${this.escapeHtml(request.category_name)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Expected Return</p>
                                    <p class="font-medium text-gray-900">${new Date(request.expected_return_date).toLocaleDateString()}</p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p class="text-sm text-gray-600">Purpose</p>
                                <p class="text-gray-900">${this.escapeHtml(request.purpose || 'N/A')}</p>
                            </div>

                            <div class="mt-3 text-xs text-gray-500">
                                <i class="far fa-clock mr-1"></i>Requested ${timeAgo}
                            </div>
                        </div>

                        <div class="ml-4 flex flex-col space-y-2">
                            <button class="view-request-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm" data-request-id="${request.id}">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                            ${request.status === 'pending' ? `
                                <button onclick="requestManager.quickApprove(${request.id})" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                    <i class="fas fa-check mr-2"></i>Quick Approve
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge badge-pending">Pending</span>',
                'approved_custodian': '<span class="badge badge-approved">Approved</span>',
                'rejected': '<span class="badge badge-rejected">Rejected</span>'
            };
            return badges[status] || `<span class="badge">${status}</span>`;
        }

        getUrgency(expectedReturnDate) {
            const daysUntilReturn = Math.ceil((new Date(expectedReturnDate) - new Date()) / (1000 * 60 * 60 * 24));
            if (daysUntilReturn < 0) return 'OVERDUE';
            if (daysUntilReturn <= 2) return 'URGENT';
            if (daysUntilReturn <= 7) return 'SOON';
            return null;
        }

        async viewRequest(requestId) {
            try {
                const response = await fetch(`/AMS-REQ/api/requests.php?action=get_request&request_id=${requestId}`);
                const data = await response.json();

                if (data.success) {
                    this.currentRequest = data.request;
                    this.showRequestModal();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error loading request details:', error);
                Swal.fire('Error', 'Failed to load request details', 'error');
            }
        }

        showRequestModal() {
            const request = this.currentRequest;
            const modal = document.getElementById('requestModal');
            const content = document.getElementById('modalContent');
            const actions = document.getElementById('modalActions');

            content.innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Request ID</label>
                            <p class="text-gray-900 font-semibold">#${request.id}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status</label>
                            <p>${this.getStatusBadge(request.status)}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Asset</label>
                        <p class="text-lg font-semibold text-gray-900">${this.escapeHtml(request.asset_name)}</p>
                        <p class="text-sm text-gray-600">${this.escapeHtml(request.category_name)} ${request.serial_number ? `(SN: ${request.serial_number})` : ''}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Quantity Requested</label>
                            <p class="text-gray-900 font-semibold">${request.quantity}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Asset Value</label>
                            <p class="text-gray-900 font-semibold">₱${parseFloat(request.asset_value || 0).toLocaleString()}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Requester</label>
                        <p class="text-gray-900 font-semibold">${this.escapeHtml(request.requester_name)}</p>
                        <p class="text-sm text-gray-600">${request.requester_email} ${request.requester_phone ? `• ${request.requester_phone}` : ''}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Expected Return Date</label>
                        <p class="text-gray-900 font-semibold">${new Date(request.expected_return_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                        <p class="text-sm text-gray-600">${this.getDaysUntil(request.expected_return_date)}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Purpose</label>
                        <p class="text-gray-900">${this.escapeHtml(request.purpose || 'Not specified')}</p>
                    </div>

                    ${request.custodian_name ? `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <label class="text-sm font-medium text-green-800">Custodian Approval</label>
                            <p class="text-green-900"><i class="fas fa-check-circle mr-2"></i>Approved by ${this.escapeHtml(request.custodian_name)}</p>
                            <p class="text-sm text-green-700">${new Date(request.custodian_approved_at).toLocaleString()}</p>
                            ${request.custodian_comments ? `<p class="text-sm text-green-800 mt-2"><strong>Comments:</strong> ${this.escapeHtml(request.custodian_comments)}</p>` : ''}
                        </div>
                    ` : ''}

                    ${request.rejection_reason ? `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <label class="text-sm font-medium text-red-800">Rejection Reason</label>
                            <p class="text-red-900">${this.escapeHtml(request.rejection_reason)}</p>
                        </div>
                    ` : ''}

                    <div class="text-sm text-gray-500">
                        <p><i class="far fa-clock mr-2"></i>Requested on ${new Date(request.created_at).toLocaleString()}</p>
                    </div>
                </div>
            `;

            // Show action buttons only for pending requests
            if (request.status === 'pending') {
                actions.innerHTML = `
                    <div class="flex justify-end space-x-3">
                        <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                            Cancel
                        </button>
                        <button onclick="requestManager.rejectRequest()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-times mr-2"></i>Reject
                        </button>
                        <button onclick="requestManager.approveRequest()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-check mr-2"></i>Approve
                        </button>
                    </div>
                `;
            } else {
                actions.innerHTML = `
                    <div class="flex justify-end">
                        <button onclick="closeModal()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium">
                            Close
                        </button>
                    </div>
                `;
            }

            modal.classList.remove('hidden');
        }

        async approveRequest() {
            const { value: comments } = await Swal.fire({
                title: 'Approve Request',
                html: '<textarea id="swal-comments" class="swal2-textarea" placeholder="Add comments (optional)"></textarea>',
                showCancelButton: true,
                confirmButtonText: 'Approve',
                confirmButtonColor: '#10b981',
                preConfirm: () => {
                    return document.getElementById('swal-comments').value;
                }
            });

            if (comments !== undefined) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'approve_as_custodian');
                    formData.append('request_id', this.currentRequest.id);
                    formData.append('comments', comments);
                    formData.append('csrf_token', this.csrfToken);

                    const response = await fetch('/AMS-REQ/api/requests.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Success!', data.message, 'success');
                        closeModal();
                        this.loadRequests();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error approving request:', error);
                    Swal.fire('Error', 'Failed to approve request', 'error');
                }
            }
        }

        async rejectRequest() {
            const { value: reason } = await Swal.fire({
                title: 'Reject Request',
                html: '<textarea id="swal-reason" class="swal2-textarea" placeholder="Reason for rejection (required)" required></textarea>',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#dc2626',
                preConfirm: () => {
                    const reason = document.getElementById('swal-reason').value;
                    if (!reason) {
                        Swal.showValidationMessage('Please provide a reason for rejection');
                    }
                    return reason;
                }
            });

            if (reason) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'reject_request');
                    formData.append('request_id', this.currentRequest.id);
                    formData.append('reason', reason);
                    formData.append('csrf_token', this.csrfToken);

                    const response = await fetch('/AMS-REQ/api/requests.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Rejected', data.message, 'success');
                        closeModal();
                        this.loadRequests();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error rejecting request:', error);
                    Swal.fire('Error', 'Failed to reject request', 'error');
                }
            }
        }

        async quickApprove(requestId) {
            const result = await Swal.fire({
                title: 'Quick Approve?',
                text: 'Approve this request without adding comments?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, approve it',
                confirmButtonColor: '#10b981'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'approve_as_custodian');
                    formData.append('request_id', requestId);
                    formData.append('comments', '');
                    formData.append('csrf_token', this.csrfToken);

                    const response = await fetch('/AMS-REQ/api/requests.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Success!', data.message, 'success');
                        this.loadRequests();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error approving request:', error);
                    Swal.fire('Error', 'Failed to approve request', 'error');
                }
            }
        }

        clearFilters() {
            document.getElementById('statusFilter').value = 'pending';
            document.getElementById('dateFilter').value = 'all';
            document.getElementById('searchInput').value = '';
            this.applyFilters();
        }

        getDaysUntil(date) {
            const days = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24));
            if (days < 0) return `${Math.abs(days)} days overdue`;
            if (days === 0) return 'Today';
            if (days === 1) return 'Tomorrow';
            return `In ${days} days`;
        }

        getTimeAgo(datetime) {
            const now = new Date();
            const past = new Date(datetime);
            const diffMs = now - past;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'just now';
            if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

            return past.toLocaleDateString();
        }

        showLoading() {
            document.getElementById('loadingState').classList.remove('hidden');
            document.getElementById('requestsContainer').classList.add('hidden');
            document.getElementById('emptyState').classList.add('hidden');
        }

        showRequests() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('requestsContainer').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');
        }

        showEmpty() {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('requestsContainer').classList.add('hidden');
            document.getElementById('emptyState').classList.remove('hidden');
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Global functions
    function closeModal() {
        document.getElementById('requestModal').classList.add('hidden');
    }

    // Initialize
    let requestManager;
    document.addEventListener('DOMContentLoaded', () => {
        requestManager = new RequestManager();
    });
    </script>
</body>
</html>
