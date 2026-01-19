<?php
// Include database configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin/actions/asset_actions.php';
require_once __DIR__ . '/../includes/email_functions.php';

// Enforce authentication and role-based access (custodian or admin)
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}
$user = getUserInfo();
$role = strtolower($user['role'] ?? '');
if ($role !== 'custodian' && $role !== 'admin') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. This page is for Custodian or Admin users only.']);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied. This page is for Custodian or Admin users only.';
    }
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    try {
        switch ($_POST['action']) {
            case 'add_asset':
                // VALIDATION 1: Ensure campus_id matches logged-in user's campus
                $providedCampusId = $_POST['campus_id'] ?? null;
                if ($providedCampusId != $user['campus_id']) {
                    throw new Exception('Invalid campus assignment. You can only add assets to your own campus.');
                }

                // VALIDATION 2: Check for duplicate serial number (if provided)
                $warningMessage = null;
                if (!empty($_POST['serial_number'])) {
                    $stmt = $pdo->prepare("
                        SELECT id, asset_name
                        FROM assets
                        WHERE serial_number = ? AND campus_id = ?
                    ");
                    $stmt->execute([$_POST['serial_number'], $user['campus_id']]);
                    $existing = $stmt->fetch();

                    if ($existing) {
                        throw new Exception("Asset with serial number '{$_POST['serial_number']}' already exists in your campus (Asset: {$existing['asset_name']})!");
                    }
                }

                // VALIDATION 3: Check for similar asset names (soft warning)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM assets
                    WHERE LOWER(asset_name) = LOWER(?)
                    AND campus_id = ?
                    AND status != 'Disposed'
                ");
                $stmt->execute([$_POST['asset_name'], $user['campus_id']]);
                $similarCount = $stmt->fetchColumn();

                if ($similarCount > 3) {
                    $warningMessage = "Note: You have {$similarCount} similar assets named '{$_POST['asset_name']}' in inventory.";
                }

                $result = createAsset($pdo, $_POST);

                echo json_encode([
                    'success' => true,
                    'message' => 'Asset added successfully!',
                    'warning' => $warningMessage,
                    'data' => $result
                ]);
                break;

            case 'get_categories':
                $categories = getCategories($pdo);
                echo json_encode(['success' => true, 'data' => $categories]);
                break;

            case 'get_asset_details':
                $asset = fetchOne($pdo, "SELECT a.*, c.category_name, cam.campus_name FROM assets a LEFT JOIN categories c ON a.category_id = c.id LEFT JOIN campuses cam ON a.campus_id = cam.id WHERE a.id = ?", [$_POST['asset_id']]);
                echo json_encode(['success' => true, 'data' => $asset]);
                break;

            case 'update_stock':
                $assetId = $_POST['asset_id'];
                $change = (int)$_POST['quantity'];
                $type = $_POST['type']; // 'in' or 'out'

                $currentAsset = fetchOne($pdo, "SELECT quantity FROM assets WHERE id = ?", [$assetId]);
                if ($type === 'out' && $currentAsset['quantity'] < $change) {
                    throw new Exception("Not enough stock to perform stock out.");
                }

                $newQuantity = $type === 'in' ? $currentAsset['quantity'] + $change : $currentAsset['quantity'] - $change;
                executeQuery($pdo, "UPDATE assets SET quantity = ? WHERE id = ?", [$newQuantity, $assetId]);
                
                $logAction = $type === 'in' ? 'STOCK_IN' : 'STOCK_OUT';
                logActivity($pdo, $assetId, $logAction, "Stock changed by {$change}. New quantity: {$newQuantity}");

                echo json_encode(['success' => true, 'message' => 'Stock updated successfully!', 'new_quantity' => $newQuantity]);
                break;

            case 'get_offices':
                $offices = fetchAll($pdo, "SELECT id, office_name, floor, section_code FROM offices WHERE campus_id = ? ORDER BY office_name", [$_POST['campus_id']]);
                echo json_encode(['success' => true, 'data' => $offices]);
                break;

            case 'get_office_list':
                $sql = "SELECT o.id, o.office_name, o.floor, o.section_code, COUNT(it.id) as assigned_tags_count 
                        FROM offices o 
                        LEFT JOIN inventory_tags it ON o.id = it.office_id 
                        WHERE o.campus_id = ? 
                        GROUP BY o.id 
                        ORDER BY o.office_name";
                $offices = fetchAll($pdo, $sql, [$_POST['campus_id']]);
                echo json_encode(['success' => true, 'data' => $offices]);
                break;

            case 'get_assets_for_office':
                $officeId = $_POST['office_id'];
                $filters = json_decode($_POST['filters'] ?? '{}', true);
                
                $sql = "SELECT it.id, it.tag_number, it.status, a.asset_name, a.serial_number 
                        FROM inventory_tags it
                        JOIN assets a ON it.asset_id = a.id
                        WHERE it.office_id = ?";
                $params = [$officeId];

                if (!empty($filters['search'])) {
                    $searchTerm = "%{$filters['search']}%";
                    $sql .= " AND (a.asset_name LIKE ? OR it.tag_number LIKE ?)";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                $assets = fetchAll($pdo, $sql, $params);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;
            
            case 'get_tag_details_for_print':
                $tagId = $_POST['tag_id'];
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
                        WHERE it.id = ?";
                $tagDetails = fetchOne($pdo, $sql, [$tagId]);

                if (!$tagDetails) {
                    throw new Exception("Inventory tag not found.");
                }

                // Add the full URL for the QR code
                $tagDetails['qr_code_url'] = "http://{$_SERVER['HTTP_HOST']}/AMS%20REQ/asset_lookup.php?tag={$tagDetails['tag_number']}";

                echo json_encode(['success' => true, 'data' => $tagDetails]);
                break;

            case 'assign_and_generate_tag':
                $pdo->beginTransaction();
                $assetId = $_POST['asset_id'];
                $officeId = $_POST['office_id'];
                $tagNumber = $_POST['tag_number'];
                $quantity = (int)($_POST['quantity'] ?? 1);
                $custodianUserId = $user['id'];
                $selectedUnitIds = !empty($_POST['selected_unit_ids']) ? explode(',', $_POST['selected_unit_ids']) : [];

                // Check if asset has enough quantity
                $currentAsset = fetchOne($pdo, "SELECT quantity, track_individually FROM assets WHERE id = ?", [$assetId]);
                if ($currentAsset['quantity'] < $quantity) {
                    throw new Exception("Not enough quantity available. Current quantity: {$currentAsset['quantity']}");
                }

                // 1. Create the new inventory tag record
                $sql = "INSERT INTO inventory_tags (asset_id, office_id, tag_number, inventory_date, article, size, counted_by, checked_by, location_row, location_section, location_floor, quantity, unit_price, total_value, amount, supplier, status, remarks, assigned_by_custodian_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                executeQuery($pdo, $sql, [
                    $assetId, $officeId, $tagNumber, $_POST['inventory_date'], $_POST['article'], $_POST['size'],
                    $_POST['counted_by'], $_POST['checked_by'], $_POST['location_row'], $_POST['location_section'],
                    $_POST['location_floor'], $quantity, $_POST['unit_price'], $_POST['amount'], $_POST['amount'],
                    $_POST['supplier'], 'Pending Verification', $_POST['remarks'], $custodianUserId
                ]);
                $tagId = $pdo->lastInsertId();

                // 2. If individual tracking is enabled and units are selected, link them
                if ($currentAsset['track_individually'] && !empty($selectedUnitIds)) {
                    foreach ($selectedUnitIds as $unitId) {
                        $unitId = (int)trim($unitId);
                        // Link unit to tag
                        executeQuery($pdo, "
                            INSERT INTO tag_units (tag_id, unit_id, is_active)
                            VALUES (?, ?, TRUE)
                        ", [$tagId, $unitId]);

                        // Update unit status to 'In Use'
                        executeQuery($pdo, "
                            UPDATE asset_units
                            SET unit_status = 'In Use'
                            WHERE id = ?
                        ", [$unitId]);

                        // Log unit assignment
                        executeQuery($pdo, "
                            INSERT INTO unit_history (unit_id, action, new_value, description, performed_by, performed_by_name)
                            VALUES (?, 'ASSIGNED', ?, ?, ?, ?)
                        ", [
                            $unitId,
                            "Tag: {$tagNumber}",
                            "Assigned to office via tag {$tagNumber}",
                            $custodianUserId,
                            $user['full_name']
                        ]);
                    }
                }

                // 3. Decrease asset quantity and assign to office
                executeQuery($pdo, "UPDATE assets SET quantity = quantity - ?, assigned_to = ? WHERE id = ?", [$quantity, $officeId, $assetId]);

                // 4. Update asset status to 'In Use' if quantity becomes 0 (all units assigned)
                $newQuantity = $currentAsset['quantity'] - $quantity;
                if ($newQuantity == 0) {
                    executeQuery($pdo, "UPDATE assets SET status = 'In Use' WHERE id = ?", [$assetId]);
                }

                // 5. Log the activity
                $unitsInfo = !empty($selectedUnitIds) ? " (" . count($selectedUnitIds) . " individual units tracked)" : "";
                logActivity($pdo, $assetId, 'TAG_GENERATED', "Inventory tag #{$tagNumber} generated for office ID #{$officeId} by custodian. Quantity: {$quantity}{$unitsInfo}");

                $pdo->commit();

                // Send email notification to office users
                $asset = fetchOne($pdo, "SELECT asset_name FROM assets WHERE id = ?", [$assetId]);
                $custodianName = $user['full_name'];
                sendTagGenerationNotification($pdo, $officeId, $asset['asset_name'], $tagNumber, $custodianName);

                echo json_encode(['success' => true, 'message' => 'Inventory tag generated successfully! Asset quantity updated.', 'tag_id' => $tagId]);
                break;

            case 'assign_asset_to_office':
                $pdo->beginTransaction();
                $assetId = $_POST['asset_id'];
                $officeId = $_POST['office_id'];
                $tagNumber = $_POST['tag_number']; // This should be the unique generated tag
                $custodianUserId = $user['id'];

                // 1. Link tag to asset and office, and record who assigned it
                $sql = "INSERT INTO inventory_tags (asset_id, office_id, tag_number, status, assigned_by_custodian_id, inventory_date) VALUES (?, ?, ?, 'Pending Verification', ?, NOW())";
                executeQuery($pdo, $sql, [$assetId, $officeId, $tagNumber, $custodianUserId]);
                $tagId = $pdo->lastInsertId();

                // 2. Update asset status to In Use and assign to office
                executeQuery($pdo, "UPDATE assets SET status = 'In Use', assigned_to = ? WHERE id = ?", [$officeId, $assetId]);

                // 3. Log the activity
                logActivity($pdo, $assetId, 'ASSIGNED_TO_OFFICE', "Asset assigned to office ID #{$officeId} with tag #{$tagNumber} by custodian.");

                $pdo->commit();

                // Send email notification to office users
                $asset = fetchOne($pdo, "SELECT asset_name FROM assets WHERE id = ?", [$assetId]);
                $custodianName = $user['full_name'];
                sendTagGenerationNotification($pdo, $officeId, $asset['asset_name'], $tagNumber, $custodianName);

                echo json_encode(['success' => true, 'message' => 'Asset successfully assigned to office and is now pending verification.', 'tag_id' => $tagId]);
                break;

            case 'generate_inventory_tag':
                $pdo->beginTransaction();
                $assetId = $_POST['asset_id'];
                $quantityToTag = (int)($_POST['quantity'] ?? 1);
                $officeId = $_POST['office_id'];

                // Decrease asset quantity and assign to office
                executeQuery($pdo, "UPDATE assets SET quantity = quantity - ?, assigned_to = ? WHERE id = ?", [$quantityToTag, $officeId, $assetId]);

                // Insert new inventory tags in a loop
                $baseTagNumber = $_POST['tag_number'];
                for ($i = 1; $i <= $quantityToTag; $i++) {
                    $uniqueTagNumber = ($quantityToTag > 1) ? $baseTagNumber . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) : $baseTagNumber;
                    $sql = "INSERT INTO inventory_tags (asset_id, office_id, tag_number, inventory_date, article, size, counted_by, checked_by, location_row, location_section, location_floor, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending Verification')";
                    $params = [
                        $assetId, $officeId, $uniqueTagNumber, $_POST['inventory_date'], $_POST['article'], $_POST['size'], $_POST['counted_by'], $_POST['checked_by'], $_POST['location_row'], $_POST['location_section'], $_POST['location_floor']
                    ];
                    executeQuery($pdo, $sql, $params);
                }
                $tagId = $pdo->lastInsertId(); // Returns the last inserted ID
                $pdo->commit();

                // Send email notification to office users
                $asset = fetchOne($pdo, "SELECT asset_name FROM assets WHERE id = ?", [$assetId]);
                $custodianName = $user['full_name'];
                sendTagGenerationNotification($pdo, $_POST['office_id'], $asset['asset_name'], $_POST['tag_number'], $custodianName);

                logActivity($pdo, $assetId, 'TAG_GENERATED', "Inventory tag #{$_POST['tag_number']} generated.");
                echo json_encode(['success' => true, 'message' => 'Inventory tag generated successfully!', 'tag_id' => $tagId]);
                break;

            case 'get_all_assets':
                // Filter assets by the custodian's campus
                $filters = ['campus_id' => $_POST['campus_id'] ?? null];
                $assets = getAllAssets($pdo, $filters);
                echo json_encode(['success' => true, 'data' => $assets]);
                break;

            case 'check_duplicate_serial':
                // Real-time check for duplicate serial numbers
                $serialNumber = $_POST['serial_number'] ?? '';
                $campusId = $user['campus_id'];

                if (empty($serialNumber)) {
                    echo json_encode(['success' => true, 'exists' => false]);
                    break;
                }

                $stmt = $pdo->prepare("
                    SELECT id, asset_name
                    FROM assets
                    WHERE serial_number = ? AND campus_id = ?
                ");
                $stmt->execute([$serialNumber, $campusId]);
                $existing = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'exists' => $existing ? true : false,
                    'asset' => $existing
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// If it's a GET request, render the HTML dashboard
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../api.js"></script>
    <title>Custodian Dashboard - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <style>
        .tab-active { border-color: #3b82f6; color: #3b82f6; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #4b5563; border-radius: 20px; }
    </style>
    <style>
        /* Print styles for 100mm x 150mm label */
        @page {
            size: 150mm 100mm;
            margin: 0;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #printableTagModal,
            #printableTagModal *,
            #printable-tag-content,
            #printable-tag-content *,
            #tag-to-print,
            #tag-to-print * {
                visibility: visible;
            }

            #printableTagModal {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 150mm !important;
                height: 100mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            #printable-tag-content {
                width: 150mm !important;
                height: 100mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
            }

            #tag-to-print {
                width: 150mm !important;
                height: 100mm !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 1px solid black !important;
                box-sizing: border-box !important;
                display: flex !important;
                min-height: 100mm !important;
                max-height: 100mm !important;
            }

            /* Left sidebar with logo */
            #tag-to-print > div:first-child {
                width: 12mm !important;
                padding: 1mm !important;
            }

            #tag-to-print img {
                height: 15mm !important;
                width: 15mm !important;
            }

            /* Main content area */
            #tag-to-print > div:last-child {
                flex: 1 !important;
                padding: 1mm !important;
            }

            #tag-to-print table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10px !important;
                height: 100% !important;
            }

            #tag-to-print td {
                border: 1px solid black !important;
                padding: 1.5mm !important;
                font-size: 10px !important;
                line-height: 1.4 !important;
            }

            #tag-to-print tr {
                height: auto !important;
            }

            #tag-to-print .text-sm {
                font-size: 9px !important;
            }

            #tag-to-print .text-xs {
                font-size: 7px !important;
            }

            #tag-to-print .font-bold {
                font-weight: bold !important;
            }

            #tag-to-print .bg-gray-100 {
                background-color: #f3f4f6 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            #tag-to-print .bg-gray-50 {
                background-color: #f9fafb !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            #pt_qrcode canvas,
            #pt_qrcode img {
                max-width: 32mm !important;
                max-height: 32mm !important;
            }

            #pt_barcode {
                max-width: 45mm !important;
                max-height: 20mm !important;
            }

            .print\:hidden {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Custodian Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <!-- Simplified Navigation -->
                <a href="#" onclick="showTab('manage-assets')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-box-open w-6"></i><span>Manage Assets</span>
                </a>
                <a href="#" onclick="showTab('offices')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
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
                <h1 class="text-xl font-semibold text-gray-800">Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <!-- Report Missing Asset -->
                    <a href="../report_missing_asset.php" class="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 border border-red-700 transition-colors">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Report Missing
                    </a>

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
                <!-- Manage Assets Tab -->
                <div id="manage-assets-tab" class="tab-content">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-semibold text-gray-800">Manage Assets</h2>
                            <button onclick="openAddAssetModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add New Asset
                            </button>
                        </div>
                        <p class="text-gray-600 mb-6">Here you can add new assets to the system and view all existing assets in your campus.</p>
                        
                        <!-- Asset List Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                    <tr>
                                        <th class="py-3 px-6 text-left">Asset Name</th>
                                        <th class="py-3 px-6 text-left">Category</th>
                                        <th class="py-3 px-6 text-center">Status</th>
                                        <th class="py-3 px-6 text-center">Quantity</th>
                                        <th class="py-3 px-6 text-left">Location</th>
                                        <th class="py-3 px-6 text-left">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="assets-table-body" class="text-gray-600 text-sm font-light">
                                    <!-- Asset rows will be injected here by JavaScript -->
                                </tbody>
                            </table>
                            <div id="assets-loading" class="text-center py-4">Loading assets...</div>
                        </div>
                    </div>
                </div>

                <!-- Offices Tab -->
                <div id="offices-tab" class="tab-content hidden">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Office Asset Overview</h2>
                        <p class="text-gray-600 mb-6">View assets assigned to each office in your campus.</p>
                        <div id="office-list-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Office cards will be loaded here -->
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

<!-- Office Assets Modal -->
<div id="officeAssetsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl h-[90vh] flex flex-col">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-semibold" id="officeAssetsModalTitle">Office Assets</h3>
            <button onclick="closeOfficeAssetsModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <input type="text" id="officeAssetSearch" placeholder="Search by asset name or tag..." class="w-full p-2 border border-gray-300 rounded-md mb-4">
        <div class="flex-1 overflow-y-auto">
            <div id="office-assets-list" class="space-y-2"></div>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div id="addAssetModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-semibold">Add New Asset</h3>
            <button onclick="closeAddAssetModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form id="addAssetForm" class="space-y-6">
            <!-- Asset Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="asset_name" class="block text-sm font-medium text-gray-700">Asset Name</label>
                    <input type="text" name="asset_name" id="asset_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category / Type</label>
                    <select name="category_id" id="category_id" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <!-- Categories will be loaded here -->
                    </select>
                </div>
            </div>

            <!-- Purchase and Inventory Details -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                <!-- Inventory Date is now fully automatic and hidden -->
                <div>
                    <label for="purchase_date" class="block text-sm font-medium text-gray-700">Purchase Date</label>
                    <input type="date" name="purchase_date" id="purchase_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="supplier" class="block text-sm font-medium text-gray-700">Price By (Supplier)</label>
                    <input type="text" name="supplier" id="supplier" placeholder="e.g., Wiilman Computer Shop" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Location Details -->
            <div class="md:col-span-2">
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <input type="text" name="location" id="location" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="e.g., Library, Canteen, Room 101">
            </div>

            <!-- Financials and Remarks -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="grid grid-cols-2 gap-x-4">
                    <div>
                        <label for="value" class="block text-sm font-medium text-gray-700">Unit Price (₱)</label>
                        <input type="number" step="0.01" name="value" id="value" value="0" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity (Qty)</label>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Individual Tracking Info (Auto-enabled for quantity > 1) -->
                <div id="individual-tracking-info" class="md:col-span-2 bg-green-50 border border-green-300 rounded-lg p-4" style="display: none;">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        <div class="flex-1">
                            <span class="font-semibold text-green-900">
                                Individual Unit Tracking Enabled
                            </span>
                            <p class="text-sm text-green-700 mt-1">
                                Each unit will automatically get a unique serial number (e.g., CHAIR-001, CHAIR-002, CHAIR-003...). This ensures full accountability for every item.
                            </p>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="total_value" class="block text-sm font-medium text-gray-700">Total Value (₱)</label>
                    <input type="text" id="total_value" readonly class="mt-1 block w-full border-gray-200 bg-gray-100 rounded-md shadow-sm py-2 px-3 text-gray-500">
                </div>
            </div>

            <div>
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <select name="remarks" id="remarks" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="WORKING">Working</option>
                    <option value="GOOD CONDITION">Good Condition</option>
                    <option value="FAIR CONDITION">Fair Condition</option>
                    <option value="POOR CONDITION">Poor Condition</option>
                    <option value="FOR REPAIR">For Repair</option>
                    <option value="FOR DISPOSAL">For Disposal</option>
                </select>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" name="status" value="Available">
            <input type="hidden" name="inventory_date" id="inventory_date">
            <input type="hidden" name="serial_number" id="serial_number"> <!-- Keep for compatibility, can be auto-generated -->
            <input type="hidden" name="campus_id" id="campus_id" value="<?= $user['campus_id'] ?>">

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeAddAssetModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Actions Modal -->
<div id="actionsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-semibold" id="actionsModalTitle">Actions</h3>
            <button onclick="closeActionsModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <div class="grid grid-cols-2 gap-4 text-center">
            <button onclick="viewAssetDetails()" class="p-4 bg-blue-100 hover:bg-blue-200 rounded-lg"><i class="fas fa-eye mb-2"></i><br>View Details</button>
            <button onclick="openStockModal('in')" class="p-4 bg-green-100 hover:bg-green-200 rounded-lg"><i class="fas fa-plus-circle mb-2"></i><br>Stock In</button>
            <button onclick="openStockModal('out')" class="p-4 bg-red-100 hover:bg-red-200 rounded-lg"><i class="fas fa-minus-circle mb-2"></i><br>Stock Out</button>
            <button id="assignOfficeBtn" onclick="openAssignOfficeModal()" class="p-4 bg-purple-100 hover:bg-purple-200 rounded-lg disabled:bg-gray-200 disabled:cursor-not-allowed col-span-2"><i class="fas fa-building-user mb-2"></i><br>Activate / Generate Tag</button>
            <button onclick="alert('Edit Asset function to be implemented.')" class="p-4 bg-yellow-100 hover:bg-yellow-200 rounded-lg col-span-2"><i class="fas fa-edit mb-2"></i><br>Edit Asset</button>
        </div>
    </div>
</div>

<!-- Assign Office Modal -->
<div id="assignOfficeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl h-[70vh] flex flex-col">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-semibold">Select an Office to Assign Asset</h3>
            <button onclick="closeAssignOfficeModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <input type="text" id="assignOfficeSearch" placeholder="Search for an office..." class="w-full p-2 border border-gray-300 rounded-md mb-4">
        <div id="assign-office-list" class="flex-1 overflow-y-auto space-y-2">
            <!-- Office list will be populated here -->
        </div>
        <div id="assign-office-pagination" class="mt-4 flex justify-center items-center space-x-2">
            <!-- Pagination controls will be here -->
        </div>
        <div class="mt-4 text-right">
             <button type="button" onclick="closeAssignOfficeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- New Generate Inventory Tag Modal -->
<div id="generateTagModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-semibold">Generate Inventory Tag</h3>
            <button onclick="closeGenerateTagModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form id="generateTagForm">
            <input type="hidden" name="asset_id" id="gt_asset_id">
            <input type="hidden" name="office_id" id="gt_office_id">
            <div class="space-y-6">
                <!-- Asset Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold mb-3">Asset Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Asset Name / Code</label>
                            <p id="gt_asset_name" class="mt-1 p-2 bg-white border rounded-md"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Selected Office</label>
                            <p id="gt_office_name" class="mt-1 p-2 bg-white border rounded-md"></p>
                        </div>
                    </div>
                </div>

                <!-- Inventory Details -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold mb-3">Inventory Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="gt_inventory_date" class="block text-sm font-medium text-gray-700">Inventory Date</label>
                            <input type="date" id="gt_inventory_date" name="inventory_date" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="gt_inventory_tag_number" class="block text-sm font-medium text-gray-700">Tag Number</label>
                            <input type="text" id="gt_inventory_tag_number" name="tag_number" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="gt_article" class="block text-sm font-medium text-gray-700">Article / Material</label>
                            <input type="text" id="gt_article" name="article" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="gt_quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                            <input type="number" id="gt_quantity" name="quantity" value="1" min="1" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="gt_size" class="block text-sm font-medium text-gray-700">Size</label>
                            <input type="text" id="gt_size" name="size" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Counted By</label>
                            <input type="text" id="gt_counted_by" name="counted_by" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Checked By</label>
                            <input type="text" id="gt_checked_by" name="checked_by" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                    </div>
                </div>

                <!-- Location Details -->
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold mb-3">Location Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="gt_location_row" class="block text-sm font-medium text-gray-700">Row</label>
                            <input type="text" id="gt_location_row" name="location_row" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Section</label>
                            <input type="text" id="gt_location_section" name="location_section" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Floor</label>
                            <input type="text" id="gt_location_floor" name="location_floor" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold mb-3">Financial Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price By (Supplier)</label>
                            <input type="text" id="gt_supplier" name="supplier" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Unit Price (₱)</label>
                            <input type="number" step="0.01" id="gt_unit_price" name="unit_price" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Value (₱)</label>
                            <input type="text" id="gt_total_value" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div>
                            <label for="gt_amount" class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" step="0.01" id="gt_amount" name="amount" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Remarks</label>
                            <input type="text" id="gt_remarks" name="remarks" readonly class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeGenerateTagModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Save</button>
            </div>
        </form>
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

<!-- Stock In/Out Modal -->
<div id="stockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
        <h3 class="text-xl font-semibold mb-4" id="stockModalTitle">Stock In</h3>
        <form id="stockForm">
            <label for="stock_quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
            <input type="number" id="stock_quantity" name="quantity" min="1" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeStockModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Barcode/QR Code Modal -->
<div id="codeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md text-center">
        <h3 class="text-xl font-semibold mb-4" id="codeModalTitle">Barcode</h3>
        <div id="codeCanvas" class="flex justify-center items-center my-4"></div>
        <p id="codeNumber" class="text-lg font-mono tracking-widest"></p>
        <div class="mt-6">
            <button onclick="closeCodeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Close</button>
        </div>
    </div>
</div>

<!-- Inventory Tag Modal -->
<div id="inventoryTagModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Generate Inventory Tag</h3>
            <button onclick="closeInventoryTagModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form id="inventoryTagForm">
            <div class="space-y-6">
                <!-- Tag and Date Info -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h4 class="text-md font-semibold text-blue-800 mb-2">Assignment Details</h4>
                    <p><strong>Asset:</strong> <span id="tag_asset_name"></span></p>
                    <p><strong>Assigning to Office:</strong> <span id="tag_office_name" class="font-bold text-blue-700"></span></p>
                    <p><strong>Generated Tag Number:</strong> <span id="tag_number" class="font-mono text-red-600"></span></p>
                    <input type="hidden" name="tag_number" id="tag_number_hidden">
                    <input type="hidden" name="office_id" id="tag_office_id_hidden">
                    <input type="hidden" name="asset_id" id="tag_asset_id_hidden">
                </div>

                <!-- Additional Details (Optional) -->
                <div>
                    <label for="tag_notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                    <textarea id="tag_notes" name="notes" rows="3" class="form-input w-full" placeholder="Add any relevant notes for this assignment..."></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeInventoryTagModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Confirm Assignment</button>
            </div>
        </form>
    </div>
    <style>
        #inventoryTagForm .form-input {
            @apply mt-1 border border-gray-300 rounded-md shadow-sm py-1 px-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500;
        }
        @media print {
            body * { visibility: hidden; }
            #inventoryTagModal, #inventoryTagModal * { visibility: visible; }
            #inventoryTagModal {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: white;
                border: none;
                box-shadow: none;
            }
            #inventoryTagModal button { display: none; }
        }
    </style>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    window.campusId = <?= json_encode($user['campus_id']) ?>;
    
    // --- Tab Navigation ---
    window.showTab = function(tabId) {
        // This function is simplified as there's only one tab now.
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        document.getElementById(tabId + '-tab').classList.remove('hidden');
        document.querySelectorAll('.tab-item').forEach(item => item.classList.remove('tab-active'));
        document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('tab-active');
        
        if (tabId === 'manage-assets') loadAllAssets();
        if (tabId === 'offices') loadOfficeList();

    }

    // --- Offices Tab ---
    let officeListCache = [];
    async function loadOfficeList() {
        const container = document.getElementById('office-list-container');
        container.innerHTML = '<p>Loading offices...</p>';
        try {
            const res = await apiRequest('dashboard.php', 'get_office_list');
            if (res && res.success) {
                officeListCache = res.data;
                container.innerHTML = '';
                if (officeListCache.length > 0) {
                    officeListCache.forEach(office => {
                        const card = `
                            <div class="bg-white p-4 rounded-lg border border-gray-200 hover:shadow-lg transition-shadow cursor-pointer" onclick="openOfficeAssetsModal(${office.id}, '${office.office_name}')">
                                <h3 class="font-semibold text-lg text-blue-700">${office.office_name}</h3>
                                <p class="text-sm text-gray-500">${office.floor || 'N/A'} Floor - Section ${office.section_code || 'N/A'}</p>
                                <p class="text-sm text-gray-600 mt-2">Assigned Tags: <span class="font-bold">${office.assigned_tags_count}</span></p>
                            </div>
                        `;
                        container.innerHTML += card;
                    });
                } else {
                    container.innerHTML = '<p>No offices found for this campus.</p>';
                }
            } else {
                container.innerHTML = `<p class="text-red-500">Error loading offices: ${res ? res.message : 'Unknown error'}</p>`;
            }
        } catch (error) {
            console.error("Failed to load offices:", error);
            container.innerHTML = `<p class="text-red-500">A critical error occurred while loading offices.</p>`;
        }
    }

    const officeAssetsModal = document.getElementById('officeAssetsModal');
    let currentOfficeId = null;

    window.openOfficeAssetsModal = async function(officeId, officeName) {
        currentOfficeId = officeId;
        document.getElementById('officeAssetsModalTitle').innerText = `Assets in: ${officeName}`;
        officeAssetsModal.classList.remove('hidden');
        await loadAssetsForOffice();
    }

    window.closeOfficeAssetsModal = function() {
        officeAssetsModal.classList.add('hidden');
        currentOfficeId = null;
        document.getElementById('officeAssetSearch').value = '';
    }

    document.getElementById('officeAssetSearch').addEventListener('input', function() {
        // Debounce search to avoid excessive requests
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => loadAssetsForOffice(), 300);
    });

    // --- Add Asset Modal ---
    const addAssetModal = document.getElementById('addAssetModal');
    const addAssetForm = document.getElementById('addAssetForm');

    window.openAddAssetModal = async function() {
        addAssetForm.reset();
        await loadCategoriesIntoSelect();

        // Automatically set inventory date to today
        const today = new Date();
        document.getElementById('inventory_date').value = today.toISOString().split('T')[0];

        calculateTotalValue(); // Calculate on open
        addAssetModal.classList.remove('hidden');
    }

    window.closeAddAssetModal = function() {
        addAssetModal.classList.add('hidden');
    }

    async function loadCategoriesIntoSelect() {
        const select = document.getElementById('category_id');
        try {
            const res = await apiRequest('dashboard.php', 'get_categories');
            if (res && res.success) {
                select.innerHTML = '<option value="">Select a Category</option>';
                res.data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.category_name}</option>`;
                });
            }
        } catch (error) {
            console.error("Failed to load categories:", error);
        }
    }

    // --- Load All Assets ---
    async function loadAllAssets() {
        const tableBody = document.getElementById('assets-table-body');
        const loading = document.getElementById('assets-loading');
        loading.style.display = 'block';
        tableBody.innerHTML = '';

        try {
            const res = await apiRequest('dashboard.php', 'get_all_assets');
            loading.style.display = 'none';
            if (res && res.success && res.data.length > 0) {
                res.data.forEach(asset => {
                    const statusClass = getStatusClass(asset.status);
                    const remarksClass = getRemarksClass(asset.remarks);
                    const row = `
                        <tr class="border-b border-gray-200 hover:bg-gray-100 cursor-pointer" onclick='openActionsModal(${JSON.stringify(asset)})'>
                            <td class="py-3 px-6 text-left whitespace-nowrap">${asset.asset_name}</td>
                            <td class="py-3 px-6 text-left">${asset.category_name || 'N/A'}</td>
                            <td class="py-3 px-6 text-center"><span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full ${statusClass}">${asset.status}</span></td>
                            <td class="py-3 px-6 text-center" id="quantity-${asset.id}">${asset.quantity}</td>
                            <td class="py-3 px-6 text-left">${asset.location || 'N/A'}</td>
                            <td class="py-3 px-6 text-center"><span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full ${remarksClass}">${asset.remarks || 'N/A'}</span></td>
                        </tr>`;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No assets found in your campus.</td></tr>';
            }
        } catch (error) {
            loading.style.display = 'none';
            console.error("Failed to load assets:", error);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">A critical error occurred while loading assets.</td></tr>';
        }
    }

    async function loadAssetsForOffice() {
        if (!currentOfficeId) return;
        const listContainer = document.getElementById('office-assets-list');
        listContainer.innerHTML = '<p>Loading assets...</p>';
        const searchTerm = document.getElementById('officeAssetSearch').value;

        try {
            const res = await apiRequest('dashboard.php', 'get_assets_for_office', {
                office_id: currentOfficeId,
                filters: JSON.stringify({ search: searchTerm })
            });

            if (res && res.success) {
                listContainer.innerHTML = '';
                if (res.data.length > 0) {
                    res.data.forEach(asset => {
                        const statusClass = getStatusClass(asset.status);
                        const item = `
                            <div class="p-3 bg-gray-50 border rounded-md flex justify-between items-center">
                                <div>
                                    <p class="font-semibold">${asset.asset_name}</p>
                                    <p class="text-xs text-gray-500">Tag: ${asset.tag_number} | Status: <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full ${statusClass}">${asset.status}</span></p>
                                </div>
                                <button onclick="openPrintableTagModal(${asset.id})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm flex items-center gap-1" title="Print Tag">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>`;
                        listContainer.innerHTML += item;
                    });
                } else {
                    listContainer.innerHTML = '<p>No assets found for this office matching your search.</p>';
                }
            } else {
                listContainer.innerHTML = `<p class="text-red-500">Error: ${res ? res.message : 'Unknown error'}</p>`;
            }
        } catch (error) {
            console.error("Failed to load office assets:", error);
            listContainer.innerHTML = `<p class="text-red-500">A critical error occurred while loading office assets.</p>`;
        }
    }

    // --- Actions Modal ---
    let selectedAsset = null;
    let assigningAsset = null;
    const actionsModal = document.getElementById('actionsModal');
    const stockModal = document.getElementById('stockModal');
    const codeModal = document.getElementById('codeModal');
    const inventoryTagModal = document.getElementById('inventoryTagModal');

    window.openActionsModal = function(asset) {
        selectedAsset = asset;
        document.getElementById('actionsModalTitle').innerText = `Actions for: ${asset.asset_name}`;
        actionsModal.classList.remove('hidden'); 
        document.getElementById('assignOfficeBtn').disabled = asset.quantity === 0;
    }

    window.closeActionsModal = function() {
        actionsModal.classList.add('hidden');
        selectedAsset = null;
    }

    window.viewAssetDetails = async function() {
        const res = await apiRequest('dashboard.php', 'get_asset_details', { asset_id: selectedAsset.id });
        if (res.success) {
            const asset = res.data;
            let detailsHtml = '<div class="text-left space-y-2">';
            for (const [key, value] of Object.entries(asset)) {
                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                detailsHtml += `<div><strong>${formattedKey}:</strong> ${value || 'N/A'}</div>`;
            }
            detailsHtml += '</div>';
            Swal.fire({
                title: `Details for ${asset.asset_name}`,
                html: detailsHtml,
                confirmButtonText: 'Close'
            });
        }
        closeActionsModal();
    }

    window.openStockModal = function(type) {
        document.getElementById('stockModalTitle').innerText = type === 'in' ? 'Stock In' : 'Stock Out';
        document.getElementById('stockForm').dataset.type = type;
        stockModal.classList.remove('hidden');
        closeActionsModal();
    }

    window.closeStockModal = function() {
        stockModal.classList.add('hidden');
        document.getElementById('stockForm').reset();
    }

    document.getElementById('stockForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const type = e.target.dataset.type;
        const quantity = document.getElementById('stock_quantity').value;

        const res = await apiRequest('dashboard.php', 'update_stock', {
            asset_id: selectedAsset.id,
            quantity: quantity,
            type: type
        });

        if (res.success) {
            Swal.fire('Success', res.message, 'success');
            document.getElementById(`quantity-${selectedAsset.id}`).innerText = res.new_quantity;
            closeStockModal();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });

    window.viewBarcode = function() {
        const codeCanvas = document.getElementById('codeCanvas');
        codeCanvas.innerHTML = '<svg id="barcode"></svg>';
        JsBarcode("#barcode", selectedAsset.barcode, {
            format: "CODE128",
            lineColor: "#000",
            width: 1.5,
            height: 60,
            displayValue: false
        });
        document.getElementById('codeModalTitle').innerText = 'Barcode';
        document.getElementById('codeNumber').innerText = selectedAsset.barcode;
        codeModal.classList.remove('hidden');
        closeActionsModal();
    }

    window.viewQRCode = function() {
        const codeCanvas = document.getElementById('codeCanvas');
        codeCanvas.innerHTML = ''; // Clear previous content
        new QRCode(codeCanvas, {
            text: JSON.stringify({ id: selectedAsset.id, barcode: selectedAsset.barcode, name: selectedAsset.asset_name }),
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        document.getElementById('codeModalTitle').innerText = 'QR Code';
        document.getElementById('codeNumber').innerText = selectedAsset.barcode;
        codeModal.classList.remove('hidden');
        closeActionsModal();
    }

    // --- New Assign Office Flow ---
    const assignOfficeModal = document.getElementById('assignOfficeModal');
    let officeCache = [];
    let officeCurrentPage = 1;
    const officesPerPage = 10;

    window.openAssignOfficeModal = async function() {
        assigningAsset = selectedAsset; // Store the asset being assigned
        assignOfficeModal.classList.remove('hidden');
        closeActionsModal();        const res = await apiRequest('dashboard.php', 'get_offices');
        if (res.success) {
            officeCache = res.data;
            officeCurrentPage = 1;
            renderOfficeList();
        } else {
            document.getElementById('assign-office-list').innerHTML = `<p class="text-red-500">Error: ${res.message}</p>`;
        }
    }

    window.closeAssignOfficeModal = function() {
        assignOfficeModal.classList.add('hidden');
    }

    document.getElementById('assignOfficeSearch').addEventListener('input', () => {
        officeCurrentPage = 1;
        renderOfficeList();
    });

    function renderOfficeList() {
        const searchTerm = document.getElementById('assignOfficeSearch').value.toLowerCase();
        const filteredOffices = officeCache.filter(office => office.office_name.toLowerCase().includes(searchTerm));

        const listEl = document.getElementById('assign-office-list');
        const paginationEl = document.getElementById('assign-office-pagination');
        listEl.innerHTML = '';
        paginationEl.innerHTML = '';

        if (filteredOffices.length === 0) {
            listEl.innerHTML = '<p class="text-center text-gray-500">No offices found.</p>';
            return;
        }

        const totalPages = Math.ceil(filteredOffices.length / officesPerPage);
        const startIndex = (officeCurrentPage - 1) * officesPerPage;
        const endIndex = startIndex + officesPerPage;
        const paginatedOffices = filteredOffices.slice(startIndex, endIndex);

        paginatedOffices.forEach(office => {
            const officeDiv = document.createElement('div');
            officeDiv.className = 'p-3 bg-gray-50 border rounded-md hover:bg-blue-100 hover:border-blue-300 cursor-pointer transition-colors';
            officeDiv.innerHTML = `<p class="font-semibold">${office.office_name}</p><p class="text-sm text-gray-600">${office.floor || ''} Floor - Section ${office.section_code || 'N/A'}</p>`;
            officeDiv.onclick = () => selectOfficeForAssignment(office);
            listEl.appendChild(officeDiv);
        });

        // Render pagination
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.innerText = i;
            pageButton.className = `px-3 py-1 rounded-md text-sm ${i === officeCurrentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'}`;
            pageButton.onclick = () => {
                officeCurrentPage = i;
                renderOfficeList();
            };
            paginationEl.appendChild(pageButton);
        }
    }

    window.selectOfficeForAssignment = function(office) {
        // This function is now the trigger for the new "Generate Tag" modal
        closeAssignOfficeModal();
        if (assigningAsset) {
            openGenerateTagModal(office, assigningAsset);
        } else {
            console.error('No asset selected for tag generation');
            Swal.fire('Error', 'No asset selected. Please try again.', 'error');
        }
    }

    const generateTagModal = document.getElementById('generateTagModal');

    window.openGenerateTagModal = async function(office, asset) {
        // 1. Populate the modal with data
        document.getElementById('gt_asset_id').value = asset.id;
        document.getElementById('gt_office_id').value = office.id;
        document.getElementById('gt_asset_name').innerText = `${asset.asset_name} (ID: ${asset.id})`;
        document.getElementById('gt_office_name').innerText = `${office.office_name} (Floor: ${office.floor})`;

        // 2. Auto-generate a unique tag number
        const today = new Date();
        const datePart = `${String(today.getMonth() + 1).padStart(2, '0')}${String(today.getDate()).padStart(2, '0')}${String(today.getFullYear()).slice(-2)}`;
        const uniquePart = Date.now().toString().slice(-4); // 4-digit unique part
        const tagNumber = `MIS-${datePart}-${uniquePart}`;
        document.getElementById('gt_inventory_tag_number').value = tagNumber;

        // 3. Set inventory date to today
        document.getElementById('gt_inventory_date').value = today.toISOString().split('T')[0];

        // 4. Set counted by and checked by to current user
        const currentUserName = '<?= htmlspecialchars($user['full_name']) ?>';
        document.getElementById('gt_counted_by').value = currentUserName;
        document.getElementById('gt_checked_by').value = currentUserName;

        // 5. Set location details from office
        document.getElementById('gt_location_section').value = office.section_code || '';
        document.getElementById('gt_location_floor').value = office.floor || '';

        // 6. Set financial details from asset
        document.getElementById('gt_supplier').value = asset.supplier || '';
        document.getElementById('gt_unit_price').value = asset.value || 0;
        document.getElementById('gt_remarks').value = asset.remarks || '';

        // 7. Calculate total value and amount
        calculateTagTotalValue();

        // 8. Show the modal
        generateTagModal.classList.remove('hidden');
    }

    window.closeGenerateTagModal = function() {
        generateTagModal.classList.add('hidden');
        document.getElementById('generateTagForm').reset();
    }

    // Calculate total value and amount for tag generation
    function calculateTagTotalValue() {
        const unitPrice = parseFloat(document.getElementById('gt_unit_price').value) || 0;
        const quantity = parseInt(document.getElementById('gt_quantity').value) || 1;
        const totalValue = unitPrice * quantity;
        document.getElementById('gt_total_value').value = totalValue.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
        document.getElementById('gt_amount').value = totalValue;
    }

    // Add event listeners for calculation
    document.getElementById('gt_quantity').addEventListener('input', calculateTagTotalValue);


    window.openInventoryTagModal = function(office) {
        // 1. Generate a unique tag number
        const today = new Date();
        const datePart = `${String(today.getMonth() + 1).padStart(2, '0')}${String(today.getDate()).padStart(2, '0')}${String(today.getFullYear()).slice(-2)}`;
        const uniquePart = Date.now().toString().slice(-4); // 4-digit unique identifier
        const tagNumber = `HCC-${office.section_code || 'GEN'}-${datePart}-${uniquePart}`;

        // 2. Populate the new "Generate Inventory Tag" modal
        document.getElementById('tag_asset_name').innerText = selectedAsset.asset_name;
        document.getElementById('tag_office_name').innerText = office.office_name;
        document.getElementById('tag_number').innerText = tagNumber;

        // 3. Set hidden inputs for form submission
        document.getElementById('tag_number_hidden').value = tagNumber;
        document.getElementById('tag_office_id_hidden').value = office.id;
        document.getElementById('tag_asset_id_hidden').value = selectedAsset.id;

        // 4. Show the modal
        inventoryTagModal.classList.remove('hidden');
    }

    window.closeInventoryTagModal = function() {
        inventoryTagModal.classList.add('hidden');
        document.getElementById('inventoryTagForm').reset();
    }

    document.getElementById('generateTagForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.action = 'assign_and_generate_tag';
        data.tag_number = data.tag_number; // Rename to match PHP expectation

        const res = await apiRequest('dashboard.php', 'assign_and_generate_tag', data);
        if (res.success) {
            closeGenerateTagModal();
            loadAllAssets(); // Refresh the main asset list
            closeActionsModal(); // Close the actions modal as well
            // Open the printable tag modal
            await openPrintableTagModal(res.tag_id);
        } else {
            Swal.fire('Error!', res.message, 'error');
        }
    });

    document.getElementById('inventoryTagForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await apiRequest('dashboard.php', 'assign_asset_to_office', data);

        if (res.success) {
            Swal.fire('Success', res.message, 'success');
            closeInventoryTagModal();
            loadAllAssets(); // Refresh the asset list to show updated status
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });

    window.closeCodeModal = function() {
        codeModal.classList.add('hidden');
        document.getElementById('codeCanvas').innerHTML = '';
    }

    // --- Printable Tag Modal ---
    const printableTagModal = document.getElementById('printableTagModal');

    window.openPrintableTagModal = async function(tagId) {
        const res = await apiRequest('dashboard.php', 'get_tag_details_for_print', { tag_id: tagId });
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
                width: 120,
                height: 120,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });

            // Generate Barcode - Optimized for thermal printing
            JsBarcode("#pt_barcode", tag.tag_number, {
                format: "CODE128",
                lineColor: "#000000",
                width: 2,
                height: 100,
                displayValue: true,
                fontSize: 18,
                margin: 5,
                background: "#ffffff"
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
        // The @media print CSS will handle the printing layout
        window.print();
    }

    // Close modals with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeActionsModal();
            closeStockModal();
            closeCodeModal();
            closeInventoryTagModal();
            closeOfficeAssetsModal();
            closeGenerateTagModal();
            closePrintableTagModal();
            closeAssignOfficeModal();
        }
    });


    // --- Total Value Calculation ---
    const unitPriceInput = document.getElementById('value');
    const quantityInput = document.getElementById('quantity');
    const totalValueInput = document.getElementById('total_value');

    function calculateTotalValue() {
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const total = unitPrice * quantity;
        totalValueInput.value = total.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
    }

    unitPriceInput.addEventListener('input', calculateTotalValue);
    quantityInput.addEventListener('input', calculateTotalValue);

    // Show individual tracking info when quantity > 1 (automatic, no checkbox needed)
    quantityInput.addEventListener('input', function() {
        const trackingInfo = document.getElementById('individual-tracking-info');
        if (parseInt(this.value) > 1) {
            trackingInfo.style.display = 'block';
        } else {
            trackingInfo.style.display = 'none';
        }
        calculateTotalValue();
    });


    addAssetForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(addAssetForm);
        const data = Object.fromEntries(formData.entries());

        // AUTO-ASSIGN CAMPUS ID - Critical for data integrity
        data.campus_id = window.campusId;

        const quantity = parseInt(data.quantity) || 1;

        const res = await apiRequest('dashboard.php', 'add_asset', data);

        if (res.success) {
            // Backend already creates units automatically for quantity > 1
            // Show enhanced message if units were created
            if (quantity > 1 && res.data && res.data.units_created) {
                Swal.fire({
                    icon: 'success',
                    title: 'Asset Added with Individual Tracking!',
                    html: `<p>Asset created successfully!</p><p class="text-green-600 mt-2"><i class="fas fa-check-circle"></i> ${res.data.units_created} individual units created with unique serial numbers</p>`,
                    confirmButtonText: 'OK'
                });
            } else {
                // Standard success message
                if (res.warning) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Asset Added Successfully!',
                        html: `<p>New asset has been added and is now available in inventory.</p><p class="text-yellow-600 mt-2"><i class="fas fa-exclamation-triangle"></i> ${res.warning}</p>`,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire('Success!', 'New asset has been added and is now available in inventory.', 'success');
                }
            }
            closeAddAssetModal();
            loadAllAssets(); // Refresh the asset list
        } else {
            Swal.fire('Error!', res.message, 'error');
        }
    });

    function getStatusClass(status) {
        if (!status) return 'text-gray-600 bg-gray-200';
        switch (status.toLowerCase()) {
            case 'active': 
            case 'in use':
            case 'pending verification':
            case 'available': return 'text-green-600 bg-green-200';
            case 'in storage':
                return 'text-blue-600 bg-blue-200';
            case 'for verification': return 'text-orange-600 bg-orange-200';
            case 'for repair':
                return 'text-orange-600 bg-orange-200';
            case 'inactive': return 'text-gray-600 bg-gray-200';
            case 'maintenance': return 'text-yellow-800 bg-yellow-200';
            case 'retired': 
            case 'missing':
                return 'text-red-600 bg-red-200';
            case 'declined':
                return 'text-pink-600 bg-pink-200';
            default: return 'text-gray-600 bg-gray-200';
        }
    }

    function getRemarksClass(remarks) {
        const lowerRemarks = (remarks || '').toLowerCase();
        switch (lowerRemarks) {
            case 'working':
            case 'good condition':
                return 'text-green-600 bg-green-200';
            case 'fair condition':
                return 'text-yellow-800 bg-yellow-200';
            case 'poor condition':
            case 'for repair':
            case 'for disposal':
                return 'text-red-600 bg-red-200';
            default:
                return 'text-gray-500 bg-gray-100';
        }
    }


    // Initial load
    showTab('manage-assets');
});
</script>

<!-- Individual Tracking Enhancement -->
<script src="individual_tracking_enhancement.js"></script>

</body>
</html>
