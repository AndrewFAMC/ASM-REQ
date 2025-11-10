<?php
/**
 * Office/Department Head Approval Dashboard
 *
 * Allows department heads (office role) to review and approve/reject asset requests
 */

require_once '../config.php';

// Check authentication and role
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$role = strtolower($user['role'] ?? '');

if ($role !== 'office') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This page is for Office/Department Head users only.';
    exit;
}

// Verify user is actually a department approver
$deptCheck = $pdo->prepare("
    SELECT da.*, o.office_name
    FROM department_approvers da
    JOIN offices o ON da.office_id = o.id
    WHERE da.approver_user_id = ? AND da.is_active = TRUE
");
$deptCheck->execute([$user['id']]);
$approverInfo = $deptCheck->fetch();

if (!$approverInfo) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. You are not assigned as a department approver.';
    exit;
}

$campusId = $user['campus_id'];
$officeId = $approverInfo['office_id'];
$officeName = $approverInfo['office_name'];

// Get statistics
$stats = [
    'pending_approval' => 0,
    'approved_today' => 0,
    'total_this_month' => 0
];

try {
    // Pending approval (office_review status for dual-flow system)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests ar
        WHERE ar.status = 'office_review'
        AND ar.request_source = 'office'
        AND ar.target_office_id = ?
        AND ar.campus_id = ?
    ");
    $stmt->execute([$officeId, $campusId]);
    $stats['pending_approval'] = (int)$stmt->fetch()['count'];

    // Approved today by this office
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE office_approved_by = ?
        AND DATE(office_approved_at) = CURDATE()
    ");
    $stmt->execute([$user['id']]);
    $stats['approved_today'] = (int)$stmt->fetch()['count'];

    // Total approved this month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE office_approved_by = ?
        AND YEAR(office_approved_at) = YEAR(CURDATE())
        AND MONTH(office_approved_at) = MONTH(CURDATE())
    ");
    $stmt->execute([$user['id']]);
    $stats['total_this_month'] = (int)$stmt->fetch()['count'];

} catch (PDOException $e) {
    error_log("Error fetching department approval statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Requests - Department Head Dashboard</title>
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
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md bg-gray-700 text-white">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                    <?php if ($stats['pending_approval'] > 0): ?>
                        <span class="ml-auto bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $stats['pending_approval'] ?></span>
                    <?php endif; ?>
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

                <!-- My Approvals History -->
                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
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

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Pending Approval -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Pending Your Approval</p>
                                <p id="pendingCount" class="text-2xl font-bold text-gray-900"><?= $stats['pending_approval'] ?></p>
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
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['approved_today'] ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Total This Month -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                                <i class="fas fa-list text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total This Month</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['total_this_month'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Banner -->
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800">
                                <strong>Your Role:</strong> You are authorized to approve asset requests directed to the <strong><?= htmlspecialchars($officeName) ?></strong> office.
                                These are requests where employees chose to request assets from your office inventory.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="office_review">Pending My Approval</option>
                                <option value="all">All Requests</option>
                                <option value="approved">Approved by Me</option>
                                <option value="rejected">Rejected</option>
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
            this.officeId = <?= json_encode($officeId) ?>;

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
                const status = document.getElementById('statusFilter').value;
                // Use get_pending_requests which is already filtered by role and office in the backend
                const response = await fetch('/AMS-REQ/api/requests.php?action=get_pending_requests');
                const data = await response.json();

                if (data.success) {
                    // Backend already filters by office_id, just apply status filter if needed
                    if (status === 'all') {
                        this.requests = data.requests;
                    } else {
                        this.requests = data.requests.filter(r => r.status === status);
                    }
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

        applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            this.filteredRequests = this.requests.filter(request => {
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
            const timeAgo = this.getTimeAgo(request.request_date);

            return `
                <div class="request-card p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">${this.escapeHtml(request.asset_name)}</h3>
                                ${statusBadge}
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

                            ${request.custodian_name ? `
                                <div class="mt-3 bg-green-50 border border-green-200 rounded p-2">
                                    <p class="text-xs text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Approved by Custodian: ${this.escapeHtml(request.custodian_name)}
                                    </p>
                                </div>
                            ` : ''}

                            <div class="mt-3 text-xs text-gray-500">
                                <i class="far fa-clock mr-1"></i>Requested ${timeAgo}
                            </div>
                        </div>

                        <div class="ml-4 flex flex-col space-y-2">
                            <button class="view-request-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm" data-request-id="${request.id}">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                            ${request.status === 'office_review' ? `
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
                'office_review': '<span class="badge badge-pending">Pending Your Approval</span>',
                'approved': '<span class="badge badge-approved">Approved</span>',
                'rejected': '<span class="badge badge-rejected">Rejected</span>'
            };
            return badges[status] || `<span class="badge">${status}</span>`;
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
                        <p class="text-sm text-gray-600">${this.escapeHtml(request.category_name)}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Requester (Your Department Employee)</label>
                        <p class="text-gray-900 font-semibold">${this.escapeHtml(request.requester_name)}</p>
                        <p class="text-sm text-gray-600">${request.requester_email}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Quantity Requested</label>
                            <p class="text-gray-900 font-semibold">${request.quantity}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Expected Return Date</label>
                            <p class="text-gray-900 font-semibold">${new Date(request.expected_return_date).toLocaleDateString()}</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Purpose</label>
                        <p class="text-gray-900">${this.escapeHtml(request.purpose || 'Not specified')}</p>
                    </div>

                    ${request.custodian_name ? `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <label class="text-sm font-medium text-green-800">Custodian Approval</label>
                            <p class="text-green-900"><i class="fas fa-check-circle mr-2"></i>Approved by ${this.escapeHtml(request.custodian_name)}</p>
                            <p class="text-sm text-green-700">${new Date(request.custodian_reviewed_at).toLocaleString()}</p>
                            ${request.custodian_review_notes ? `<p class="text-sm text-green-800 mt-2"><strong>Notes:</strong> ${this.escapeHtml(request.custodian_review_notes)}</p>` : ''}
                        </div>
                    ` : ''}

                    ${request.rejection_reason ? `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <label class="text-sm font-medium text-red-800">Rejection Reason</label>
                            <p class="text-red-900">${this.escapeHtml(request.rejection_reason)}</p>
                        </div>
                    ` : ''}
                </div>
            `;

            // Show action buttons only for pending requests
            if (request.status === 'office_review') {
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
                    formData.append('action', 'approve_as_office');
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
                    formData.append('action', 'approve_as_office');
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
            document.getElementById('statusFilter').value = 'office_review';
            document.getElementById('searchInput').value = '';
            this.loadRequests();
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
