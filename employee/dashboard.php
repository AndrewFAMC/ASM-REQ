<?php
// Include database configuration
require_once '../config.php';
// Include employee actions
require_once __DIR__ . '/actions/employee_actions.php';

// Enforce authentication and role-based access
if (!isLoggedIn() || !validateSession($pdo)) {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax || (isset($_POST['action']) && $_SERVER['REQUEST_METHOD'] === 'POST')) {
        header('Content-Type: application/json');
        // Send a specific error for session expiry
        echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page and log in again.', 'session_expired' => true]);
    } else {
        // For non-AJAX requests, redirect to login
        header('Location: ../login.php');
    }
    exit; // Stop execution
}
$user = getUserInfo();
$role = strtolower($user['role'] ?? '');
if ($role !== 'employee') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. This page is for Employee users only.']);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied. This page is for Employee users only.';
    }
    exit;
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    try {
        switch ($_POST['action']) {
            case 'request_asset':
                // This is for the general asset request modal
                // The function was restored in staff_actions.php
                $result = requestAsset($pdo, $_POST);
                echo json_encode(['success' => true, 'message' => 'Asset request submitted successfully', 'data' => $result]);
                break;

            case 'return_asset':
                $result = returnAsset($pdo, $_POST);
                echo json_encode(['success' => true, 'message' => 'Asset return initiated successfully', 'data' => $result]);
                break;

            case 'get_borrowable_assets':
                $sql = "SELECT
                            it.id as tag_id, a.asset_name, o.office_name, c.category_name
                        FROM inventory_tags it
                        JOIN assets a ON it.asset_id = a.id
                        JOIN offices o ON it.office_id = o.id
                        JOIN categories c ON a.category_id = c.id
                        WHERE it.is_borrowable = 1
                          AND it.borrowable_quantity > 0
                          AND o.campus_id = ?";
                $assets = fetchAll($pdo, $sql, [$user['campus_id']]);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;

            case 'get_my_assets':
                $assets = getMyAssets($pdo, $_POST['campus_id'] ?? null, $_POST['filters'] ?? []);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;

            case 'get_available_assets':
                $assets = getAvailableAssets($pdo, $_POST['campus_id'] ?? null, $_POST['filters'] ?? []);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;

            case 'get_employee_stats':
                $stats = getEmployeeStats($pdo, $_POST['campus_id'] ?? null);
                echo json_encode(['success' => true, 'data' => $stats]);
                break;

            case 'get_my_activities':
                $activities = getMyActivities($pdo, $_POST['campus_id'] ?? null, $_POST['limit'] ?? 10);
                echo json_encode(['success' => true, 'data' => $activities]);
                break;

            case 'get_asset_requests':
                $requests = getMyAssetRequests($pdo, $_POST['campus_id'] ?? null);
                echo json_encode(['success' => true, 'data' => $requests]);
                break;

            case 'record_borrowing':
                $result = recordBorrowing($pdo, $_POST);
                echo json_encode(['success' => true, 'message' => 'Borrowing recorded successfully', 'data' => $result]);
                break;

            case 'get_borrowings':
                $borrowings = getBorrowings($pdo, $_POST);
                echo json_encode(['success' => true, 'data' => $borrowings]);
                break;

            case 'return_borrowed_asset':
                $result = returnBorrowedAsset($pdo, $_POST);
                echo json_encode(['success' => true, 'message' => 'Borrowed asset returned successfully', 'data' => $result]);
                break;

            case 'get_available_assets_for_borrowing':
                $assets = getAvailableAssetsForBorrowing($pdo, $_POST['campus_id'] ?? null);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;


            case 'search_asset_by_barcode':
                $asset = searchAssetByBarcode($pdo, $_POST['barcode'] ?? '', $_SESSION['campus_id'] ?? null);
                if ($asset) {
                    echo json_encode(['success' => true, 'data' => $asset]);
                } else {
                    // Explicitly handle not found case
                    echo json_encode(['success' => false, 'message' => 'Invalid Barcode: No asset found with this barcode.']);
                }
                break;

            case 'borrow_asset':
                $result = borrowAsset($pdo, $_POST);
                echo json_encode(['success' => true, 'message' => 'Asset borrowed successfully', 'data' => $result]);
                break;

            case 'get_my_borrowings':
                $borrowings = getMyBorrowings($pdo, $_POST['campus_id'] ?? null);
                echo json_encode(['success' => true, 'data' => $borrowings]);
                break;

            case 'get_brands_for_asset':
                $brands = getBrandsForAsset($pdo, $_POST['asset_name'] ?? '');
                echo json_encode(['success' => true, 'data' => $brands]);
                break;
case 'create_asset':
                $result = createAsset($pdo, $_POST);
                echo json_encode(['success' => true, 'message' => 'Asset created successfully', 'data' => $result]);
                break;

            case 'borrow_asset_self':
                $result = borrowAssetForSelf($pdo, $_POST['asset_id']);
                echo json_encode(['success' => true, 'message' => 'Asset borrowed successfully!', 'data' => $result]);
                break;

            case 'get_latest_employee_activity_timestamp':
                $timestamp = getLatestEmployeeActivityTimestamp($pdo);
                echo json_encode(['success' => true, 'data' => $timestamp]);
                break;

            case 'request_specific_asset':
                $result = requestSpecificAsset($pdo,
                    $_POST['asset_id'], $_POST['quantity'] ?? 1,
                    $_POST['purpose'] ?? '',
                    $_POST['expected_return_date'] ?? null);
                echo json_encode(['success' => true, 'message' => 'Asset requested successfully! Your request is pending approval.', 'data' => $result]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit; // Prevent further script execution and HTML rendering on error
    }
    exit;
}





// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - HCC Asset Management</title>
    <script src="../api.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <style>
        .tab-active { border-color: #3b82f6; color: #3b82f6; background-color: #374151; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #4b5563; border-radius: 20px; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Employee Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <a href="#" onclick="showTab('my-assets')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700 tab-active">
                    <i class="fas fa-user-tag w-6"></i><span>My Assets</span>
                </a>
                <a href="#" onclick="showTab('available-assets')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-boxes w-6"></i><span>Available Assets</span>
                </a>
                <a href="my_requests.php" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-paper-plane w-6"></i><span>My Requests</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <h1 class="text-xl font-semibold text-gray-800">Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
                <div class="flex items-center space-x-4">
                    <!-- Request Asset Button -->
                    <a href="request_asset.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-plus-circle mr-2"></i>Request Asset
                    </a>

                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> My Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

                <!-- Released Assets Alert Banner -->
                <div id="released-assets-alert" class="hidden mb-6">
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl"></i>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-yellow-900">You Have Assets to Return</h3>
                                <p class="mt-1 text-sm text-yellow-700">
                                    You currently have <strong id="released-count">0</strong> asset(s) in your possession.
                                    <span id="overdue-text" class="hidden text-red-600 font-bold"></span>
                                </p>
                                <div class="mt-3 flex items-center space-x-4">
                                    <a href="my_requests.php" class="inline-flex items-center text-sm font-medium text-yellow-800 hover:text-yellow-900">
                                        <i class="fas fa-list mr-2"></i>View My Requests
                                    </a>
                                    <span class="text-xs text-yellow-600">
                                        <i class="fas fa-info-circle"></i> Visit custodian office to return
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <!-- Total Assets -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase mb-1">My Assets</p>
                                <p id="stat-total-assets" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-3">
                                <i class="fas fa-box text-blue-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Requests -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase mb-1">Pending</p>
                                <p id="stat-pending" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                            <div class="bg-yellow-100 rounded-full p-3">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Approved -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase mb-1">Approved</p>
                                <p id="stat-approved" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                            <div class="bg-green-100 rounded-full p-3">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Released (In Use) -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-orange-500 cursor-pointer hover:shadow-md transition-shadow" onclick="window.location.href='my_requests.php'">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase mb-1">In Use</p>
                                <p id="stat-released" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                            <div class="bg-orange-100 rounded-full p-3">
                                <i class="fas fa-hand-holding text-orange-600"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Click to view</p>
                    </div>

                    <!-- Available -->
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase mb-1">Available</p>
                                <p id="stat-available" class="text-2xl font-bold text-gray-900">0</p>
                            </div>
                            <div class="bg-purple-100 rounded-full p-3">
                                <i class="fas fa-boxes text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Assets Tab -->
                <div id="my-assets-tab" class="tab-content">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">My Assigned Assets</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                    <tr>
                                        <th class="py-3 px-6 text-left">Asset Name</th>
                                        <th class="py-3 px-6 text-left">Category</th>
                                        <th class="py-3 px-6 text-center">Assigned Qty</th>
                                        <th class="py-3 px-6 text-center">Date Assigned</th>
                                        <th class="py-3 px-6 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="my-assets-tbody" class="text-gray-600 text-sm font-light">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                            <div id="my-assets-loading" class="text-center py-4">Loading my assets...</div>
                        </div>
                    </div>
                </div>

                <!-- Available Assets Tab -->
                <div id="available-assets-tab" class="tab-content hidden">
                     <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Available Assets for Request</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                    <tr>
                                        <th class="py-3 px-6 text-left">Asset Name</th>
                                        <th class="py-3 px-6 text-left">Category</th>
                                        <th class="py-3 px-6 text-center">Available Qty</th>
                                        <th class="py-3 px-6 text-left">Location</th>
                                        <th class="py-3 px-6 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="available-assets-tbody" class="text-gray-600 text-sm font-light">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                             <div id="available-assets-loading" class="text-center py-4">Loading available assets...</div>
                        </div>
                    </div>
                </div>

                <!-- My Requests Tab -->
                <div id="my-requests-tab" class="tab-content hidden">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">My Asset Requests</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                    <tr>
                                        <th class="py-3 px-6 text-left">Asset Name</th>
                                        <th class="py-3 px-6 text-center">Request Date</th>
                                        <th class="py-3 px-6 text-center">Status</th>
                                        <th class="py-3 px-6 text-left">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="my-requests-tbody" class="text-gray-600 text-sm font-light">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                            <div id="my-requests-loading" class="text-center py-4">Loading my requests...</div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

<!-- Return Asset Modal -->
<div id="returnAssetModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-semibold mb-4">Return Asset</h3>
        <form id="returnAssetForm">
            <input type="hidden" id="return_asset_id" name="asset_id">
            <div class="mb-4">
                <label for="return_quantity" class="block text-sm font-medium text-gray-700">Quantity to Return</label>
                <input type="number" id="return_quantity" name="return_quantity" min="1" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div class="mb-4">
                <label for="return_notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                <textarea id="return_notes" name="return_notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeReturnModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Submit Return</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    window.campusId = <?= json_encode($user['campus_id']) ?>;

    // --- Tab Navigation ---
    window.showTab = function(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        document.getElementById(tabId + '-tab').classList.remove('hidden');

        document.querySelectorAll('.tab-item').forEach(item => item.classList.remove('tab-active'));
        document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('tab-active');

        // Load data for the activated tab
        if (tabId === 'my-assets') loadMyAssets();
        if (tabId === 'available-assets') loadAvailableAssets();
        if (tabId === 'my-requests') loadMyRequests();
    }

    // --- Data Loading Functions ---
    async function loadMyAssets() {
        const tbody = document.getElementById('my-assets-tbody');
        const loading = document.getElementById('my-assets-loading');
        loading.style.display = 'block';
        tbody.innerHTML = '';

        const res = await apiRequest('dashboard.php', 'get_my_assets');
        loading.style.display = 'none';

        if (res.success && res.data) {
            // Update stats
            document.getElementById('stat-total-assets').textContent = res.data.length;

            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">You have no assets assigned to you.</td></tr>';
                return;
            }
            res.data.forEach(asset => {
                const row = `
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left">${asset.asset_name}</td>
                        <td class="py-3 px-6 text-left">${asset.category_name}</td>
                        <td class="py-3 px-6 text-center">${asset.assigned_quantity}</td>
                        <td class="py-3 px-6 text-center">${new Date(asset.assignment_date).toLocaleDateString()}</td>
                        <td class="py-3 px-6 text-center">
                            <button onclick="openReturnModal(${asset.id}, ${asset.assigned_quantity})" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded">
                                Return
                            </button>
                        </td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        }
    }

    async function loadAvailableAssets() {
        const tbody = document.getElementById('available-assets-tbody');
        const loading = document.getElementById('available-assets-loading');
        loading.style.display = 'block';
        tbody.innerHTML = '';

        const res = await apiRequest('dashboard.php', 'get_borrowable_assets');
        loading.style.display = 'none';

        if (res.success && res.data) {
            // Update stats
            document.getElementById('stat-available').textContent = res.data.length;

            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No available assets to request at the moment.</td></tr>';
                return;
            }
            res.data.forEach(asset => {
                const row = `
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left">${asset.asset_name}</td>
                        <td class="py-3 px-6 text-left">${asset.category_name}</td>
                        <td class="py-3 px-6 text-center">${asset.borrowable_quantity}</td>
                        <td class="py-3 px-6 text-left">${asset.office_name}</td>
                        <td class="py-3 px-6 text-center">
                            <button onclick="requestSpecificAsset(${asset.tag_id})" class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold py-1 px-3 rounded">
                                Request
                            </button>
                        </td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        }
    }

    async function loadMyRequests() {
        const tbody = document.getElementById('my-requests-tbody');
        const loading = document.getElementById('my-requests-loading');
        loading.style.display = 'block';
        tbody.innerHTML = '';

        const res = await apiRequest('dashboard.php', 'get_asset_requests');
        loading.style.display = 'none';

        if (res.success && res.data) {
            // Update stats
            const pending = res.data.filter(r => r.status === 'pending').length;
            const approved = res.data.filter(r => r.status === 'approved' || r.status === 'custodian_review').length;
            const released = res.data.filter(r => r.status === 'released').length;

            // Count overdue
            let overdueCount = 0;
            res.data.filter(r => r.status === 'released').forEach(request => {
                if (new Date(request.expected_return_date) < new Date()) {
                    overdueCount++;
                }
            });

            document.getElementById('stat-pending').textContent = pending;
            document.getElementById('stat-approved').textContent = approved;
            document.getElementById('stat-released').textContent = released;

            // Show/hide released assets alert banner
            const alertBanner = document.getElementById('released-assets-alert');
            const releasedCountEl = document.getElementById('released-count');
            const overdueTextEl = document.getElementById('overdue-text');

            if (released > 0) {
                alertBanner.classList.remove('hidden');
                releasedCountEl.textContent = released;

                if (overdueCount > 0) {
                    overdueTextEl.classList.remove('hidden');
                    overdueTextEl.textContent = overdueCount + ' of them are OVERDUE!';
                    alertBanner.querySelector('.border-yellow-500').classList.remove('border-yellow-500');
                    alertBanner.querySelector('.from-yellow-50').classList.add('border-red-500', 'from-red-50');
                } else {
                    overdueTextEl.classList.add('hidden');
                }
            } else {
                alertBanner.classList.add('hidden');
            }

            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">You have not made any asset requests.</td></tr>';
                return;
            }
            res.data.forEach(request => {
                const row = `
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left">${request.asset_name}</td>
                        <td class="py-3 px-6 text-center">${new Date(request.request_date).toLocaleDateString()}</td>
                        <td class="py-3 px-6 text-center">
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full ${getStatusClass(request.status)}">
                                ${request.status}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-left">${request.remarks || 'N/A'}</td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        }
    }

    // --- Modals ---
    const returnModal = document.getElementById('returnAssetModal');
    window.openReturnModal = function(assetId, maxQuantity) {
        document.getElementById('return_asset_id').value = assetId;
        const quantityInput = document.getElementById('return_quantity');
        quantityInput.value = 1;
        quantityInput.max = maxQuantity;
        returnModal.classList.remove('hidden');
    }

    window.closeReturnModal = function() {
        returnModal.classList.add('hidden');
        document.getElementById('returnAssetForm').reset();
    }

    document.getElementById('returnAssetForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await apiRequest('dashboard.php', 'return_asset', data);
        if (res.success) {
            Swal.fire('Success', res.message, 'success');
            closeReturnModal();
            loadMyAssets();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });

    window.requestSpecificAsset = async function(assetId) {
        const { value: formValues } = await Swal.fire({
            title: 'Request Asset',
            html:
                '<input id="swal-purpose" class="swal2-input" placeholder="Purpose of use">' +
                '<input id="swal-return-date" type="date" class="swal2-input">',
            focusConfirm: false,
            preConfirm: () => {
                return [
                    document.getElementById('swal-purpose').value,
                    document.getElementById('swal-return-date').value
                ]
            }
        });

        if (formValues) {
            const [purpose, returnDate] = formValues;
            if (!purpose || !returnDate) {
                Swal.fire('Error', 'Purpose and return date are required.', 'error');
                return;
            }
            const res = await apiRequest('dashboard.php', 'request_specific_asset', {
                asset_id: assetId,
                purpose: purpose,
                expected_return_date: returnDate
            });
            if (res.success) {
                Swal.fire('Success', res.message, 'success');
                showTab('my-requests');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    }

    function getStatusClass(status) {
        if (!status) return 'text-gray-600 bg-gray-200';
        switch (status.toLowerCase()) {
            case 'approved':
            case 'released':
            case 'active': return 'text-green-600 bg-green-200';
            case 'pending': return 'text-yellow-800 bg-yellow-200';
            case 'rejected':
            case 'declined': return 'text-red-600 bg-red-200';
            default: return 'text-gray-600 bg-gray-200';
        }
    }

    // Initial load
    showTab('my-assets');
});
</script>

</body>
</html>
