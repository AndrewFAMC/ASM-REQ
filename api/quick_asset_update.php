<?php
/**
 * API Endpoint: Quick Asset Update
 * Handles rapid asset updates from the Quick Scan Update interface
 * Supports: status changes, location updates, and note additions
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication and custodian role
requireLogin();

$user = getUserInfo();
if (!hasRole('custodian') && !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Custodian access required.']);
    exit;
}

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Verify CSRF token
if (!isset($input['csrf_token']) || $input['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $input['action'] ?? '';
$assetId = $input['asset_id'] ?? null;

if (!$assetId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Asset ID is required']);
    exit;
}

try {
    // Verify asset exists and belongs to user's campus
    $stmt = $pdo->prepare("SELECT * FROM assets WHERE id = ? AND campus_id = ?");
    $stmt->execute([$assetId, $user['campus_id']]);
    $asset = $stmt->fetch();

    if (!$asset) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Asset not found or access denied']);
        exit;
    }

    switch ($action) {
        case 'update_status':
            handleUpdateStatus($pdo, $assetId, $asset, $input, $user);
            break;

        case 'update_location':
            handleUpdateLocation($pdo, $assetId, $asset, $input, $user);
            break;

        case 'add_notes':
            handleAddNotes($pdo, $assetId, $asset, $input, $user);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }

} catch (Exception $e) {
    error_log("Quick asset update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update asset: ' . $e->getMessage()
    ]);
}

/**
 * Handle status update
 */
