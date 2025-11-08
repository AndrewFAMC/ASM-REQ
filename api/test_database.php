<?php
/**
 * Test Database API
 * Backend for testing database schema and data
 */

require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'check_schema':
        checkDatabaseSchema();
        break;

    case 'check_users':
        checkUserRoles();
        break;

    case 'check_assets':
        checkAssetAvailability();
        break;

    case 'cleanup_test_data':
        cleanupTestData();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function checkDatabaseSchema() {
    global $pdo;

    $requiredTables = [
        'users',
        'assets',
        'asset_requests',
        'asset_borrowings',
        'notifications',
        'approval_history',
        'campuses'
    ];

    $missingTables = [];
    $existingTables = [];

    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        } catch (PDOException $e) {
            $missingTables[] = $table;
        }
    }

    $details = [
        "✓ Existing tables: " . implode(', ', $existingTables)
    ];

    if (!empty($missingTables)) {
        $details[] = "✗ Missing tables: " . implode(', ', $missingTables);
    }

    // Check critical columns in asset_requests
    try {
        $stmt = $pdo->query("DESCRIBE asset_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $requiredColumns = ['id', 'user_id', 'asset_id', 'quantity', 'purpose', 'expected_return_date', 'status'];
        $missingColumns = array_diff($requiredColumns, $columns);

        if (!empty($missingColumns)) {
            // Check if requester_id exists (alternative column name)
            if (in_array('requester_id', $columns) && in_array('user_id', $missingColumns)) {
                $details[] = "⚠ Using 'requester_id' instead of 'user_id' (compatibility mode)";
                $key = array_search('user_id', $missingColumns);
                if ($key !== false) {
                    unset($missingColumns[$key]);
                }
            }

            if (!empty($missingColumns)) {
                $details[] = "✗ Missing columns in asset_requests: " . implode(', ', $missingColumns);
            }
        } else {
            $details[] = "✓ All required columns exist in asset_requests";
        }
    } catch (PDOException $e) {
        $details[] = "✗ Could not check asset_requests columns: " . $e->getMessage();
    }

    echo json_encode([
        'success' => empty($missingTables),
        'message' => empty($missingTables) ? 'Database schema OK' : 'Missing tables detected',
        'details' => $details
    ]);
}

function checkUserRoles() {
    global $pdo;

    try {
        // Count users by role
        $stmt = $pdo->query("
            SELECT role, COUNT(*) as count
            FROM users
            WHERE is_active = TRUE
            GROUP BY role
        ");
        $roleCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $employeeCount = ($roleCounts['staff'] ?? 0) + ($roleCounts['employee'] ?? 0);
        $custodianCount = $roleCounts['custodian'] ?? 0;
        $adminCount = $roleCounts['admin'] ?? 0;

        $details = [];
        $details[] = "Employees/Staff: {$employeeCount}";
        $details[] = "Custodians: {$custodianCount}";
        $details[] = "Admins: {$adminCount}";

        $warnings = [];
        if ($employeeCount == 0) $warnings[] = "No employees found - you won't be able to test request submission";
        if ($custodianCount == 0) $warnings[] = "No custodians found - approval workflow will be incomplete";
        if ($adminCount == 0) $warnings[] = "No admins found - final approval cannot be tested";

        if (!empty($warnings)) {
            $details = array_merge($details, ['', 'Warnings:'], $warnings);
        }

        echo json_encode([
            'success' => true,
            'employee_count' => $employeeCount,
            'custodian_count' => $custodianCount,
            'admin_count' => $adminCount,
            'details' => $details
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error checking users: ' . $e->getMessage(),
            'details' => []
        ]);
    }
}

function checkAssetAvailability() {
    global $pdo;

    try {
        // Count available assets
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM assets
            WHERE status = 'Available'
        ");
        $availableCount = (int)$stmt->fetch()['count'];

        // Get sample assets
        $stmt = $pdo->query("
            SELECT asset_name, asset_code, campus_id
            FROM assets
            WHERE status = 'Available'
            LIMIT 5
        ");
        $sampleAssets = $stmt->fetchAll();

        $details = ["Available assets: {$availableCount}"];

        if (!empty($sampleAssets)) {
            $details[] = "";
            $details[] = "Sample available assets:";
            foreach ($sampleAssets as $asset) {
                $details[] = "- {$asset['asset_name']} ({$asset['asset_code']}) - Campus {$asset['campus_id']}";
            }
        } else {
            $details[] = "⚠ No available assets found - you won't be able to create requests";
        }

        echo json_encode([
            'success' => true,
            'available_count' => $availableCount,
            'details' => $details
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error checking assets: ' . $e->getMessage(),
            'details' => []
        ]);
    }
}

function cleanupTestData() {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Delete test notifications (created in last hour)
        $stmt = $pdo->prepare("
            DELETE FROM notifications
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        $notificationsDeleted = $stmt->rowCount();

        // Delete test requests (pending/rejected in last hour)
        $stmt = $pdo->prepare("
            DELETE FROM asset_requests
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND status IN ('pending', 'rejected')
        ");
        $stmt->execute();
        $requestsDeleted = $stmt->rowCount();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Deleted {$notificationsDeleted} notifications and {$requestsDeleted} requests",
            'notifications_deleted' => $notificationsDeleted,
            'requests_deleted' => $requestsDeleted
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error cleaning up: ' . $e->getMessage()
        ]);
    }
}
?>
