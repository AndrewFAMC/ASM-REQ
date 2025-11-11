<?php
require_once '../config.php';

// Generate CSRF token
generateCSRFToken();

// Enforce authentication and role-based access
if (!isLoggedIn() || !validateSession($pdo)) {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Your session has expired. Please refresh the page and log in again.', 'session_expired' => true]);
    } else {
        header('Location: ../login.php');
    }
    exit; // Stop execution
}
$user = getUserInfo();
$role = strtolower($user['role'] ?? '');
if ($role !== 'office') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. This page is for Office users only.']);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied. This page is for Office users only.';
    }
    exit;
}

$officeId = $user['office_id'] ?? 0;

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        switch ($_POST['action']) {
            case 'get_pending_assets':
                $sql = "SELECT it.id as tag_id, it.tag_number, it.inventory_date, a.id as asset_id, a.asset_name, u.full_name as assigned_by
                        FROM inventory_tags it
                        JOIN assets a ON it.asset_id = a.id
                        JOIN users u ON it.assigned_by_custodian_id = u.id
                        WHERE it.office_id = ? AND it.status = 'Pending Verification'";
                $assets = fetchAll($pdo, $sql, [$user['office_id']]);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;

            case 'get_verified_assets':
                $sql = "SELECT
                            it.id,
                            it.tag_number,
                            it.status,
                            it.verified_at,
                            it.borrowable_quantity,
                            a.asset_name,
                            u_verifier.full_name as verified_by
                        FROM inventory_tags it
                        JOIN assets a ON it.asset_id = a.id
                        LEFT JOIN users u_verifier ON it.verified_by_user_id = u_verifier.id
                        WHERE it.office_id = ? AND it.status NOT IN ('Pending Verification', 'Declined')
                        ORDER BY it.verified_at DESC";
                $assets = fetchAll($pdo, $sql, [$user['office_id']]);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;

            case 'get_tag_details_for_print':
                $tagId = $_POST['tag_id'];
                // Security check: Ensure the tag belongs to the user's office
                $sql = "SELECT
                            it.*,
                            a.asset_name,
                            a.serial_number,
                            o.office_name,
                            c.campus_name
                        FROM inventory_tags it
                        JOIN assets a ON it.asset_id = a.id
                        JOIN offices o ON it.office_id = o.id
                        JOIN campuses c ON o.campus_id = c.id
                        WHERE it.id = ? AND it.office_id = ?";
                $tagDetails = fetchOne($pdo, $sql, [$tagId, $user['office_id']]);

                if (!$tagDetails) {
                    throw new Exception("Inventory tag not found or access denied.");
                }
                $tagDetails['qr_code_url'] = "http://{$_SERVER['HTTP_HOST']}/AMS%20REQ/asset_lookup.php?tag={$tagDetails['tag_number']}";
                echo json_encode(['success' => true, 'data' => $tagDetails]);
                break;

            case 'verify_asset_receipt':
                $pdo->beginTransaction();
                $tagId = $_POST['tag_id'];

                // Get asset_id from tag
                $tag = fetchOne($pdo, "SELECT asset_id FROM inventory_tags WHERE id = ? AND office_id = ?", [$tagId, $user['office_id']]);
                if (!$tag) throw new Exception("Tag not found or access denied.");

                // Update tag status to Active
                executeQuery($pdo, "UPDATE inventory_tags SET status = 'Active', verified_by_user_id = ?, verified_at = NOW() WHERE id = ?", [$user['id'], $tagId]);
                // Update main asset status to Active
                executeQuery($pdo, "UPDATE assets SET status = 'Active' WHERE id = ?", [$tag['asset_id']]);

                logActivity($pdo, $tag['asset_id'], 'ASSET_VERIFIED', "Office user {$user['full_name']} verified receipt of the asset.");
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Asset receipt verified successfully!']);
                break;

            case 'decline_asset_receipt':
                $pdo->beginTransaction();
                $tagId = $_POST['tag_id'];
                // Sanitize the reason to prevent XSS
                $reason = htmlspecialchars($_POST['reason'] ?? 'No reason provided.', ENT_QUOTES, 'UTF-8');
                $tag = fetchOne($pdo, "SELECT asset_id FROM inventory_tags WHERE id = ? AND office_id = ?", [$tagId, $user['office_id']]);
                if (!$tag) throw new Exception("Tag not found or access denied.");

                executeQuery($pdo, "UPDATE inventory_tags SET status = 'Declined', remarks = CONCAT(IFNULL(remarks, ''), '\nDeclined: ', ?) WHERE id = ?", [$reason, $tagId]);
                executeQuery($pdo, "UPDATE assets SET status = 'Inactive' WHERE id = ?", [$tag['asset_id']]); // Or another appropriate status
                logActivity($pdo, $tag['asset_id'], 'ASSET_DECLINED', "Office user declined receipt of the asset. Reason: " . $reason);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Asset receipt declined successfully.']);
                break;

            case 'update_asset_status':
                $pdo->beginTransaction();
                $tagId = $_POST['tag_id'];
                $newStatus = $_POST['status'];
                $remarks = $_POST['remarks'] ?? '';

                $tag = fetchOne($pdo, "SELECT asset_id, status as old_status FROM inventory_tags WHERE id = ? AND office_id = ?", [$tagId, $user['office_id']]);
                if (!$tag) throw new Exception("Asset not found or access denied.");

                executeQuery($pdo, "UPDATE inventory_tags SET status = ?, remarks = CONCAT(IFNULL(remarks, ''), '\nStatus Update: ', ?) WHERE id = ?", [$newStatus, $remarks, $tagId]);

                logActivity($pdo, $tag['asset_id'], 'STATUS_UPDATED', "Office user {$user['full_name']} updated asset status from '{$tag['old_status']}' to '{$newStatus}'. Remarks: {$remarks}");
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Asset status updated successfully.']);
                break;

            case 'make_asset_borrowable':
                $pdo->beginTransaction();
                $tagId = $_POST['tag_id'];
                $borrowableQuantity = (int)($_POST['quantity'] ?? 0);
                $isBorrowable = $borrowableQuantity > 0 ? 1 : 0;

                $tag = fetchOne($pdo, "SELECT asset_id FROM inventory_tags WHERE id = ? AND office_id = ?", [$tagId, $user['office_id']]);
                if (!$tag) throw new Exception("Asset not found or access denied.");

                executeQuery($pdo, "UPDATE inventory_tags SET is_borrowable = ?, borrowable_quantity = ? WHERE id = ?", [$isBorrowable, $borrowableQuantity, $tagId]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Asset borrowable status updated.']);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get initial data for page load
try {
    $office = fetchOne($pdo, "SELECT * FROM offices WHERE id = ?", [$officeId]);
    if (!$office && $officeId > 0) { // Only fail if a specific, non-existent office ID was provided.
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $errorMessage = "Error: Office #{$officeId} not found for this user account. Please contact an administrator.";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        } else {
            die($errorMessage);
        }
    }
} catch (Exception $e) {
    // This part is fine, as a database connection error should stop everything.
    // For consistency, we could also check for AJAX here, but a DB error is more critical.
    die("Database Error loading initial data: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../api.js"></script>
    <title>Office Dashboard - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <style>
        @page {
            size: landscape;
            margin: 0;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #printableTagModal, #printable-tag-content, #tag-to-print, #tag-to-print * {
                visibility: visible;
            }
            #printableTagModal {
                position: absolute; left: 0; top: 0; width: 100%; height: 100%;
                display: flex; align-items: flex-start; justify-content: flex-start;
                background: white; border: none; box-shadow: none; padding: 0; margin: 0;
            }
            #printable-tag-content {
                width: 150mm; height: 100mm; margin: 0; padding: 0; box-shadow: none;
                border: none; border-radius: 0;
            }
            #tag-to-print {
                width: 150mm; height: 100mm; margin: 0; padding: 10mm;
                border: 2px solid black !important;
            }
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
                <a href="office_dashboard.php" class="flex items-center px-4 py-3 text-white bg-gray-700 rounded-md">
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
                    <?php
                    // Get pending approval count for dual-flow system
                    // Office users approve requests where request_source='office' and target_office_id matches their office
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM asset_requests ar
                        WHERE ar.status = 'office_review'
                        AND ar.request_source = 'office'
                        AND ar.target_office_id = (SELECT office_id FROM department_approvers WHERE approver_user_id = ? LIMIT 1)
                        AND ar.campus_id = ?
                    ");
                    $stmt->execute([$user['id'], $user['campus_id']]);
                    $pendingCount = (int)$stmt->fetch()['count'];

                    if ($pendingCount > 0):
                    ?>
                        <span class="ml-auto bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="release_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                    <?php
                    // Get approved requests ready for release
                    $releaseStmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM asset_requests ar
                        WHERE ar.status = 'approved'
                        AND ar.request_source = 'office'
                        AND ar.target_office_id = (SELECT office_id FROM department_approvers WHERE approver_user_id = ? LIMIT 1)
                        AND ar.campus_id = ?
                    ");
                    $releaseStmt->execute([$user['id'], $user['campus_id']]);
                    $releaseCount = (int)$releaseStmt->fetch()['count'];

                    if ($releaseCount > 0):
                    ?>
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $releaseCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="return_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-undo-alt w-6"></i>
                    <span>Accept Returns</span>
                    <?php
                    // Get released assets pending return
                    $returnStmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM asset_requests ar
                        WHERE ar.status = 'released'
                        AND ar.request_source = 'office'
                        AND ar.target_office_id = (SELECT office_id FROM department_approvers WHERE approver_user_id = ? LIMIT 1)
                        AND ar.campus_id = ?
                    ");
                    $returnStmt->execute([$user['id'], $user['campus_id']]);
                    $returnCount = (int)$returnStmt->fetch()['count'];

                    if ($returnCount > 0):
                    ?>
                        <span class="ml-auto bg-blue-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $returnCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-history w-6"></i>
                    <span>Approval History</span>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <a href="request_from_custodian.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
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
                <h1 class="text-xl font-semibold">Welcome, <?= htmlspecialchars($office['office_name']) ?></h1>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Pending Asset Verifications</h2>
                    <p class="text-gray-600 mb-6">The following assets have been assigned to your office and are awaiting your verification.</p>
                    <div id="pending-assets-list" class="space-y-4">
                        <!-- Pending assets will be loaded here -->
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Verified Assets in Office</h2>
                    <p class="text-gray-600 mb-6">This is a list of all assets that your office has confirmed receipt of.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <tr>
                                    <th class="py-3 px-6 text-left">Asset Name</th>
                                    <th class="py-3 px-6 text-left">Tag Number</th>
                                    <th class="py-3 px-6 text-center">Status</th>
                                    <th class="py-3 px-6 text-center">Borrowable Qty</th>
                                    <th class="py-3 px-6 text-left">Verified By</th>
                                    <th class="py-3 px-6 text-left">Date Verified</th>
                                    <th class="py-3 px-6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="verified-assets-tbody" class="text-gray-600 text-sm font-light">
                                <!-- Verified assets will be loaded here -->
                            </tbody>
                        </table>
                        <div id="verified-assets-loading" class="text-center py-4">Loading verified assets...</div>
                    </div>
                </div>
            </main>
        </div>
    </div>

