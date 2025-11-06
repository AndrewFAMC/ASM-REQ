<?php
// Admin Request Handler - Manages asset requests from staff
require_once __DIR__ . '/../../config.php';

/**
 * Generate unique receipt code for asset request
 */
function generateReceiptCode($pdo) {
    $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
    
    // Check if code already exists
    $stmt = $pdo->prepare("SELECT id FROM asset_requests WHERE receipt_code = ?");
    $stmt->execute([$code]);
    
    if ($stmt->fetch()) {
        // Recursively generate new code if exists
        return generateReceiptCode($pdo);
    }
    
    return $code;
}

/**
 * Approve asset request and generate receipt code
 */
function approveAssetRequestWithCode($pdo, $requestId, $adminNotes = '') {
    try {
        $pdo->beginTransaction();

        // Get request details
        $stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            throw new Exception("Request not found");
        }

        if ($request['status'] !== 'pending') {
            throw new Exception("Request is not in pending status");
        }

        // Check for sufficient quantity before approving
        $asset = fetchOne($pdo, "SELECT quantity FROM assets WHERE id = ?", [$request['asset_id'] ?? null]);
        if (!$asset || $asset['quantity'] < $request['quantity']) {
            throw new Exception("Not enough stock to approve this request. Available: " . ($asset['quantity'] ?? 0) . ", Requested: " . $request['quantity']);
        }

        // Generate receipt code
        $receiptCode = generateReceiptCode($pdo);

        // Get current user (admin)
        $user = getUserInfo();
        $approvedBy = $user['id'];

        // Update request status to approved with receipt code
        $stmt = $pdo->prepare("
            UPDATE asset_requests
            SET status = 'approved', 
                approved_by = ?, 
                approved_at = NOW(), 
                receipt_code = ?,
                admin_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$approvedBy, $receiptCode, $adminNotes, $requestId]);

        logActivity($pdo, $request['asset_id'], 'STOCK_RESERVED', "Stock of {$request['quantity']} reserved for request #{$requestId}");

        // Log activity
        logActivity($pdo, $request['asset_id'], 'REQUEST_APPROVED', 
            "Asset request approved by admin. Receipt Code: {$receiptCode}");

        $pdo->commit();

        return [
            'success' => true,
            'receipt_code' => $receiptCode,
            'message' => 'Request approved successfully'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Reject asset request
 */
function rejectAssetRequest($pdo, $requestId, $reason = 'No reason provided.') {
    try {
        $pdo->beginTransaction();

        $user = getUserInfo();
        $approvedBy = $user['id'];

        // Get request details for logging and potential stock return
        $request = fetchOne($pdo, "SELECT * FROM asset_requests WHERE id = ?", [$requestId]);
        if (!$request) {
            throw new Exception("Request not found or already processed.");
        }

        // If the request was already approved, we need to return the stock
        if ($request['status'] === 'approved') {
            executeQuery($pdo, "UPDATE assets SET quantity = quantity + ? WHERE id = ?", [$request['quantity'], $request['asset_id']]);
            logActivity($pdo, $request['asset_id'], 'STOCK_RETURNED', "Stock of {$request['quantity']} returned to inventory due to rejected request #{$requestId}.");
        }

        $stmt = $pdo->prepare("
            UPDATE asset_requests
            SET status = 'rejected', 
                approved_by = ?, 
                approved_at = NOW(), 
                admin_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$approvedBy, $reason, $requestId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Request not found or not in pending status");
        }

        // Log activity
        logActivity($pdo, $request['asset_id'], 'REQUEST_REJECTED', 
            "Asset request rejected by admin. Reason: " . $reason);

        $pdo->commit();
        return ['success' => true, 'message' => 'Request rejected successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get pending asset requests
 */
function getPendingAssetRequests($pdo, $campusId = null) {
    $sql = "SELECT ar.*, u.full_name as requested_by_name, u.email as requested_by_email, 
                   a.asset_name, a.serial_number, c.category_name, cam.campus_name
            FROM asset_requests ar
            JOIN users u ON ar.requested_by = u.id
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON ar.campus_id = cam.id
            WHERE ar.status = 'pending'";
    
    $params = [];
    
    if ($campusId) {
        $sql .= " AND ar.campus_id = ?";
        $params[] = $campusId;
    }
    
    $sql .= " ORDER BY ar.created_at DESC";
    
    return fetchAll($pdo, $sql, $params);
}

/**
 * Get approved asset requests (ready for custodian release)
 */
function getApprovedAssetRequests($pdo, $campusId = null) {
    $sql = "SELECT ar.*, u.full_name as requested_by_name, u.email as requested_by_email, 
                   a.asset_name, a.serial_number, c.category_name, cam.campus_name
            FROM asset_requests ar
            JOIN users u ON ar.requested_by = u.id
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON ar.campus_id = cam.id
            WHERE ar.status = 'approved'";
    
    $params = [];
    
    if ($campusId) {
        $sql .= " AND ar.campus_id = ?";
        $params[] = $campusId;
    }
    
    $sql .= " ORDER BY ar.created_at DESC";
    
    return fetchAll($pdo, $sql, $params);
}

/**
 * Get all asset requests with filters
 */
function getAllAssetRequests($pdo, $filters = []) {
    $sql = "SELECT ar.*, u.full_name as requested_by_name, u.email as requested_by_email, 
                   a.asset_name, a.serial_number, c.category_name, cam.campus_name
            FROM asset_requests ar
            JOIN users u ON ar.requested_by = u.id
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON ar.campus_id = cam.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND ar.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['campus_id'])) {
        $sql .= " AND ar.campus_id = ?";
        $params[] = $filters['campus_id'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND ar.created_at >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND ar.created_at <= ?";
        $params[] = $filters['date_to'];
    }
    
    $sql .= " ORDER BY ar.created_at DESC";
    
    return fetchAll($pdo, $sql, $params);
}
?>
