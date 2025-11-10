<?php
/**
 * Barcode/Asset Scanner
 * Scan or search for assets to view complete details, history, and status
 */

require_once 'config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: login.php');
    exit;
}

$user = getUserInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Scanner - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .scan-input {
            font-size: 1.5rem;
            padding: 1rem;
        }
        .detail-card {
            transition: all 0.2s;
        }
        .detail-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 0.375rem;
            top: 0.5rem;
            bottom: -0.5rem;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-dot {
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="<?= getRoleDashboard($user['role']) ?>" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Asset Scanner</h1>
                            <p class="text-sm text-gray-600 mt-1">Scan barcode or search by name/serial number</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600"><?= htmlspecialchars($user['full_name']) ?></span>
                        <a href="logout.php" class="text-sm text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Search Input -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center space-x-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            id="searchInput"
                            class="scan-input w-full border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
                            placeholder="Scan barcode or enter asset name, serial number, or ID..."
                            autofocus
                        >
                    </div>
                    <button
                        onclick="searchAsset()"
                        class="px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors"
                    >
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
                <p class="text-sm text-gray-500 mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Tip: Use a barcode scanner or manually type the asset code
                </p>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="hidden bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-600">Searching for asset...</p>
            </div>

            <!-- Asset Details Container -->
            <div id="assetDetails" class="hidden">
                <!-- Will be populated dynamically -->
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-barcode text-6xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No Asset Scanned</h3>
                <p class="text-gray-500">Scan a barcode or search for an asset to view details</p>
            </div>

        </main>
    </div>

    <script>
    // Auto-submit on barcode scan (when Enter is pressed)
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchAsset();
        }
    });

    async function searchAsset() {
        const searchTerm = document.getElementById('searchInput').value.trim();

        if (!searchTerm) {
            Swal.fire({
                icon: 'warning',
                title: 'Empty Search',
                text: 'Please enter a barcode, serial number, or asset name'
            });
            return;
        }

        // Show loading
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('assetDetails').classList.add('hidden');
        document.getElementById('loadingState').classList.remove('hidden');

        try {
            const response = await fetch(`/AMS-REQ/api/barcode_lookup.php?search=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();

            document.getElementById('loadingState').classList.add('hidden');

            if (data.success) {
                displayAssetDetails(data);
            } else {
                document.getElementById('emptyState').classList.remove('hidden');
                Swal.fire({
                    icon: 'error',
                    title: 'Asset Not Found',
                    text: data.message || 'No asset found matching your search criteria'
                });
            }
        } catch (error) {
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('emptyState').classList.remove('hidden');
            console.error('Search error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Search Failed',
                text: 'An error occurred while searching. Please try again.'
            });
        }
    }

    function displayAssetDetails(data) {
        const container = document.getElementById('assetDetails');
        const asset = data.asset;
        const depreciation = data.depreciation;
        const currentBorrowing = data.current_borrowing;

        // Generate status badge
        const statusColors = {
            'Available': 'bg-green-100 text-green-800',
            'Unavailable': 'bg-gray-100 text-gray-800',
            'Damaged': 'bg-red-100 text-red-800',
            'Missing': 'bg-red-100 text-red-800',
            'Under Repair': 'bg-yellow-100 text-yellow-800',
            'Retired': 'bg-gray-100 text-gray-600'
        };

        const statusBadge = `<span class="px-3 py-1 rounded-full text-sm font-semibold ${statusColors[asset.status] || 'bg-gray-100 text-gray-800'}">${asset.status}</span>`;

        let html = `
            <!-- Asset Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h2 class="text-3xl font-bold text-gray-900">${escapeHtml(asset.asset_name)}</h2>
                            ${statusBadge}
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                            <div>
                                <p class="text-gray-600">Asset ID</p>
                                <p class="font-semibold text-gray-900">#${asset.id}</p>
                            </div>
                            ${asset.barcode ? `
                            <div>
                                <p class="text-gray-600">Barcode</p>
                                <p class="font-semibold text-gray-900">${escapeHtml(asset.barcode)}</p>
                            </div>
                            ` : ''}
                            ${asset.serial_number ? `
                            <div>
                                <p class="text-gray-600">Serial Number</p>
                                <p class="font-semibold text-gray-900">${escapeHtml(asset.serial_number)}</p>
                            </div>
                            ` : ''}
                            <div>
                                <p class="text-gray-600">Category</p>
                                <p class="font-semibold text-gray-900">${escapeHtml(asset.category || 'N/A')}</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button onclick="window.print()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Current Borrowing Alert -->
            ${currentBorrowing ? `
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 text-xl mr-3 mt-1"></i>
                    <div class="flex-1">
                        <p class="font-semibold text-blue-900">Currently Borrowed</p>
                        <p class="text-sm text-blue-800 mt-1">
                            Borrowed by <strong>${escapeHtml(currentBorrowing.requester_name)}</strong>
                            since ${formatDate(currentBorrowing.released_date)}
                            ${currentBorrowing.expected_return_date ? ` • Expected return: ${formatDate(currentBorrowing.expected_return_date)}` : ''}
                        </p>
                    </div>
                </div>
            </div>
            ` : ''}

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Basic Information -->
                    <div class="detail-card bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            Basic Information
                        </h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-600">Location:</span> <span class="font-medium">${escapeHtml(asset.location || 'N/A')}</span></div>
                            <div><span class="text-gray-600">Office:</span> <span class="font-medium">${escapeHtml(asset.office || 'Unassigned')}</span></div>
                            <div><span class="text-gray-600">Building:</span> <span class="font-medium">${escapeHtml(asset.building || 'N/A')}</span></div>
                            <div><span class="text-gray-600">Room:</span> <span class="font-medium">${escapeHtml(asset.room || 'N/A')}</span></div>
                            <div><span class="text-gray-600">Brand:</span> <span class="font-medium">${escapeHtml(asset.brand || 'N/A')}</span></div>
                            <div><span class="text-gray-600">Quantity:</span> <span class="font-medium">${asset.quantity}</span></div>
                            <div><span class="text-gray-600">Purchase Date:</span> <span class="font-medium">${asset.purchase_date ? formatDate(asset.purchase_date) : 'N/A'}</span></div>
                            <div><span class="text-gray-600">Supplier:</span> <span class="font-medium">${escapeHtml(asset.supplier || 'N/A')}</span></div>
                            ${asset.assigned_to ? `
                            <div class="col-span-2">
                                <span class="text-gray-600">Assigned To:</span>
                                <span class="font-medium">${escapeHtml(asset.assigned_to)}</span>
                                ${asset.assigned_email ? ` <span class="text-gray-500">(${escapeHtml(asset.assigned_email)})</span>` : ''}
                            </div>
                            ` : ''}
                        </div>
                        ${asset.description ? `
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-gray-600 text-sm mb-1">Description:</p>
                            <p class="text-gray-900">${escapeHtml(asset.description)}</p>
                        </div>
                        ` : ''}
                        ${asset.remarks ? `
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-gray-600 text-sm mb-1">Remarks:</p>
                            <p class="text-gray-900">${escapeHtml(asset.remarks)}</p>
                        </div>
                        ` : ''}
                    </div>

                    <!-- Maintenance History -->
                    ${data.maintenance_history && data.maintenance_history.length > 0 ? `
                    <div class="detail-card bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-tools text-orange-600 mr-2"></i>
                            Maintenance History
                        </h3>
                        <div class="space-y-3">
                            ${data.maintenance_history.map(m => `
                                <div class="timeline-item">
                                    <div class="timeline-dot bg-orange-500"></div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-semibold text-gray-900">${escapeHtml(m.maintenance_type)}</span>
                                            <span class="text-sm text-gray-600">${formatDate(m.maintenance_date)}</span>
                                        </div>
                                        <p class="text-sm text-gray-700 mb-1">${escapeHtml(m.description)}</p>
                                        <div class="flex items-center justify-between text-xs text-gray-600">
                                            <span>By: ${escapeHtml(m.performed_by || 'N/A')}</span>
                                            ${m.cost ? `<span>Cost: ₱${parseFloat(m.cost).toFixed(2)}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}

                    <!-- Borrowing History -->
                    ${data.borrowing_history && data.borrowing_history.length > 0 ? `
                    <div class="detail-card bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-history text-green-600 mr-2"></i>
                            Borrowing History
                        </h3>
                        <div class="space-y-3">
                            ${data.borrowing_history.map(b => `
                                <div class="timeline-item">
                                    <div class="timeline-dot ${b.status === 'released' ? 'bg-blue-500' : 'bg-green-500'}"></div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-semibold text-gray-900">${escapeHtml(b.requester_name)}</span>
                                            <span class="px-2 py-1 rounded text-xs font-semibold ${b.status === 'released' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                                                ${b.status === 'released' ? 'Currently Borrowed' : 'Returned'}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-700 space-y-1">
                                            <div>Released: ${formatDate(b.released_date)} ${b.released_by_name ? `by ${escapeHtml(b.released_by_name)}` : ''}</div>
                                            ${b.returned_date ? `<div>Returned: ${formatDate(b.returned_date)} ${b.returned_by_name ? `to ${escapeHtml(b.returned_by_name)}` : ''}</div>` : ''}
                                            ${b.expected_return_date ? `<div>Expected Return: ${formatDate(b.expected_return_date)}</div>` : ''}
                                            ${b.days_overdue && b.days_overdue > 0 ? `<div class="text-red-600 font-semibold">⚠ ${b.days_overdue} days overdue</div>` : ''}
                                            ${b.return_condition ? `<div>Condition: ${escapeHtml(b.return_condition)}</div>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}

                </div>

                <!-- Right Column: Quick Stats & Activity -->
                <div class="space-y-6">

                    <!-- Depreciation Info -->
                    ${depreciation ? `
                    <div class="detail-card bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-chart-line text-purple-600 mr-2"></i>
                            Valuation
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Original Value:</span>
                                <span class="font-bold text-gray-900">₱${depreciation.original_value.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current Value:</span>
                                <span class="font-bold text-green-600">₱${depreciation.current_value.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t">
                                <span class="text-gray-600">Total Depreciation:</span>
                                <span class="font-bold text-red-600">₱${depreciation.total_depreciation.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Age:</span>
                                <span class="font-semibold">${depreciation.age_years} years</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Depreciation Rate:</span>
                                <span class="font-semibold">${depreciation.depreciation_rate}% per year</span>
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Recent Activity -->
                    ${data.activity_log && data.activity_log.length > 0 ? `
                    <div class="detail-card bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-clock text-gray-600 mr-2"></i>
                            Recent Activity
                        </h3>
                        <div class="space-y-2">
                            ${data.activity_log.slice(0, 5).map(a => `
                                <div class="text-sm py-2 border-b last:border-b-0">
                                    <p class="font-semibold text-gray-900">${escapeHtml(a.action.replace('_', ' '))}</p>
                                    <p class="text-gray-600 text-xs mt-1">${escapeHtml(a.description)}</p>
                                    <p class="text-gray-500 text-xs mt-1">${formatDateTime(a.created_at)}</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}

                </div>

            </div>
        `;

        container.innerHTML = html;
        container.classList.remove('hidden');
        document.getElementById('emptyState').classList.add('hidden');
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

    function getRoleDashboard(role) {
        const dashboards = {
            'admin': 'dashboard.php',
            'custodian': 'custodian_dashboard.php',
            'employee': 'employee_dashboard.php',
            'office': 'office_dashboard.php'
        };
        return dashboards[role.toLowerCase()] || 'index.php';
    }
    </script>
</body>
</html>
<?php
// Helper function to get role-specific dashboard
function getRoleDashboard($role) {
    $dashboards = [
        'admin' => 'dashboard.php',
        'custodian' => 'custodian_dashboard.php',
        'employee' => 'employee_dashboard.php',
        'office' => 'office_dashboard.php'
    ];
    return $dashboards[strtolower($role)] ?? 'index.php';
}
?>
