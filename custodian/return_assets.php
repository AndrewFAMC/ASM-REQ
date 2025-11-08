<?php
/**
 * Return Assets Page
 * Custodian processes asset returns from employees
 */

require_once dirname(__DIR__) . '/config.php';

// Require custodian access
if (!isLoggedIn() || !hasRole('custodian')) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

// Get statistics
$stats = [
    'pending_return' => 0,
    'overdue' => 0,
    'returned_today' => 0
];


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
} catch (PDOException $e) {
    error_log("Error fetching custodian statistics: " . $e->getMessage());
}


try {
    // Pending return (released but not yet returned)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'released'
        AND campus_id = ?
    ");
    $stmt->execute([$campusId]);
    $stats['pending_return'] = (int)$stmt->fetch()['count'];

    // Overdue returns
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'released'
        AND campus_id = ?
        AND expected_return_date < CURDATE()
    ");
    $stmt->execute([$campusId]);
    $stats['overdue'] = (int)$stmt->fetch()['count'];

    // Returned today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'returned'
        AND campus_id = ?
        AND DATE(returned_date) = CURDATE()
    ");
    $stmt->execute([$campusId]);
    $stats['returned_today'] = (int)$stmt->fetch()['count'];

} catch (PDOException $e) {
    error_log("Error fetching return statistics: " . $e->getMessage());
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
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Navigation -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Custodian Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <!-- Simplified Navigation -->
                <a href="dashboard.php" onclick="showTab('manage-assets')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-box-open w-6"></i><span>Manage Assets</span>
                </a>
                <a href="dashboard.php" onclick="showTab('offices')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Offices</span>
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-700 my-2"></div>

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
            </nav>
        </div>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Process Asset Returns</h2>
                <p class="text-gray-600 mt-1">Receive assets back from employees</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending Return</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2" id="pendingCount"><?= $stats['pending_return'] ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="fas fa-undo text-blue-600 text-2xl"></i>
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
                            <option value="returned">Already Returned</option>
                        </select>

                        <!-- Search -->
                        <input type="text" id="searchInput" placeholder="Search by asset or employee..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
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
        </main>
    </div>

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
                const response = await fetch('../api/requests.php?action=get_pending_requests');
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
                } else if (status === 'released') {
                    matchesStatus = request.status === 'released' && !this.isOverdue(request);
                } else if (status === 'overdue') {
                    matchesStatus = request.status === 'released' && this.isOverdue(request);
                } else if (status === 'returned') {
                    matchesStatus = request.status === 'returned';
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
            const returnDate = new Date(request.expected_return_date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            return returnDate < today;
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

            let statusBadge = '';
            let borderColor = 'border-blue-500';

            if (request.status === 'returned') {
                statusBadge = '<span class="badge badge-returned">Returned</span>';
                borderColor = 'border-green-500';
            } else if (isOverdue) {
                statusBadge = '<span class="badge badge-overdue">Overdue!</span>';
                borderColor = 'border-red-500';
            } else {
                statusBadge = '<span class="badge badge-released">Out for Use</span>';
                borderColor = 'border-blue-500';
            }

            const daysOut = request.released_date ?
                Math.floor((new Date() - new Date(request.released_date)) / (1000 * 60 * 60 * 24)) : 0;

            return `
                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 ${borderColor}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        ${this.escapeHtml(request.asset_name)}
                                        ${isOverdue ? '<i class="fas fa-exclamation-circle text-red-600 ml-2"></i>' : ''}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Employee: ${this.escapeHtml(request.requester_name)}
                                        <span class="mx-2">•</span>
                                        ${daysOut > 0 ? `Out for ${daysOut} day(s)` : 'Just released'}
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
                                    <p class="text-xs text-gray-500">Released On</p>
                                    <p class="text-sm font-semibold text-gray-900">${request.released_date ? new Date(request.released_date).toLocaleDateString() : 'N/A'}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Expected Return</p>
                                    <p class="text-sm font-semibold ${isOverdue ? 'text-red-600' : 'text-gray-900'}">
                                        ${new Date(request.expected_return_date).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>

                            ${isOverdue ? `
                                <div class="bg-red-50 border border-red-200 rounded p-3 mb-4">
                                    <p class="text-sm text-red-900">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        This asset is overdue! Expected return was ${new Date(request.expected_return_date).toLocaleDateString()}
                                    </p>
                                </div>
                            ` : ''}

                            ${request.returned_date ? `
                                <div class="bg-green-50 border border-green-200 rounded p-3">
                                    <p class="text-sm text-green-900">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Returned on ${new Date(request.returned_date).toLocaleString()}
                                    </p>
                                    ${request.return_notes ? `
                                        <p class="text-sm text-green-800 mt-2">
                                            <strong>Notes:</strong> ${this.escapeHtml(request.return_notes)}
                                        </p>
                                    ` : ''}
                                </div>
                            ` : ''}
                        </div>

                        <div class="ml-4 flex flex-col space-y-2">
                            <button onclick="returnManager.viewDetails(${request.id})"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                            ${canReturn ? `
                                <button onclick="returnManager.returnAsset(${request.id})"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                                    <i class="fas fa-check mr-2"></i>Process Return
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        async viewDetails(requestId) {
            try {
                const response = await fetch(`../api/requests.php?action=get_request&request_id=${requestId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load details');
                }

                const request = data.request;
                const modal = document.getElementById('returnModal');
                const content = document.getElementById('modalContent');

                content.innerHTML = `
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Request Details</h3>
                            <button onclick="returnManager.closeModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Asset</p>
                                    <p class="font-semibold text-gray-900">${this.escapeHtml(request.asset_name)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Employee</p>
                                    <p class="font-semibold text-gray-900">${this.escapeHtml(request.requester_name)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Quantity</p>
                                    <p class="font-semibold text-gray-900">${request.quantity}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Released On</p>
                                    <p class="font-semibold text-gray-900">${request.released_date ? new Date(request.released_date).toLocaleDateString() : 'N/A'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Expected Return</p>
                                    <p class="font-semibold text-gray-900">${new Date(request.expected_return_date).toLocaleDateString()}</p>
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
                                    <button onclick="returnManager.returnAsset(${request.id})"
                                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                                        <i class="fas fa-check mr-2"></i>Process Return
                                    </button>
                                </div>
                            ` : `
                                <div class="flex justify-end pt-4 border-t">
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
                Swal.fire('Error', 'Failed to load request details', 'error');
            }
        }

        async returnAsset(requestId) {
            const result = await Swal.fire({
                title: 'Process Asset Return',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Asset Condition</label>
                            <select id="assetCondition" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="good">Good - No issues</option>
                                <option value="fair">Fair - Minor wear and tear</option>
                                <option value="damaged">Damaged - Needs repair</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Return Notes</label>
                            <textarea id="returnNotes" class="w-full border border-gray-300 rounded-lg px-3 py-2" rows="3" placeholder="Any notes about the condition or return..."></textarea>
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirm Return',
                confirmButtonColor: '#059669',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    return {
                        condition: document.getElementById('assetCondition').value,
                        notes: document.getElementById('returnNotes').value
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
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('Returned!', 'Asset has been returned successfully', 'success');
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
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    }

    // Initialize
    const returnManager = new ReturnManager();
    </script>
</body>
</html>