<!-- Printable Tag Modal -->
<div id="printableTagModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl" id="printable-tag-content">
        <!-- Header -->
        <div class="p-4 border-b flex justify-between items-center print:hidden">
            <h3 class="text-lg font-semibold">Inventory Tag</h3>
            <div>
                <button onclick="printTag()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-sm">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <button onclick="closePrintableTagModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg text-sm">
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
                        <td class="border border-black p-1" id="pt_unit_price"></td>
                    </tr>
                    <!-- Row 8: Remarks and QR/Barcode -->
                    <tr>
                        <td class="border border-black p-1 bg-gray-100 font-bold">Remarks</td>
                        <td class="border border-black p-1" id="pt_remarks"></td>
                        <td class="border border-black p-1" colspan="2" rowspan="2">
                            <!-- QR Code and Barcode -->
                            <div class="flex flex-col items-center justify-center h-full">
                                <div id="pt_qrcode" class="mb-1"></div>
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

<!-- Update Status Modal -->
<div id="updateStatusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-semibold">Update Asset Status</h3>
            <button onclick="closeUpdateStatusModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form id="updateStatusForm">
            <input type="hidden" name="tag_id" id="us_tag_id">
            <div class="space-y-4">
                <div>
                    <label for="us_status" class="block text-sm font-medium text-gray-700">New Status</label>
                    <select name="status" id="us_status" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="Active">Active / In Use</option>
                        <option value="In Storage">In Storage</option>
                        <option value="For Repair">For Repair</option>
                        <option value="Missing">Missing</option>
                    </select>
                </div>
                <div>
                    <label for="us_remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                    <textarea name="remarks" id="us_remarks" rows="3" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Provide details about the status change (e.g., 'Moved to storage room', 'Screen is broken')."></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeUpdateStatusModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    // Office dashboard doesn't need a global campusId, but we define it as null for consistency.
    window.campusId = null; 

    async function loadPendingAssets() {
        const listContainer = document.getElementById('pending-assets-list');
        listContainer.innerHTML = '<p>Loading pending assets...</p>';

        const res = await apiRequest('office_dashboard.php', 'get_pending_assets');

        if (res.success) {
            listContainer.innerHTML = '';
            if (res.data.length > 0) {
                res.data.forEach(asset => {
                    const item = document.createElement('div');
                    item.className = 'p-4 bg-gray-50 border rounded-lg flex justify-between items-center';
                    item.innerHTML = `
                        <div>
                            <p class="font-bold text-lg">${asset.asset_name}</p>
                            <p class="text-sm text-gray-600">Tag: <span class="font-mono">${asset.tag_number}</span></p>
                            <p class="text-xs text-gray-500">Assigned by: ${asset.assigned_by} on ${new Date(asset.inventory_date).toLocaleDateString()}</p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="verifyAsset(${asset.tag_id})" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm">Verify Receipt</button>
                            <button onclick="declineAsset(${asset.tag_id})" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-sm">Decline</button>
                        </div>
                    `;
                    listContainer.appendChild(item);
                });
            } else {
                listContainer.innerHTML = '<p class="text-center text-gray-500">No assets are currently pending verification.</p>';
            }
        } else {
            listContainer.innerHTML = `<p class="text-red-500">Error: ${res.message}</p>`;
        }
    }

    async function loadVerifiedAssets() {
        const tableBody = document.getElementById('verified-assets-tbody');
        const loading = document.getElementById('verified-assets-loading');
        loading.style.display = 'block';
        tableBody.innerHTML = '';

        const res = await apiRequest('office_dashboard.php', 'get_verified_assets');

        if (res.success) {
            tableBody.innerHTML = '';
            if (res.data.length > 0) {
                res.data.forEach(asset => {
                    const row = document.createElement('tr');
                    row.className = 'border-b border-gray-200 hover:bg-gray-100';
                    row.innerHTML = `
                        <td class="py-3 px-6 text-left whitespace-nowrap">${asset.asset_name}</td>
                        <td class="py-3 px-6 text-left"><span class="font-mono">${asset.tag_number}</span></td>
                        <td class="py-3 px-6 text-center"><span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full ${getStatusClass(asset.status)}">${asset.status}</span></td>
                        <td class="py-3 px-6 text-center">${asset.borrowable_quantity || 0}</td>
                        <td class="py-3 px-6 text-left">${asset.verified_by || 'N/A'}</td>
                        <td class="py-3 px-6 text-left">${new Date(asset.verified_at).toLocaleString()}</td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center">
                                <button onclick="setBorrowable(${asset.id}, ${asset.borrowable_quantity || 0})" class="w-4 mr-2 transform hover:text-green-500 hover:scale-110" title="Set Borrowable Quantity"><i class="fas fa-hand-holding"></i></button>
                                <button onclick="openUpdateStatusModal(${asset.id}, '${asset.status}')" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110" title="Update Status"><i class="fas fa-edit"></i></button>
                                <button onclick="openPrintableTagModal(${asset.id})" class="w-4 mr-2 transform hover:text-blue-500 hover:scale-110" title="Print Tag"><i class="fas fa-print"></i></button>
                            </div>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">No verified assets found in your office.</td></tr>';
            }
        } else {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">Error: ${res.message}</td></tr>`;
        }
        loading.style.display = 'none';
    }

    window.verifyAsset = async (tagId) => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: "You are about to confirm the receipt of this asset.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, verify it!',
        });

        if (result.isConfirmed) {
            try {
                const res = await apiRequest('office_dashboard.php', 'verify_asset_receipt', { tag_id: tagId });
                if (res.success) {
                    await Swal.fire('Verified!', res.message, 'success');
                    loadPendingAssets();
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            } catch (error) {
                Swal.fire('Request Failed!', 'An error occurred while verifying the asset.', 'error');
            }
        }
    };

    window.declineAsset = async (tagId) => {
        const { value: reason } = await Swal.fire({
            title: 'Decline Asset Receipt',
            input: 'textarea',
            inputLabel: 'Reason for declining',
            inputPlaceholder: 'Please provide a reason why you are declining this asset (e.g., wrong item, damaged)...',
            inputAttributes: { 'aria-label': 'Type your reason here' },
            showCancelButton: true,
            confirmButtonText: 'Decline',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to provide a reason!';
                }
            },
        });

        if (reason) {
            try {
                const res = await apiRequest('office_dashboard.php', 'decline_asset_receipt', { tag_id: tagId, reason: reason });
                if (res.success) {
                    await Swal.fire('Declined!', 'The asset assignment has been declined.', 'success');
                    loadPendingAssets();
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            } catch (error) {
                Swal.fire('Request Failed!', 'An error occurred while declining the asset.', 'error');
            }
        }
    };

    // --- Printable Tag Modal ---
    const printableTagModal = document.getElementById('printableTagModal');

    window.openPrintableTagModal = async function(tagId) {
        const res = await apiRequest('office_dashboard.php', 'get_tag_details_for_print', { tag_id: tagId });
        if (res.success) {
            const tag = res.data;

            // Populate all tag fields
            document.getElementById('pt_tag_number').innerText = tag.tag_number || 'N/A';
            document.getElementById('pt_inventory_date').innerText = tag.inventory_date ? new Date(tag.inventory_date).toLocaleDateString() : 'N/A';
            document.getElementById('pt_article').innerText = tag.article || 'N/A';
            document.getElementById('pt_serial_number').innerText = tag.serial_number || 'N/A';
            document.getElementById('pt_quantity').innerText = tag.quantity || '1';
            document.getElementById('pt_size').innerText = tag.size || 'N/A';
            document.getElementById('pt_location_row').innerText = tag.location_row || 'N/A';
            document.getElementById('pt_location_section').innerText = tag.location_section || 'N/A';
            document.getElementById('pt_location_floor').innerText = tag.location_floor || 'N/A';
            document.getElementById('pt_counted_by').innerText = tag.counted_by || 'N/A';
            document.getElementById('pt_supplier').innerText = tag.supplier || 'N/A';
            document.getElementById('pt_checked_by').innerText = tag.checked_by || 'N/A';
            document.getElementById('pt_unit_price').innerText = tag.unit_price ? parseFloat(tag.unit_price).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' }) : '₱0.00';
            document.getElementById('pt_remarks').innerText = tag.remarks || 'N/A';
            document.getElementById('pt_total_value').innerText = tag.total_value ? parseFloat(tag.total_value).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' }) : '₱0.00';
            document.getElementById('pt_updated_at').innerText = tag.updated_at ? new Date(tag.updated_at).toLocaleString() : 'N/A';

            // Generate QR Code
            const qrContainer = document.getElementById('pt_qrcode');
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, {
                text: tag.qr_code_url,
                width: 80,
                height: 80,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // Generate Barcode
            JsBarcode("#pt_barcode", tag.tag_number, {
                format: "CODE128",
                lineColor: "#000",
                width: 1.5,
                height: 40,
                displayValue: true,
                fontSize: 12
            });

            printableTagModal.classList.remove('hidden');
        } else {
            Swal.fire('Error', `Could not load tag details: ${res.message}`, 'error');
        }
    }

    window.closePrintableTagModal = function() {
        printableTagModal.classList.add('hidden');
    }

    window.printTag = function() {
        // This uses browser's print functionality.
        window.print();
    }

    // --- Update Status Modal ---
    const updateStatusModal = document.getElementById('updateStatusModal');
    const updateStatusForm = document.getElementById('updateStatusForm');

    window.openUpdateStatusModal = function(tagId, currentStatus) {
        updateStatusForm.reset();
        document.getElementById('us_tag_id').value = tagId;
        document.getElementById('us_status').value = currentStatus;
        updateStatusModal.classList.remove('hidden');
    }

    window.closeUpdateStatusModal = function() {
        updateStatusModal.classList.add('hidden');
    }

    updateStatusForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await apiRequest('office_dashboard.php', 'update_asset_status', data);

        if (res.success) {
            Swal.fire('Success', res.message, 'success');
            closeUpdateStatusModal();
            loadVerifiedAssets();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });

    window.setBorrowable = async (tagId, currentQuantity) => {
        const { value: quantity } = await Swal.fire({
            title: 'Set Borrowable Quantity',
            input: 'number',
            inputLabel: 'Enter the quantity to make available for borrowing',
            inputValue: currentQuantity,
            inputAttributes: {
                min: 0,
                step: 1
            },
            showCancelButton: true,
            confirmButtonText: 'Set Quantity',
            inputValidator: (value) => {
                if (!value || value < 0) {
                    return 'Please enter a valid quantity (0 or more)';
                }
            }
        });

        if (quantity !== undefined) {
            try {
                const res = await apiRequest('office_dashboard.php', 'make_asset_borrowable', { tag_id: tagId, quantity: quantity });
                if (res.success) {
                    await Swal.fire('Updated!', res.message, 'success');
                    loadVerifiedAssets();
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            } catch (error) {
                Swal.fire('Request Failed!', 'An error occurred while updating the borrowable quantity.', 'error');
            }
        }
    };

    function getStatusClass(status) {
        if (!status) return 'text-gray-600 bg-gray-200';
        switch (status.toLowerCase()) {
            case 'active':
            case 'in use':
                return 'text-green-600 bg-green-200';
            case 'in storage':
                return 'text-blue-600 bg-blue-200';
            case 'for repair':
                return 'text-orange-600 bg-orange-200';
            case 'missing':
                return 'text-red-600 bg-red-200';
            case 'pending verification':
                return 'text-yellow-800 bg-yellow-200';
            case 'declined':
                return 'text-pink-600 bg-pink-200';
            default:
                return 'text-gray-600 bg-gray-200';
        }
    }

    // Initial load
    loadPendingAssets();
    loadVerifiedAssets();
});
</script>
</body>
</html>
