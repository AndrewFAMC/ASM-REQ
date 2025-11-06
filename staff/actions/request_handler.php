<?php
// Staff Request Handler - Manages asset requests from staff
require_once '../../config.php';

/**
 * Create asset request
 */
function createAssetRequest($pdo, $data) {
    try {
        $pdo->beginTransaction();

        $user = getUserInfo();
        $requestedBy = $user['id'];
        $campusId = $user['campus_id'];

        // Get asset details
        $asset = fetchOne($pdo, 
            "SELECT id, asset_name, category_id FROM assets WHERE id = ? AND campus_id = ?",
            [$data['asset_id'], $campusId]
        );

        if (!$asset) {
            throw new Exception("Asset not found in your campus");
        }

        // Create request
        $stmt = $pdo->prepare("
            INSERT INTO asset_requests (asset_id, requested_by, campus_id, category_id, description, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $data['asset_id'],
            $requestedBy,
            $campusId,
            $asset['category_id'],
            $data['description'] ?? ''
        ]);

        $requestId = $pdo->lastInsertId();

        // Log activity
        logActivity($pdo, $asset['id'], 'REQUEST_CREATED', 
            "Asset request created by staff: {$asset['asset_name']}");

        $pdo->commit();

        return [
            'id' => $requestId,
            'asset_name' => $asset['asset_name'],
            'status' => 'pending'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get my asset requests
 */
function getMyAssetRequests($pdo) {
    $user = getUserInfo();
    $userId = $user['id'];
    $campusId = $user['campus_id'];

    $sql = "SELECT ar.*, a.asset_name, a.serial_number, c.category_name, 
                   u_admin.full_name as approved_by_name
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            LEFT JOIN users u_admin ON ar.approved_by = u_admin.id
            WHERE ar.requested_by = ? AND ar.campus_id = ?
            ORDER BY ar.created_at DESC";

    return fetchAll($pdo, $sql, [$userId, $campusId]);
}

/**
 * Get my approved requests (ready to pick up)
 */
function getMyApprovedRequests($pdo) {
    $user = getUserInfo();
    $userId = $user['id'];
    $campusId = $user['campus_id'];

    $sql = "SELECT ar.*, a.asset_name, a.serial_number, c.category_name
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            WHERE ar.requested_by = ? AND ar.campus_id = ? AND ar.status = 'approved'
            ORDER BY ar.created_at DESC";

    return fetchAll($pdo, $sql, [$userId, $campusId]);
}

/**
 * Get my released assets (currently using)
 */
function getMyReleasedAssets($pdo) {
    $user = getUserInfo();
    $userId = $user['id'];
    $campusId = $user['campus_id'];

    $sql = "SELECT ar.*, a.asset_name, a.serial_number, c.category_name
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            WHERE ar.requested_by = ? AND ar.campus_id = ? AND ar.status = 'released'
            ORDER BY ar.released_date DESC";

    return fetchAll($pdo, $sql, [$userId, $campusId]);
}

/**
 * Get request by receipt code (for staff to verify)
 */
function getRequestByReceiptCode($pdo, $receiptCode) {
    $user = getUserInfo();
    $userId = $user['id'];
    $campusId = $user['campus_id'];

    $sql = "SELECT ar.*, a.asset_name, a.serial_number, a.barcode, c.category_name
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            WHERE ar.receipt_code = ? AND ar.requested_by = ? AND ar.campus_id = ? AND ar.status = 'approved'
            LIMIT 1";

    return fetchOne($pdo, $sql, [$receiptCode, $userId, $campusId]);
}
?>
