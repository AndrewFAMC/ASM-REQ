<?php
/**
 * API Endpoint: Barcode/Asset Lookup
 * Returns complete asset details including maintenance history, borrowing history, and current status
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication
requireLogin();

header('Content-Type: application/json');

$searchTerm = $_GET['search'] ?? '';

if (empty($searchTerm)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

try {
    // Search for asset by barcode, serial number, tag number, unit code, or asset name
    // First try to find by tag number in inventory_tags table
    $tagStmt = $pdo->prepare("
        SELECT a.id as asset_id
        FROM inventory_tags it
        JOIN assets a ON it.asset_id = a.id
        WHERE a.campus_id = ?
        AND it.tag_number LIKE ?
        LIMIT 1
    ");

    $searchPattern = "%{$searchTerm}%";
    $tagStmt->execute([$campusId, $searchPattern]);
    $tagResult = $tagStmt->fetch();

    // If found by tag number, use that asset_id, otherwise search normally
    $assetIdFromTag = $tagResult ? $tagResult['asset_id'] : null;

    // ENHANCEMENT: Search by unit code or unit serial number
    $unitStmt = $pdo->prepare("
        SELECT au.asset_id, au.id as unit_id, au.unit_code, au.unit_serial_number
        FROM asset_units au
        JOIN assets a ON au.asset_id = a.id
        WHERE a.campus_id = ?
        AND (au.unit_code LIKE ? OR au.unit_serial_number LIKE ?)
        LIMIT 1
    ");
    $unitStmt->execute([$campusId, $searchPattern, $searchPattern]);
    $unitResult = $unitStmt->fetch();

    // If found by unit, use that asset_id and store unit info
    $assetIdFromUnit = $unitResult ? $unitResult['asset_id'] : null;
    $unitInfo = $unitResult ?: null;

    $stmt = $pdo->prepare("
        SELECT
            a.*,
            c.category_name,
            cam.campus_name,
            o.office_name,
            b.building_name,
            r.room_name,
            br.name as brand_name
        FROM assets a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN campuses cam ON a.campus_id = cam.id
        LEFT JOIN offices o ON a.office_id = o.id
        LEFT JOIN rooms r ON a.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN brands br ON a.brand_id = br.id
        WHERE a.campus_id = ?
        AND (
            a.barcode LIKE ? OR
            a.serial_number LIKE ? OR
            LOWER(a.asset_name) LIKE ? OR
            a.id = ? OR
            a.id = ? OR
            a.id = ?
        )
        LIMIT 1
    ");

    $assetId = is_numeric($searchTerm) ? (int)$searchTerm : 0;

    $stmt->execute([
        $campusId,
        $searchPattern,
        $searchPattern,
        strtolower($searchPattern),
        $assetId,
        $assetIdFromTag,
        $assetIdFromUnit
    ]);

    $asset = $stmt->fetch();

    if (!$asset) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Asset not found. Please check the barcode or serial number and try again.'
        ]);
        exit;
    }

    // Get maintenance history
    $maintenanceStmt = $pdo->prepare("
        SELECT
            id,
            maintenance_type,
            maintenance_date,
            description,
            performed_by,
            cost,
            status,
            next_maintenance_date,
            notes,
            created_at
        FROM asset_maintenance
        WHERE asset_id = ?
        ORDER BY maintenance_date DESC
        LIMIT 10
    ");
    $maintenanceStmt->execute([$asset['id']]);
    $maintenanceHistory = $maintenanceStmt->fetchAll();

    // Get borrowing history
    $borrowingStmt = $pdo->prepare("
        SELECT
            ar.id,
            ar.status,
            ar.request_date,
            ar.released_date,
            ar.expected_return_date,
            ar.returned_date,
            ar.days_overdue,
            ar.return_condition,
            ar.quantity,
            u.full_name as requester_name,
            u.email as requester_email,
            released_by.full_name as released_by_name,
            returned_by.full_name as returned_by_name
        FROM asset_requests ar
        JOIN users u ON ar.requester_id = u.id
        LEFT JOIN users released_by ON ar.released_by = released_by.id
        LEFT JOIN users returned_by ON ar.returned_by = returned_by.id
        WHERE ar.asset_id = ?
        AND ar.status IN ('released', 'returned')
        ORDER BY ar.released_date DESC
        LIMIT 10
    ");
    $borrowingStmt->execute([$asset['id']]);
    $borrowingHistory = $borrowingStmt->fetchAll();

    // Get current borrowing (if any)
    $currentBorrowingStmt = $pdo->prepare("
        SELECT
            ar.*,
            u.full_name as requester_name,
            u.email as requester_email
        FROM asset_requests ar
        JOIN users u ON ar.requester_id = u.id
        WHERE ar.asset_id = ?
        AND ar.status = 'released'
        LIMIT 1
    ");
    $currentBorrowingStmt->execute([$asset['id']]);
    $currentBorrowing = $currentBorrowingStmt->fetch() ?: null;

    // Get recent scans
    $scansStmt = $pdo->prepare("
        SELECT
            id,
            scan_type,
            scanned_by,
            scan_location,
            notes,
            created_at
        FROM asset_scans
        WHERE asset_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $scansStmt->execute([$asset['id']]);
    $recentScans = $scansStmt->fetchAll();

    // Get activity log
    $activityStmt = $pdo->prepare("
        SELECT
            id,
            action,
            description,
            performed_by,
            created_at
        FROM activity_log
        WHERE asset_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $activityStmt->execute([$asset['id']]);
    $activityLog = $activityStmt->fetchAll();

    // Calculate depreciation info
    $depreciationInfo = null;
    if ($asset['original_value'] && $asset['original_value'] > 0) {
        $monthsSincePurchase = 0;
        if ($asset['purchase_date']) {
            $purchaseDate = new DateTime($asset['purchase_date']);
            $now = new DateTime();
            $diff = $purchaseDate->diff($now);
            $monthsSincePurchase = ($diff->y * 12) + $diff->m;
        }

        $depreciationInfo = [
            'original_value' => (float)$asset['original_value'],
            'current_value' => (float)($asset['current_value'] ?? $asset['original_value']),
            'total_depreciation' => (float)($asset['original_value'] - ($asset['current_value'] ?? $asset['original_value'])),
            'depreciation_rate' => (float)$asset['depreciation_rate'],
            'age_months' => $monthsSincePurchase,
            'age_years' => round($monthsSincePurchase / 12, 1),
            'last_depreciation_date' => $asset['last_depreciation_date']
        ];
    }

    // Log this scan
    $logScanStmt = $pdo->prepare("
        INSERT INTO asset_scans (asset_id, scan_type, scanned_by, notes, created_at)
        VALUES (?, 'Status Check', ?, 'Scanned via barcode lookup', NOW())
    ");
    $logScanStmt->execute([$asset['id'], $user['full_name']]);

    // Get full unit info if asset has individual tracking
    $unitsData = [];
    if ($asset['track_individually']) {
        $unitsStmt = $pdo->prepare("
            SELECT id, unit_code, unit_serial_number, unit_status, condition_rating, assigned_to_office
            FROM asset_units
            WHERE asset_id = ?
            ORDER BY unit_code
        ");
        $unitsStmt->execute([$asset['id']]);
        $unitsData = $unitsStmt->fetchAll();
    }

    // Return comprehensive asset data
    echo json_encode([
        'success' => true,
        'asset' => [
            'id' => $asset['id'],
            'asset_name' => $asset['asset_name'],
            'barcode' => $asset['barcode'],
            'serial_number' => $asset['serial_number'],
            'status' => $asset['status'],
            'category' => $asset['category_name'],
            'brand' => $asset['brand_name'],
            'quantity' => $asset['quantity'],
            'inactive_quantity' => $asset['inactive_quantity'],
            'value' => $asset['value'],
            'description' => $asset['description'],
            'purchase_date' => $asset['purchase_date'],
            'location' => $asset['location'],
            'office' => $asset['office_name'],
            'room' => $asset['room_name'],
            'building' => $asset['building_name'],
            'campus' => $asset['campus_name'],
            'assigned_to' => $asset['assigned_to'],
            'assigned_email' => $asset['assigned_email'],
            'assignment_date' => $asset['assignment_date'],
            'remarks' => $asset['remarks'],
            'supplier' => $asset['supplier'],
            'created_at' => $asset['created_at'],
            'updated_at' => $asset['updated_at'],
            'track_individually' => $asset['track_individually']
        ],
        'unit_searched' => $unitInfo,  // The specific unit that was searched (if applicable)
        'all_units' => $unitsData,     // All units for this asset (if individually tracked)
        'depreciation' => $depreciationInfo,
        'current_borrowing' => $currentBorrowing,
        'maintenance_history' => $maintenanceHistory,
        'borrowing_history' => $borrowingHistory,
        'recent_scans' => $recentScans,
        'activity_log' => $activityLog
    ]);

} catch (Exception $e) {
    error_log("Error in barcode lookup: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to lookup asset: ' . $e->getMessage()
    ]);
}
?>
