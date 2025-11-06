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

// Require authentication
requireLogin();

header('Content-Type: application/json');

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
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

            // Determine which requests to show based on role
            if ($userRole === 'custodian') {
                // Show requests that are pending initial custodian approval
                $status = 'pending';
            } elseif ($userRole === 'department' || hasRole('department')) {
                // Show requests approved by custodian, pending department approval
                $status = 'approved_custodian';
            } elseif ($userRole === 'admin' || $userRole === 'super_admin') {
                // Show requests pending admin approval
                $status = $_GET['status'] ?? 'approved_department,approved_custodian';
            }

            $statusList = explode(',', $status);
            $placeholders = str_repeat('?,', count($statusList) - 1) . '?';

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
                    admin.full_name as admin_approver_name
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.user_id = u.id
                JOIN campuses cam ON ar.campus_id = cam.id
                LEFT JOIN users custodian ON ar.custodian_approved_by = custodian.id
                LEFT JOIN users dept ON ar.department_approved_by = dept.id
                LEFT JOIN users admin ON ar.admin_approved_by = admin.id
                WHERE ar.campus_id = ? AND ar.status IN ($placeholders)
                ORDER BY ar.created_at DESC
            ");

            $params = array_merge([$campusId], $statusList);
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
                    u.phone as requester_phone,
                    cam.campus_name,
                    custodian.full_name as custodian_name,
                    custodian.email as custodian_email,
                    dept.full_name as department_approver_name,
                    dept.email as department_approver_email,
                    admin.full_name as admin_approver_name,
                    admin.email as admin_approver_email
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON ar.user_id = u.id
                JOIN campuses cam ON ar.campus_id = cam.id
                LEFT JOIN users custodian ON ar.custodian_approved_by = custodian.id
                LEFT JOIN users dept ON ar.department_approved_by = dept.id
                LEFT JOIN users admin ON ar.admin_approved_by = admin.id
                WHERE ar.id = ?
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

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

            // Get request details
            $stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ? AND campus_id = ?");
            $stmt->execute([$requestId, $user['campus_id']]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found or access denied');
            }

            if ($request['status'] !== 'pending') {
                throw new Exception('Request is not in pending status');
            }

            // Update request
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'approved_custodian',
                    custodian_approved_by = ?,
                    custodian_approved_at = NOW(),
                    custodian_comments = ?
                WHERE id = ?
            ");
            $stmt->execute([$userId, $comments, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_APPROVED_CUSTODIAN', "Request #{$requestId} approved by custodian");

            // Determine next approver
            $requireDeptApproval = getSystemSetting($pdo, 'require_department_approval', false);
            $requireAdminApproval = getSystemSetting($pdo, 'require_admin_approval', true);

            // Send notification to next approver
            if ($requireDeptApproval) {
                // Find department head for this campus
                $deptHeadStmt = $pdo->prepare("SELECT approver_user_id FROM department_approvers WHERE campus_id = ? AND is_active = TRUE LIMIT 1");
                $deptHeadStmt->execute([$user['campus_id']]);
                $deptHead = $deptHeadStmt->fetch();

                if ($deptHead) {
                    createNotification(
                        $pdo,
                        $deptHead['approver_user_id'],
                        NOTIFICATION_APPROVAL_REQUEST,
                        "Asset Request Needs Department Approval",
                        "Request #{$requestId} for {$request['quantity']}x asset has been approved by custodian and requires your approval.",
                        [
                            'related_type' => 'request',
                            'related_id' => $requestId,
                            'priority' => 'high',
                            'action_url' => '/AMS-REQ/department/requests.php?id=' . $requestId
                        ]
                    );
                }
            } elseif ($requireAdminApproval) {
                // Send to admin directly
                $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND campus_id = ? AND is_active = TRUE LIMIT 1");
                $adminStmt->execute([$user['campus_id']]);
                $admin = $adminStmt->fetch();

                if ($admin) {
                    createNotification(
                        $pdo,
                        $admin['id'],
                        NOTIFICATION_APPROVAL_REQUEST,
                        "Asset Request Needs Admin Approval",
                        "Request #{$requestId} has been approved by custodian and requires final approval.",
                        [
                            'related_type' => 'request',
                            'related_id' => $requestId,
                            'priority' => 'medium',
                            'action_url' => '/AMS-REQ/admin/requests.php?id=' . $requestId
                        ]
                    );
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Request approved successfully. Notification sent to next approver.'
            ]);
            break;

        // Approve request as Department Head
        case 'approve_as_department':
            // Check if user is department head
            $deptCheck = $pdo->prepare("SELECT * FROM department_approvers WHERE approver_user_id = ? AND is_active = TRUE");
            $deptCheck->execute([$userId]);
            if (!$deptCheck->fetch() && !hasRole('admin')) {
                throw new Exception('You are not authorized as a department approver');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);
            $comments = $_POST['comments'] ?? '';

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details
            $stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            if ($request['status'] !== 'approved_custodian') {
                throw new Exception('Request must be approved by custodian first');
            }

            // Update request
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'approved_department',
                    department_approved_by = ?,
                    department_approved_at = NOW(),
                    department_comments = ?
                WHERE id = ?
            ");
            $stmt->execute([$userId, $comments, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_APPROVED_DEPARTMENT', "Request #{$requestId} approved by department head");

            // Send notification to admin for final approval
            $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND campus_id = ? AND is_active = TRUE LIMIT 1");
            $adminStmt->execute([$request['campus_id']]);
            $admin = $adminStmt->fetch();

            if ($admin) {
                createNotification(
                    $pdo,
                    $admin['id'],
                    NOTIFICATION_APPROVAL_REQUEST,
                    "Asset Request Needs Final Approval",
                    "Request #{$requestId} has been approved by custodian and department. Requires final approval.",
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
                'message' => 'Request approved successfully. Notification sent to admin.'
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

            // Get request details
            $stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            // Check if custodian approved
            if ($request['custodian_approved_by'] === null) {
                throw new Exception('Request must be approved by custodian first');
            }

            // Update request to approved_admin status
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'approved_admin',
                    admin_approved_by = ?,
                    admin_approved_at = NOW(),
                    admin_comments = ?
                WHERE id = ?
            ");
            $stmt->execute([$userId, $comments, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_APPROVED_ADMIN', "Request #{$requestId} approved by admin - ready for release");

            // Notify requester
            createNotification(
                $pdo,
                $request['user_id'],
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

            // Notify custodian to release asset
            createNotification(
                $pdo,
                $request['custodian_approved_by'],
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

            // Get request details
            $stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            // Verify user has permission to reject
            $canReject = false;
            if (hasRole('admin') || hasRole('super_admin')) {
                $canReject = true;
            } elseif (hasRole('custodian') && $request['status'] === 'pending') {
                $canReject = true;
            } elseif ($request['status'] === 'approved_custodian') {
                // Check if user is department approver
                $deptCheck = $pdo->prepare("SELECT * FROM department_approvers WHERE approver_user_id = ? AND is_active = TRUE");
                $deptCheck->execute([$userId]);
                if ($deptCheck->fetch()) {
                    $canReject = true;
                }
            }

            if (!$canReject) {
                throw new Exception('You do not have permission to reject this request');
            }

            // Update request status to rejected
            $stmt = $pdo->prepare("
                UPDATE asset_requests
                SET status = 'rejected',
                    rejection_reason = ?,
                    rejected_by = ?,
                    rejected_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $userId, $requestId]);

            // Log activity
            logActivity($pdo, $request['asset_id'], 'REQUEST_REJECTED', "Request #{$requestId} rejected: {$reason}");

            // Notify requester
            createNotification(
                $pdo,
                $request['user_id'],
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

            echo json_encode([
                'success' => true,
                'message' => 'Request rejected. Requester has been notified.'
            ]);
            break;

        // Release asset (create borrowing record after full approval)
        case 'release_asset':
            if (!hasRole('custodian') && !hasRole('admin')) {
                throw new Exception('Only custodians can release assets');
            }

            $requestId = (int)($_POST['request_id'] ?? 0);

            if (!$requestId) {
                throw new Exception('Request ID is required');
            }

            // Get request details
            $stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('Request not found');
            }

            if ($request['status'] !== 'approved_admin') {
                throw new Exception('Request must be fully approved before release');
            }

            // Begin transaction
            $pdo->beginTransaction();

            try {
                // Create borrowing record
                $borrowingStmt = $pdo->prepare("
                    INSERT INTO asset_borrowings
                    (user_id, asset_id, campus_id, quantity, borrow_date, expected_return_date, purpose, status)
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, 'active')
                ");
                $borrowingStmt->execute([
                    $request['user_id'],
                    $request['asset_id'],
                    $request['campus_id'],
                    $request['quantity'],
                    $request['expected_return_date'],
                    $request['purpose']
                ]);
                $borrowingId = $pdo->lastInsertId();

                // Update request status to released
                $updateReqStmt = $pdo->prepare("
                    UPDATE asset_requests
                    SET status = 'released',
                        released_at = NOW(),
                        borrowing_id = ?
                    WHERE id = ?
                ");
                $updateReqStmt->execute([$borrowingId, $requestId]);

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
                    $request['user_id'],
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
