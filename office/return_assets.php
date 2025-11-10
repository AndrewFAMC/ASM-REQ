<?php
/**
 * Office Return Assets Page
 * Office heads accept asset returns from employees
 */

require_once dirname(__DIR__) . '/config.php';

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
    'pending_return' => 0,
    'overdue' => 0,
    'returned_today' => 0
];

try {
    // Pending return (released but not yet returned)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'released'
        AND request_source = 'office'
        AND target_office_id = ?
        AND campus_id = ?
    ");
    $stmt->execute([$officeId, $campusId]);
    $stats['pending_return'] = (int)$stmt->fetch()['count'];

    // Overdue returns
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'released'
        AND request_source = 'office'
        AND target_office_id = ?
        AND campus_id = ?
        AND expected_return_date < CURDATE()
    ");
    $stmt->execute([$officeId, $campusId]);
    $stats['overdue'] = (int)$stmt->fetch()['count'];

    // Returned today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'returned'
        AND request_source = 'office'
        AND target_office_id = ?
        AND campus_id = ?
        AND DATE(returned_date) = CURDATE()
    ");
    $stmt->execute([$officeId, $campusId]);
    $stats['returned_today'] = (int)$stmt->fetch()['count'];

} catch (PDOException $e) {
    error_log("Error fetching return statistics: " . $e->getMessage());
}

