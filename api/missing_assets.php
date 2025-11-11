<?php
/**
 * API Endpoint: Missing Assets Management
 * Handles retrieval and updates of missing asset reports
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

// Determine action - handle both GET and JSON POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If no action found in GET/POST, check JSON body
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

try {
    switch ($action) {
        case 'get_missing_assets':
            getMissingAssets($pdo, $user);
            break;

        case 'get_report_details':
            getReportDetails($pdo, $user);
            break;

        case 'update_status':
            updateReportStatus($pdo, $user);
            break;

        case 'add_note':
            addInvestigationNote($pdo, $user);
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
 * Get missing assets reports with filters
 */
function getMissingAssets($pdo, $user) {
    $campusId = $user['campus_id'];
    $filterStatus = $_GET['status'] ?? 'all';

    // Build query based on filter
    $sql = "
        SELECT
            mar.id,
            mar.asset_id,
            mar.status,
            mar.reported_date,
            mar.last_known_location,
            mar.last_known_borrower,
            mar.last_known_borrower_contact,
            mar.last_seen_date,
            mar.responsible_department,
            mar.description,
            mar.resolution_notes,
            mar.resolved_date,
            a.asset_name,
            COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
            c.category_name as category,
            reporter.full_name as reporter_name,
            reporter.email as reporter_email,
            resolver.full_name as resolved_by_name
        FROM missing_assets_reports mar
        JOIN assets a ON mar.asset_id = a.id
        LEFT JOIN categories c ON a.category_id = c.id
        JOIN users reporter ON mar.reported_by = reporter.id
        LEFT JOIN users resolver ON mar.resolved_by = resolver.id
        WHERE mar.campus_id = ?
    ";

    $params = [$campusId];

    if ($filterStatus !== 'all') {
        $sql .= " AND mar.status = ?";
        $params[] = $filterStatus;
    }

    $sql .= " ORDER BY
        CASE
            WHEN mar.status = 'reported' THEN 1
            WHEN mar.status = 'investigating' THEN 2
            WHEN mar.status = 'found' THEN 3
            WHEN mar.status = 'permanently_lost' THEN 4
        END,
        mar.reported_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $reports
    ]);
}

/**
 * Get detailed information about a specific report
 */
function getReportDetails($pdo, $user) {
    $reportId = filter_input(INPUT_GET, 'report_id', FILTER_VALIDATE_INT);

    if (!$reportId) {
        throw new Exception('Invalid report ID');
    }

    $stmt = $pdo->prepare("
        SELECT
            mar.*,
            a.asset_name,
            COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
            c.category_name as category,
            a.location as current_location,
            reporter.full_name as reporter_name,
            reporter.email as reporter_email,
            resolver.full_name as resolved_by_name
        FROM missing_assets_reports mar
        JOIN assets a ON mar.asset_id = a.id
        LEFT JOIN categories c ON a.category_id = c.id
        JOIN users reporter ON mar.reported_by = reporter.id
        LEFT JOIN users resolver ON mar.resolved_by = resolver.id
        WHERE mar.id = ?
        AND mar.campus_id = ?
    ");

    $stmt->execute([$reportId, $user['campus_id']]);
    $report = $stmt->fetch();

    if (!$report) {
        throw new Exception('Report not found or access denied');
    }

    // Get borrowing history for this asset
    $historyStmt = $pdo->prepare("
        SELECT
            ar.id,
            ar.status,
            ar.request_date,
            ar.approval_date,
            ar.released_date,
            ar.expected_return_date,
            ar.returned_date,
            u.full_name as requester_name,
            u.email as requester_email
        FROM asset_requests ar
        JOIN users u ON ar.requester_id = u.id
        WHERE ar.asset_id = ?
        ORDER BY ar.request_date DESC
        LIMIT 10
    ");

    $historyStmt->execute([$report['asset_id']]);
    $borrowingHistory = $historyStmt->fetchAll();

    // Get activity logs related to this asset
    $logsStmt = $pdo->prepare("
        SELECT
            action,
            description,
            created_at
        FROM activity_log
        WHERE asset_id = ?
        OR description LIKE ?
        ORDER BY created_at DESC
        LIMIT 20
    ");

    $logsStmt->execute([$report['asset_id'], "%Report ID: #$reportId%"]);
    $activityLogs = $logsStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'report' => $report,
            'borrowing_history' => $borrowingHistory,
            'activity_logs' => $activityLogs
        ]
    ]);
}

/**
 * Update report status (start investigation, mark as found, mark as lost)
 */
