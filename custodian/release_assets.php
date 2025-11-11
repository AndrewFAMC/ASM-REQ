<?php
/**
 * Release Assets Page
 * Custodian releases approved assets to requesters
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
    'pending_release' => 0,
    'released_today' => 0,
    'total_released' => 0
];


// Get statistics for release/return badges
$campusId = $user['campus_id'];
$pendingReleaseCount = 0;
$pendingReturnCount = 0;
$overdueCount = 0;

try {
    // Count pending releases (approved but not yet released) - only custodian requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'approved' AND campus_id = ? AND request_source = 'custodian'");
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


try {
    // Pending release (approved by admin, not yet released)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'approved'
        AND campus_id = ?
    ");
    $stmt->execute([$campusId]);
    $stats['pending_release'] = (int)$stmt->fetch()['count'];

    // Released today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'released'
        AND campus_id = ?
        AND DATE(released_date) = CURDATE()
    ");
    $stmt->execute([$campusId]);
    $stats['released_today'] = (int)$stmt->fetch()['count'];

    // Total released this month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM asset_requests
        WHERE status = 'released'
        AND campus_id = ?
        AND YEAR(released_date) = YEAR(CURDATE())
        AND MONTH(released_date) = MONTH(CURDATE())
    ");
    $stmt->execute([$campusId]);
    $stats['total_released'] = (int)$stmt->fetch()['count'];

} catch (PDOException $e) {
    error_log("Error fetching release statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release Assets - HCC Asset Management</title>
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
        .badge-approved { background: #DEF7EC; color: #03543F; }
        .badge-released { background: #E1EFFE; color: #1E429F; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex h-screen bg-gray-200">
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

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Release Approved Assets</h2>
                    <p class="text-gray-600 mt-1">Hand out approved assets to requesters</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, <strong><?= htmlspecialchars($user['full_name']) ?></strong></span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm">Logout</a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto">

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending Release</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2" id="pendingCount"><?= $stats['pending_release'] ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="fas fa-box text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Released Today</p>
                            <p class="text-3xl font-bold text-green-600 mt-2" id="releasedTodayCount"><?= $stats['released_today'] ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">This Month</p>
                            <p class="text-3xl font-bold text-gray-600 mt-2" id="totalCount"><?= $stats['total_released'] ?></p>
                        </div>
                        <div class="bg-gray-100 rounded-full p-3">
                            <i class="fas fa-calendar text-gray-600 text-2xl"></i>
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
                            <option value="approved" selected>Ready to Release</option>
                            <option value="released">Already Released</option>
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

    <!-- Release Modal -->
    <div id="releaseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div id="modalContent"></div>
        </div>
    </div>

    <!-- Printable Tag Modal -->
    <div id="printableTagModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl" id="printable-tag-content">
            <!-- Header -->
            <div class="p-4 border-b flex justify-between items-center print:hidden">
                <h3 class="text-lg font-semibold">Inventory Tag</h3>
                <div>
                    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <button onclick="document.getElementById('printableTagModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
                        Close
                    </button>
                </div>
            </div>

            <!-- Tag Content -->
            <div class="border-2 border-black m-4 flex" id="tag-to-print" style="font-family: Arial, sans-serif; min-height: 400px;">
                <!-- Left Side Header (Vertical) -->
                <div class="border-r-2 border-black flex flex-col items-center justify-between py-2 px-1" style="width: 50px;">
                    <div class="flex flex-col items-center gap-2">
                        <img src="../logo/1.png" alt="HCC Logo" class="h-10 w-10">
                    </div>
                    <div class="flex-1 flex items-center justify-center">
                        <p class="text-xs font-bold tracking-wider" style="writing-mode: vertical-lr; transform: rotate(180deg); letter-spacing: 3px;">INVENTORY TAG</p>
                    </div>
                    <div class="flex items-end">
                        <p class="text-xs font-bold" style="writing-mode: vertical-lr; transform: rotate(180deg);">Attach This Style</p>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="flex-1 p-3">
                    <!-- Main Table -->
                    <table class="w-full text-sm border-collapse">
                    <tbody>
                        <!-- Row 1: Inventory Date and Tag Number -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold w-1/4">Inventory Date</td>
                            <td class="border border-black p-1 w-1/4" id="pt_inventory_date"></td>
                            <td class="border border-black p-1 bg-gray-100 font-bold w-1/4">Tag Number</td>
                            <td class="border border-black p-1 w-1/4" id="pt_tag_number"></td>
                        </tr>
                        <!-- Row 2: Article/Material -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Article / Material</td>
                            <td class="border border-black p-1" colspan="3" id="pt_article"></td>
                        </tr>
                        <!-- Row 3: SKU Details -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">SKU Details</td>
                            <td class="border border-black p-1" colspan="3" id="pt_serial_number"></td>
                        </tr>
                        <!-- Row 4: Quantity and Size -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Quantity</td>
                            <td class="border border-black p-1" id="pt_quantity"></td>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Size</td>
                            <td class="border border-black p-1" id="pt_size"></td>
                        </tr>
                        <!-- Row 5: Location -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold" rowspan="2">Location</td>
                            <td class="border border-black p-1 text-center bg-gray-50 font-semibold">Row</td>
                            <td class="border border-black p-1 text-center bg-gray-50 font-semibold">Section</td>
                            <td class="border border-black p-1 text-center bg-gray-50 font-semibold">Floor</td>
                        </tr>
                        <tr>
                            <td class="border border-black p-1" id="pt_location_row"></td>
                            <td class="border border-black p-1" id="pt_location_section"></td>
                            <td class="border border-black p-1" id="pt_location_floor"></td>
                        </tr>
                        <!-- Row 6: Counted By and Price By -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Counted By</td>
                            <td class="border border-black p-1" id="pt_counted_by"></td>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Price By</td>
                            <td class="border border-black p-1" id="pt_supplier"></td>
                        </tr>
                        <!-- Row 7: Checked By and Amount -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Checked By</td>
                            <td class="border border-black p-1" id="pt_checked_by"></td>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Amount / Unit Price</td>
                            <td class="border border-black p-1" id="pt_price"></td>
                        </tr>
                        <!-- Row 8: Remarks and QR/Barcode -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Remarks</td>
                            <td class="border border-black p-1" id="pt_remarks"></td>
                            <td class="border border-black p-1" colspan="2" rowspan="2">
                                <!-- QR Code and Barcode -->
                                <div class="flex flex-col items-center justify-center h-full">
                                    <div id="pt_qr_code" class="mb-1"></div>
                                    <svg id="pt_barcode" class="mt-1"></svg>
                                </div>
                            </td>
                        </tr>
                        <!-- Row 9: Total Value -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Total Value</td>
                            <td class="border border-black p-1" id="pt_total_value"></td>
                        </tr>
                        <!-- Row 10: Updated on -->
                        <tr>
                            <td class="border border-black p-1 bg-gray-100 font-bold">Updated on:</td>
                            <td class="border border-black p-1" colspan="3" id="pt_updated_at"></td>
                        </tr>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include QRCode and JsBarcode libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #tag-to-print, #tag-to-print * {
                visibility: visible;
            }
            #tag-to-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>

    <script>
    class ReleaseManager {
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
                const response = await fetch('../api/requests.php?action=get_pending_requests&status=approved,released&request_source=custodian');
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
                const matchesStatus = status === 'all' || request.status === status;
                const matchesSearch = !search ||
                    request.asset_name.toLowerCase().includes(search) ||
                    request.requester_name.toLowerCase().includes(search);

                return matchesStatus && matchesSearch;
            });

            this.renderRequests();
        }

        updateStatistics() {
            const pending = this.requests.filter(r => r.status === 'approved').length;
            document.getElementById('pendingCount').textContent = pending;

            const today = new Date().toISOString().split('T')[0];
            const releasedToday = this.requests.filter(r =>
                r.status === 'released' &&
                r.released_date &&
                r.released_date.startsWith(today)
            ).length;
            document.getElementById('releasedTodayCount').textContent = releasedToday;
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
            const isOfficeTransfer = request.requester_office_id != null;
            const statusBadges = {
                'approved': isOfficeTransfer
                    ? '<span class="badge badge-approved"><i class="fas fa-building mr-1"></i>Ready to Transfer</span>'
                    : '<span class="badge badge-approved">Ready to Release</span>',
                'released': '<span class="badge badge-released">Released</span>'
            };

            const canRelease = request.status === 'approved';

            return `
                <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 ${canRelease ? 'border-green-500' : 'border-blue-500'}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        ${this.escapeHtml(request.asset_name)}
                                        ${isOfficeTransfer ? '<span class="ml-2 inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-purple-100 text-purple-800"><i class="fas fa-building mr-1"></i>Office Transfer</span>' : ''}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Requester: ${this.escapeHtml(request.requester_name)}
                                        ${isOfficeTransfer && request.requester_office_name ? `<span class="text-purple-600 font-semibold">(${this.escapeHtml(request.requester_office_name)})</span>` : ''}
                                        <span class="mx-2">•</span>
                                        Requested: ${new Date(request.request_date).toLocaleDateString()}
                                    </p>
                                </div>
                                ${statusBadges[request.status]}
                            </div>

                            ${isOfficeTransfer ? `
                                <div class="bg-purple-50 border border-purple-200 rounded p-3 mb-4">
                                    <p class="text-sm text-purple-900">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <strong>Permanent Transfer:</strong> This asset will be assigned to ${this.escapeHtml(request.requester_office_name || 'the office')} via inventory tag.
                                    </p>
                                </div>
                            ` : ''}

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Quantity</p>
                                    <p class="text-sm font-semibold text-gray-900">${request.quantity}</p>
                                </div>
                                ${!isOfficeTransfer && request.expected_return_date ? `
                                    <div>
                                        <p class="text-xs text-gray-500">Expected Return</p>
                                        <p class="text-sm font-semibold text-gray-900">${new Date(request.expected_return_date).toLocaleDateString()}</p>
                                    </div>
                                ` : `
                                    <div>
                                        <p class="text-xs text-gray-500">Transfer Type</p>
                                        <p class="text-sm font-semibold text-purple-900">Permanent (No Return)</p>
                                    </div>
                                `}
                            </div>

                            <div class="mb-4">
                                <p class="text-xs text-gray-500 mb-1">Purpose</p>
                                <p class="text-sm text-gray-700">${this.escapeHtml(request.purpose)}</p>
                            </div>

                            ${request.released_date ? `
                                <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                    <p class="text-sm text-blue-900">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Released on ${new Date(request.released_date).toLocaleString()}
                                    </p>
                                </div>
                            ` : ''}
                        </div>

                        <div class="ml-4 flex flex-col space-y-2">
                            <button onclick="releaseManager.viewDetails(${request.id})"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                            ${canRelease ? `
                                <button onclick="releaseManager.${isOfficeTransfer ? 'generateTagForOffice' : 'releaseAsset'}(${request.id})"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                                    <i class="fas fa-hand-holding mr-2"></i>Release Asset
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
                const modal = document.getElementById('releaseModal');
                const content = document.getElementById('modalContent');

                content.innerHTML = `
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Request Details</h3>
                            <button onclick="releaseManager.closeModal()" class="text-gray-400 hover:text-gray-600">
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
                                    <p class="text-sm text-gray-500">Requester</p>
                                    <p class="font-semibold text-gray-900">${this.escapeHtml(request.requester_name)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Quantity</p>
                                    <p class="font-semibold text-gray-900">${request.quantity}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Request Date</p>
                                    <p class="font-semibold text-gray-900">${new Date(request.request_date).toLocaleDateString()}</p>
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

                            ${request.status === 'approved' ? `
                                <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                                    <button onclick="releaseManager.closeModal()"
                                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg">
                                        Close
                                    </button>
                                    <button onclick="releaseManager.releaseAsset(${request.id})"
                                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                                        <i class="fas fa-hand-holding mr-2"></i>Release Asset
                                    </button>
                                </div>
                            ` : `
                                <div class="flex justify-end pt-4 border-t">
                                    <button onclick="releaseManager.closeModal()"
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

        async releaseAsset(requestId) {
            const result = await Swal.fire({
                title: 'Release Asset',
                text: 'Confirm that you are handing over this asset to the requester',
                input: 'textarea',
                inputLabel: 'Release Notes (optional)',
                inputPlaceholder: 'Add any notes about the asset condition or special instructions...',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Release Asset',
                confirmButtonColor: '#059669',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('../api/requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'release_asset',
                        request_id: requestId,
                        notes: result.value || '',
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('Released!', 'Asset has been released to the requester', 'success');
                    this.closeModal();
                    await this.loadRequests();
                } else {
                    throw new Error(data.message || 'Failed to release asset');
                }
            } catch (error) {
                console.error('Error releasing asset:', error);
                Swal.fire('Error', error.message, 'error');
            }
        }

        async generateTagForOffice(requestId) {
            // Simple confirmation - no modal, auto-generate tag
            const result = await Swal.fire({
                title: 'Release & Transfer Asset',
                text: 'This will automatically generate an inventory tag and transfer the asset to the requesting office.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Release Asset',
                confirmButtonColor: '#059669',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            try {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Generating tag and transferring asset',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Auto-generate and transfer
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('../api/requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'auto_generate_tag_and_transfer',
                        request_id: requestId,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Close loading and show success briefly
                    await Swal.fire({
                        icon: 'success',
                        title: 'Transfer Complete!',
                        html: `
                            <div class="text-left">
                                <p class="mb-2"><i class="fas fa-tag text-green-600 mr-2"></i>Tag Generated: <strong>${data.tagNumber}</strong></p>
                                <p class="mb-2"><i class="fas fa-building text-purple-600 mr-2"></i>Transferred to: <strong>${data.officeName}</strong></p>
                            </div>
                        `,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    this.closeModal();
                    await this.loadRequests();

                    // Automatically show printable tag
                    if (data.tagId) {
                        this.showPrintableTag(data.tagId);
                    }
                } else {
                    throw new Error(data.message || 'Failed to generate tag and transfer');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', error.message, 'error');
            }
        }

        async showPrintableTag(tagId) {
            try {
                // Fetch tag details
                const response = await fetch(`../api/inventory_tags.php?action=get_tag_details&tag_id=${tagId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error('Failed to load tag details');
                }

                const tag = data.tag;

                // Populate all tag fields
                document.getElementById('pt_tag_number').textContent = tag.tag_number || 'N/A';
                document.getElementById('pt_inventory_date').textContent = tag.inventory_date || new Date().toLocaleDateString();
                document.getElementById('pt_article').textContent = tag.article || 'N/A';
                document.getElementById('pt_serial_number').textContent = tag.serial_number || 'N/A';
                document.getElementById('pt_quantity').textContent = tag.quantity || '1';
                document.getElementById('pt_size').textContent = tag.size || 'N/A';
                document.getElementById('pt_location_row').textContent = tag.location_row || 'N/A';
                document.getElementById('pt_location_section').textContent = tag.location_section || 'N/A';
                document.getElementById('pt_location_floor').textContent = tag.location_floor || 'N/A';
                document.getElementById('pt_counted_by').textContent = tag.counted_by || 'N/A';
                document.getElementById('pt_supplier').textContent = tag.supplier || 'N/A';
                document.getElementById('pt_checked_by').textContent = tag.checked_by || 'N/A';
                document.getElementById('pt_price').textContent = tag.unit_price ? `₱${parseFloat(tag.unit_price).toLocaleString('en-PH')}` : '₱0.00';
                document.getElementById('pt_remarks').textContent = tag.remarks || 'N/A';
                document.getElementById('pt_total_value').textContent = tag.total_value ? `₱${parseFloat(tag.total_value).toLocaleString('en-PH')}` : '₱0.00';
                document.getElementById('pt_updated_at').textContent = tag.updated_at ? new Date(tag.updated_at).toLocaleString() : 'N/A';

                // Generate QR code
                const qrContainer = document.getElementById('pt_qr_code');
                qrContainer.innerHTML = '';
                new QRCode(qrContainer, {
                    text: `${window.location.origin}/AMS-REQ/asset_lookup.php?tag=${tag.tag_number}`,
                    width: 80,
                    height: 80
                });

                // Generate barcode
                JsBarcode("#pt_barcode", tag.tag_number, {
                    format: "CODE128",
                    width: 1.5,
                    height: 40,
                    displayValue: true,
                    fontSize: 12
                });

                // Show the modal
                document.getElementById('printableTagModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error loading tag:', error);
                Swal.fire('Error', 'Failed to load printable tag', 'error');
            }
        }

        closeModal() {
            document.getElementById('releaseModal').classList.add('hidden');
        }

        escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    }

    // Initialize
    const releaseManager = new ReleaseManager();
    </script>
</body>
</html>
