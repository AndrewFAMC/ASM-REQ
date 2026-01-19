<?php
/**
 * Quick Scan Update Page
 * Allows custodians to scan assets and quickly update their status, location, and other details
 */

require_once dirname(__DIR__) . '/config.php';

// Require custodian access
if (!isLoggedIn() || !hasRole('custodian')) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

// Get statistics for navigation badges
$pendingReleaseCount = 0;
$pendingReturnCount = 0;
$overdueCount = 0;
$missingAssetsCount = 0;

try {
    // Count pending releases
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'approved' AND campus_id = ? AND request_source = 'custodian'");
    $stmt->execute([$campusId]);
    $pendingReleaseCount = (int)$stmt->fetchColumn();

    // Count pending returns
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'released' AND campus_id = ?");
    $stmt->execute([$campusId]);
    $pendingReturnCount = (int)$stmt->fetchColumn();

    // Count overdue returns
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'released' AND campus_id = ? AND expected_return_date < CURDATE()");
    $stmt->execute([$campusId]);
    $overdueCount = (int)$stmt->fetchColumn();

    // Count missing assets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM missing_assets_reports WHERE campus_id = ? AND status IN ('reported', 'investigating')");
    $stmt->execute([$campusId]);
    $missingAssetsCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Scan Update - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .scan-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .quick-action-btn {
            transition: all 0.2s;
        }
        .quick-action-btn:active {
            transform: scale(0.95);
        }
        .scanner-active {
            border: 3px solid #10b981;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }
        .scan-history-item {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex h-screen bg-gray-200">
        <!-- Navigation Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Custodian Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <a href="dashboard.php" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-box-open w-6"></i><span>Manage Assets</span>
                </a>
                <a href="dashboard.php" onclick="showTab('offices')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Offices</span>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <!-- Quick Scan Update - Active -->
                <a href="quick_scan_update.php" class="flex items-center px-4 py-2 rounded-md bg-blue-600 text-white">
                    <i class="fas fa-barcode-read w-6"></i>
                    <span>Quick Scan Update</span>
                </a>

                <a href="manage_units.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-tags w-6"></i>
                    <span>Manage Units</span>
                </a>

                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                </a>

                <a href="release_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                    <?php if ($pendingReleaseCount > 0): ?>
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingReleaseCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="return_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-undo w-6"></i>
                    <span>Return Assets</span>
                    <?php if ($pendingReturnCount > 0): ?>
                        <span class="ml-auto <?= $overdueCount > 0 ? 'bg-red-500' : 'bg-yellow-500' ?> text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingReturnCount ?></span>
                    <?php endif; ?>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <a href="missing_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-search w-6"></i>
                    <span>Missing Assets</span>
                    <?php if ($missingAssetsCount > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $missingAssetsCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-history w-6"></i>
                    <span>Approval History</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Quick Scan Update</h2>
                    <p class="text-gray-600 mt-1">Scan assets to quickly update status and information</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div id="scannerStatus" class="flex items-center space-x-2 px-3 py-2 rounded-lg bg-gray-100">
                        <div class="h-2 w-2 rounded-full bg-gray-400"></div>
                        <span class="text-sm text-gray-600">Scanner: Detecting...</span>
                    </div>
                    <span class="text-sm text-gray-600">Welcome, <strong><?= htmlspecialchars($user['full_name']) ?></strong></span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm">Logout</a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto">

                    <!-- Scanner Input Section -->
                    <div class="bg-white rounded-lg shadow-lg p-8 mb-6 border-2 border-gray-200" id="scannerInput">
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4">
                                <i class="fas fa-barcode text-4xl text-blue-600 scan-pulse"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Ready to Scan</h3>
                            <p class="text-gray-600">Scan asset barcode or manually enter asset code</p>
                        </div>

                        <div class="max-w-2xl mx-auto">
                            <div class="flex items-center space-x-4">
                                <input
                                    type="text"
                                    id="assetSearchInput"
                                    class="flex-1 text-2xl px-6 py-4 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-200 transition-all"
                                    placeholder="Scan or type barcode / serial number / asset name..."
                                    autofocus
                                >
                                <button
                                    onclick="searchAsset()"
                                    class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors text-lg"
                                >
                                    <i class="fas fa-search mr-2"></i>Search
                                </button>
                            </div>
                            <p class="text-sm text-gray-500 mt-3 text-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Press Enter or click Search after scanning
                            </p>
                        </div>
                    </div>

                    <!-- Asset Details and Quick Actions Panel (Hidden by default) -->
                    <div id="assetPanel" class="hidden">
                        <!-- Content populated by JavaScript -->
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Include barcode scanner library -->
    <script src="../assets/js/barcode-scanner.js"></script>

    <script>
    // Current asset tracking
    let currentAsset = null;
    let currentUnit = null; // Track scanned unit

    // Initialize barcode scanner
    const scanner = new BarcodeScannerManager({
        onScan: (barcode) => {
            document.getElementById('assetSearchInput').value = barcode;
            searchAsset();
        },
        onConnect: () => {
            updateScannerStatus(true);
        },
        onDisconnect: () => {
            updateScannerStatus(false);
        }
    });

    // Initialize scanner
    scanner.init();

    // Update scanner status indicator
    function updateScannerStatus(connected) {
        const statusEl = document.getElementById('scannerStatus');
        if (connected) {
            statusEl.innerHTML = `
                <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
                <span class="text-sm text-green-700 font-semibold">Scanner: Connected</span>
            `;
            statusEl.className = 'flex items-center space-x-2 px-3 py-2 rounded-lg bg-green-100';
        } else {
            statusEl.innerHTML = `
                <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                <span class="text-sm text-yellow-700">Scanner: Keyboard Mode</span>
            `;
            statusEl.className = 'flex items-center space-x-2 px-3 py-2 rounded-lg bg-yellow-100';
        }
    }

    // Auto-submit on Enter key
    document.getElementById('assetSearchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchAsset();
        }
    });

    async function searchAsset() {
        const searchTerm = document.getElementById('assetSearchInput').value.trim();

        if (!searchTerm) {
            Swal.fire({
                icon: 'warning',
                title: 'Empty Search',
                text: 'Please scan or enter an asset code',
                timer: 2000
            });
            return;
        }

        const scanStartTime = Date.now();

        // Show loading
        Swal.fire({
            title: 'Searching...',
            text: 'Looking up asset information',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch(`../api/barcode_lookup.php?search=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();

            Swal.close();

            if (data.success) {
                currentAsset = data.asset;
                currentUnit = data.unit_searched || null; // Store scanned unit info
                displayAssetPanel(data);

                // Clear input for next scan
                document.getElementById('assetSearchInput').value = '';

                // Add scanner active effect
                document.getElementById('scannerInput').classList.add('scanner-active');
                setTimeout(() => {
                    document.getElementById('scannerInput').classList.remove('scanner-active');
                }, 500);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Asset Not Found',
                    text: data.message || 'No asset found matching your search',
                    timer: 3000
                });
                document.getElementById('assetSearchInput').value = '';
            }
        } catch (error) {
            Swal.close();
            console.error('Search error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Search Failed',
                text: 'An error occurred. Please try again.',
                timer: 3000
            });
        }
    }

    function displayAssetPanel(data) {
        const asset = data.asset;
        const unit = data.unit_searched;
        const panel = document.getElementById('assetPanel');

        const statusColors = {
            'Available': 'bg-green-100 text-green-800 border-green-300',
            'In Use': 'bg-blue-100 text-blue-800 border-blue-300',
            'Unavailable': 'bg-gray-100 text-gray-800 border-gray-300',
            'Damaged': 'bg-red-100 text-red-800 border-red-300',
            'Missing': 'bg-red-100 text-red-800 border-red-300',
            'Under Repair': 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'Retired': 'bg-gray-100 text-gray-600 border-gray-300',
            'Disposed': 'bg-gray-200 text-gray-700 border-gray-400'
        };

        const currentBorrowing = data.current_borrowing;

        panel.innerHTML = `
            <div class="bg-white rounded-lg shadow-lg border-l-4 ${unit ? 'border-purple-500' : 'border-blue-500'} mb-6">
                <!-- Asset Header -->
                <div class="p-6 border-b bg-gradient-to-r from-${unit ? 'purple' : 'blue'}-50 to-white">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">${escapeHtml(asset.asset_name)}</h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                <span><i class="fas fa-hashtag mr-1"></i>ID: ${asset.id}</span>
                                ${asset.barcode ? `<span><i class="fas fa-barcode mr-1"></i>${escapeHtml(asset.barcode)}</span>` : ''}
                                ${asset.serial_number ? `<span><i class="fas fa-fingerprint mr-1"></i>${escapeHtml(asset.serial_number)}</span>` : ''}
                            </div>
                            ${unit ? `
                                <div class="mt-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
                                    <p class="text-xs font-semibold text-purple-600 uppercase mb-1">
                                        <i class="fas fa-tag mr-1"></i>Scanned Individual Unit
                                    </p>
                                    <p class="text-sm font-bold text-purple-900">Unit Code: ${escapeHtml(unit.unit_code)}</p>
                                    <p class="text-xs text-purple-700 mt-1">
                                        Status: <span class="font-semibold">${unit.unit_status || 'Unknown'}</span>
                                        ${unit.unit_serial_number ? ` • Serial: ${escapeHtml(unit.unit_serial_number)}` : ''}
                                    </p>
                                </div>
                            ` : ''}
                        </div>
                        <span class="px-4 py-2 rounded-lg text-sm font-bold border-2 ${statusColors[unit ? unit.unit_status : asset.status] || 'bg-gray-100 text-gray-800 border-gray-300'}">
                            ${unit ? unit.unit_status : asset.status}
                        </span>
                    </div>
                </div>

                <!-- Current Status Alert -->
                ${currentBorrowing ? `
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mx-6 mt-4 rounded-r">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-blue-900">Currently Borrowed</p>
                            <p class="text-sm text-blue-800 mt-1">
                                Borrowed by <strong>${escapeHtml(currentBorrowing.requester_name)}</strong>
                                ${currentBorrowing.expected_return_date ? ` • Return: ${formatDate(currentBorrowing.expected_return_date)}` : ''}
                            </p>
                        </div>
                    </div>
                </div>
                ` : ''}

                <!-- Asset Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Location</p>
                        <p class="text-sm text-gray-900">${escapeHtml(asset.location || 'Not Set')}</p>
                        <p class="text-xs text-gray-600 mt-1">${escapeHtml(asset.office || 'Unassigned')}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Category</p>
                        <p class="text-sm text-gray-900">${escapeHtml(asset.category || 'N/A')}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Quantity</p>
                        <p class="text-sm text-gray-900">${asset.quantity}</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="p-6 bg-gray-50 border-t">
                    <h4 class="text-sm font-semibold text-gray-700 uppercase mb-4">
                        <i class="fas fa-bolt mr-2 text-yellow-500"></i>Quick Actions
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <!-- Status Update Buttons -->
                        <button onclick="quickUpdateStatus('Available')" class="quick-action-btn bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-check-circle mr-2"></i>Available
                        </button>
                        <button onclick="quickUpdateStatus('In Use')" class="quick-action-btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-user mr-2"></i>In Use
                        </button>
                        <button onclick="quickUpdateStatus('Under Repair')" class="quick-action-btn bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-tools mr-2"></i>Repair
                        </button>
                        <button onclick="quickUpdateStatus('Damaged')" class="quick-action-btn bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Damaged
                        </button>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3">
                        <button onclick="updateLocation()" class="quick-action-btn bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-map-marker-alt mr-2"></i>Update Location
                        </button>
                        <button onclick="addNotes()" class="quick-action-btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-sticky-note mr-2"></i>Add Notes
                        </button>
                        <button onclick="viewFullDetails()" class="quick-action-btn bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-lg font-semibold transition-all">
                            <i class="fas fa-eye mr-2"></i>Full Details
                        </button>
                    </div>
                </div>
            </div>
        `;

        panel.classList.remove('hidden');

        // Scroll to panel
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function quickUpdateStatus(newStatus) {
        if (!currentAsset) return;

        const updateTarget = currentUnit
            ? `Unit: ${currentUnit.unit_code}`
            : `Asset: ${currentAsset.asset_name}`;

        const result = await Swal.fire({
            title: `Update Status to "${newStatus}"?`,
            text: updateTarget,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Update',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6'
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const requestData = {
                action: 'update_status',
                asset_id: currentAsset.id,
                status: newStatus,
                csrf_token: csrfToken
            };

            // If a specific unit was scanned, include unit_id
            if (currentUnit && currentUnit.unit_id) {
                requestData.unit_id = currentUnit.unit_id;
            }

            const response = await fetch('../api/quick_asset_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Status Updated!',
                    text: `Status changed to: ${newStatus}`,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Update current asset status
                currentAsset.status = newStatus;

                // Refresh asset panel
                searchAsset.lastSearch = currentAsset.barcode || currentAsset.serial_number || currentAsset.id;

                // Hide panel and focus on input
                setTimeout(() => {
                    document.getElementById('assetPanel').classList.add('hidden');
                    document.getElementById('assetSearchInput').focus();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to update status');
            }
        } catch (error) {
            console.error('Update error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: error.message
            });
        }
    }

    async function updateLocation() {
        if (!currentAsset) return;

        const result = await Swal.fire({
            title: 'Update Location',
            html: `
                <input id="location" class="swal2-input" placeholder="Location (e.g., Room 101)" value="${currentAsset.location || ''}">
            `,
            showCancelButton: true,
            confirmButtonText: 'Update',
            preConfirm: () => {
                return {
                    location: document.getElementById('location').value
                };
            }
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('../api/quick_asset_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_location',
                    asset_id: currentAsset.id,
                    location: result.value.location,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Location Updated!',
                    timer: 2000,
                    showConfirmButton: false
                });

                setTimeout(() => {
                    document.getElementById('assetPanel').classList.add('hidden');
                    document.getElementById('assetSearchInput').focus();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to update location');
            }
        } catch (error) {
            console.error('Update error:', error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function addNotes() {
        if (!currentAsset) return;

        const result = await Swal.fire({
            title: 'Add Notes',
            input: 'textarea',
            inputLabel: 'Notes / Remarks',
            inputPlaceholder: 'Enter any notes or observations about this asset...',
            inputValue: currentAsset.remarks || '',
            showCancelButton: true,
            confirmButtonText: 'Save'
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('../api/quick_asset_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add_notes',
                    asset_id: currentAsset.id,
                    notes: result.value,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Notes Saved!',
                    timer: 2000,
                    showConfirmButton: false
                });

                setTimeout(() => {
                    document.getElementById('assetPanel').classList.add('hidden');
                    document.getElementById('assetSearchInput').focus();
                }, 1500);
            } else {
                throw new Error(data.message || 'Failed to save notes');
            }
        } catch (error) {
            console.error('Update error:', error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function viewFullDetails() {
        if (!currentAsset) return;

        try {
            // Fetch full asset details including history
            const response = await fetch(`../api/barcode_lookup.php?search=${currentAsset.id}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load details');
            }

            const asset = data.asset;
            const depreciation = data.depreciation;
            const currentBorrowing = data.current_borrowing;
            const maintenanceHistory = data.maintenance_history || [];
            const borrowingHistory = data.borrowing_history || [];
            const recentScans = data.recent_scans || [];
            const activityLog = data.activity_log || [];

            // Build detailed modal HTML
            let modalContent = `
                <div class="text-left max-h-[70vh] overflow-y-auto">
                    <!-- Asset Header -->
                    <div class="bg-gradient-to-r from-blue-50 to-white p-4 rounded-lg mb-4 border-l-4 border-blue-500">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">${escapeHtml(asset.asset_name)}</h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-gray-600">Asset ID:</span> <span class="font-semibold">#${asset.id}</span></div>
                            ${asset.barcode ? `<div><span class="text-gray-600">Barcode:</span> <span class="font-semibold">${escapeHtml(asset.barcode)}</span></div>` : ''}
                            ${asset.serial_number ? `<div><span class="text-gray-600">Serial:</span> <span class="font-semibold">${escapeHtml(asset.serial_number)}</span></div>` : ''}
                            <div><span class="text-gray-600">Category:</span> <span class="font-semibold">${escapeHtml(asset.category || 'N/A')}</span></div>
                        </div>
                    </div>

                    <!-- Current Borrowing Alert -->
                    ${currentBorrowing ? `
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-4 rounded-r">
                        <p class="font-semibold text-blue-900 mb-1"><i class="fas fa-info-circle mr-2"></i>Currently Borrowed</p>
                        <p class="text-sm text-blue-800">By: <strong>${escapeHtml(currentBorrowing.requester_name)}</strong></p>
                        <p class="text-sm text-blue-800">Since: ${formatDate(currentBorrowing.released_date)}</p>
                        ${currentBorrowing.expected_return_date ? `<p class="text-sm text-blue-800">Expected Return: ${formatDate(currentBorrowing.expected_return_date)}</p>` : ''}
                    </div>
                    ` : ''}

                    <!-- Asset Information Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Location</p>
                            <p class="text-sm text-gray-900">${escapeHtml(asset.location || 'Not Set')}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Office</p>
                            <p class="text-sm text-gray-900">${escapeHtml(asset.office || 'Unassigned')}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Building</p>
                            <p class="text-sm text-gray-900">${escapeHtml(asset.building || 'N/A')}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Room</p>
                            <p class="text-sm text-gray-900">${escapeHtml(asset.room || 'N/A')}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Brand</p>
                            <p class="text-sm text-gray-900">${escapeHtml(asset.brand || 'N/A')}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Quantity</p>
                            <p class="text-sm text-gray-900">${asset.quantity}</p>
                        </div>
                    </div>

                    ${asset.description ? `
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Description</p>
                        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded">${escapeHtml(asset.description)}</p>
                    </div>
                    ` : ''}

                    ${asset.remarks ? `
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Remarks</p>
                        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded whitespace-pre-wrap">${escapeHtml(asset.remarks)}</p>
                    </div>
                    ` : ''}

                    <!-- Depreciation Info -->
                    ${depreciation ? `
                    <div class="mb-4 bg-purple-50 p-3 rounded border border-purple-200">
                        <p class="text-xs text-purple-900 uppercase font-semibold mb-2"><i class="fas fa-chart-line mr-1"></i>Valuation</p>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-purple-700">Original Value:</span> <span class="font-bold text-purple-900">₱${depreciation.original_value.toFixed(2)}</span></div>
                            <div><span class="text-purple-700">Current Value:</span> <span class="font-bold text-green-700">₱${depreciation.current_value.toFixed(2)}</span></div>
                            <div><span class="text-purple-700">Age:</span> <span class="font-semibold text-purple-900">${depreciation.age_years} years</span></div>
                            <div><span class="text-purple-700">Depreciation Rate:</span> <span class="font-semibold text-purple-900">${depreciation.depreciation_rate}%/yr</span></div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Maintenance History -->
                    ${maintenanceHistory.length > 0 ? `
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-900 mb-2"><i class="fas fa-tools text-orange-600 mr-2"></i>Maintenance History</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            ${maintenanceHistory.slice(0, 5).map(m => `
                                <div class="bg-orange-50 p-2 rounded text-sm border-l-2 border-orange-400">
                                    <div class="flex justify-between items-start">
                                        <span class="font-semibold text-orange-900">${escapeHtml(m.maintenance_type)}</span>
                                        <span class="text-xs text-orange-700">${formatDate(m.maintenance_date)}</span>
                                    </div>
                                    <p class="text-xs text-orange-800 mt-1">${escapeHtml(m.description)}</p>
                                    ${m.cost ? `<p class="text-xs text-orange-700 mt-1">Cost: ₱${parseFloat(m.cost).toFixed(2)}</p>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}

                    <!-- Borrowing History -->
                    ${borrowingHistory.length > 0 ? `
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-900 mb-2"><i class="fas fa-history text-green-600 mr-2"></i>Borrowing History</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            ${borrowingHistory.slice(0, 5).map(b => `
                                <div class="bg-green-50 p-2 rounded text-sm border-l-2 ${b.status === 'released' ? 'border-blue-400' : 'border-green-400'}">
                                    <div class="flex justify-between items-start">
                                        <span class="font-semibold text-green-900">${escapeHtml(b.requester_name)}</span>
                                        <span class="text-xs px-2 py-1 rounded ${b.status === 'released' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">${b.status === 'released' ? 'Active' : 'Returned'}</span>
                                    </div>
                                    <p class="text-xs text-green-800 mt-1">Released: ${formatDate(b.released_date)}</p>
                                    ${b.returned_date ? `<p class="text-xs text-green-800">Returned: ${formatDate(b.returned_date)}</p>` : ''}
                                    ${b.days_overdue && b.days_overdue > 0 ? `<p class="text-xs text-red-600 font-semibold">⚠ ${b.days_overdue} days overdue</p>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}

                    <!-- Recent Activity -->
                    ${activityLog.length > 0 ? `
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-900 mb-2"><i class="fas fa-clock text-gray-600 mr-2"></i>Recent Activity</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            ${activityLog.slice(0, 5).map(a => `
                                <div class="bg-gray-50 p-2 rounded text-xs border-l-2 border-gray-400">
                                    <div class="font-semibold text-gray-900">${escapeHtml(a.action.replace('_', ' ')).toUpperCase()}</div>
                                    <p class="text-gray-700 mt-1">${escapeHtml(a.description)}</p>
                                    <p class="text-gray-500 mt-1">${formatDateTime(a.created_at)}</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;

            Swal.fire({
                title: 'Complete Asset Details',
                html: modalContent,
                width: '800px',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'text-left'
                }
            });

        } catch (error) {
            console.error('Error loading full details:', error);
            Swal.fire({
                icon: 'error',
                title: 'Failed to Load Details',
                text: error.message
            });
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatTime(date) {
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    // Focus on input when page loads
    document.getElementById('assetSearchInput').focus();
    </script>
</body>
</html>
