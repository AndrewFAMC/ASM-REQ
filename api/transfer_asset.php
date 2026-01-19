<?php
/**
 * API Endpoint: Asset Transfer Management
 * Handles tracking of asset transfers between borrowers (borrowing chain)
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/email_functions.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

// Parse JSON input if Content-Type is application/json
$inputData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SERVER['CONTENT_TYPE']) &&
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true) ?? [];
    // Merge with $_POST for compatibility
    $_POST = array_merge($_POST, $inputData);
}

// Determine action
$action = $_GET['action'] ?? $_POST['action'] ?? $inputData['action'] ?? '';

try {
    switch ($action) {
        case 'record_transfer':
            recordTransfer($pdo, $user, $inputData);
            break;

        case 'get_transfer_chain':
            getTransferChain($pdo, $user);
            break;

        case 'get_active_borrowing':
            getActiveBorrowing($pdo, $user);
            break;

        case 'verify_borrower':
            verifyBorrower($pdo, $user, $inputData);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Record a transfer between borrowers
 */
function recordTransfer($pdo, $user, $inputData = []) {
    // Require custodian or admin access
    if (!hasRole('custodian') && !hasRole('admin')) {
        throw new Exception('Only custodians can record transfers');
    }

    // Verify CSRF token
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        throw new Exception('Invalid CSRF token');
    }

    // Use already parsed input data or parse from POST
    $input = !empty($inputData) ? $inputData : $_POST;

    // Use filter_var but convert false to null (false means validation failed or value was null)
    $borrowingId = filter_var($input['borrowing_id'] ?? null, FILTER_VALIDATE_INT);
    $borrowingId = ($borrowingId === false) ? null : $borrowingId;

    $requestId = filter_var($input['request_id'] ?? null, FILTER_VALIDATE_INT);
    $requestId = ($requestId === false) ? null : $requestId;

    $fromPerson = trim($input['from_person'] ?? '');
    $toPerson = trim($input['to_person'] ?? '');
    $toPersonContact = trim($input['to_person_contact'] ?? '');
    $transferDate = $input['transfer_date'] ?? date('Y-m-d H:i:s');
    $expectedReturnDate = $input['expected_return_date'] ?? null;
    $notes = trim($input['notes'] ?? '');

    // Validate required fields
    if (!$fromPerson || !$toPerson) {
        throw new Exception('From and To persons are required');
    }

    // Get borrowing or request details
    $assetId = null;
    $originalBorrower = '';

    if ($borrowingId) {
        $stmt = $pdo->prepare("
            SELECT ab.*, a.asset_name, COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code
            FROM asset_borrowings ab
            JOIN assets a ON ab.asset_id = a.id
            WHERE ab.id = ?
        ");
        $stmt->execute([$borrowingId]);
        $borrowing = $stmt->fetch();

        if (!$borrowing) {
            throw new Exception('Borrowing record not found');
        }

        $assetId = $borrowing['asset_id'];
        $originalBorrower = $borrowing['borrower_name'];
        $expectedReturnDate = $expectedReturnDate ?? $borrowing['expected_return_date'];
    } elseif ($requestId) {
        $stmt = $pdo->prepare("
            SELECT ar.*, a.asset_name, COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code, u.full_name as requester_name
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN users u ON ar.requester_id = u.id
            WHERE ar.id = ?
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            throw new Exception('Request record not found');
        }

        $assetId = $request['asset_id'];
        $originalBorrower = $request['requester_name'];
        $expectedReturnDate = $expectedReturnDate ?? $request['expected_return_date'];
    } else {
        throw new Exception('Either borrowing_id or request_id is required');
    }

    $pdo->beginTransaction();

    try {
        // Insert transfer record into borrowing_chain
        $insertStmt = $pdo->prepare("
            INSERT INTO borrowing_chain (
                borrowing_id,
                request_id,
                asset_id,
                from_person,
                to_person,
                to_person_contact,
                transfer_date,
                expected_return_date,
                status,
                notes,
                recorded_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ");

        $insertStmt->execute([
            $borrowingId,  // Already null if not provided
            $requestId,    // Already null if not provided
            $assetId,
            $fromPerson,
            $toPerson,
            $toPersonContact,
            $transferDate,
            $expectedReturnDate,
            $notes,
            $user['id']
        ]);

        $transferId = $pdo->lastInsertId();

        // Update borrowing record with last known borrower
        if ($borrowingId) {
            $updateBorrowingStmt = $pdo->prepare("
                UPDATE asset_borrowings
                SET last_known_borrower = ?
                WHERE id = ?
            ");
            $updateBorrowingStmt->execute([$toPerson, $borrowingId]);
        }

        // Update request record with last known borrower (if applicable)
        if ($requestId) {
            // For requests, we might want to add a similar field or use notes
            // For now, we'll log it in activity
        }

        // Log activity
        logActivity(
            $pdo,
            $assetId,
            'ASSET_TRANSFERRED',
            "Asset transferred from '{$fromPerson}' to '{$toPerson}'. Transfer ID: #{$transferId}. Recorded by: {$user['full_name']}",
            $user['campus_id']
        );

        // Create notification for the custodian (confirmation)
        createNotification(
            $pdo,
            $user['id'],
            NOTIFICATION_SYSTEM_ALERT,
            "Transfer Recorded",
            "Asset transfer from '{$fromPerson}' to '{$toPerson}' has been recorded successfully.",
            [
                'related_type' => 'transfer',
                'related_id' => $transferId,
                'priority' => NOTIFICATION_PRIORITY_MEDIUM
            ]
        );

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Transfer recorded successfully',
            'data' => [
                'transfer_id' => $transferId,
                'from_person' => $fromPerson,
                'to_person' => $toPerson
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get transfer chain for a specific borrowing or asset
 */
function getTransferChain($pdo, $user) {
    $borrowingId = filter_input(INPUT_GET, 'borrowing_id', FILTER_VALIDATE_INT);
    $assetId = filter_input(INPUT_GET, 'asset_id', FILTER_VALIDATE_INT);
    $requestId = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);

    if (!$borrowingId && !$assetId && !$requestId) {
        throw new Exception('Either borrowing_id, asset_id, or request_id is required');
    }

    $sql = "
        SELECT
            bc.*,
            a.asset_name,
            COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
            u.full_name as recorded_by_name
        FROM borrowing_chain bc
        JOIN assets a ON bc.asset_id = a.id
        LEFT JOIN users u ON bc.recorded_by = u.id
        WHERE 1=1
    ";

    $params = [];

    if ($borrowingId) {
        $sql .= " AND bc.borrowing_id = ?";
        $params[] = $borrowingId;
    } elseif ($requestId) {
        // Filter by request_id directly
        $sql .= " AND bc.request_id = ?";
        $params[] = $requestId;
    } elseif ($assetId) {
        $sql .= " AND bc.asset_id = ?";
        $params[] = $assetId;
    }

    $sql .= " ORDER BY bc.transfer_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transfers = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $transfers
    ]);
}

/**
 * Get active borrowing details for an asset
 */
function getActiveBorrowing($pdo, $user) {
    $assetId = filter_input(INPUT_GET, 'asset_id', FILTER_VALIDATE_INT);
    $requestId = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);

    if (!$assetId && !$requestId) {
        throw new Exception('Either asset_id or request_id is required');
    }

    // Try to find active request first
    if ($requestId) {
        $stmt = $pdo->prepare("
            SELECT
                ar.*,
                a.asset_name,
                COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
                u.full_name as original_borrower,
                u.email as borrower_email
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN users u ON ar.requester_id = u.id
            WHERE ar.id = ?
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if ($request) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'type' => 'request',
                    'details' => $request
                ]
            ]);
            return;
        }
    }

    // Try to find active borrowing
    if ($assetId || ($requestId && isset($request))) {
        $searchAssetId = $assetId ?? $request['asset_id'];

        $stmt = $pdo->prepare("
            SELECT
                ab.*,
                a.asset_name,
                COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code
            FROM asset_borrowings ab
            JOIN assets a ON ab.asset_id = a.id
            WHERE ab.asset_id = ?
            AND ab.status = 'active'
            ORDER BY ab.borrowed_date DESC
            LIMIT 1
        ");
        $stmt->execute([$searchAssetId]);
        $borrowing = $stmt->fetch();

        if ($borrowing) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'type' => 'borrowing',
                    'details' => $borrowing
                ]
            ]);
            return;
        }
    }

    throw new Exception('No active borrowing found for this asset');
}

