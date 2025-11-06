<?php
require_once __DIR__ . '/../../config.php';

// Custodian-specific functions

function createAsset($pdo, $data) {
    try {
        $pdo->beginTransaction();

        // Get current user info
        $user = getUserInfo();
        $createdBy = $user['id'];
        $campusId = $user['campus_id'];
        $userEmail = $user['email'];

        // Check if serial number already exists
        if (!empty($data['serial_number'])) {
            $stmt = $pdo->prepare("SELECT id FROM assets WHERE serial_number = ?");
            $stmt->execute([$data['serial_number']]);
            if ($stmt->fetch()) {
                throw new Exception("Serial number already exists");
            }
        }

        // Validate required fields
        if (empty($data['asset_name']) || empty($data['category_id'])) {
            throw new Exception("Asset name and category are required");
        }

        // Insert asset - auto-assign to current custodian
        $stmt = $pdo->prepare("
            INSERT INTO assets (asset_name, serial_number, category_id, campus_id, location, purchase_date, value, status, description, assigned_to, assigned_email, assignment_date, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, NOW(), ?, NOW())
        ");
        $stmt->execute([
            $data['asset_name'],
            $data['serial_number'] ?? null,
            $data['category_id'],
            $campusId,
            $data['location'] ?? '',
            $data['purchase_date'] ?? null,
            $data['value'] ?? 0,
            $data['description'] ?? '',
            $createdBy,
            $userEmail,
            $createdBy
        ]);

        $assetId = $pdo->lastInsertId();

        // Generate barcode data (using serial number or asset ID)
        $barcodeData = $data['serial_number'] ?? (string)$assetId;

        // Update asset with barcode
        $stmt = $pdo->prepare("UPDATE assets SET barcode = ? WHERE id = ?");
        $stmt->execute([$barcodeData, $assetId]);

        // Log activity
        logActivity($pdo, $assetId, 'CREATED', "Asset created by custodian: " . $data['asset_name']);

        // Add to assignment history
        $assignSql = "INSERT INTO asset_assignments (asset_id, assigned_to, assigned_email, assignment_date) VALUES (?, ?, ?, NOW())";
        executeQuery($pdo, $assignSql, [$assetId, $createdBy, $userEmail]);

        $pdo->commit();
        return ['id' => $assetId, 'barcode' => $barcodeData];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating asset: " . $e->getMessage());
        throw $e;
    }
}

function getMyAssets($pdo, $filters = []) {
    $user = getUserInfo();
    $userId = $user['id'];
    $campusId = $user['campus_id'];

    $page = (int)($filters['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT a.*, c.category_name, cam.campus_name, cam.campus_code
            FROM assets a
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON a.campus_id = cam.id
            WHERE a.assigned_to = ?";

    $params = [$userId];
    $conditions = [];

    if (!empty($filters['category'])) {
        $conditions[] = "c.category_name = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['status'])) {
        $conditions[] = "a.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $conditions[] = "(a.asset_name LIKE ? OR a.serial_number LIKE ? OR a.location LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($conditions) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY a.assignment_date DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    return fetchAll($pdo, $sql, $params);
}

function updateAssetStatus($pdo, $data) {
    $assetId = $data['asset_id'];
    $newStatus = $data['status'];
    $notes = $data['notes'] ?? '';

    $user = getUserInfo();
    $userId = $user['id'];

    // Check if asset is assigned to this custodian
    $asset = fetchOne($pdo, "SELECT id, asset_name, assigned_to FROM assets WHERE id = ?", [$assetId]);
    if (!$asset || $asset['assigned_to'] != $userId) {
        throw new Exception('Asset not found or not assigned to you');
    }

    $sql = "UPDATE assets SET status = ?, updated_at = NOW() WHERE id = ?";
    executeQuery($pdo, $sql, [$newStatus, $assetId]);

    $description = "Asset status updated to {$newStatus} by custodian" . ($notes ? " - Notes: {$notes}" : "");
    logActivity($pdo, $assetId, 'STATUS_UPDATED', $description);

    return ['success' => true];
}

function logMaintenance($pdo, $data) {
    $assetId = $data['asset_id'];
    $maintenanceType = $data['maintenance_type'];
    $description = $data['description'];
    $cost = $data['cost'] ?? 0;

    $user = getUserInfo();
    $userId = $user['id'];

    // Check if asset is assigned to this custodian
    $asset = fetchOne($pdo, "SELECT id, asset_name, assigned_to FROM assets WHERE id = ?", [$assetId]);
    if (!$asset || $asset['assigned_to'] != $userId) {
        throw new Exception('Asset not found or not assigned to you');
    }

    $sql = "INSERT INTO maintenance_log (asset_id, maintenance_type, description, cost, performed_by, performed_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    executeQuery($pdo, $sql, [$assetId, $maintenanceType, $description, $cost, $userId]);

    // Update asset status if needed
    if ($maintenanceType === 'repair') {
        $updateSql = "UPDATE assets SET status = 'Active', updated_at = NOW() WHERE id = ?";
        executeQuery($pdo, $updateSql, [$assetId]);
    }

    $description = "Maintenance logged: {$maintenanceType} - {$description}";
    logActivity($pdo, $assetId, 'MAINTENANCE', $description);

    return ['success' => true];
}

function getMaintenanceHistory($pdo, $assetId) {
    $user = getUserInfo();
    $userId = $user['id'];

    // Check if asset is assigned to this custodian
    $asset = fetchOne($pdo, "SELECT id, assigned_to FROM assets WHERE id = ?", [$assetId]);
    if (!$asset || $asset['assigned_to'] != $userId) {
        throw new Exception('Asset not found or not assigned to you');
    }

    $sql = "SELECT ml.*, u.full_name as performed_by_name
            FROM maintenance_log ml
            LEFT JOIN users u ON ml.performed_by = u.id
            WHERE ml.asset_id = ?
            ORDER BY ml.performed_at DESC";

    return fetchAll($pdo, $sql, [$assetId]);
}

function getCustodianStats($pdo) {
    $user = getUserInfo();
    $userId = $user['id'];
    $campusId = $user['campus_id'];

    $sql = "SELECT
                COUNT(*) as total_assets,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_assets,
                SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                SUM(CASE WHEN status = 'Retired' THEN 1 ELSE 0 END) as retired_assets
            FROM assets WHERE assigned_to = ?";

    $stats = fetchOne($pdo, $sql, [$userId]);

    // Get maintenance count for this month
    $maintenanceSql = "SELECT COUNT(*) as maintenance_this_month
                       FROM maintenance_log ml
                       JOIN assets a ON ml.asset_id = a.id
                       WHERE a.assigned_to = ? AND MONTH(ml.performed_at) = MONTH(CURRENT_DATE())
                       AND YEAR(ml.performed_at) = YEAR(CURRENT_DATE())";

    $maintenanceStats = fetchOne($pdo, $maintenanceSql, [$userId]);

    return array_merge($stats, $maintenanceStats);
}

function getCustodianActivities($pdo, $limit = 10) {
    $user = getUserInfo();
    $userId = $user['id'];

    $sql = "SELECT al.*, a.asset_name, cam.campus_name
            FROM activity_log al
            LEFT JOIN assets a ON al.asset_id = a.id
            LEFT JOIN campuses cam ON a.campus_id = cam.id
            WHERE al.performed_by = ?
            ORDER BY al.created_at DESC LIMIT ?";

    return fetchAll($pdo, $sql, [$user['username'], $limit]);
}
?>
