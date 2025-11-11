<?php
/**
 * API Endpoint: Report Missing Asset
 * Handles submission of missing asset reports
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

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!$csrfToken || !validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];
$reporterId = $user['id'];

try {
    // Validate required fields
    $assetId = filter_input(INPUT_POST, 'asset_id', FILTER_VALIDATE_INT);
    $lastSeenDate = filter_input(INPUT_POST, 'last_seen_date', FILTER_SANITIZE_STRING);
    $lastKnownLocation = filter_input(INPUT_POST, 'last_known_location', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    if (!$assetId || !$lastSeenDate || !$lastKnownLocation || !$description) {
        throw new Exception('Missing required fields');
    }

    // Optional fields
    $lastKnownBorrower = filter_input(INPUT_POST, 'last_known_borrower', FILTER_SANITIZE_STRING);
    $lastKnownBorrowerContact = filter_input(INPUT_POST, 'last_known_borrower_contact', FILTER_SANITIZE_STRING);
    $responsibleDepartment = filter_input(INPUT_POST, 'responsible_department', FILTER_SANITIZE_STRING);

    // Verify asset exists and belongs to this campus
    $assetStmt = $pdo->prepare("
        SELECT
            a.id,
            a.asset_name,
            COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
            c.category_name as category,
            a.location,
            a.status
        FROM assets a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id = ? AND a.campus_id = ?
    ");
    $assetStmt->execute([$assetId, $campusId]);
    $asset = $assetStmt->fetch();

    if (!$asset) {
        throw new Exception('Asset not found or access denied');
    }

    // Check if asset is already reported as missing
    if ($asset['status'] === 'Missing') {
        throw new Exception('This asset is already reported as missing');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Create missing asset report
    $insertStmt = $pdo->prepare("
        INSERT INTO missing_assets_reports (
            asset_id,
            reported_by,
            reported_date,
            last_known_location,
            last_known_borrower,
            last_known_borrower_contact,
            last_seen_date,
            responsible_department,
            description,
            status,
            campus_id
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'reported', ?)
    ");

    $insertStmt->execute([
        $assetId,
        $reporterId,
        $lastKnownLocation,
        $lastKnownBorrower,
        $lastKnownBorrowerContact,
        $lastSeenDate,
        $responsibleDepartment,
        $description,
        $campusId
    ]);

    $reportId = $pdo->lastInsertId();

    // Update asset status to Missing
    $updateAssetStmt = $pdo->prepare("
        UPDATE assets
        SET status = 'Missing'
        WHERE id = ?
    ");
    $updateAssetStmt->execute([$assetId]);

    // If asset is currently borrowed, update borrowing status
    $updateBorrowingStmt = $pdo->prepare("
        UPDATE asset_requests
        SET status = 'missing'
        WHERE asset_id = ?
        AND status IN ('released')
    ");
    $updateBorrowingStmt->execute([$assetId]);

    // Log activity
    logActivity(
        $pdo,
        $assetId,
        'ASSET_REPORTED_MISSING',
        "Asset reported as missing by {$user['full_name']}. Report ID: #{$reportId}",
        $campusId
    );

    // Commit transaction
    $pdo->commit();

    // Check if emails should be sent
    $emailEnabled = getSystemSetting($pdo, 'enable_email_notifications', true);

    if ($emailEnabled) {
        // Prepare report details for emails
        $reportDetails = [
            'last_known_location' => $lastKnownLocation,
            'last_known_borrower' => $lastKnownBorrower,
            'last_seen_date' => $lastSeenDate,
            'reported_by_name' => $user['full_name'],
            'reported_date' => date('Y-m-d H:i:s'),
            'description' => $description
        ];

        // USING ASYNC EMAIL QUEUE - Returns immediately without waiting for emails to send
        // Background worker will process these emails asynchronously

        // 1. Queue confirmation email to reporter
        try {
            queueMissingAssetAlertEmail(
                $pdo,
                $user['email'],
                $user['full_name'],
                $asset['asset_name'],
                $asset['asset_code'],
                $reportId,
                'reporter',
                $reportDetails
            );
        } catch (Exception $emailError) {
            error_log("Failed to queue reporter email: " . $emailError->getMessage());
        }

        // 2. Queue alert to all custodians at this campus
        $custodianStmt = $pdo->prepare("
            SELECT id, email, full_name
            FROM users
            WHERE role = 'custodian'
            AND campus_id = ?
            AND is_active = 1
        ");
        $custodianStmt->execute([$campusId]);
        $custodians = $custodianStmt->fetchAll();

        foreach ($custodians as $custodian) {
            try {
                queueMissingAssetAlertEmail(
                    $pdo,
                    $custodian['email'],
                    $custodian['full_name'],
                    $asset['asset_name'],
                    $asset['asset_code'],
                    $reportId,
                    'custodian',
                    $reportDetails
                );
            } catch (Exception $emailError) {
                error_log("Failed to queue custodian email to {$custodian['email']}: " . $emailError->getMessage());
            }
        }

        // 3. Queue alert to all admins
        $adminStmt = $pdo->prepare("
            SELECT id, email, full_name
            FROM users
            WHERE role IN ('admin', 'super_admin')
            AND is_active = 1
        ");
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll();

        foreach ($admins as $admin) {
            try {
                queueMissingAssetAlertEmail(
                    $pdo,
                    $admin['email'],
                    $admin['full_name'],
                    $asset['asset_name'],
                    $asset['asset_code'],
                    $reportId,
                    'admin',
                    $reportDetails
                );
            } catch (Exception $emailError) {
                error_log("Failed to queue admin email to {$admin['email']}: " . $emailError->getMessage());
            }
        }

        // 4. If there's a last known borrower with contact info, consider notifying them
        // (Optional - you can uncomment if needed)
        /*
        if ($lastKnownBorrower && $lastKnownBorrowerContact && filter_var($lastKnownBorrowerContact, FILTER_VALIDATE_EMAIL)) {
            try {
                queueMissingAssetAlertEmail(
                    $pdo,
                    $lastKnownBorrowerContact,
                    $lastKnownBorrower,
                    $asset['asset_name'],
                    $asset['asset_code'],
                    $reportId,
                    'borrower',
                    $reportDetails
                );
            } catch (Exception $emailError) {
                error_log("Failed to queue borrower email: " . $emailError->getMessage());
            }
        }
        */
    }

    // Return success response immediately (emails are queued for async processing)
    echo json_encode([
        'success' => true,
        'message' => 'Missing asset report submitted successfully',
        'report_id' => $reportId,
        'data' => [
            'report_id' => $reportId,
            'asset_name' => $asset['asset_name'],
            'asset_code' => $asset['asset_code'],
            'status' => 'reported'
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error reporting missing asset: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