// Get count of pending releases for badge
$pendingReleaseCount = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'approved'
        AND request_source = 'office'
        AND target_office_id = ?
    ");
    $stmt->execute([$officeId]);
    $pendingReleaseCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching release count: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Assets - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-released { background: #E1EFFE; color: #1E429F; }
        .badge-overdue { background: #FDE8E8; color: #9B1C1C; }
        .badge-returned { background: #DEF7EC; color: #03543F; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex h-screen bg-gray-200">
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
                    <?php if ($pendingReleaseCount > 0): ?>
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingReleaseCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Return Assets -->
                <a href="return_assets.php" class="flex items-center px-4 py-2 rounded-md bg-gray-700 text-white">
                    <i class="fas fa-undo w-6"></i>
                    <span>Return Assets</span>
                    <?php if ($stats['pending_return'] > 0): ?>
                        <span class="ml-auto <?= $stats['overdue'] > 0 ? 'bg-red-500' : 'bg-yellow-500' ?> text-white text-xs px-2 py-1 rounded-full font-bold"><?= $stats['pending_return'] ?></span>
                    <?php endif; ?>
                </a>

                <!-- Approval History -->
                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-history w-6"></i>
                    <span>Approval History</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Accept Asset Returns</h2>
                    <p class="text-gray-600 mt-1">Process returns for assets from <?= htmlspecialchars($officeName) ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> My Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto">

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending Returns</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2" id="pendingCount"><?= $stats['pending_return'] ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="fas fa-clock text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Overdue</p>
                            <p class="text-3xl font-bold text-red-600 mt-2" id="overdueCount"><?= $stats['overdue'] ?></p>
                        </div>
                        <div class="bg-red-100 rounded-full p-3">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Returned Today</p>
                            <p class="text-3xl font-bold text-green-600 mt-2" id="returnedTodayCount"><?= $stats['returned_today'] ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
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
                            <option value="released" selected>Pending Return</option>
                            <option value="overdue">Overdue</option>
                            <option value="returned">Returned</option>
                        </select>

                        <!-- Search -->
                        <input type="text" id="searchInput" placeholder="Search requests..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center space-x-3">
                        <button onclick="location.reload()" class="border border-gray-300 rounded-lg px-4 py-2 text-sm hover:bg-gray-50">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Requests List -->
            <div id="requestsContainer" class="space-y-4">
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-600">Loading requests...</p>
                </div>
            </div>

                </div> <!-- Close max-w-7xl -->
            </main>
        </div> <!-- Close flex-1 flex flex-col -->
    </div> <!-- Close main flex container -->

    <!-- Return Modal -->
    <div id="returnModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
    class ReturnManager {
        constructor() {
            this.requests = [];
            this.filteredRequests = [];
            this.init();
        }

        async init() {
            await this.loadRequests();
            this.setupEventListeners();
        }

        setupEventListeners() {
            document.getElementById('statusFilter').addEventListener('change', () => this.filterRequests());
            document.getElementById('searchInput').addEventListener('input', () => this.filterRequests());
        }

        async loadRequests() {
            try {
                // Load office-specific requests for returns
                const response = await fetch('../api/requests.php?action=get_office_requests_for_return');
                const data = await response.json();

                if (data.success) {
                    this.requests = data.requests;
                    this.filterRequests();
                    this.updateStatistics();
                } else {
                    throw new Error(data.message || 'Failed to load requests');
                }
            } catch (error) {
                console.error('Error loading requests:', error);
                Swal.fire('Error', 'Failed to load requests', 'error');
            }
        }

        filterRequests() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value.toLowerCase();

            this.filteredRequests = this.requests.filter(request => {
                let matchesStatus = false;

                if (status === 'all') {
                    matchesStatus = true;
                } else if (status === 'overdue') {
                    matchesStatus = request.status === 'released' && this.isOverdue(request);
                } else {
                    matchesStatus = request.status === status;
                }

                const matchesSearch = !search ||
                    request.asset_name.toLowerCase().includes(search) ||
                    request.requester_name.toLowerCase().includes(search);

                return matchesStatus && matchesSearch;
            });

            this.renderRequests();
        }

        isOverdue(request) {
            if (request.status !== 'released') return false;
            return new Date(request.expected_return_date) < new Date();
        }

        updateStatistics() {
            const pending = this.requests.filter(r => r.status === 'released').length;
            document.getElementById('pendingCount').textContent = pending;

            const overdue = this.requests.filter(r => this.isOverdue(r)).length;
            document.getElementById('overdueCount').textContent = overdue;

            const today = new Date().toISOString().split('T')[0];
            const returnedToday = this.requests.filter(r =>
                r.status === 'returned' &&
                r.returned_date &&
                r.returned_date.startsWith(today)
            ).length;
            document.getElementById('returnedTodayCount').textContent = returnedToday;
        }

        renderRequests() {
            const container = document.getElementById('requestsContainer');

            if (this.filteredRequests.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 bg-white rounded-lg">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No requests found</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = this.filteredRequests.map(request => this.renderRequestCard(request)).join('');
        }

        renderRequestCard(request) {
            const isOverdue = this.isOverdue(request);
            const canReturn = request.status === 'released';

            let statusBadge;
            if (request.status === 'returned') {
                statusBadge = '<span class="badge badge-returned">Returned</span>';
            } else if (isOverdue) {
                statusBadge = '<span class="badge badge-overdue">Overdue</span>';
            } else {
                statusBadge = '<span class="badge badge-released">Out with Employee</span>';
            }

            return `
                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 ${isOverdue ? 'border-red-500' : (canReturn ? 'border-yellow-500' : 'border-green-500')}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        ${this.escapeHtml(request.asset_name)}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Borrower: ${this.escapeHtml(request.requester_name)}
                                        <span class="mx-2">•</span>
                                        Released: ${new Date(request.released_date).toLocaleDateString()}
                                    </p>
                                </div>
                                ${statusBadge}
                            </div>

                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Quantity</p>
                                    <p class="text-sm font-semibold text-gray-900">${request.quantity}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Expected Return</p>
                                    <p class="text-sm font-semibold ${isOverdue ? 'text-red-600' : 'text-gray-900'}">
                                        ${new Date(request.expected_return_date).toLocaleDateString()}
                                        ${isOverdue ? ' <i class="fas fa-exclamation-circle"></i>' : ''}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Days ${isOverdue ? 'Overdue' : 'Remaining'}</p>
                                    <p class="text-sm font-semibold ${isOverdue ? 'text-red-600' : 'text-green-600'}">
                                        ${this.getDaysRemaining(request)}
                                    </p>
                                </div>
                            </div>

                            ${request.returned_date ? `
                                <div class="bg-green-50 border border-green-200 rounded p-3">
                                    <p class="text-sm text-green-900">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Returned on ${new Date(request.returned_date).toLocaleString()}
                                    </p>
                                </div>
                            ` : ''}
                        </div>

                        <div class="ml-4 flex flex-col space-y-2">
                            <button onclick="returnManager.viewDetails(${request.id})"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                            ${canReturn ? `
                                <button onclick="returnManager.acceptReturn(${request.id})"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                                    <i class="fas fa-undo mr-2"></i>Accept Return
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        getDaysRemaining(request) {
            const today = new Date();
            const returnDate = new Date(request.expected_return_date);
            const diffTime = returnDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays < 0) {
                return Math.abs(diffDays) + ' days';
            } else {
                return diffDays + ' days';
            }
        }

        async viewDetails(requestId) {
            try {
                const response = await fetch(`../api/requests.php?action=get_request&request_id=${requestId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load details');
                }

                const request = data.request;
                const isOverdue = this.isOverdue(request);
                const modal = document.getElementById('returnModal');
                const content = document.getElementById('modalContent');

                content.innerHTML = `
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Return Request Details</h3>
                            <button onclick="returnManager.closeModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        ${isOverdue ? `
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                                <div class="flex">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                                    <div>
                                        <p class="font-semibold text-red-800">Overdue Return</p>
                                        <p class="text-sm text-red-700">This asset is ${this.getDaysRemaining(request)} overdue</p>
                                    </div>
                                </div>
                            </div>
                        ` : ''}

                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Asset</p>
                                    <p class="font-semibold text-gray-900">${this.escapeHtml(request.asset_name)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Borrower</p>
                                    <p class="font-semibold text-gray-900">${this.escapeHtml(request.requester_name)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Quantity</p>
                                    <p class="font-semibold text-gray-900">${request.quantity}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Released Date</p>
                                    <p class="font-semibold text-gray-900">${new Date(request.released_date).toLocaleDateString()}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Expected Return</p>
                                    <p class="font-semibold ${isOverdue ? 'text-red-600' : 'text-gray-900'}">${new Date(request.expected_return_date).toLocaleDateString()}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p class="font-semibold text-gray-900">${request.status}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-2">Purpose</p>
                                <p class="text-gray-900">${this.escapeHtml(request.purpose)}</p>
                            </div>

                            ${request.status === 'released' ? `
                                <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                                    <button onclick="returnManager.closeModal()"
                                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg">
                                        Close
                                    </button>
                                    <button onclick="returnManager.acceptReturn(${request.id})"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                        <i class="fas fa-undo mr-2"></i>Accept Return
                                    </button>
                                </div>
                            ` : `
                                <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                                    <button onclick="returnManager.closeModal()"
                                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg">
                                        Close
                                    </button>
                                </div>
                            `}
                        </div>
                    </div>
                `;

                modal.classList.remove('hidden');
            } catch (error) {
                console.error('Error loading details:', error);
                Swal.fire('Error', error.message, 'error');
            }
        }

        async acceptReturn(requestId) {
            const request = this.requests.find(r => r.id === requestId);
            const isOverdue = this.isOverdue(request);

            const result = await Swal.fire({
                title: 'Accept Asset Return',
                html: `
                    <div class="text-left space-y-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Asset Condition</label>
                            <select id="swal-condition" class="w-full border border-gray-300 rounded px-3 py-2">
                                <option value="Good">Good Condition</option>
                                <option value="Fair">Fair Condition</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Missing">Missing/Not Returned</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Return Notes</label>
                            <textarea id="swal-notes" class="w-full border border-gray-300 rounded px-3 py-2" rows="3" placeholder="Any observations about the asset condition..."></textarea>
                        </div>
                        ${isOverdue ? `
                            <div class="bg-red-50 border border-red-200 rounded p-3">
                                <p class="text-sm text-red-800">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    This return is <strong>${this.getDaysRemaining(request)}</strong> overdue
                                </p>
                            </div>
                        ` : ''}
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Accept Return',
                confirmButtonColor: '#2563eb',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    return {
                        condition: document.getElementById('swal-condition').value,
                        notes: document.getElementById('swal-notes').value,
                        is_overdue: isOverdue,
                        days_overdue: isOverdue ? Math.abs(parseInt(this.getDaysRemaining(request))) : 0
                    };
                }
            });

            if (!result.isConfirmed) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('../api/requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'return_asset',
                        request_id: requestId,
                        condition: result.value.condition,
                        notes: result.value.notes,
                        is_overdue: result.value.is_overdue,
                        days_overdue: result.value.days_overdue,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('Success!', 'Asset return has been processed successfully', 'success');
                    this.closeModal();
                    await this.loadRequests();
                } else {
                    throw new Error(data.message || 'Failed to process return');
                }
            } catch (error) {
                console.error('Error processing return:', error);
                Swal.fire('Error', error.message, 'error');
            }
        }

        closeModal() {
            document.getElementById('returnModal').classList.add('hidden');
        }

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize
    let returnManager;
    document.addEventListener('DOMContentLoaded', () => {
        returnManager = new ReturnManager();
    });
    </script>
</body>
</html>
