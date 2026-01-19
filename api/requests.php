<?php
/**
 * Asset Requests API Endpoint
 *
 * Handles approval workflow for asset requests:
 * - Get pending requests by role
 * - Approve/reject as custodian
 * - Approve/reject as department head
 * - Approve/reject as admin
 * - Release approved assets
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/email/EmailQueue.php';

// Require authentication
requireLogin();

header('Content-Type: application/json');

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

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? $inputData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];
$user = getUserInfo();
$userRole = strtolower($user['role']);

try {
    switch ($action) {
        // Get requests pending approval (filtered by user role)
        case 'get_pending_requests':
            $campusId = $user['campus_id'];
            $status = '';

            // Allow custom status override via query parameter
            if (isset($_GET['status'])) {
                $status = $_GET['status'];
            } else {
                // Determine which requests to show based on role
                // DUAL FLOW SYSTEM:
                // Flow 1: Employee -> Office Head (for office assets)
                // Flow 2: Employee -> Custodian -> Admin (for custodian assets)
                if ($userRole === 'custodian') {
                    // Show requests directed to custodian (request_source='custodian')
                    $status = 'pending';
                } elseif ($userRole === 'office') {
                    // Show requests directed to this office (request_source='office')
                    $status = 'office_review';
                } elseif ($userRole === 'admin' || $userRole === 'super_admin') {
                    // Show requests approved by custodian, pending admin final approval
                    $status = 'custodian_review';
                }
            }

            $statusList = explode(',', $status);
            $placeholders = str_repeat('?,', count($statusList) - 1) . '?';

            // Build WHERE clause based on role
            $whereClause = "ar.campus_id = ? AND ar.status IN ($placeholders)";
            $params = [$campusId];
            $params = array_merge($params, $statusList);

            // For office users, only show requests directed to their office
            if ($userRole === 'office') {
                $whereClause .= " AND ar.request_source = 'office' AND ar.target_office_id = ?";
                $params[] = $user['office_id'];
            }
            // For custodian, only show requests directed to custodian
            elseif ($userRole === 'custodian') {
                $whereClause .= " AND ar.request_source = 'custodian'";
            }

            $stmt = $pdo->prepare("
                SELECT
                    ar.*,
                    a.asset_name,
                    a.value as asset_value,
                    c.category_name,
                    u.full_name as requester_name,
                    u.email as requester_email,
                    cam.campus_name,
                    custodian.full_name as custodian_name,
                    dept.full_name as department_approver_name,
                    admin.full_name as admin_approver_name,
                    office_head.full_name as office_approver_name,
                    target_office.office_name as target_office_name,
                    requester_office.office_name as requester_office_name
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.requester_id = u.id
                JOIN campuses cam ON ar.campus_id = cam.id
                LEFT JOIN users custodian ON ar.custodian_reviewed_by = custodian.id
                LEFT JOIN users dept ON ar.department_approved_by = dept.id
                LEFT JOIN users admin ON ar.final_approved_by = admin.id
                LEFT JOIN users office_head ON ar.office_approved_by = office_head.id
                LEFT JOIN offices target_office ON ar.target_office_id = target_office.id
                LEFT JOIN offices requester_office ON ar.requester_office_id = requester_office.id
                WHERE $whereClause
                ORDER BY ar.request_date DESC
            ");

            $stmt->execute($params);
            $requests = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'requests' => $requests,
                'count' => count($requests)
            ]);
            break;

        // Get single request details
        case 'get_request':
            $requestId = (int)($_GET['request_id'] ?? 0);

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT
                    ar.*,
                    a.asset_name,
                    a.serial_number,
                    a.value as asset_value,
                    c.category_name,
                    u.full_name as requester_name,
                    u.email as requester_email,
                    cam.campus_name,
                    custodian.full_name as custodian_name,
                    custodian.email as custodian_email,
                    dept.full_name as department_approver_name,
                    dept.email as department_approver_email,
                    admin.full_name as admin_approver_name,
                    admin.email as admin_approver_email,
                    office_head.full_name as office_approver_name,
                    office_head.email as office_approver_email,
                    releaser.full_name as releaser_name,
                    returner.full_name as returner_name,
                    COALESCE(a.serial_number, CONCAT('ID-', a.id)) as asset_code
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.requester_id = u.id
                JOIN campuses cam ON ar.campus_id = cam.id
                LEFT JOIN users custodian ON ar.custodian_reviewed_by = custodian.id
                LEFT JOIN users dept ON ar.department_approved_by = dept.id
                LEFT JOIN users admin ON ar.final_approved_by = admin.id
                LEFT JOIN users office_head ON ar.office_approved_by = office_head.id
                LEFT JOIN users releaser ON ar.released_by = releaser.id
                LEFT JOIN users returner ON ar.returned_by = returner.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            // Build approval history from the request data
            $approvalHistory = [];

            // Custodian Review
            if ($request['custodian_reviewed_by'] && $request['custodian_reviewed_at']) {
                $approvalHistory[] = [
                    'approver_name' => $request['custodian_name'],
                    'approver_role' => 'Custodian',
                    'status' => 'approved',
                    'comments' => $request['custodian_review_notes'] ?? '',
                    'created_at' => $request['custodian_reviewed_at']
                ];
            }

            // Office Head Approval
            if ($request['office_approved_by'] && $request['office_approved_at']) {
                $approvalHistory[] = [
                    'approver_name' => $request['office_approver_name'],
                    'approver_role' => 'Office Head',
                    'status' => 'approved',
                    'comments' => $request['office_approval_notes'] ?? '',
                    'created_at' => $request['office_approved_at']
                ];
            }

            // Department Approval
            if ($request['department_approved_by'] && $request['department_approved_at']) {
                $approvalHistory[] = [
                    'approver_name' => $request['department_approver_name'],
                    'approver_role' => 'Department Head',
                    'status' => 'approved',
                    'comments' => '',
                    'created_at' => $request['department_approved_at']
                ];
            }

            // Admin Final Approval
            if ($request['final_approved_by'] && $request['final_approved_at']) {
                $approvalHistory[] = [
                    'approver_name' => $request['admin_approver_name'],
                    'approver_role' => 'Administrator',
                    'status' => 'approved',
                    'comments' => $request['admin_notes'] ?? '',
                    'created_at' => $request['final_approved_at']
                ];
            }

            // Rejection
            if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])) {
                $approvalHistory[] = [
                    'approver_name' => 'System',
                    'approver_role' => 'Approver',
                    'status' => 'rejected',
                    'comments' => $request['rejection_reason'],
                    'created_at' => $request['approval_date'] ?? $request['request_date']
                ];
            }

            // Released
            if ($request['released_by'] && $request['released_date']) {
                $approvalHistory[] = [
                    'approver_name' => $request['releaser_name'],
                    'approver_role' => 'Asset Releaser',
                    'status' => 'released',
                    'comments' => $request['release_notes'] ?? 'Asset released to requester',
                    'created_at' => $request['released_date']
                ];
            }

            // Returned
            if ($request['returned_by'] && $request['returned_date']) {
                $returnComment = "Asset returned in {$request['return_condition']} condition";
                if (!empty($request['return_notes'])) {
                    $returnComment .= ". Notes: {$request['return_notes']}";
                }
                if ($request['days_overdue'] > 0 && !empty($request['late_return_remarks'])) {
                    $returnComment .= ". Late Return ({$request['days_overdue']} days overdue): {$request['late_return_remarks']}";
                }

                $approvalHistory[] = [
                    'approver_name' => $request['returner_name'],
                    'approver_role' => 'Return Processor',
                    'status' => $request['days_overdue'] > 0 ? 'late_return' : 'returned',
                    'comments' => $returnComment,
                    'created_at' => $request['returned_date']
                ];
            }

            // Sort by date
            usort($approvalHistory, function($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });

            $request['approval_history'] = $approvalHistory;

            echo json_encode([
                'success' => true,
                'request' => $request
            ]);
            break;

        // Approve request as Custodian
        case 'approve_as_custodian':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Only custodians can approve at this level');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);
            $comments = $_POST['comments'] ?? '';

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details with requester info
            $stmt = $pdo->prepare("
                SELECT ar.*,
                       a.asset_name,
                       u.full_name as requester_name,
                       u.email as requester_email
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.id = ? AND ar.campus_id = ?
            ");
            $stmt->execute([$requestId, $user['campus_id']]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found or access denied');
            }

            if ($request['status'] !== 'pending') {
                throw new Exception('Request is not in pending status');
            }

            // Update request to custodian_review (ready for admin approval)
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'custodian_review',
                    custodian_reviewed_by = ?,
                    custodian_reviewed_at = NOW(),
                    custodian_review_notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$userId, $comments, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_APPROVED_CUSTODIAN', "Request #{$requestId} approved by custodian");

            // Queue email notification to requester (async)
            queueRequestNotificationEmail(
                $pdo,
                $request['requester_email'],
                $request['requester_name'],
                $requestId,
                $request['asset_name'],
                'approved',
                "Your request for {$request['asset_name']} has been approved by the custodian and is pending final approval.",
                'normal'
            );

            // Send notification to admin for final approval
            // Flow: Employee -> Custodian -> Admin (no department head)
            $adminStmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE role IN ('admin', 'super_admin') AND campus_id = ? AND is_active = TRUE LIMIT 1");
            $adminStmt->execute([$user['campus_id']]);
            $admin = $adminStmt->fetch();

            if ($admin) {
                // Send in-app notification to admin
                createNotification(
                    $pdo,
                    $admin['id'],
                    NOTIFICATION_APPROVAL_REQUEST,
                    "Asset Request Needs Admin Approval",
                    "Request #{$requestId} has been reviewed by custodian and requires final approval.",
                    [
                        'related_type' => 'request',
                        'related_id' => $requestId,
                        'priority' => 'medium',
                        'action_url' => '/AMS-REQ/admin/requests.php?id=' . $requestId
                    ]
                );
            }

            echo json_encode([
                'success' => true,
                'message' => 'Request approved successfully. Notification sent to next approver.'
            ]);
            break;

        // Office Head Approval (for requests directed to office)
        case 'approve_as_office':
            // Check if user is office head
            if ($userRole !== 'office') {
                throw new Exception('Only office users can approve office requests');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);
            $comments = $_POST['comments'] ?? '';

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details
            $stmt = $pdo->prepare("
                SELECT ar.*, a.asset_name, u.full_name as requester_name, u.email as requester_email
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.id = ? AND ar.target_office_id = ?
            ");
            $stmt->execute([$requestId, $user['office_id']]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found or not directed to your office');
            }

            if ($request['status'] !== 'office_review') {
                throw new Exception('Request is not pending office approval. Current status: ' . $request['status']);
            }

            // Approve request - this completes the flow for office requests
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'approved',
                    office_approved_by = ?,
                    office_approved_at = NOW(),
                    office_approval_notes = ?,
                    approved_by = ?,
                    approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId, $comments, $userId, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_APPROVED_OFFICE', "Request #{$requestId} approved by office head");

            // Send notification to requester
            queueRequestNotificationEmail(
                $pdo,
                $request['requester_email'],
                $request['requester_name'],
                $requestId,
                $request['asset_name'],
                'approved',
                "Your request for {$request['asset_name']} has been approved by {$user['full_name']} and is ready for pickup.",
                'high'
            );

            // Send in-app notification to requester
            createNotification(
                $pdo,
                $request['requester_id'],
                NOTIFICATION_APPROVAL_RESPONSE,
                "Asset Request Approved",
                "Your request for {$request['asset_name']} has been approved. Please coordinate pickup.",
                [
                    'related_type' => 'request',
                    'related_id' => $requestId,
                    'priority' => 'high'
                ]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Request approved successfully. Requester has been notified.'
            ]);
            break;

        // Final approval as Admin
        case 'approve_as_admin':
            if (!hasRole('admin') && !hasRole('super_admin')) {
                throw new Exception('Only administrators can perform final approval');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);
            $comments = $_POST['comments'] ?? '';

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details with requester and asset info
            $stmt = $pdo->prepare("
                SELECT ar.*,
                       a.asset_name,
                       u.full_name as requester_name,
                       u.email as requester_email
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            // Check if custodian reviewed (status should be 'custodian_review')
            if ($request['status'] !== 'custodian_review') {
                throw new Exception('Request must be reviewed by custodian first. Current status: ' . $request['status']);
            }

            // Update request to 'approved' status (final approval)
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'approved',
                    approved_by = ?,
                    approved_at = NOW(),
                    admin_notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$userId, $comments, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_APPROVED_ADMIN', "Request #{$requestId} approved by admin - ready for release");

            // Notify requester
            createNotification(
                $pdo,
                $request['requester_id'],
                NOTIFICATION_APPROVAL_RESPONSE,
                "Asset Request Approved!",
                "Your request #{$requestId} has been approved. Please coordinate with the custodian for asset pickup.",
                [
                    'related_type' => 'request',
                    'related_id' => $requestId,
                    'priority' => 'high',
                    'action_url' => '/AMS-REQ/staff/my_requests.php?id=' . $requestId
                ]
            );

            // Queue email notification to requester (async)
            queueRequestNotificationEmail(
                $pdo,
                $request['requester_email'],
                $request['requester_name'],
                $requestId,
                $request['asset_name'],
                'approved',
                "Your request for {$request['asset_name']} has been fully approved! Please coordinate with the custodian for asset pickup.",
                'high'
            );

            // Notify custodian to release asset
            createNotification(
                $pdo,
                $request['custodian_reviewed_by'],
                NOTIFICATION_SYSTEM_ALERT,
                "Asset Ready for Release",
                "Request #{$requestId} has been fully approved. Please release the asset to the requester.",
                [
                    'related_type' => 'request',
                    'related_id' => $requestId,
                    'priority' => 'medium',
                    'action_url' => '/AMS-REQ/custodian/requests.php?id=' . $requestId
                ]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Request approved successfully. Requester and custodian have been notified.'
            ]);
            break;

        // Reject request (can be done at any approval level)
        case 'reject_request':
            $requestId = (int)($_POST['request_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            if (empty($reason)) {
                throw new Exception('Rejection reason is required');
            }

            // Get request details with requester and asset info
            $stmt = $pdo->prepare("
                SELECT ar.*,
                       a.asset_name,
                       u.full_name as requester_name,
                       u.email as requester_email
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            // Verify user has permission to reject
            // DUAL FLOW SYSTEM:
            // Office requests: Office head can reject
            // Custodian requests: Custodian and Admin can reject
            $canReject = false;
            if (hasRole('admin') || hasRole('super_admin')) {
                // Admin can reject at any stage
                $canReject = true;
            } elseif (hasRole('custodian') && $request['request_source'] === 'custodian' && in_array($request['status'], ['pending', 'custodian_review'])) {
                // Custodian can reject custodian-directed requests
                $canReject = true;
            } elseif (hasRole('office') && $request['request_source'] === 'office' && $request['status'] === 'office_review' && $request['target_office_id'] == $user['office_id']) {
                // Office head can reject requests directed to their office
                $canReject = true;
            }

            if (!$canReject) {
                throw new Exception('You do not have permission to reject this request at this stage');
            }

            // Update request status to rejected
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'rejected',
                    rejection_reason = ?
                WHERE id = ?
            ");
            $stmt->execute([$reason, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_REJECTED', "Request #{$requestId} rejected: {$reason}");

            // Notify requester
            createNotification(
                $pdo,
                $request['requester_id'],
                NOTIFICATION_APPROVAL_RESPONSE,
                "Asset Request Rejected",
                "Your request #{$requestId} has been rejected. Reason: {$reason}",
                [
                    'related_type' => 'request',
                    'related_id' => $requestId,
                    'priority' => 'high',
                    'action_url' => '/AMS-REQ/staff/my_requests.php?id=' . $requestId
                ]
            );

            // Queue email notification to requester (async)
            queueRequestNotificationEmail(
                $pdo,
                $request['requester_email'],
                $request['requester_name'],
                $requestId,
                $request['asset_name'],
                'rejected',
                "Your request for {$request['asset_name']} has been rejected. Reason: {$reason}",
                'high'
            );

            echo json_encode([
                'success' => true,
                'message' => 'Request rejected. Requester has been notified.'
            ]);
            break;

        // Release asset (create borrowing record after full approval)
        case 'release_asset':
            if (!hasRole('custodian') && !hasRole('admin') && !hasRole('office')) {
                throw new Exception('Only custodians, admins, or office heads can release assets');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details with requester info
            $stmt = $pdo->prepare("
                SELECT ar.*,
                       a.asset_name,
                       u.full_name as requester_name,
                       u.email as requester_email
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            if ($request['status'] !== 'approved') {
                throw new Exception('Request must be fully approved before release. Current status: ' . $request['status']);
            }

            // Begin transaction
            $pdo->beginTransaction();

            try {
                // Create borrowing record using the actual table structure
                $borrowingStmt = $pdo->prepare("
                    INSERT INTO asset_borrowings
                    (asset_id, borrower_name, borrower_type, expected_return_date, notes, status, recorded_by)
                    VALUES (?, ?, 'Teacher', ?, ?, 'active', ?)
                ");
                $borrowingStmt->execute([
                    $request['asset_id'],
                    $request['requester_name'],
                    $request['expected_return_date'],
                    $request['purpose'],
                    $user['full_name']
                ]);
                $borrowingId = $pdo->lastInsertId();

                // Update request status to released
                $updateReqStmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET status = 'released',
                        released_date = NOW(),
                        released_by = ?
                    WHERE id = ?
                ");
                $updateReqStmt->execute([$userId, $requestId]);

                // Update asset quantity (if tracked)
                $updateAssetStmt = $pdo->prepare("
                    UPDATE assets
                    SET quantity = quantity - ?
                    WHERE id = ? AND quantity >= ?
                ");
                $updated = $updateAssetStmt->execute([$request['quantity'], $request['asset_id'], $request['quantity']]);

                if (!$updated) {
                    throw new Exception('Insufficient asset quantity available');
                }

                // Log activity
                logActivity($pdo, $request['asset_id'], 'ASSET_RELEASED', "Asset released for borrowing #{$borrowingId} via request #{$requestId}");

                $pdo->commit();

                // Notify requester
                createNotification(
                    $pdo,
                    $request['requester_id'],
                    NOTIFICATION_SYSTEM_ALERT,
                    "Asset Released",
                    "Your requested asset has been released. Please return by {$request['expected_return_date']}.",
                    [
                        'related_type' => 'borrowing',
                        'related_id' => $borrowingId,
                        'priority' => 'medium',
                        'action_url' => '/AMS-REQ/staff/borrowings.php?id=' . $borrowingId
                    ]
                );

                // Queue email notification to requester (async)
                queueRequestNotificationEmail(
                    $pdo,
                    $request['requester_email'],
                    $request['requester_name'],
                    $requestId,
                    $request['asset_name'],
                    'released',
                    "Your requested asset '{$request['asset_name']}' has been released. Please return it by " . date('F j, Y', strtotime($request['expected_return_date'])) . ".",
                    'high'
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Asset released successfully. Borrowing record created.',
                    'borrowing_id' => $borrowingId
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // Return asset (process asset return)
        case 'return_asset':
            if (!hasRole('custodian') && !hasRole('admin') && !hasRole('office')) {
                throw new Exception('Only custodians, admins, or office heads can process returns');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);
            $condition = $_POST['condition'] ?? 'good';
            $notes = $_POST['notes'] ?? '';
            $lateReturnRemarks = $_POST['late_return_remarks'] ?? null;
            $isOverdue = $_POST['is_overdue'] ?? false;
            $daysOverdue = (int)($_POST['days_overdue'] ?? 0);

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details with requester and asset info
            $stmt = $pdo->prepare("
                SELECT ar.*,
                       a.asset_name,
                       u.full_name as requester_name,
                       u.email as requester_email
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            if ($request['status'] !== 'released') {
                throw new Exception('Asset is not currently released');
            }

            // Begin transaction
            $pdo->beginTransaction();

            try {
                // Update request status
                $updateStmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET status = 'returned',
                        returned_date = NOW(),
                        returned_by = ?,
                        return_condition = ?,
                        return_notes = ?,
                        late_return_remarks = ?,
                        days_overdue = ?,
                        late_return_recorded_by = ?,
                        late_return_recorded_at = ?
                    WHERE id = ?
                ");

                $updateStmt->execute([
                    $userId,
                    $condition,
                    $notes,
                    $isOverdue ? $lateReturnRemarks : null,
                    $isOverdue ? $daysOverdue : 0,
                    $isOverdue ? $userId : null,
                    $isOverdue ? date('Y-m-d H:i:s') : null,
                    $requestId
                ]);

                // Note: Borrowing record updates handled separately if needed

                // Increase asset quantity back
                $assetStmt = $pdo->prepare("
                    UPDATE assets
                    SET quantity = quantity + ?
                    WHERE id = ?
                ");
                $assetStmt->execute([$request['quantity'], $request['asset_id']]);

                // Log activity
                $activityMessage = $isOverdue
                    ? "Asset returned {$daysOverdue} day(s) late via request #{$requestId}. Remarks: {$lateReturnRemarks}"
                    : "Asset returned on time via request #{$requestId}";
                logActivity($pdo, $request['asset_id'], 'ASSET_RETURNED', $activityMessage);

                $pdo->commit();

                // Notify requester
                createNotification(
                    $pdo,
                    $request['requester_id'],
                    NOTIFICATION_SYSTEM_ALERT,
                    "Asset Return Confirmed",
                    "Your asset return has been processed. Condition: {$condition}." . ($isOverdue ? " Note: This was a late return." : ""),
                    [
                        'related_type' => 'request',
                        'related_id' => $requestId,
                        'priority' => $isOverdue ? 'high' : 'medium'
                    ]
                );

                // Queue email notification to requester (async, especially important for late returns)
                if ($isOverdue) {
                    $emailMessage = "Your borrowed asset '{$request['asset_name']}' has been returned {$daysOverdue} day(s) late. " .
                                    "Condition: {$condition}. Late return remarks: {$lateReturnRemarks}";
                    queueRequestNotificationEmail(
                        $pdo,
                        $request['requester_email'],
                        $request['requester_name'],
                        $requestId,
                        $request['asset_name'],
                        'late_return',
                        $emailMessage,
                        'high'
                    );
                } else {
                    $emailMessage = "Your borrowed asset '{$request['asset_name']}' has been returned on time. Condition: {$condition}. Thank you!";
                    queueRequestNotificationEmail(
                        $pdo,
                        $request['requester_email'],
                        $request['requester_name'],
                        $requestId,
                        $request['asset_name'],
                        'returned',
                        $emailMessage,
                        'normal'
                    );
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Asset returned successfully',
                    'is_late' => $isOverdue,
                    'days_overdue' => $daysOverdue
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // Create new asset request with dual-flow support
        case 'create_request':
            // Only employees can create requests
            if ($userRole !== 'employee') {
                throw new Exception('Only employees can create requests');
            }

            $assetId = (int)($_POST['asset_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            $purpose = trim($_POST['purpose'] ?? '');
            $expectedReturnDate = $_POST['expected_return_date'] ?? '';
            $requestSource = $_POST['request_source'] ?? 'custodian'; // 'office' or 'custodian'
            $targetOfficeId = isset($_POST['target_office_id']) ? (int)$_POST['target_office_id'] : null;

            // Validation
            if (!$assetId) {
                throw new Exception('Asset ID is required');
            }

            if ($quantity < 1) {
                throw new Exception('Quantity must be at least 1');
            }

            if (empty($purpose)) {
                throw new Exception('Purpose is required');
            }

            if (strlen($purpose) < 10) {
                throw new Exception('Purpose must be at least 10 characters');
            }

            if (empty($expectedReturnDate)) {
                throw new Exception('Expected return date is required');
            }

            if (strtotime($expectedReturnDate) <= time()) {
                throw new Exception('Expected return date must be in the future');
            }

            // Validate request source
            if (!in_array($requestSource, ['office', 'custodian'])) {
                throw new Exception('Invalid request source');
            }

            // For office requests, target_office_id is required
            if ($requestSource === 'office' && !$targetOfficeId) {
                throw new Exception('Target office is required for office requests');
            }

            // For custodian requests, target_office_id should be null
            if ($requestSource === 'custodian') {
                $targetOfficeId = null;
            }

            // Verify asset exists and is available
            $stmt = $pdo->prepare("
                SELECT a.*, c.category_name
                FROM assets a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.id = ?
                AND a.status IN ('Available', 'Unavailable')
            ");
            $stmt->execute([$assetId]);
            $asset = $stmt->fetch();

            if (!$asset) {
                throw new Exception('Asset not found or not available');
            }

            // Check if asset is from the correct source
            if ($requestSource === 'office') {
                // Verify office exists and asset is assigned to that office
                $stmt = $pdo->prepare("
                    SELECT id, office_name, campus_id
                    FROM offices
                    WHERE id = ?
                ");
                $stmt->execute([$targetOfficeId]);
                $office = $stmt->fetch();

                if (!$office) {
                    throw new Exception('Office not found');
                }

                // Verify asset is assigned to this office via inventory_tags
                // and check available quantity excluding damaged/missing/under repair/disposed units
                $tagCheckStmt = $pdo->prepare("
                    SELECT
                        it.borrowable_quantity,
                        COALESCE(unavailable_units.unavailable_count, 0) as unavailable_units,
                        (COALESCE(it.borrowable_quantity, 0) - COALESCE(unavailable_units.unavailable_count, 0)) as office_available
                    FROM inventory_tags it
                    LEFT JOIN (
                        SELECT asset_id, COUNT(*) as unavailable_count
                        FROM asset_units
                        WHERE unit_status IN ('Damaged', 'Missing', 'Under Repair', 'Disposed')
                        GROUP BY asset_id
                    ) unavailable_units ON it.asset_id = unavailable_units.asset_id
                    WHERE it.asset_id = ? AND it.office_id = ? AND it.status IN ('Active', 'Available')
                ");
                $tagCheckStmt->execute([$assetId, $targetOfficeId]);
                $tagCheck = $tagCheckStmt->fetch();

                if (!$tagCheck || $tagCheck['office_available'] <= 0) {
                    throw new Exception('Asset is not available in the selected office - all units assigned, damaged, missing, under repair, or disposed');
                }

                // Use office available quantity for validation
                $availableQty = $tagCheck['office_available'];
            } else {
                // For custodian requests, check remaining quantity in central inventory
                // Calculate: Total - Inactive - Assigned to Offices - Unavailable Units (damaged/missing/under repair/disposed)
                $custodianQtyStmt = $pdo->prepare("
                    SELECT
                        a.quantity,
                        COALESCE(a.inactive_quantity, 0) as inactive,
                        COALESCE(SUM(it.quantity), 0) as assigned_to_offices,
                        COALESCE(unavailable_units.unavailable_count, 0) as unavailable_units,
                        (a.quantity - COALESCE(a.inactive_quantity, 0) - COALESCE(SUM(it.quantity), 0) - COALESCE(unavailable_units.unavailable_count, 0)) as custodian_available
                    FROM assets a
                    LEFT JOIN inventory_tags it ON a.id = it.asset_id AND it.status IN ('Active', 'Available')
                    LEFT JOIN (
                        SELECT asset_id, COUNT(*) as unavailable_count
                        FROM asset_units
                        WHERE unit_status IN ('Damaged', 'Missing', 'Under Repair', 'Disposed')
                        GROUP BY asset_id
                    ) unavailable_units ON a.id = unavailable_units.asset_id
                    WHERE a.id = ?
                    GROUP BY a.id, a.quantity, a.inactive_quantity, unavailable_units.unavailable_count
                ");
                $custodianQtyStmt->execute([$assetId]);
                $custodianQty = $custodianQtyStmt->fetch();

                if (!$custodianQty || $custodianQty['custodian_available'] <= 0) {
                    throw new Exception('Asset is not available in central inventory - all units assigned, inactive, or unavailable (damaged/missing/under repair/disposed)');
                }

                // Use custodian available quantity for validation
                $availableQty = $custodianQty['custodian_available'];
            }

            // Check available quantity
            if (!isset($availableQty)) {
                $availableQty = $asset['quantity'] - ($asset['inactive_quantity'] ?? 0);
            }

            if ($quantity > $availableQty) {
                throw new Exception("Only {$availableQty} units available in " . ($requestSource === 'office' ? 'this office' : 'central inventory'));
            }

            // Create the request
            $pdo->beginTransaction();
            try {
                // Set initial status based on request source
                $initialStatus = ($requestSource === 'office') ? 'office_review' : 'pending';

                $stmt = $pdo->prepare("
                    INSERT INTO asset_requests
                    (requester_id, asset_id, campus_id, quantity, purpose, expected_return_date,
                     status, request_date, request_source, target_office_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                ");

                $stmt->execute([
                    $userId,
                    $assetId,
                    $user['campus_id'],
                    $quantity,
                    $purpose,
                    $expectedReturnDate,
                    $initialStatus,
                    $requestSource,
                    $targetOfficeId
                ]);

                $requestId = $pdo->lastInsertId();

                // Notify appropriate approver based on request source
                if ($requestSource === 'office') {
                    // Notify office head(s)
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.full_name, u.email
                        FROM users u
                        WHERE u.role = 'office'
                        AND u.office_id = ?
                        AND u.is_active = TRUE
                    ");
                    $stmt->execute([$targetOfficeId]);
                    $approvers = $stmt->fetchAll();

                    if (empty($approvers)) {
                        throw new Exception("No office head found for the selected office");
                    }

                    // Initialize EmailQueue
                    $emailQueue = new EmailQueue($pdo);

                    foreach ($approvers as $approver) {
                        // Create in-app notification
                        createNotification(
                            $pdo,
                            $approver['id'],
                            NOTIFICATION_APPROVAL_REQUEST,
                            'New Office Asset Request',
                            "{$user['full_name']} has requested {$asset['asset_name']} (Qty: {$quantity}) from your office. Please review.",
                            [
                                'related_type' => 'request',
                                'related_id' => $requestId,
                                'priority' => 'high',
                                'action_url' => '/AMS-REQ/office/approve_requests.php',
                                'requester_name' => $user['full_name'],
                                'asset_name' => $asset['asset_name'],
                                'quantity' => $quantity,
                                'request_id' => $requestId,
                                'role' => 'office'
                            ]
                        );

                        // Queue email notification to office head
                        $emailSubject = "New Asset Request from {$user['full_name']}";
                        $emailBody = "
                            <h2>New Office Asset Request</h2>
                            <p>You have received a new asset request that requires your approval.</p>

                            <h3>Request Details:</h3>
                            <ul>
                                <li><strong>Requester:</strong> {$user['full_name']} ({$user['email']})</li>
                                <li><strong>Asset:</strong> {$asset['asset_name']}</li>
                                <li><strong>Quantity:</strong> {$quantity}</li>
                                <li><strong>Purpose:</strong> {$purpose}</li>
                                <li><strong>Expected Return:</strong> {$expectedReturnDate}</li>
                                <li><strong>Request ID:</strong> #{$requestId}</li>
                            </ul>

                            <p><strong>Action Required:</strong> Please log in to review and approve or reject this request.</p>

                            <p><a href='" . ($_SERVER['REQUEST_SCHEME'] ?? 'http') . "://{$_SERVER['HTTP_HOST']}/AMS-REQ/office/approve_requests.php' style='background-color: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Review Request</a></p>

                            <hr>
                            <p style='font-size: 12px; color: #666;'>This is an automated notification from the Asset Management System.</p>
                        ";

                        $emailQueue->add(
                            $approver['email'],
                            $approver['full_name'],
                            $emailSubject,
                            $emailBody,
                            'high',
                            'request',
                            $requestId
                        );
                    }
                } else {
                    // Notify custodian(s)
                    $stmt = $pdo->prepare("
                        SELECT id, full_name, email
                        FROM users
                        WHERE role = 'custodian'
                        AND campus_id = ?
                        AND is_active = TRUE
                    ");
                    $stmt->execute([$user['campus_id']]);
                    $custodians = $stmt->fetchAll();

                    if (empty($custodians)) {
                        throw new Exception("No custodian found for your campus");
                    }

                    foreach ($custodians as $custodian) {
                        createNotification(
                            $pdo,
                            $custodian['id'],
                            NOTIFICATION_APPROVAL_REQUEST,
                            'New Asset Request',
                            "{$user['full_name']} has requested {$asset['asset_name']} (Qty: {$quantity}). Please review and approve.",
                            [
                                'related_type' => 'request',
                                'related_id' => $requestId,
                                'priority' => 'high',
                                'action_url' => '/AMS-REQ/custodian/approve_requests.php',
                                'requester_name' => $user['full_name'],
                                'asset_name' => $asset['asset_name'],
                                'quantity' => $quantity,
                                'request_id' => $requestId,
                                'role' => 'custodian'
                            ]
                        );
                    }
                }

                // Log activity
                logActivity($pdo, $assetId, 'REQUEST_SUBMITTED',
                    "Asset request submitted by {$user['full_name']} for {$quantity} unit(s) via {$requestSource}");

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Request submitted successfully!',
                    'request_id' => $requestId,
                    'status' => $initialStatus,
                    'request_source' => $requestSource
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // Get office requests ready for release (approved status)
        case 'get_office_requests_for_release':
            if (!hasRole('office') && !hasRole('admin')) {
                throw new Exception('Only office heads can access this endpoint');
            }

            $campusId = $user['campus_id'];
            $officeId = $user['office_id'];

            // Get approved requests for this office
            $stmt = $pdo->prepare("
                SELECT
                    ar.*,
                    a.asset_name,
                    a.serial_number,
                    a.value as asset_value,
                    c.category_name,
                    u.full_name as requester_name,
                    u.email as requester_email,
                    u.office_id as requester_office_id,
                    req_office.office_name as requester_office_name,
                    office_head.full_name as office_approver_name,
                    DATEDIFF(ar.expected_return_date, CURDATE()) as days_until_return
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.requester_id = u.id
                LEFT JOIN offices req_office ON u.office_id = req_office.id
                LEFT JOIN users office_head ON ar.office_approved_by = office_head.id
                WHERE ar.campus_id = ?
                    AND ar.request_source = 'office'
                    AND ar.target_office_id = ?
                    AND ar.status = 'approved'
                ORDER BY ar.request_date ASC
            ");
            $stmt->execute([$campusId, $officeId]);
            $requests = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'requests' => $requests,
                'count' => count($requests)
            ]);
            break;

        // Get office requests ready for return (released status)
        case 'get_office_requests_for_return':
            if (!hasRole('office') && !hasRole('admin')) {
                throw new Exception('Only office heads can access this endpoint');
            }

            $campusId = $user['campus_id'];
            $officeId = $user['office_id'];

            // Get released requests for this office
            $stmt = $pdo->prepare("
                SELECT
                    ar.*,
                    a.asset_name,
                    a.serial_number,
                    a.value as asset_value,
                    c.category_name,
                    u.full_name as requester_name,
                    u.email as requester_email,
                    u.office_id as requester_office_id,
                    req_office.office_name as requester_office_name,
                    DATEDIFF(CURDATE(), ar.expected_return_date) as days_overdue,
                    DATEDIFF(ar.expected_return_date, CURDATE()) as days_remaining,
                    CASE
                        WHEN CURDATE() > ar.expected_return_date THEN 1
                        ELSE 0
                    END as is_overdue
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.requester_id = u.id
                LEFT JOIN offices req_office ON u.office_id = req_office.id
                WHERE ar.campus_id = ?
                    AND ar.request_source = 'office'
                    AND ar.target_office_id = ?
                    AND ar.status = 'released'
                ORDER BY
                    CASE WHEN CURDATE() > ar.expected_return_date THEN 0 ELSE 1 END,
                    ar.expected_return_date ASC
            ");
            $stmt->execute([$campusId, $officeId]);
            $requests = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'requests' => $requests,
                'count' => count($requests)
            ]);
            break;

        // Get office approval history (all requests approved/rejected by this office head)
        case 'get_office_approval_history':
            if (!hasRole('office') && !hasRole('admin')) {
                throw new Exception('Only office heads can access this endpoint');
            }

            $campusId = $user['campus_id'];
            $userId = $user['id'];

            // Get all requests approved or rejected by this office head
            $stmt = $pdo->prepare("
                SELECT
                    ar.*,
                    a.asset_name,
                    a.serial_number,
                    a.value as asset_value,
                    c.category_name,
                    u.full_name as requester_name,
                    u.email as requester_email,
                    u.office_id as requester_office_id,
                    req_office.office_name as requester_office_name
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.requester_id = u.id
                LEFT JOIN offices req_office ON u.office_id = req_office.id
                WHERE ar.campus_id = ?
                    AND ar.office_approved_by = ?
                    AND ar.status IN ('approved', 'rejected', 'released', 'returned')
                ORDER BY ar.office_approved_at DESC
            ");
            $stmt->execute([$campusId, $userId]);
            $requests = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'requests' => $requests,
                'count' => count($requests)
            ]);
            break;

        // Get custodian approval history (all requests approved/rejected by this custodian)
        case 'get_custodian_approval_history':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Only custodians can access this endpoint');
            }

            $campusId = $user['campus_id'];
            $userId = $user['id'];

            // Get all requests approved or rejected by this custodian
            $stmt = $pdo->prepare("
                SELECT
                    ar.*,
                    a.asset_name,
                    a.serial_number,
                    a.value as asset_value,
                    c.category_name,
                    u.full_name as requester_name,
                    u.email as requester_email,
                    u.office_id as requester_office_id,
                    req_office.office_name as requester_office_name
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.requester_id = u.id
                LEFT JOIN offices req_office ON u.office_id = req_office.id
                WHERE ar.campus_id = ?
                    AND ar.custodian_reviewed_by = ?
                    AND ar.status IN ('approved', 'rejected', 'released', 'returned', 'office_review', 'department_review', 'custodian_review')
                ORDER BY ar.custodian_reviewed_at DESC
            ");
            $stmt->execute([$campusId, $userId]);
            $requests = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'requests' => $requests,
                'count' => count($requests)
            ]);
            break;

        case 'generate_random_tag_number':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Only custodians and admins can generate tag numbers');
            }

            // Get request ID if provided (to determine office prefix)
            $requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : null;
            $officePrefix = 'OFFICE'; // Default prefix

            // If request ID provided, get the office prefix
            if ($requestId) {
                $stmt = $pdo->prepare("
                    SELECT o.office_name
                    FROM asset_requests ar
                    JOIN offices o ON ar.requester_office_id = o.id
                    WHERE ar.id = ?
                ");
                $stmt->execute([$requestId]);
                $officeName = $stmt->fetchColumn();

                if ($officeName) {
                    // Extract prefix from office name (e.g., "MIS Office" -> "MIS")
                    $words = explode(' ', $officeName);
                    $officePrefix = strtoupper(substr($words[0], 0, 3));
                }
            }

            // Generate tag number with format: {PREFIX}-{MMDDYY}-{RANDOM4DIGITS}
            $month = date('m');
            $day = date('d');
            $year = date('y');

            // Generate random 4-digit number and ensure uniqueness
            $maxAttempts = 100;
            $attempts = 0;
            $tagNumber = null;

            while ($attempts < $maxAttempts) {
                $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $proposedTag = "{$officePrefix}-{$month}{$day}{$year}-{$random}";

                // Check if tag already exists
                $stmt = $pdo->prepare("SELECT id FROM inventory_tags WHERE tag_number = ?");
                $stmt->execute([$proposedTag]);

                if (!$stmt->fetch()) {
                    $tagNumber = $proposedTag;
                    break;
                }

                $attempts++;
            }

            if (!$tagNumber) {
                throw new Exception('Failed to generate unique tag number after multiple attempts');
            }

            echo json_encode([
                'success' => true,
                'tagNumber' => $tagNumber
            ]);
            break;

        case 'auto_generate_tag_and_transfer':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Only custodians and admins can release assets');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details
            $stmt = $pdo->prepare("
                SELECT ar.*,
                       a.asset_name,
                       u.full_name as requester_name,
                       u.email as requester_email,
                       o.office_name as requester_office_name
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                LEFT JOIN offices o ON ar.requester_office_id = o.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            if ($request['status'] !== 'approved') {
                throw new Exception('Request must be approved before release. Current status: ' . $request['status']);
            }

            if (!$request['requester_office_id']) {
                throw new Exception('This is not an office transfer request');
            }

            // Auto-generate tag number
            $officeName = $request['requester_office_name'];
            $words = explode(' ', $officeName);
            $officePrefix = strtoupper(substr($words[0], 0, 3));

            $month = date('m');
            $day = date('d');
            $year = date('y');

            // Generate unique random tag
            $maxAttempts = 100;
            $attempts = 0;
            $tagNumber = null;

            while ($attempts < $maxAttempts) {
                $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $proposedTag = "{$officePrefix}-{$month}{$day}{$year}-{$random}";

                $stmt = $pdo->prepare("SELECT id FROM inventory_tags WHERE tag_number = ?");
                $stmt->execute([$proposedTag]);

                if (!$stmt->fetch()) {
                    $tagNumber = $proposedTag;
                    break;
                }

                $attempts++;
            }

            if (!$tagNumber) {
                throw new Exception('Failed to generate unique tag number');
            }

            // Begin transaction
            $pdo->beginTransaction();

            try {
                // 1. Create inventory tag
                $stmt = $pdo->prepare("
                    INSERT INTO inventory_tags
                    (tag_number, asset_id, office_id, status, assigned_by_custodian_id,
                     inventory_date, remarks, quantity, unit_price, total_value)
                    VALUES (?, ?, ?, 'Pending Verification', ?, NOW(), ?, ?, 0, 0)
                ");
                $stmt->execute([
                    $tagNumber,
                    $request['asset_id'],
                    $request['requester_office_id'],
                    $userId,
                    "Auto-generated via approved office request #{$requestId}",
                    $request['quantity']
                ]);
                $tagId = $pdo->lastInsertId();

                // 2. Update asset: decrease quantity, assign to office
                $stmt = $pdo->prepare("SELECT quantity FROM assets WHERE id = ?");
                $stmt->execute([$request['asset_id']]);
                $currentQuantity = $stmt->fetchColumn();

                if ($currentQuantity < $request['quantity']) {
                    throw new Exception('Insufficient asset quantity available');
                }

                $stmt = $pdo->prepare("
                    UPDATE assets
                    SET quantity = quantity - ?,
                        assigned_to = ?,
                        status = CASE
                            WHEN quantity - ? <= 0 THEN 'In Use'
                            ELSE status
                        END
                    WHERE id = ? AND quantity >= ?
                ");
                $stmt->execute([
                    $request['quantity'],
                    $request['requester_office_id'],
                    $request['quantity'],
                    $request['asset_id'],
                    $request['quantity']
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Failed to update asset quantity');
                }

                // 3. Update request status to 'released'
                $stmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET status = 'released',
                        released_date = NOW(),
                        released_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $requestId]);

                // 4. Log activity
                logActivity($pdo, $request['asset_id'], 'ASSIGNED_TO_OFFICE',
                    "Asset transferred to office '{$request['requester_office_name']}' via approved request #{$requestId}. Tag: {$tagNumber}");

                // 5. Send notification
                createNotification(
                    $pdo,
                    $request['requester_id'],
                    'request_approved',
                    'Asset Transfer Completed',
                    "Your request has been approved and the asset has been transferred to your office. Inventory tag: {$tagNumber}. Please verify receipt on your dashboard.",
                    [
                        'related_type' => 'request',
                        'related_id' => $requestId,
                        'priority' => 'high',
                        'action_url' => '/AMS-REQ/office/office_dashboard.php'
                    ]
                );

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Asset released and transferred successfully',
                    'tagNumber' => $tagNumber,
                    'officeName' => $request['requester_office_name'],
                    'tagId' => $tagId
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'generate_tag_for_office_request':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Only custodians and admins can generate inventory tags');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);
            $tagNumber = trim($_POST['tag_number'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');

            if (!$requestId || !$tagNumber) {
                throw new Exception('Request ID and tag number are required');
            }

            // Get request details
            $stmt = $pdo->prepare("
                SELECT ar.*,
                       a.asset_name,
                       u.full_name as requester_name,
                       u.email as requester_email,
                       o.office_name as requester_office_name
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN users u ON ar.requester_id = u.id
                LEFT JOIN offices o ON ar.requester_office_id = o.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            if ($request['status'] !== 'approved') {
                throw new Exception('Request must be approved before generating tag. Current status: ' . $request['status']);
            }

            if (!$request['requester_office_id']) {
                throw new Exception('This is not an office transfer request');
            }

            // Check if tag number already exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_tags WHERE tag_number = ?");
            $stmt->execute([$tagNumber]);
            if ($stmt->fetch()) {
                throw new Exception('Tag number already exists. Please use a different tag number.');
            }

            // Begin transaction
            $pdo->beginTransaction();

            try {
                // 1. Create inventory tag
                $stmt = $pdo->prepare("
                    INSERT INTO inventory_tags
                    (tag_number, asset_id, office_id, status, assigned_by_custodian_id,
                     inventory_date, remarks, quantity, unit_price, total_value)
                    VALUES (?, ?, ?, 'Pending Verification', ?, NOW(), ?, ?, 0, 0)
                ");
                $stmt->execute([
                    $tagNumber,
                    $request['asset_id'],
                    $request['requester_office_id'],
                    $userId,
                    $remarks,
                    $request['quantity']
                ]);
                $tagId = $pdo->lastInsertId();

                // 2. Update asset: decrease quantity, assign to office
                $stmt = $pdo->prepare("
                    SELECT quantity FROM assets WHERE id = ?
                ");
                $stmt->execute([$request['asset_id']]);
                $currentQuantity = $stmt->fetchColumn();

                if ($currentQuantity < $request['quantity']) {
                    throw new Exception('Insufficient asset quantity available');
                }

                $stmt = $pdo->prepare("
                    UPDATE assets
                    SET quantity = quantity - ?,
                        assigned_to = ?,
                        status = CASE
                            WHEN quantity - ? <= 0 THEN 'In Use'
                            ELSE status
                        END
                    WHERE id = ? AND quantity >= ?
                ");
                $stmt->execute([
                    $request['quantity'],
                    $request['requester_office_id'],
                    $request['quantity'],
                    $request['asset_id'],
                    $request['quantity']
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Failed to update asset quantity');
                }

                // 3. Update request status to 'released' (transferred)
                $stmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET status = 'released',
                        released_date = NOW(),
                        released_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $requestId]);

                // 4. Log activity
                logActivity($pdo, $request['asset_id'], 'ASSIGNED_TO_OFFICE',
                    "Asset transferred to office '{$request['requester_office_name']}' via approved request #{$requestId}. Tag: {$tagNumber}");

                // 5. Create notification for office user
                createNotification(
                    $pdo,
                    $request['requester_id'],
                    'request_approved',
                    'Asset Transfer Completed',
                    "Your request has been approved and the asset has been transferred to your office. Inventory tag: {$tagNumber}. Please verify receipt on your dashboard.",
                    [
                        'related_type' => 'request',
                        'related_id' => $requestId,
                        'priority' => 'high',
                        'action_url' => '/AMS-REQ/office/office_dashboard.php'
                    ]
                );

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Inventory tag generated and asset transferred to office',
                    'tag_id' => $tagId,
                    'tag_number' => $tagNumber
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
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
?>
