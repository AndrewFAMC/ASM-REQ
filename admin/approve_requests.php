<?php
/**
 * Admin Approval Dashboard
 * Final approval tier for asset requests
 */

require_once dirname(__DIR__) . '/config.php';

// Require admin access
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$userId = $_SESSION['user_id'];

// Get statistics
$stats = [
    'pending_final_approval' => 0,
    'approved_today' => 0,
    'total_this_month' => 0,
    'rejected_this_month' => 0
];

try {
    // Pending final approval (approved by custodian but not by admin)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'custodian_review'
    ");
    $stmt->execute();
    $stats['pending_final_approval'] = (int)$stmt->fetch()['count'];

    // Approved today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'approved'
        AND DATE(updated_at) = CURDATE()
    ");
    $stmt->execute();
    $stats['approved_today'] = (int)$stmt->fetch()['count'];

    // Total this month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'approved'
        AND MONTH(updated_at) = MONTH(CURRENT_DATE())
        AND YEAR(updated_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $stats['total_this_month'] = (int)$stmt->fetch()['count'];

    // Rejected this month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'rejected'
        AND MONTH(updated_at) = MONTH(CURRENT_DATE())
        AND YEAR(updated_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $stats['rejected_this_month'] = (int)$stmt->fetch()['count'];

} catch (Exception $e) {
    error_log("Stats query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Approval Dashboard - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .request-card { transition: all 0.2s; }
        .request-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background-color: #FEF3C7; color: #92400E; }
        .badge-approved { background-color: #D1FAE5; color: #065F46; }
        .badge-rejected { background-color: #FEE2E2; color: #991B1B; }
        .approval-timeline { position: relative; padding-left: 2rem; }
        .approval-timeline::before { content: ''; position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 2px; background: #E5E7EB; }
        .approval-step { position: relative; margin-bottom: 1.5rem; }
        .approval-step::before { content: ''; position: absolute; left: -1.4rem; top: 0.25rem; width: 1rem; height: 1rem; border-radius: 50%; background: white; border: 2px solid #E5E7EB; }
        .approval-step.completed::before { background: #10B981; border-color: #10B981; }
        .approval-step.current::before { background: #3B82F6; border-color: #3B82F6; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Admin Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="admin_dashboard.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-home w-6"></i><span>Dashboard</span>
                </a>
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 bg-gray-700">
                    <i class="fas fa-check-circle w-6"></i><span>Approve Requests</span>
                </a>
                <a href="users.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-users w-6"></i><span>User Management</span>
                </a>
                <a href="offices.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Office Management</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-chart-bar w-6"></i><span>Reports</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Approve Requests</h1>
                    <p class="text-sm text-gray-600 mt-1">Final approval for asset requests</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include dirname(__DIR__) . '/includes/notification_center.php'; ?>

                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> My Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Pending Final Approval -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                            <i class="fas fa-hourglass-half text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Pending Approval</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['pending_final_approval'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Approved Today -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Approved Today</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['approved_today'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total This Month -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                            <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Approved This Month</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['total_this_month'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Rejected This Month -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Rejected This Month</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['rejected_this_month'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center space-x-4">
                        <!-- Status Filter -->
                        <select id="statusFilter" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Statuses</option>
                            <option value="custodian_review" selected>Pending Final Approval</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <!-- Search -->
                        <input type="text" id="searchInput" placeholder="Search requests..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center space-x-2">
                        <button onclick="requestManager.bulkApprove()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-check-double mr-2"></i>Bulk Approve Selected
                        </button>
                        <button onclick="requestManager.refresh()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Requests List -->
            <div id="requestsList" class="space-y-4">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    <p class="text-gray-600 mt-4">Loading requests...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Request Detail Modal -->
    <div id="requestModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Request Details</h2>
                    <button onclick="requestManager.closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
    class AdminRequestManager {
        constructor() {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            this.currentRequest = null;
            this.selectedRequests = new Set();
            this.init();
        }

        init() {
            this.loadRequests();

            // Auto-refresh every 30 seconds
            setInterval(() => this.loadRequests(), 30000);

            // Event listeners
            document.getElementById('statusFilter').addEventListener('change', () => this.loadRequests());
            document.getElementById('searchInput').addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.loadRequests(), 300);
            });
        }

        async loadRequests() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value;

            try {
                const params = new URLSearchParams({
                    action: 'get_pending_requests',
                    status: status,
                    search: search
                });

                const response = await fetch(`/AMS-REQ/api/requests.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.renderRequests(data.requests);
                } else {
                    throw new Error(data.message || 'Failed to load requests');
                }
            } catch (error) {
                console.error('Error loading requests:', error);
                document.getElementById('requestsList').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-3xl mb-3"></i>
                        <p class="text-red-800">Failed to load requests</p>
                        <p class="text-red-600 text-sm mt-2">${error.message}</p>
                    </div>
                `;
            }
        }

        renderRequests(requests) {
            const container = document.getElementById('requestsList');

            if (requests.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-lg shadow-sm py-16 text-center">
                        <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                        <p class="text-lg text-gray-600">No requests found</p>
                        <p class="text-sm text-gray-500 mt-2">All caught up!</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = requests.map(request => this.renderRequestCard(request)).join('');
        }

        renderRequestCard(request) {
            const statusColors = {
                'pending': 'bg-gray-50 border-l-4 border-gray-500',
                'custodian_review': 'bg-yellow-50 border-l-4 border-yellow-500',
                'department_review': 'bg-yellow-50 border-l-4 border-yellow-500',
                'approved': 'bg-green-50 border-l-4 border-green-500',
                'rejected': 'bg-red-50 border-l-4 border-red-500'
            };

            const statusBadges = {
                'pending': '<span class="badge badge-pending">Pending</span>',
                'custodian_review': '<span class="badge badge-pending">Awaiting Final Approval</span>',
                'department_review': '<span class="badge badge-pending">Awaiting Final Approval</span>',
                'approved': '<span class="badge badge-approved">Approved</span>',
                'rejected': '<span class="badge badge-rejected">Rejected</span>'
            };

            const isSelectable = request.status === 'custodian_review' || request.status === 'department_review';
            const checkboxHtml = isSelectable ? `
                <input type="checkbox" class="request-checkbox w-5 h-5 text-blue-600 rounded"
                       onchange="requestManager.toggleSelection(${request.id}, this.checked)">
            ` : '';

            return `
                <div class="request-card bg-white rounded-lg shadow-sm ${statusColors[request.status]} p-6">
                    <div class="flex items-start gap-4">
                        ${checkboxHtml ? `<div class="flex-shrink-0 pt-1">${checkboxHtml}</div>` : ''}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        ${this.escapeHtml(request.asset_name)}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Requested by: ${this.escapeHtml(request.requester_name)}
                                        <span class="mx-2">•</span>
                                        ${new Date(request.request_date).toLocaleDateString()}
                                    </p>
                                </div>
                                ${statusBadges[request.status]}
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Quantity</p>
                                    <p class="text-sm font-medium text-gray-900">${request.quantity}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Expected Return</p>
                                    <p class="text-sm font-medium text-gray-900">
                                        ${new Date(request.expected_return_date).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <p class="text-xs text-gray-500 mb-1">Purpose</p>
                                <p class="text-sm text-gray-700">${this.escapeHtml(request.purpose)}</p>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <button onclick="requestManager.viewRequest(${request.id})"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-eye mr-2"></i>View Full Details
                                </button>
                                ${isSelectable ? `
                                    <div class="space-x-2">
                                        <button onclick="requestManager.quickApprove(${request.id})"
                                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                            <i class="fas fa-check mr-2"></i>Approve
                                        </button>
                                        <button onclick="requestManager.rejectRequest(${request.id})"
                                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                                            <i class="fas fa-times mr-2"></i>Reject
                                        </button>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        toggleSelection(requestId, isSelected) {
            if (isSelected) {
                this.selectedRequests.add(requestId);
            } else {
                this.selectedRequests.delete(requestId);
            }
        }

        async viewRequest(requestId) {
            try {
                const response = await fetch(`/AMS-REQ/api/requests.php?action=get_request&request_id=${requestId}`);
                const data = await response.json();

                if (data.success) {
                    this.currentRequest = data.request;
                    this.showModal(data.request);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire('Error', 'Failed to load request details', 'error');
            }
        }

        showModal(request) {
            const approvalHistory = request.approval_history || [];

            const historyHtml = approvalHistory.length > 0 ? `
                <div class="approval-timeline">
                    ${approvalHistory.map(approval => `
                        <div class="approval-step ${approval.status === 'approved' ? 'completed' : ''}">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">${this.escapeHtml(approval.approver_name)}</p>
                                    <p class="text-sm text-gray-600">${approval.approver_role}</p>
                                    ${approval.comments ? `<p class="text-sm text-gray-700 mt-1 italic">"${this.escapeHtml(approval.comments)}"</p>` : ''}
                                </div>
                                <div class="text-right">
                                    <span class="badge ${approval.status === 'approved' ? 'badge-approved' : 'badge-rejected'}">
                                        ${approval.status}
                                    </span>
                                    <p class="text-xs text-gray-500 mt-1">
                                        ${new Date(approval.created_at).toLocaleString()}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            ` : '<p class="text-gray-500 text-sm">No approval history yet</p>';

            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-6">
                    <!-- Request Info -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Asset</p>
                                <p class="font-medium">${this.escapeHtml(request.asset_name)}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Requester</p>
                                <p class="font-medium">${this.escapeHtml(request.requester_name)}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Quantity</p>
                                <p class="font-medium">${request.quantity}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Expected Return Date</p>
                                <p class="font-medium">${new Date(request.expected_return_date).toLocaleDateString()}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-gray-600">Purpose</p>
                                <p class="font-medium">${this.escapeHtml(request.purpose)}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Approval History -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Chain</h3>
                        ${historyHtml}
                    </div>

                    <!-- Actions -->
                    ${request.status === 'custodian_review' || request.status === 'department_review' ? `
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button onclick="requestManager.closeModal()"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg">
                                Close
                            </button>
                            <button onclick="requestManager.rejectRequest(${request.id})"
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-times mr-2"></i>Reject
                            </button>
                            <button onclick="requestManager.approveRequest()"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-check mr-2"></i>Approve & Release Asset
                            </button>
                        </div>
                    ` : `
                        <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                            <button onclick="requestManager.closeModal()"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg">
                                Close
                            </button>
                        </div>
                    `}
                </div>
            `;

            document.getElementById('requestModal').classList.remove('hidden');
        }

        closeModal() {
            document.getElementById('requestModal').classList.add('hidden');
            this.currentRequest = null;
        }

        async quickApprove(requestId) {
            this.currentRequest = { id: requestId };
            await this.approveRequest();
        }

        async approveRequest() {
            const { value: comments } = await Swal.fire({
                title: 'Final Approval',
                html: `
                    <textarea id="swal-comments" class="swal2-textarea" placeholder="Add comments (optional)"></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Approve & Release Asset',
                confirmButtonColor: '#059669',
                preConfirm: () => {
                    return document.getElementById('swal-comments').value;
                }
            });

            if (comments !== undefined) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'approve_as_admin');
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
                        Swal.fire('Approved!', 'Request has been approved and asset released', 'success');
                        this.closeModal();
                        this.loadRequests();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }
        }

        async rejectRequest(requestId) {
            const { value: reason } = await Swal.fire({
                title: 'Reject Request',
                html: `
                    <textarea id="swal-reason" class="swal2-textarea" placeholder="Rejection reason (required)"></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#DC2626',
                preConfirm: () => {
                    const reason = document.getElementById('swal-reason').value;
                    if (!reason) {
                        Swal.showValidationMessage('Rejection reason is required');
                    }
                    return reason;
                }
            });

            if (reason) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'reject_request');
                    formData.append('request_id', requestId);
                    formData.append('reason', reason);
                    formData.append('csrf_token', this.csrfToken);

                    const response = await fetch('/AMS-REQ/api/requests.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Rejected', 'Request has been rejected', 'info');
                        this.closeModal();
                        this.loadRequests();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }
        }

        async bulkApprove() {
            if (this.selectedRequests.size === 0) {
                Swal.fire('No Selection', 'Please select requests to approve', 'info');
                return;
            }

            const result = await Swal.fire({
                title: 'Bulk Approve',
                text: `Approve ${this.selectedRequests.size} selected request(s)?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, approve all',
                confirmButtonColor: '#059669'
            });

            if (result.isConfirmed) {
                let successCount = 0;
                let failCount = 0;

                for (const requestId of this.selectedRequests) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'approve_as_admin');
                        formData.append('request_id', requestId);
                        formData.append('comments', 'Bulk approved');
                        formData.append('csrf_token', this.csrfToken);

                        const response = await fetch('/AMS-REQ/api/requests.php', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.csrfToken },
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            successCount++;
                        } else {
                            failCount++;
                        }
                    } catch (error) {
                        failCount++;
                    }
                }

                this.selectedRequests.clear();
                this.loadRequests();

                Swal.fire(
                    'Complete',
                    `Approved: ${successCount}, Failed: ${failCount}`,
                    successCount > 0 ? 'success' : 'error'
                );
            }
        }

        refresh() {
            this.loadRequests();
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    }

    // Initialize
    const requestManager = new AdminRequestManager();
    </script>
            </main>
        </div>
    </div>
</body>
</html>