function updateReportStatus($pdo, $user) {
    // Require custodian or admin access
    if (!hasRole('custodian') && !hasRole('admin')) {
        throw new Exception('Insufficient permissions');
    }

    // Verify CSRF token
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        throw new Exception('Invalid CSRF token');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $reportId = filter_var($input['report_id'] ?? null, FILTER_VALIDATE_INT);
    $newStatus = $input['status'] ?? '';
    $notes = $input['notes'] ?? '';
    $foundLocation = $input['found_location'] ?? null;

    if (!$reportId || !$newStatus) {
        throw new Exception('Missing required fields');
    }

    // Validate status
    $validStatuses = ['reported', 'investigating', 'found', 'permanently_lost'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('Invalid status');
    }

    // Get current report
    $stmt = $pdo->prepare("
        SELECT mar.*, a.asset_name, COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code, a.id as asset_id
        FROM missing_assets_reports mar
        JOIN assets a ON mar.asset_id = a.id
        WHERE mar.id = ? AND mar.campus_id = ?
    ");
    $stmt->execute([$reportId, $user['campus_id']]);
    $report = $stmt->fetch();

    if (!$report) {
        throw new Exception('Report not found or access denied');
    }

    $pdo->beginTransaction();

    // Update report status
    if ($newStatus === 'found' || $newStatus === 'permanently_lost') {
        // Resolution statuses
        $updateStmt = $pdo->prepare("
            UPDATE missing_assets_reports
            SET
                status = ?,
                resolution_notes = ?,
                resolved_by = ?,
                resolved_date = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$newStatus, $notes, $user['id'], $reportId]);

        // Update asset status
        if ($newStatus === 'found') {
            $assetStatus = 'Available';
            $location = $foundLocation ?? $report['last_known_location'];

            $assetStmt = $pdo->prepare("
                UPDATE assets
                SET status = ?, location = ?
                WHERE id = ?
            ");
            $assetStmt->execute([$assetStatus, $location, $report['asset_id']]);

            logActivity(
                $pdo,
                $report['asset_id'],
                'ASSET_FOUND',
                "Missing asset found and returned to inventory. Found at: {$location}. Report ID: #{$reportId}",
                $user['campus_id']
            );
        } else {
            // permanently_lost
            $assetStmt = $pdo->prepare("
                UPDATE assets
                SET status = 'Disposed'
                WHERE id = ?
            ");
            $assetStmt->execute([$report['asset_id']]);

            logActivity(
                $pdo,
                $report['asset_id'],
                'ASSET_LOST',
                "Asset marked as permanently lost. Report ID: #{$reportId}",
                $user['campus_id']
            );
        }
    } else {
        // investigating or reported status
        $updateStmt = $pdo->prepare("
            UPDATE missing_assets_reports
            SET
                status = ?,
                resolution_notes = CONCAT(COALESCE(resolution_notes, ''), '\n\n[', NOW(), '] ', ?)
            WHERE id = ?
        ");
        $updateStmt->execute([$newStatus, $notes, $reportId]);

        logActivity(
            $pdo,
            $report['asset_id'],
            'INVESTIGATION_UPDATE',
            "Investigation status updated to: {$newStatus}. Report ID: #{$reportId}",
            $user['campus_id']
        );
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Report status updated successfully',
        'data' => [
            'report_id' => $reportId,
            'new_status' => $newStatus
        ]
    ]);
}

/**
 * Add investigation note to a report
 */
function addInvestigationNote($pdo, $user) {
    // Require custodian or admin access
    if (!hasRole('custodian') && !hasRole('admin')) {
        throw new Exception('Insufficient permissions');
    }

    // Verify CSRF token
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        throw new Exception('Invalid CSRF token');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $reportId = filter_var($input['report_id'] ?? null, FILTER_VALIDATE_INT);
    $note = $input['note'] ?? '';

    if (!$reportId || !$note) {
        throw new Exception('Missing required fields');
    }

    // Get report
    $stmt = $pdo->prepare("
        SELECT id, asset_id
        FROM missing_assets_reports
        WHERE id = ? AND campus_id = ?
    ");
    $stmt->execute([$reportId, $user['campus_id']]);
    $report = $stmt->fetch();

    if (!$report) {
        throw new Exception('Report not found or access denied');
    }

    // Add note to resolution_notes field with timestamp
    $updateStmt = $pdo->prepare("
        UPDATE missing_assets_reports
        SET
            resolution_notes = CONCAT(
                COALESCE(resolution_notes, ''),
                '\n\n[', NOW(), '] ',
                ?, ': ',
                ?
            )
        WHERE id = ?
    ");

    $updateStmt->execute([$user['full_name'], $note, $reportId]);

    logActivity(
        $pdo,
        $report['asset_id'],
        'INVESTIGATION_NOTE',
        "Investigation note added by {$user['full_name']}. Report ID: #{$reportId}",
        $user['campus_id']
    );

    echo json_encode([
        'success' => true,
        'message' => 'Investigation note added successfully'
    ]);
}
?>