function handleUpdateStatus($pdo, $assetId, $asset, $input, $user) {
    $newStatus = $input['status'] ?? '';
    $unitId = $input['unit_id'] ?? null; // Optional: specific unit to update

    // Validate status
    $validStatuses = ['Available', 'Unavailable', 'In Use', 'Damaged', 'Missing', 'Under Repair', 'Retired', 'Disposed'];
    if (!in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit;
    }

    $oldStatus = $asset['status'];

    // Check if asset has individual tracking
    if ($asset['track_individually'] && $unitId) {
        // Update specific unit status
        $unitCheckStmt = $pdo->prepare("SELECT unit_status, unit_code FROM asset_units WHERE id = ? AND asset_id = ?");
        $unitCheckStmt->execute([$unitId, $assetId]);
        $unit = $unitCheckStmt->fetch();

        if (!$unit) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Unit not found']);
            exit;
        }

        $oldUnitStatus = $unit['unit_status'];

        // Update unit status
        $unitStmt = $pdo->prepare("
            UPDATE asset_units
            SET unit_status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $unitStmt->execute([$newStatus, $unitId]);

        // Log to unit history
        $historyStmt = $pdo->prepare("
            INSERT INTO unit_history (unit_id, action, old_value, new_value, description, performed_by, performed_by_name, created_at)
            VALUES (?, 'status_change', ?, ?, ?, ?, ?, NOW())
        ");
        $description = "Status updated via Quick Scan Update";
        $historyStmt->execute([$unitId, $oldUnitStatus, $newStatus, $description, $user['id'], $user['full_name']]);

        // Log to activity log
        $activityStmt = $pdo->prepare("
            INSERT INTO activity_log (asset_id, action, description, performed_by, created_at)
            VALUES (?, 'unit_status_update', ?, ?, NOW())
        ");
        $activityDescription = "Unit {$unit['unit_code']} status changed from '{$oldUnitStatus}' to '{$newStatus}' via Quick Scan Update";
        $activityStmt->execute([$assetId, $activityDescription, $user['full_name']]);

        echo json_encode([
            'success' => true,
            'message' => 'Unit status updated successfully',
            'new_status' => $newStatus,
            'old_status' => $oldUnitStatus,
            'unit_code' => $unit['unit_code']
        ]);

    } else {
        // Update overall asset status (legacy behavior)
        $stmt = $pdo->prepare("
            UPDATE assets
            SET status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $assetId]);

        // Log to activity log
        $activityStmt = $pdo->prepare("
            INSERT INTO activity_log (asset_id, action, description, performed_by, created_at)
            VALUES (?, 'status_update', ?, ?, NOW())
        ");
        $description = "Status changed from '{$oldStatus}' to '{$newStatus}' via Quick Scan Update";
        $activityStmt->execute([$assetId, $description, $user['full_name']]);

        echo json_encode([
            'success' => true,
            'message' => 'Asset status updated successfully',
            'new_status' => $newStatus,
            'old_status' => $oldStatus
        ]);
    }

    // Log scan (common for both)
    $scanStmt = $pdo->prepare("
        INSERT INTO asset_scans (asset_id, scan_type, scanned_by, notes, created_at)
        VALUES (?, 'Status Check', ?, ?, NOW())
    ");
    $scanStmt->execute([$assetId, $user['full_name'], "Status updated to: {$newStatus}"]);
}

/**
 * Handle location update
 */
function handleUpdateLocation($pdo, $assetId, $asset, $input, $user) {
    $newLocation = trim($input['location'] ?? '');

    if (empty($newLocation)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Location cannot be empty']);
        exit;
    }

    $oldLocation = $asset['location'] ?? 'Not Set';

    // Update asset location
    $stmt = $pdo->prepare("
        UPDATE assets
        SET location = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newLocation, $assetId]);

    // Log to activity log
    $activityStmt = $pdo->prepare("
        INSERT INTO activity_log (asset_id, action, description, performed_by, created_at)
        VALUES (?, 'location_update', ?, ?, NOW())
    ");
    $description = "Location changed from '{$oldLocation}' to '{$newLocation}' via Quick Scan Update";
    $activityStmt->execute([$assetId, $description, $user['full_name']]);

    // Log scan with location update
    $scanStmt = $pdo->prepare("
        INSERT INTO asset_scans (asset_id, scan_type, scanned_by, scan_location, notes, created_at)
        VALUES (?, 'Location Update', ?, ?, ?, NOW())
    ");
    $scanStmt->execute([
        $assetId,
        $user['full_name'],
        $newLocation,
        "Location updated via Quick Scan"
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully',
        'new_location' => $newLocation,
        'old_location' => $oldLocation
    ]);
}

/**
 * Handle adding notes/remarks
 */
function handleAddNotes($pdo, $assetId, $asset, $input, $user) {
    $notes = trim($input['notes'] ?? '');

    if (empty($notes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notes cannot be empty']);
        exit;
    }

    // Append to existing remarks or create new
    $existingRemarks = $asset['remarks'] ?? '';
    $timestamp = date('Y-m-d H:i:s');
    $newRemarks = $existingRemarks
        ? $existingRemarks . "\n\n[{$timestamp}] {$user['full_name']}:\n{$notes}"
        : "[{$timestamp}] {$user['full_name']}:\n{$notes}";

    // Update asset remarks
    $stmt = $pdo->prepare("
        UPDATE assets
        SET remarks = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newRemarks, $assetId]);

    // Log to activity log
    $activityStmt = $pdo->prepare("
        INSERT INTO activity_log (asset_id, action, description, performed_by, created_at)
        VALUES (?, 'notes_added', ?, ?, NOW())
    ");
    $description = "Notes added via Quick Scan Update: " . substr($notes, 0, 100) . (strlen($notes) > 100 ? '...' : '');
    $activityStmt->execute([$assetId, $description, $user['full_name']]);

    // Log scan
    $scanStmt = $pdo->prepare("
        INSERT INTO asset_scans (asset_id, scan_type, scanned_by, notes, created_at)
        VALUES (?, 'Status Check', ?, ?, NOW())
    ");
    $scanStmt->execute([$assetId, $user['full_name'], "Notes added: " . substr($notes, 0, 50)]);

    echo json_encode([
        'success' => true,
        'message' => 'Notes saved successfully',
        'notes' => $notes
    ]);
}
?>