/**
 * Verify if the person returning is the original borrower
 */
function verifyBorrower($pdo, $user, $inputData = []) {
    // Use already parsed input data or parse from POST
    $input = !empty($inputData) ? $inputData : $_POST;

    $requestId = filter_var($input['request_id'] ?? null, FILTER_VALIDATE_INT);
    $requestId = ($requestId === false) ? null : $requestId;

    $returningPersonName = trim($input['returning_person'] ?? '');

    if (!$requestId || !$returningPersonName) {
        throw new Exception('Request ID and returning person name are required');
    }

    // Get original borrower
    $stmt = $pdo->prepare("
        SELECT
            ar.*,
            u.full_name as original_borrower,
            a.asset_name
        FROM asset_requests ar
        JOIN users u ON ar.requester_id = u.id
        JOIN assets a ON ar.asset_id = a.id
        WHERE ar.id = ?
    ");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception('Request not found');
    }

    // Get transfer chain to find current borrower
    $chainStmt = $pdo->prepare("
        SELECT *
        FROM borrowing_chain
        WHERE asset_id = ?
        AND status = 'active'
        ORDER BY transfer_date DESC
        LIMIT 1
    ");
    $chainStmt->execute([$request['asset_id']]);
    $lastTransfer = $chainStmt->fetch();

    $currentBorrower = $lastTransfer ? $lastTransfer['to_person'] : $request['original_borrower'];
    $isOriginalBorrower = (strcasecmp($returningPersonName, $request['original_borrower']) === 0);
    $isCurrentBorrower = (strcasecmp($returningPersonName, $currentBorrower) === 0);
    $isIndirectReturn = !$isOriginalBorrower && !$isCurrentBorrower;

    echo json_encode([
        'success' => true,
        'data' => [
            'is_original_borrower' => $isOriginalBorrower,
            'is_current_borrower' => $isCurrentBorrower,
            'is_indirect_return' => $isIndirectReturn,
            'original_borrower' => $request['original_borrower'],
            'current_borrower' => $currentBorrower,
            'returning_person' => $returningPersonName,
            'has_transfers' => $lastTransfer ? true : false,
            'transfer_count' => $pdo->query("SELECT COUNT(*) FROM borrowing_chain WHERE asset_id = {$request['asset_id']}")->fetchColumn()
        ]
    ]);
}
?>
