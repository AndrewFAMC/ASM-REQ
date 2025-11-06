<?php
require_once __DIR__ . '/../../config.php';

function requestAsset($pdo, $data) {
    $user = getUserInfo();
    if (empty($user['id']) || empty($user['campus_id'])) {
        throw new Exception("User or campus information is missing.");
    }
    if (empty($data['asset_name']) || empty($data['justification'])) {
        throw new Exception("Asset name and justification are required.");
    }
    
    // This is a general request, so it's not tied to a specific asset ID yet.
    // We will insert it into a different table or a table with nullable asset_id.
    // For now, let's assume a simple log. A dedicated table `general_requests` would be better.
    $sql = "INSERT INTO activity_log (asset_id, action, description, performed_by, campus_id, created_at) 
            VALUES (NULL, 'GENERAL_REQUEST', ?, ?, ?, NOW())";
    
    $params = [
        "Asset: {$data['asset_name']}, Justification: {$data['justification']}",
        $user['full_name'],
        $user['campus_id']
    ];
    
    executeQuery($pdo, $sql, $params);
    
    return ['request_id' => $pdo->lastInsertId()];
}

function returnAsset($pdo, $data) {
    $user = getUserInfo();
    $userId = $user['id'] ?? null;
    $assetId = (int)$data['asset_id'];
    $returnNotes = $data['return_notes'] ?? '';
    $returnQuantity = (int)($data['return_quantity'] ?? 1);

    if (!$userId) {
        throw new Exception("User session not found. Please log in again.");
    }
    if ($returnQuantity <= 0) {
        throw new Exception("Return quantity must be a positive number.");
    }
    
    $pdo->beginTransaction();
    try {
        // Find the active assignment for this user and asset, and lock the row for the transaction
        $assignment = fetchOne($pdo, 
            "SELECT * FROM asset_assignments WHERE asset_id = ? AND assigned_to_id = ? AND return_date IS NULL FOR UPDATE", 
            [$assetId, $userId]
        );

        if (!$assignment) {
            throw new Exception('No active assignment found for this asset in your name.');
        }

        if ($returnQuantity > $assignment['quantity']) {
            throw new Exception("Cannot return more than the assigned quantity of {$assignment['quantity']}.");
        }

        $remainingQuantity = $assignment['quantity'] - $returnQuantity;

        if ($remainingQuantity > 0) {
            // Update the quantity on the existing assignment
            executeQuery($pdo, "UPDATE asset_assignments SET quantity = ? WHERE id = ?", [$remainingQuantity, $assignment['id']]);
            // For partial returns, we might not want to add back to the main asset's "available" pool
            // but rather keep it under the user's name. If the goal is to make it available, the next line is correct.
            // For now, let's assume returning it makes it available again.
            executeQuery($pdo, "UPDATE assets SET quantity = quantity + ? WHERE id = ?", [$returnQuantity, $assetId]);
        } else {
            // Mark the assignment as fully returned and add quantity back to the main asset.
            executeQuery($pdo, "UPDATE assets SET quantity = quantity + ? WHERE id = ?", [$returnQuantity, $assetId]);
            executeQuery($pdo, "UPDATE asset_assignments SET return_date = NOW(), quantity = 0 WHERE id = ?", [$assignment['id']]);
        }

        $description = "{$returnQuantity} unit(s) of asset returned by staff. " . ($remainingQuantity > 0 ? "{$remainingQuantity} remaining." : "Fully returned.") . ($returnNotes ? " Notes: {$returnNotes}" : "");
        logActivity($pdo, $assetId, 'RETURNED', $description);

        $pdo->commit();
        return ['return_date' => date('Y-m-d'), 'remaining_quantity' => $remainingQuantity];
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getMyAssets($pdo, $campusId = null, $filters = []) {
    $campusId = $_SESSION['campus_id'] ?? null;
    $userEmail = $_SESSION['email'] ?? null;
    $page = (int)($filters['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;

    if (!$userEmail) {
        return [];
    }

    $sql = "SELECT a.*, aa.quantity as assigned_quantity, aa.assignment_date, c.category_name, cam.campus_name, cam.campus_code
            FROM asset_assignments aa
            JOIN assets a ON aa.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON a.campus_id = cam.id
            WHERE aa.assigned_to_id = ? AND aa.return_date IS NULL AND aa.quantity > 0";

    $params = [$_SESSION['user_id']];
    $conditions = [];

    if ($campusId) {
        $conditions[] = "a.campus_id = ?";
        $params[] = $campusId;
    }

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

    $sql .= " ORDER BY aa.assignment_date DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    return fetchAll($pdo, $sql, $params);
}

function getAvailableAssets($pdo, $campusId = null, $filters = []) {
    $campusId = $_SESSION['campus_id'] ?? null;
    $page = (int)($filters['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT a.*, c.category_name, cam.campus_name, cam.campus_code
            FROM assets a
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON a.campus_id = cam.id
            WHERE a.status = 'Active' AND a.quantity > 0";

    $params = [];
    $conditions = [];

    if ($campusId) {
        $conditions[] = "a.campus_id = ?";
        $params[] = $campusId;
    }

    if (!empty($filters['category'])) {
        $conditions[] = "c.category_name = ?";
        $params[] = $filters['category'];
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

    $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    return fetchAll($pdo, $sql, $params);
}

function getStaffStats($pdo, $campusId = null) {
    $campusId = $_SESSION['campus_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        return ['my_assets' => 0, 'available_assets' => 0, 'borrowed_out' => 0, 'maintenance' => 0];
    }
    
    // Get my assets count
    $myAssetsSql = "SELECT COALESCE(SUM(quantity), 0) as my_assets FROM asset_assignments WHERE assigned_to_id = ? AND return_date IS NULL";
    $myAssetsParams = [$userId];
    $myAssetsResult = fetchOne($pdo, $myAssetsSql, $myAssetsParams);
    
    // Get available assets count
    $availableSql = "SELECT COUNT(*) as available_assets FROM assets WHERE status = 'Active' AND quantity > 0";
    $availableParams = [];
    if ($campusId) {
        $availableSql .= " AND campus_id = ?";
        $availableParams[] = $campusId;
    }
    $availableResult = fetchOne($pdo, $availableSql, $availableParams);
    
    // Get borrowed out count (assets I've lent out)
    $borrowedSql = "SELECT COUNT(ab.id) as borrowed_out 
                    FROM asset_borrowings ab
                    JOIN asset_assignments aa ON ab.asset_id = aa.asset_id
                    WHERE ab.status = 'active' AND aa.assigned_to_id = ? AND aa.return_date IS NULL";
    $borrowedParams = [$userId];
    $borrowedResult = fetchOne($pdo, $borrowedSql, $borrowedParams);
    
    // Get maintenance count
    $maintenanceSql = "SELECT COUNT(*) as maintenance FROM assets WHERE status = 'Maintenance'";
    $maintenanceParams = [];
    if ($campusId) {
        $maintenanceSql .= " AND campus_id = ?";
        $maintenanceParams[] = $campusId;
    }
    $maintenanceResult = fetchOne($pdo, $maintenanceSql, $maintenanceParams);
    
    return [
        'my_assets' => $myAssetsResult['my_assets'] ?? 0,
        'available_assets' => $availableResult['available_assets'] ?? 0,
        'borrowed_out' => $borrowedResult['borrowed_out'] ?? 0,
        'maintenance' => $maintenanceResult['maintenance'] ?? 0
    ];
}

function getMyActivities($pdo, $campusId = null, $limit = 10) {
    $campusId = $_SESSION['campus_id'] ?? null;
    $sql = "SELECT al.*, a.asset_name, cam.campus_name 
            FROM activity_log al 
            LEFT JOIN assets a ON al.asset_id = a.id 
            LEFT JOIN campuses cam ON a.campus_id = cam.id
            WHERE al.performed_by LIKE '%Staff%' OR al.action IN ('REQUESTED', 'RETURNED')";
    
    $params = [];
    
    if ($campusId) {
        $sql .= " AND (a.campus_id = ? OR al.asset_id IS NULL)";
        $params[] = $campusId;
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    return fetchAll($pdo, $sql, $params);
}

function getMyAssetRequests($pdo, $campusId = null) {
    $campusId = $_SESSION['campus_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        return [];
    }

    $sql = "SELECT ar.*, a.asset_name, a.serial_number, c.category_name, cam.campus_name
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            LEFT JOIN campuses cam ON ar.campus_id = cam.id
            WHERE ar.requester_id = ?";

    $params = [];
    $params[] = $userId;

    $sql .= " ORDER BY ar.request_date DESC";

    return fetchAll($pdo, $sql, $params);
}

function returnBorrowedAsset($pdo, $data) {
    if (empty($data['borrowing_id'])) {
        throw new Exception("borrowing_id is required");
    }

    $borrowingId = (int) $data['borrowing_id'];
    $returnNotes = !empty($data['return_notes']) ? trim($data['return_notes']) : '';

    $borrowing = fetchOne($pdo, "SELECT id, asset_id, borrower_name, status FROM asset_borrowings WHERE id = ?", [$borrowingId]);
    if (!$borrowing) {
        throw new Exception("Borrowing record not found");
    }

    if ($borrowing['status'] !== 'active') {
        throw new Exception("Borrowing is not active (current status: {$borrowing['status']})");
    }

    $sql = "UPDATE asset_borrowings SET status = 'returned', return_date = NOW(), return_notes = ? WHERE id = ?";
    executeQuery($pdo, $sql, [$returnNotes, $borrowingId]);

    $description = "Borrowed asset returned by {$borrowing['borrower_name']}" . ($returnNotes ? " - Notes: {$returnNotes}" : "");
    logActivity($pdo, $borrowing['asset_id'], 'BORROWED_RETURNED', $description);

    return ['message' => "Asset returned from {$borrowing['borrower_name']} successfully.", 'return_date' => date('Y-m-d H:i:s')];
}

function getAvailableAssetsForBorrowing($pdo, $campusId = null) {
    $campusId = $_SESSION['campus_id'] ?? null;
    $sql = "SELECT a.*, c.category_name, cam.campus_name, cam.campus_code
            FROM assets a
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON a.campus_id = cam.id
            WHERE a.status = 'Active'
            AND a.id NOT IN (SELECT asset_id FROM asset_borrowings WHERE status = 'active')";

    $params = [];

    if ($campusId) {
        $sql .= " AND a.campus_id = ?";
        $params[] = $campusId;
    }

    $sql .= " ORDER BY a.asset_name ASC";

    return fetchAll($pdo, $sql, $params);
}

function getBorrowings($pdo, $data) {
    $campusId = $_SESSION['campus_id'] ?? null;
    $filters = $data['filters'] ?? [];
    $page = (int)($data['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT ab.*, a.asset_name, a.serial_number, c.category_name
            FROM asset_borrowings ab
            JOIN assets a ON ab.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            WHERE ab.status = 'active'";

    $params = [];

    if ($campusId) {
        $sql .= " AND a.campus_id = ?";
        $params[] = $campusId;
    }

    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $sql .= " AND (ab.borrower_name LIKE ? OR a.asset_name LIKE ? OR a.serial_number LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    if (!empty($filters['borrower_type'])) {
        $sql .= " AND ab.borrower_type = ?";
        $params[] = $filters['borrower_type'];
    }

    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $sql .= " AND DATE(ab.borrowed_date) BETWEEN ? AND ?";
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
    }

    $sql .= " ORDER BY ab.borrowed_date DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    return fetchAll($pdo, $sql, $params);
}

function createAsset($pdo, $data) {
    try {
        $pdo->beginTransaction();

        // Get current user info
        $user = getUserInfo();
        $createdBy = $user['id'];
        $campusId = $user['campus_id'] ?? $data['campus_id'];

        // Handle 'other' asset name
        $assetName = $data['asset_name'];
        if ($assetName === 'other' && !empty($data['asset_name_other'])) {
            $assetName = trim($data['asset_name_other']);
            // Insert into asset_names if it doesn't exist
            $stmt = $pdo->prepare("INSERT INTO asset_names (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name");
            $stmt->execute([$assetName]);
        }

        // Check if a similar asset is already assigned to the user
        $existingAssignment = fetchOne($pdo, 
            "SELECT a.id as asset_id, aa.id as assignment_id 
             FROM assets a 
             JOIN asset_assignments aa ON a.id = aa.asset_id
             WHERE aa.assigned_to_id = ? AND a.asset_name = ? AND a.category_id = ? AND a.location = ? AND aa.return_date IS NULL",
            [$user['id'], $assetName, $data['category_id'], $data['location']]
        );

        if ($existingAssignment) {
            // If it exists, update the quantity in both assets and asset_assignments tables
            $newQuantity = (int)($data['quantity'] ?? 1);
            executeQuery($pdo, "UPDATE assets SET quantity = quantity + ? WHERE id = ?", [$newQuantity, $existingAssignment['asset_id']]);
            executeQuery($pdo, "UPDATE asset_assignments SET quantity = quantity + ? WHERE id = ?", [$newQuantity, $existingAssignment['assignment_id']]);
            logActivity($pdo, $existingAssignment['asset_id'], 'STOCK_IN', "Staff added {$newQuantity} more unit(s) to existing asset.");
            $pdo->commit();
            return ['id' => $existingAssignment['asset_id'], 'updated_existing' => true];
        }

        $user = getUserInfo();
        $createdBy = $user['id'];
        $campusId = $user['campus_id'] ?? $data['campus_id'];

        // Handle 'other' asset name
        $assetName = $data['asset_name'];
        if ($assetName === 'other' && !empty($data['asset_name_other'])) {
            $assetName = trim($data['asset_name_other']);
            // Insert into asset_names if it doesn't exist
            $stmt = $pdo->prepare("INSERT INTO asset_names (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name");
            $stmt->execute([$assetName]);
        }

        // Handle 'other' location
        $location = $data['location'];
        if ($location === 'other' && !empty($data['location_other'])) {
            $location = trim($data['location_other']);
            if ($campusId && !empty($location)) {
                $building = fetchOne($pdo, "SELECT id FROM buildings WHERE campus_id = ? LIMIT 1", [$campusId]);
                if ($building) {
                    $buildingId = $building['id'];
                    $existingLocation = fetchOne($pdo, "SELECT id FROM locations WHERE room_name = ? AND building_id = ?", [$location, $buildingId]);
                    if (!$existingLocation) {
                        $maxId = fetchOne($pdo, "SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM locations") ?? ['next_id' => 1];
                        $locationCode = 'NEW-' . $maxId['next_id'];
                        executeQuery($pdo, "INSERT INTO locations (room_name, building_id, code) VALUES (?, ?, ?)", [$location, $buildingId, $locationCode]);
                    }
                }
            }
        }

        // Auto-assign to current staff member
        $assignedTo = $user['full_name'];
        $assignedEmail = $user['email'];
        $assignmentDate = date('Y-m-d');

        // Generate serial number if not provided
        if (empty($data['serial_number'])) {
            $year = date('Y');
            $maxId = fetchOne($pdo, "SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM assets");
            $data['serial_number'] = $campusId . $year . str_pad($maxId['next_id'], 4, '0', STR_PAD_LEFT);
        } else {
            // Check if serial number already exists
            $stmt = $pdo->prepare("SELECT id FROM assets WHERE serial_number = ?");
            $stmt->execute([$data['serial_number']]);
            if ($stmt->fetch()) {
                throw new Exception("Serial number already exists");
            }
        }

        // Generate UPC-A barcode from serial number
        $barcodeData = generateUPCBarcode($data['serial_number']);

        // Insert asset
        $stmt = $pdo->prepare("
            INSERT INTO assets (
                asset_name, serial_number, barcode, category_id, campus_id,
                location, purchase_date, value, status, 
                assigned_to, assigned_email, assignment_date, 
                created_by, created_at, updated_at, quantity
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
        ");
        
        $stmt->execute([
            $assetName,
            $data['serial_number'],
            $barcodeData,
            $data['category_id'],
            $campusId,
            $location ?? '',
            $data['purchase_date'] ?? null,
            $data['value'] ?? 0,
            $data['status'] ?? 'Active', // Explicitly set to Active for staff-created assets
            $assignedTo,
            $assignedEmail,
            $assignmentDate,
            $createdBy,
            $data['quantity'] ?? 1
        ]);

        $assetId = $pdo->lastInsertId();

        // Log activity
        logActivity($pdo, $assetId, 'CREATED', "Asset '{$data['asset_name']}' created by staff member {$assignedTo}");
        logActivity($pdo, $assetId, 'ASSIGNED', "Asset automatically assigned to {$assignedTo}");

        // Add to assignment history
        $assignSql = "INSERT INTO asset_assignments (asset_id, assigned_to, assigned_email, assignment_date) VALUES (?, ?, ?, ?)";
        executeQuery($pdo, $assignSql, [$assetId, $assignedTo, $assignedEmail, $assignmentDate]);

        $pdo->commit();
        
        return [
            'id' => $assetId, 
            'serial_number' => $data['serial_number'],
            'barcode' => $barcodeData,
            'assigned_to' => $assignedTo
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating asset: " . $e->getMessage());
        throw $e;
    }
}

// Helper function to generate UPC-A barcode
function generateUPCBarcode($serialNumber) {
    // Pad serial number to 11 digits with leading zeros
    $data = str_pad($serialNumber, 11, '0', STR_PAD_LEFT);

    // Calculate UPC-A check digit
    $oddSum = 0;
    $evenSum = 0;

    for ($i = 0; $i < 11; $i++) {
        $digit = (int)$data[$i];
        if (($i + 1) % 2 === 1) { // Odd positions (1,3,5,7,9,11)
            $oddSum += $digit;
        } else { // Even positions (2,4,6,8,10)
            $evenSum += $digit;
        }
    }

    $oddSum *= 3;
    $total = $oddSum + $evenSum;
    $checkDigit = (10 - ($total % 10)) % 10;

    return $data . $checkDigit;
}

function recordBorrowing($pdo, $data) {
    $requiredFields = ['asset_id', 'borrower_name', 'borrower_type'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("{$field} is required");
        }
    }

    $assetId = (int)$data['asset_id'];
    $borrowerName = trim($data['borrower_name']);
    $borrowerType = $data['borrower_type'];
    $borrowerContact = !empty($data['borrower_contact']) ? trim($data['borrower_contact']) : null;
    $expectedReturnDate = !empty($data['expected_return_date']) ? $data['expected_return_date'] : null;
    $notes = !empty($data['notes']) ? trim($data['notes']) : null;

    if (!in_array($borrowerType, ['Teacher', 'Student'])) {
        throw new Exception("Invalid borrower type");
    }

    if ($expectedReturnDate && strtotime($expectedReturnDate) < time()) {
        throw new Exception("Expected return date cannot be in the past");
    }

    $asset = fetchOne($pdo, "SELECT id, asset_name, status, assigned_to FROM assets WHERE id = ?", [$assetId]);
    if (!$asset) {
        throw new Exception("Asset not found");
    }

    if ($asset['status'] !== 'Active') {
        throw new Exception("Asset is not active (current status: {$asset['status']})");
    }

    if (!empty($asset['assigned_email']) && $asset['assigned_email'] !== $_SESSION['email']) {
        throw new Exception("Asset is assigned to another staff member and cannot be borrowed");
    }

    $existingBorrowing = fetchOne($pdo, "SELECT id FROM asset_borrowings WHERE asset_id = ? AND status = 'active'", [$assetId]);
    if ($existingBorrowing) {
        throw new Exception("Asset is already borrowed");
    }

    $sql = "INSERT INTO asset_borrowings (asset_id, borrower_name, borrower_type, borrower_contact, expected_return_date, notes, borrowed_date, status)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'active')";
    executeQuery($pdo, $sql, [$assetId, $borrowerName, $borrowerType, $borrowerContact, $expectedReturnDate, $notes]);

    $description = "Asset borrowed by {$borrowerName} ({$borrowerType})";
    logActivity($pdo, $assetId, 'BORROWED', $description);

    return ['borrowing_id' => $pdo->lastInsertId(), 'borrowed_date' => date('Y-m-d H:i:s')];
}

// New functions for streamlined dashboard

function searchAssetByBarcode($pdo, $barcode, $campusId = null) {
    $campusId = $_SESSION['campus_id'] ?? $campusId;
    
    $sql = "SELECT a.*, c.category_name, cam.campus_name, cam.campus_code,
                   ab.id as borrowing_id, ab.borrower_name, ab.borrower_type, ab.status as borrowing_status,
                   (CASE WHEN ab.id IS NOT NULL THEN 1 ELSE 0 END) as is_borrowed_out
            FROM assets a
            JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON a.campus_id = cam.id
            LEFT JOIN asset_borrowings ab ON a.id = ab.asset_id AND ab.status = 'active'
            WHERE a.barcode = ?";
    
    $params = [$barcode];
    
    if ($campusId) {
        $sql .= " AND a.campus_id = ?";
        $params[] = $campusId;
    }
    
    $sql .= " LIMIT 1";
    
    return fetchOne($pdo, $sql, $params);
}

function borrowAsset($pdo, $data) {
    // This is an alias for recordBorrowing for consistency
    return recordBorrowing($pdo, $data);
}

function getMyBorrowings($pdo, $campusId = null) {
    $campusId = $_SESSION['campus_id'] ?? $campusId;
    $userEmail = $_SESSION['email'] ?? null;
    
    if (!$userEmail) {
        return [];
    }
    
    $sql = "SELECT ab.*, a.asset_name, a.serial_number, c.category_name
            FROM asset_borrowings ab
            JOIN assets a ON ab.asset_id = a.id
            JOIN categories c ON a.category_id = c.id
            WHERE ab.status = 'active' AND a.assigned_email = ?";
    
    $params = [$userEmail];
    
    if ($campusId) {
        $sql .= " AND a.campus_id = ?";
        $params[] = $campusId;
    }
    
    $sql .= " ORDER BY ab.borrowed_date DESC";
    
    return fetchAll($pdo, $sql, $params);
}

function getBrandsForAsset($pdo, $assetName) {
    if (empty($assetName)) {
        return [];
    }

    // This query finds all distinct brands for assets with a given name from the 'assets' table.
    // It assumes you have a 'brand' column in your 'assets' table.
    $sql = "SELECT DISTINCT brand AS name 
            FROM assets 
            WHERE asset_name = ? AND brand IS NOT NULL AND brand != ''
            ORDER BY brand ASC";
    
    return fetchAll($pdo, $sql, [$assetName]);
}

function borrowAssetForSelf($pdo, $assetId) {
    if (empty($assetId)) {
        throw new Exception("Asset ID is required.");
    }

    $assetId = (int)$assetId;
    $user = getUserInfo();

    if (empty($user['id']) || empty($user['email']) || empty($user['full_name'])) {
        throw new Exception("User information is incomplete. Please log in again.");
    }

    // Fetch the asset and lock the row for update
    $asset = fetchOne($pdo, "SELECT id, asset_name, status, assigned_to FROM assets WHERE id = ? FOR UPDATE", [$assetId]);

    if (!$asset) {
        throw new Exception("Asset not found.");
    }

    if (!in_array($asset['status'], ['Available', 'Active'])) {
        throw new Exception("This asset is not available for borrowing. Current status: " . $asset['status'] . ".");
    }

    if (!empty($asset['assigned_to'])) {
        throw new Exception("This asset is already assigned to someone else.");
    }

    // Update the asset to assign it to the current user
    $updateSql = "UPDATE assets SET status = 'Active', assigned_to = ?, assigned_email = ?, assignment_date = NOW(), updated_at = NOW() WHERE id = ?";
    executeQuery($pdo, $updateSql, [$user['full_name'], $user['email'], $assetId]);

    // Log the assignment activity
    logActivity($pdo, $assetId, 'ASSIGNED', "Asset '{$asset['asset_name']}' was borrowed by staff member {$user['full_name']}.");

    return ['asset_id' => $assetId, 'assigned_to' => $user['full_name'], 'assignment_date' => date('Y-m-d')];
}

function getLatestStaffActivityTimestamp($pdo) {
    $userEmail = $_SESSION['email'] ?? null;
    $campusId = $_SESSION['campus_id'] ?? null;

    if (!$userEmail || !$campusId) {
        return null;
    }

    // Query for the latest activity timestamp relevant to the staff member.
    // This includes:
    // 1. Activities on assets assigned to the user.
    // 2. Activities performed by the user.
    // 3. General campus-wide activities that might affect their view (e.g., new available asset).
    $stmt = $pdo->prepare("
        SELECT MAX(al.created_at) as latest_timestamp
        FROM activity_log al
        LEFT JOIN assets a ON al.asset_id = a.id
        WHERE al.campus_id = :campus_id OR a.assigned_email = :email
    ");
    $stmt->execute(['campus_id' => $campusId, 'email' => $userEmail]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['latest_timestamp'] ?? null;
}

function requestSpecificAsset($pdo, $assetId, $quantity = 1, $purpose = '', $expectedReturnDate = null) {
    if (empty($assetId)) {
        throw new Exception("Asset ID is required.");
    }

    $assetId = (int)$assetId;
    $quantity = (int)$quantity;
    $purpose = trim($purpose);
    $user = getUserInfo();

    if (empty($user['id']) || empty($user['campus_id'])) {
        throw new Exception("User or campus information is missing.");
    }
    if (empty($purpose)) {
        throw new Exception("Purpose of use is required.");
    }
    if (empty($expectedReturnDate)) {
        throw new Exception("Expected return date is required.");
    }

    // Check if a pending request for this asset already exists
    $existingRequest = fetchOne($pdo, 
        "SELECT id FROM asset_requests WHERE asset_id = ? AND requester_id = ? AND status = 'pending'", 
        [$assetId, $user['id']]
    );
    if ($existingRequest) {
        throw new Exception("There is already a pending request for this asset.");
    }

    $sql = "INSERT INTO asset_requests 
                (asset_id, requester_id, campus_id, quantity, purpose, expected_return_date, request_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')";
    executeQuery($pdo, $sql, [$assetId, $user['id'], $user['campus_id'], $quantity, $purpose, $expectedReturnDate]);

    $requestId = $pdo->lastInsertId();
    logActivity($pdo, $assetId, 'REQUESTED', "Asset requested by staff member {$user['full_name']}");

    return ['request_id' => $requestId];
}
