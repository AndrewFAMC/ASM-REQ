<?php
/**
 * Asset Units API
 * Manages individual asset units for detailed tracking
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user = getUserInfo();

try {
    switch ($action) {

        // ============================================================
        // GET UNITS FOR AN ASSET
        // ============================================================
        case 'get_units_for_asset':
            $assetId = (int)($_GET['asset_id'] ?? 0);

            if (!$assetId) {
                throw new Exception('Asset ID is required');
            }

            // Get asset info
            $assetStmt = $pdo->prepare("
                SELECT id, asset_name, quantity, serial_number, track_individually
                FROM assets
                WHERE id = ?
            ");
            $assetStmt->execute([$assetId]);
            $asset = $assetStmt->fetch();

            if (!$asset) {
                throw new Exception('Asset not found');
            }

            // Get all units for this asset
            $unitsStmt = $pdo->prepare("
                SELECT
                    u.*,
                    tu.tag_id,
                    it.tag_number,
                    it.office_id,
                    o.office_name,
                    assigned_user.full_name as assigned_to_name
                FROM asset_units u
                LEFT JOIN tag_units tu ON u.id = tu.unit_id AND tu.is_active = TRUE
                LEFT JOIN inventory_tags it ON tu.tag_id = it.id
                LEFT JOIN offices o ON it.office_id = o.id
                LEFT JOIN users assigned_user ON u.assigned_to_user_id = assigned_user.id
                WHERE u.asset_id = ?
                ORDER BY u.unit_code ASC
            ");
            $unitsStmt->execute([$assetId]);
            $units = $unitsStmt->fetchAll();

            echo json_encode([
                'success' => true,
                'asset' => $asset,
                'units' => $units,
                'total_units' => count($units)
            ]);
            break;


        // ============================================================
        // GET AVAILABLE UNITS (not assigned to any tag)
        // ============================================================
        case 'get_available_units':
            $assetId = (int)($_GET['asset_id'] ?? 0);

            if (!$assetId) {
                throw new Exception('Asset ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT u.*
                FROM asset_units u
                LEFT JOIN tag_units tu ON u.id = tu.unit_id AND tu.is_active = TRUE
                WHERE u.asset_id = ?
                AND tu.id IS NULL
                AND u.unit_status = 'Available'
                ORDER BY u.unit_code ASC
            ");
            $stmt->execute([$assetId]);
            $units = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'units' => $units,
                'count' => count($units)
            ]);
            break;


        // ============================================================
        // CREATE UNITS FOR ASSET
        // ============================================================
        case 'create_units':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Access denied. Custodian or Admin role required.');
            }

            $assetId = (int)($_POST['asset_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);

            if (!$assetId || $quantity <= 0) {
                throw new Exception('Valid asset ID and quantity are required');
            }

            // Check if asset exists
            $assetStmt = $pdo->prepare("SELECT asset_name, serial_number FROM assets WHERE id = ?");
            $assetStmt->execute([$assetId]);
            $asset = $assetStmt->fetch();

            if (!$asset) {
                throw new Exception('Asset not found');
            }

            // Call stored procedure to create units
            $stmt = $pdo->prepare("CALL sp_create_units_for_asset(?, ?, ?)");
            $stmt->execute([$assetId, $quantity, $user['id']]);

            // Enable individual tracking for this asset
            $updateStmt = $pdo->prepare("UPDATE assets SET track_individually = TRUE WHERE id = ?");
            $updateStmt->execute([$assetId]);

            echo json_encode([
                'success' => true,
                'message' => "{$quantity} units created successfully for {$asset['asset_name']}"
            ]);
            break;


        // ============================================================
        // UPDATE UNIT STATUS/CONDITION
        // ============================================================
        case 'update_unit':
            if (!hasRole('custodian') && !hasRole('admin') && !hasRole('office')) {
                throw new Exception('Access denied');
            }

            $unitId = (int)($_POST['unit_id'] ?? 0);
            $unitStatus = $_POST['unit_status'] ?? null;
            $conditionRating = $_POST['condition_rating'] ?? null;
            $notes = $_POST['notes'] ?? null;

            if (!$unitId) {
                throw new Exception('Unit ID is required');
            }

            // Get current unit data
            $currentStmt = $pdo->prepare("SELECT * FROM asset_units WHERE id = ?");
            $currentStmt->execute([$unitId]);
            $currentUnit = $currentStmt->fetch();

            if (!$currentUnit) {
                throw new Exception('Unit not found');
            }

            $pdo->beginTransaction();

            $updates = [];
            $params = [];

            if ($unitStatus !== null) {
                $updates[] = "unit_status = ?";
                $params[] = $unitStatus;

                // Log status change
                $historyStmt = $pdo->prepare("
                    INSERT INTO unit_history (unit_id, action, old_value, new_value, description, performed_by, performed_by_name)
                    VALUES (?, 'STATUS_CHANGED', ?, ?, ?, ?, ?)
                ");
                $historyStmt->execute([
                    $unitId,
                    $currentUnit['unit_status'],
                    $unitStatus,
                    "Status changed from {$currentUnit['unit_status']} to {$unitStatus}",
                    $user['id'],
                    $user['full_name']
                ]);
            }

            if ($conditionRating !== null) {
                $updates[] = "condition_rating = ?";
                $params[] = $conditionRating;

                // Log condition change
                $historyStmt = $pdo->prepare("
                    INSERT INTO unit_history (unit_id, action, old_value, new_value, description, performed_by, performed_by_name)
                    VALUES (?, 'CONDITION_CHANGED', ?, ?, ?, ?, ?)
                ");
                $historyStmt->execute([
                    $unitId,
                    $currentUnit['condition_rating'],
                    $conditionRating,
                    "Condition changed from {$currentUnit['condition_rating']} to {$conditionRating}",
                    $user['id'],
                    $user['full_name']
                ]);
            }

            if ($notes !== null) {
                $updates[] = "notes = ?";
                $params[] = $notes;
            }

            if (!empty($updates)) {
                $params[] = $unitId;
                $sql = "UPDATE asset_units SET " . implode(", ", $updates) . " WHERE id = ?";
                $updateStmt = $pdo->prepare($sql);
                $updateStmt->execute($params);
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Unit updated successfully'
            ]);
            break;


        // ============================================================
        // GET UNITS FOR OFFICE (detailed view)
        // ============================================================
        case 'get_office_units':
            $officeId = (int)($_GET['office_id'] ?? 0);

            if (!$officeId) {
                throw new Exception('Office ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT
                    a.id as asset_id,
                    a.asset_name,
                    it.id as tag_id,
                    it.tag_number,
                    it.status as tag_status,
                    COUNT(DISTINCT u.id) as total_units,
                    SUM(CASE WHEN u.unit_status = 'Available' THEN 1 ELSE 0 END) as available_units,
                    SUM(CASE WHEN u.unit_status = 'In Use' THEN 1 ELSE 0 END) as in_use_units,
                    SUM(CASE WHEN u.unit_status = 'Damaged' THEN 1 ELSE 0 END) as damaged_units,
                    SUM(CASE WHEN u.unit_status = 'Missing' THEN 1 ELSE 0 END) as missing_units,
                    SUM(CASE WHEN u.condition_rating = 'Excellent' THEN 1 ELSE 0 END) as excellent_condition,
                    SUM(CASE WHEN u.condition_rating = 'Good' THEN 1 ELSE 0 END) as good_condition,
                    SUM(CASE WHEN u.condition_rating = 'Fair' THEN 1 ELSE 0 END) as fair_condition,
                    SUM(CASE WHEN u.condition_rating = 'Poor' THEN 1 ELSE 0 END) as poor_condition,
                    GROUP_CONCAT(
                        CONCAT(u.id, ':', u.unit_code, ':', u.unit_status, ':', u.condition_rating)
                        ORDER BY u.unit_code
                        SEPARATOR '|'
                    ) as units_data
                FROM inventory_tags it
                INNER JOIN assets a ON it.asset_id = a.id
                LEFT JOIN tag_units tu ON it.id = tu.tag_id AND tu.is_active = TRUE
                LEFT JOIN asset_units u ON tu.unit_id = u.id
                WHERE it.office_id = ?
                GROUP BY a.id, a.asset_name, it.id, it.tag_number, it.status
                ORDER BY a.asset_name
            ");
            $stmt->execute([$officeId]);
            $tags = $stmt->fetchAll();

            // Parse units_data for each tag
            foreach ($tags as &$tag) {
                $unitsArray = [];
                if ($tag['units_data']) {
                    $unitsRaw = explode('|', $tag['units_data']);
                    foreach ($unitsRaw as $unitData) {
                        list($id, $code, $status, $condition) = explode(':', $unitData);
                        $unitsArray[] = [
                            'id' => $id,
                            'code' => $code,
                            'status' => $status,
                            'condition' => $condition
                        ];
                    }
                }
                $tag['units'] = $unitsArray;
                unset($tag['units_data']);
            }

            echo json_encode([
                'success' => true,
                'office_id' => $officeId,
                'tags' => $tags
            ]);
            break;


        // ============================================================
        // GET UNIT HISTORY
        // ============================================================
        case 'get_unit_history':
            $unitId = (int)($_GET['unit_id'] ?? 0);

            if (!$unitId) {
                throw new Exception('Unit ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT *
                FROM unit_history
                WHERE unit_id = ?
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$unitId]);
            $history = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;


        // ============================================================
        // ENABLE INDIVIDUAL TRACKING FOR ASSET
        // ============================================================
        case 'enable_tracking':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Access denied. Custodian or Admin role required.');
            }

            $assetId = (int)($_POST['asset_id'] ?? 0);
            $autoCreate = filter_var($_POST['auto_create'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$assetId) {
                throw new Exception('Asset ID is required');
            }

            // Get asset details
            $assetStmt = $pdo->prepare("SELECT asset_name, quantity FROM assets WHERE id = ?");
            $assetStmt->execute([$assetId]);
            $asset = $assetStmt->fetch();

            if (!$asset) {
                throw new Exception('Asset not found');
            }

            // Enable tracking
            $updateStmt = $pdo->prepare("UPDATE assets SET track_individually = TRUE WHERE id = ?");
            $updateStmt->execute([$assetId]);

            $message = "Individual tracking enabled for {$asset['asset_name']}";

            // Auto-create units if requested
            if ($autoCreate && $asset['quantity'] > 0) {
                $stmt = $pdo->prepare("CALL sp_create_units_for_asset(?, ?, ?)");
                $stmt->execute([$assetId, $asset['quantity'], $user['id']]);
                $message .= " and {$asset['quantity']} units created";
            }

            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            break;


        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
