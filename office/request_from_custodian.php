<?php
/**
 * Office Request from Custodian Page
 * Allows office users (department heads) to request assets from custodian for their office
 */

require_once '../config.php';

// Generate CSRF token
generateCSRFToken();

// Enforce authentication and role-based access
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$role = strtolower($user['role'] ?? '');

if ($role !== 'office') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This page is for Office users only.';
    exit;
}

$officeId = $user['office_id'] ?? 0;
$campusId = $user['campus_id'] ?? 0;

// Get office details
$office = fetchOne($pdo, "SELECT * FROM offices WHERE id = ?", [$officeId]);
if (!$office) {
    die("Error: Office not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        switch ($_POST['action']) {
            case 'get_custodian_assets':
                // Get assets available from custodian (central inventory)
                $sql = "SELECT
                            a.id,
                            a.asset_name,
                            a.serial_number,
                            a.status,
                            a.quantity,
                            c.category_name,
                            cam.campus_name,
                            COALESCE(a.location, 'Central Storage') as location
                        FROM assets a
                        JOIN categories c ON a.category_id = c.id
                        JOIN campuses cam ON a.campus_id = cam.id
                        WHERE a.campus_id = ?
                        AND a.status IN ('Available', 'In Storage')
                        AND a.quantity > 0
                        AND (a.office_id IS NULL OR a.office_id = 0)
                        ORDER BY a.asset_name ASC";

                $assets = fetchAll($pdo, $sql, [$campusId]);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;

            case 'submit_request':
                $pdo->beginTransaction();

                $assetId = (int)$_POST['asset_id'];
                $quantity = (int)$_POST['quantity'];
                $purpose = trim($_POST['purpose']);

                if (empty($assetId) || $quantity <= 0 || empty($purpose)) {
                    throw new Exception("Please fill all required fields.");
                }

                // Verify asset exists and has enough quantity
                $asset = fetchOne($pdo,
                    "SELECT id, asset_name, quantity FROM assets WHERE id = ? AND campus_id = ?",
                    [$assetId, $campusId]
                );

                if (!$asset) {
                    throw new Exception("Asset not found.");
                }

                if ($asset['quantity'] < $quantity) {
                    throw new Exception("Requested quantity ({$quantity}) exceeds available quantity ({$asset['quantity']}).");
                }

                // Create the request for PERMANENT TRANSFER (no return expected)
                $sql = "INSERT INTO asset_requests
                        (requester_id, asset_id, quantity, purpose,
                         status, campus_id, request_source, requester_office_id, request_date)
                        VALUES (?, ?, ?, ?, 'pending', ?, 'custodian', ?, NOW())";

                $params = [
                    $user['id'],
                    $assetId,
                    $quantity,
                    $purpose,
                    $campusId,
                    $officeId
                ];

                executeQuery($pdo, $sql, $params);
                $requestId = $pdo->lastInsertId();

                // Log activity
                logActivity($pdo, $assetId, 'REQUEST_CREATED',
                    "Office user {$user['full_name']} from {$office['office_name']} requested {$quantity} unit(s) for permanent transfer from custodian. Purpose: {$purpose}");

                // TODO: Send notification to custodian

                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Transfer request submitted successfully! The custodian and admin will review your request.',
                    'request_id' => $requestId
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request from Custodian - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../api.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <style>
        .asset-card {
            transition: all 0.3s ease;
        }
        .asset-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-gray-300">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Dept Head</span>
            </div>
            <nav class="mt-4 space-y-2 px-4">
                <a href="office_dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <?php
                // Check if user is a department approver
                $checkApprover = $pdo->prepare("SELECT COUNT(*) as is_approver FROM department_approvers WHERE approver_user_id = ? AND is_active = TRUE");
                $checkApprover->execute([$user['id']]);
                $isApprover = $checkApprover->fetch()['is_approver'] > 0;

                if ($isApprover):
                ?>
                <a href="approve_requests.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                </a>

                <a href="release_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                </a>

                <a href="return_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-undo-alt w-6"></i>
                    <span>Accept Returns</span>
                </a>
                <?php endif; ?>

                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-history w-6"></i>
                    <span>Approval History</span>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <a href="request_from_custodian.php" class="flex items-center px-4 py-3 text-white bg-gray-700 rounded-md">
                    <i class="fas fa-paper-plane w-6"></i>
                    <span>Request from Custodian</span>
                </a>

                <a href="my_requests.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-clipboard-list w-6"></i>
                    <span>My Requests</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex items-center justify-between p-4 bg-white border-b">
                <div>
                    <h1 class="text-xl font-semibold">Request Assets from Custodian</h1>
                    <p class="text-sm text-gray-600">Office: <?= htmlspecialchars($office['office_name']) ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-info-circle text-blue-500 text-2xl mr-3"></i>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Request Asset Transfer</h2>
                            <p class="text-gray-600">Request assets from the central custodian inventory for <strong>permanent transfer</strong> to your office. Once approved, the asset will be assigned to your office (no return required).</p>
                            <p class="text-sm text-gray-500 mt-2">
                                <i class="fas fa-arrow-right mr-1"></i> <strong>Approval Process:</strong> Custodian Review → Admin Approval → Asset Transferred
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Available Assets Section -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-semibold text-gray-800">Available Assets from Custodian</h2>
                        <button onclick="loadAssets()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>

                    <!-- Search and Filter -->
                    <div class="mb-4">
                        <input type="text" id="searchAssets" placeholder="Search assets..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            oninput="filterAssets()">
                    </div>

                    <div id="assets-loading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">Loading available assets...</p>
                    </div>

                    <div id="assets-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 hidden">
                        <!-- Assets will be loaded here -->
                    </div>

                    <div id="no-assets" class="text-center py-8 hidden">
                        <i class="fas fa-box-open text-4xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">No assets available from custodian at this time.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Request Asset Modal -->
    <div id="requestModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-semibold">Request Asset</h3>
                <button onclick="closeRequestModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <form id="requestForm">
                <input type="hidden" name="asset_id" id="req_asset_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Asset</label>
                    <p id="req_asset_name" class="text-lg font-semibold text-gray-900"></p>
                    <p class="text-sm text-gray-500">Available: <span id="req_available_qty"></span></p>
                </div>

                <div class="mb-4">
                    <label for="req_quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                    <input type="number" name="quantity" id="req_quantity" min="1" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="req_purpose" class="block text-sm font-medium text-gray-700 mb-2">Purpose / Justification *</label>
                    <textarea name="purpose" id="req_purpose" rows="4" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Explain why your office needs this asset (e.g., for department use, new equipment needed, etc.)"></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle"></i> This will be a permanent transfer to your office.
                    </p>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeRequestModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        window.allAssets = [];

        loadAssets();
    });

    async function loadAssets() {
        const loading = document.getElementById('assets-loading');
        const grid = document.getElementById('assets-grid');
        const noAssets = document.getElementById('no-assets');

        loading.classList.remove('hidden');
        grid.classList.add('hidden');
        noAssets.classList.add('hidden');

        try {
            const res = await apiRequest('request_from_custodian.php', 'get_custodian_assets');

            if (res.success) {
                window.allAssets = res.data;
                displayAssets(res.data);
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Failed to load assets. Please try again.', 'error');
        } finally {
            loading.classList.add('hidden');
        }
    }

    function displayAssets(assets) {
        const grid = document.getElementById('assets-grid');
        const noAssets = document.getElementById('no-assets');

        if (assets.length === 0) {
            noAssets.classList.remove('hidden');
            return;
        }

        grid.innerHTML = '';
        grid.classList.remove('hidden');

        assets.forEach(asset => {
            const card = document.createElement('div');
            card.className = 'asset-card bg-gray-50 border border-gray-200 rounded-lg p-4';
            card.innerHTML = `
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">${asset.asset_name}</h3>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full ${getStatusClass(asset.status)}">
                        ${asset.status}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-2">
                    <i class="fas fa-tag mr-1"></i>${asset.category_name}
                </p>
                <p class="text-sm text-gray-600 mb-2">
                    <i class="fas fa-map-marker-alt mr-1"></i>${asset.location}
                </p>
                ${asset.serial_number ? `<p class="text-xs text-gray-500 mb-2 font-mono">SN: ${asset.serial_number}</p>` : ''}
                <div class="flex justify-between items-center mt-4">
                    <span class="text-sm font-medium text-gray-700">
                        Qty: <span class="text-blue-600 font-bold">${asset.quantity}</span>
                    </span>
                    <button onclick="openRequestModal(${asset.id}, '${asset.asset_name.replace(/'/g, "\\'")}', ${asset.quantity})"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                        <i class="fas fa-paper-plane mr-1"></i>Request
                    </button>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    function filterAssets() {
        const searchTerm = document.getElementById('searchAssets').value.toLowerCase();
        const filtered = window.allAssets.filter(asset =>
            asset.asset_name.toLowerCase().includes(searchTerm) ||
            asset.category_name.toLowerCase().includes(searchTerm) ||
            (asset.serial_number && asset.serial_number.toLowerCase().includes(searchTerm))
        );
        displayAssets(filtered);
    }

    function getStatusClass(status) {
        switch (status.toLowerCase()) {
            case 'available':
                return 'text-green-600 bg-green-200';
            case 'in storage':
                return 'text-blue-600 bg-blue-200';
            default:
                return 'text-gray-600 bg-gray-200';
        }
    }

    const requestModal = document.getElementById('requestModal');
    const requestForm = document.getElementById('requestForm');

    function openRequestModal(assetId, assetName, availableQty) {
        document.getElementById('req_asset_id').value = assetId;
        document.getElementById('req_asset_name').innerText = assetName;
        document.getElementById('req_available_qty').innerText = availableQty;
        document.getElementById('req_quantity').max = availableQty;
        document.getElementById('req_quantity').value = 1;
        requestForm.reset();
        document.getElementById('req_asset_id').value = assetId; // Reset clears it
        requestModal.classList.remove('hidden');
    }

    function closeRequestModal() {
        requestModal.classList.add('hidden');
        requestForm.reset();
    }

    requestForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const quantity = parseInt(data.quantity);
        const maxQty = parseInt(document.getElementById('req_quantity').max);

        if (quantity > maxQty) {
            Swal.fire('Error', `Requested quantity exceeds available quantity (${maxQty})`, 'error');
            return;
        }

        try {
            const res = await apiRequest('request_from_custodian.php', 'submit_request', data);

            if (res.success) {
                await Swal.fire('Success!', res.message, 'success');
                closeRequestModal();
                loadAssets(); // Refresh the list
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Failed to submit request. Please try again.', 'error');
        }
    });
    </script>
</body>
</html>
