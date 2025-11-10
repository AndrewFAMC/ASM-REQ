<?php
require_once '../../config.php';

// User Management Actions for HCC Asset Management System

/**
 * Get user details with their assigned assets
 */
function getUserDetails($pdo, $userId) {
    try {
        // Get user information
        $stmt = $pdo->prepare("
            SELECT u.*, c.campus_name
            FROM users u
            LEFT JOIN campuses c ON u.campus_id = c.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("User not found");
        }

        // Get assets assigned to this user
        $stmt = $pdo->prepare("
            SELECT a.*, cat.category_name, c.campus_name
            FROM assets a
            LEFT JOIN categories cat ON a.category_id = cat.id
            LEFT JOIN campuses c ON a.campus_id = c.id
            WHERE a.assigned_to = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$userId]);
        $assets = $stmt->fetchAll();

        // Get asset statistics for this user
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_assets,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_assets,
                SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                COALESCE(SUM(value), 0) as total_value
            FROM assets
            WHERE assigned_to = ?
        ");
        $stmt->execute([$userId]);
        $assetStats = $stmt->fetch();

        // Get recent activities for this user's assets
        $stmt = $pdo->prepare("
            SELECT al.*, a.asset_name
            FROM activity_log al
            LEFT JOIN assets a ON al.asset_id = a.id
            WHERE a.assigned_to = ?
            ORDER BY al.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $recentActivities = $stmt->fetchAll();

        return [
            'user' => $user,
            'assets' => $assets,
            'asset_stats' => $assetStats,
            'recent_activities' => $recentActivities
        ];
    } catch (PDOException $e) {
        error_log("Error getting user details: " . $e->getMessage());
        throw new Exception("Failed to load user details");
    }
}

/**
 * Get all staff members with their asset counts
 */
function getAllUsers($pdo, $filters = []) {
    try {
        $where = [];
        $params = [];

        // Get current user info to apply campus restrictions for admins
        $currentUser = getUserInfo();
        if ($currentUser['role'] === 'admin') {
            // Admins can see all non-admin users from their assigned campus
            $where[] = "u.campus_id = ?";
            $params[] = $currentUser['campus_id'];
            // Include all role types: office, employee, custodian (and legacy staff/user for backward compatibility)
            $where[] = "u.role IN ('office', 'employee', 'custodian', 'staff', 'user', 'admin')";
        }

        if (!empty($filters['campus_id'])) {
            $where[] = "u.campus_id = ?";
            $params[] = $filters['campus_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['role'])) {
            $where[] = "u.role = ?";
            $params[] = $filters['role'];
        }

        if (isset($filters['status'])) {
            $where[] = "u.is_active = ?";
            $params[] = $filters['status'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("
            SELECT u.*, c.campus_name, o.office_name,
                   (SELECT COUNT(*) FROM assets a WHERE a.assigned_to = u.id) as asset_count,
                   da.id as approver_id,
                   da.approval_level,
                   da.is_active as is_approver_active
            FROM users u
            LEFT JOIN campuses c ON u.campus_id = c.id
            LEFT JOIN offices o ON u.office_id = o.id
            LEFT JOIN department_approvers da ON u.id = da.approver_user_id AND da.office_id = u.office_id AND da.is_active = 1
            $whereClause
            ORDER BY u.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting users: " . $e->getMessage());
        throw new Exception("Failed to load users data");
    }
}

/**
 * Create a new staff account
 */
function createStaffAccount($pdo, $data) {
    try {
        // Normalize aliases from different forms (camelCase vs snake_case)
        if (empty($data['full_name']) && isset($_POST['fullName'])) {
            $data['full_name'] = trim($_POST['fullName']);
        }
        if (!isset($data['campus_id']) && isset($_POST['campusId'])) {
            $data['campus_id'] = $_POST['campusId'];
        }
        if (!empty($data['role'])) {
            $data['role'] = strtolower(trim($data['role']));
        }
        // Validate role against allowed enum values
        $allowedRoles = ['admin', 'custodian', 'office', 'employee', 'staff', 'user']; // staff and user kept for backward compatibility
        if (empty($data['role']) || !in_array($data['role'], $allowedRoles, true)) {
            throw new Exception("Invalid role supplied");
        }
        // Normalize campus_id (allow null)
        if (array_key_exists('campus_id', $data)) {
            if ($data['campus_id'] === '' || $data['campus_id'] === null) {
                $data['campus_id'] = null;
            } else {
                $data['campus_id'] = (int)$data['campus_id'];
            }
        }

        // Validate required fields (password can be auto-assigned based on role)
        if (empty($data['username']) || empty($data['full_name']) || empty($data['role'])) {
            throw new Exception("Missing required fields");
        }

        // Validate campus and office for office approvers
        if (isset($data['is_approver']) && $data['is_approver'] && strtolower($data['role']) === 'office') {
            if (empty($data['campus_id'])) {
                throw new Exception("Campus is required for office approvers");
            }
            if (empty($data['office_id'])) {
                throw new Exception("Office is required for office approvers");
            }
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            throw new Exception("Username already exists");
        }

        // Check if email already exists (if provided)
        if (!empty($data['email'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Email already exists");
            }
        }

        // Ensure email is present and unique; if missing, auto-generate from username
        if (empty($data['email'])) {
            $baseLocal = preg_replace('/[^a-z0-9._-]/i', '', $data['username']);
            if ($baseLocal === '') {
                $baseLocal = 'user';
            }
            $domain = 'hcc.local';
            $emailCandidate = strtolower($baseLocal . '@' . $domain);
            $suffix = 0;
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$emailCandidate]);
                if (!$stmt->fetch()) {
                    break;
                }
                $suffix++;
                $emailCandidate = strtolower($baseLocal . $suffix . '@' . $domain);
                if ($suffix > 1000) { // safety break
                    throw new Exception('Unable to generate unique email, please provide one.');
                }
            }
            $data['email'] = $emailCandidate;
        }

        // Determine default password if not provided
        $forcePasswordChange = false;
        if (empty($data['password'])) {
            // Use default password 'hccams1' for all roles
            $data['password'] = 'hccams1';
            $forcePasswordChange = true;
        }

        // Hash the password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Begin transaction to ensure user is created before approver record
        $pdo->beginTransaction();

        try {
            // Insert new user with force_password_change flag if using default password
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, full_name, email, role, campus_id, office_id, is_active, force_password_change, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, ?, NOW())
            ");
            $stmt->execute([
                $data['username'],
                $hashedPassword,
                $data['full_name'],
                $data['email'],
                $data['role'],
                $data['campus_id'],
                $data['office_id'] ?? null,
                $forcePasswordChange ? 1 : 0
            ]);

            $userId = $pdo->lastInsertId();

            // Handle approver assignment (only for office role)
            if (isset($data['is_approver']) && $data['is_approver'] && !empty($data['office_id']) && !empty($data['campus_id'])) {
                // Verify role is office
                if (strtolower($data['role']) === 'office') {
                    $approvalLevel = $data['approval_level'] ?? 'primary';
                    $stmt = $pdo->prepare("
                        INSERT INTO department_approvers (office_id, approver_user_id, approval_level, campus_id, assigned_date, is_active)
                        VALUES (?, ?, ?, ?, CURRENT_DATE, 1)
                    ");
                    $stmt->execute([$data['office_id'], $userId, $approvalLevel, $data['campus_id']]);
                    error_log("Created department approver for user $userId at office {$data['office_id']} with level {$approvalLevel}");
                }
            }

            // Commit transaction
            $pdo->commit();

            // If a default password was used, send a welcome email (after commit)
            if ($forcePasswordChange) {
                $defaultPassword = $data['password']; // The password that was just set
                sendAccountCreationEmail($pdo, $data['email'], $data['full_name'], $defaultPassword);
            }

            return ['id' => $userId];
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            throw $e;
        }
    } catch (PDOException $e) {
        error_log("Error creating staff account: " . $e->getMessage());
        throw new Exception("Failed to create user account");
    }
}

/**
 * Update an existing staff account
 */
function updateStaffAccount($pdo, $userId, $data) {
    try {
        // Get current user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch();

        if (!$currentUser) {
            throw new Exception("User not found");
        }

        // Validate campus and office for office approvers
        if (isset($data['is_approver']) && $data['is_approver']) {
            $userRole = $data['role'] ?? $currentUser['role'];
            if (strtolower($userRole) === 'office') {
                $campusId = $data['campus_id'] ?? $currentUser['campus_id'];
                $officeId = $data['office_id'] ?? $currentUser['office_id'];

                if (empty($campusId)) {
                    throw new Exception("Campus is required for office approvers");
                }
                if (empty($officeId)) {
                    throw new Exception("Office is required for office approvers");
                }
            }
        }

        // Build update query
        $updateFields = [];
        $params = [];

        if (!empty($data['username'])) {
            // Check if username is taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$data['username'], $userId]);
            if ($stmt->fetch()) {
                throw new Exception("Username already exists");
            }
            $updateFields[] = "username = ?";
            $params[] = $data['username'];
        }

        if (!empty($data['password'])) {
            $updateFields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['full_name'])) {
            $updateFields[] = "full_name = ?";
            $params[] = $data['full_name'];
        }

        if (isset($data['email'])) {
            // Check if email is taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $userId]);
            if ($stmt->fetch()) {
                throw new Exception("Email already exists");
            }
            $updateFields[] = "email = ?";
            $params[] = $data['email'];
        }

        if (isset($data['role'])) {
            $updateFields[] = "role = ?";
            $params[] = $data['role'];
        }

        if (isset($data['campus_id'])) {
            $updateFields[] = "campus_id = ?";
            $params[] = $data['campus_id'];
        }

        if (isset($data['office_id'])) {
            $updateFields[] = "office_id = ?";
            $params[] = $data['office_id'];
        }

        if (isset($data['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = $data['is_active'];
        }

        if (isset($data['force_password_change'])) {
            $updateFields[] = "force_password_change = ?";
            $params[] = $data['force_password_change'];
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        $params[] = $userId;
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
        $stmt->execute($params);

        // Handle approver assignment (only for office role)
        $officeId = $data['office_id'] ?? $currentUser['office_id'];
        $campusId = $data['campus_id'] ?? $currentUser['campus_id'];
        $userRole = $data['role'] ?? $currentUser['role'];

        if (isset($data['is_approver'])) {
            if ($data['is_approver'] && !empty($officeId) && !empty($campusId) && strtolower($userRole) === 'office') {
                // Check if user is already an approver for this office
                $stmt = $pdo->prepare("SELECT id FROM department_approvers WHERE approver_user_id = ? AND office_id = ?");
                $stmt->execute([$userId, $officeId]);
                $existingApprover = $stmt->fetch();

                $approvalLevel = $data['approval_level'] ?? 'primary';

                if ($existingApprover) {
                    // Update existing approver record
                    $stmt = $pdo->prepare("
                        UPDATE department_approvers
                        SET approval_level = ?, is_active = 1
                        WHERE approver_user_id = ? AND office_id = ?
                    ");
                    $stmt->execute([$approvalLevel, $userId, $officeId]);
                    error_log("Updated department approver for user $userId at office {$officeId} with level {$approvalLevel}");
                } else {
                    // Insert new approver record
                    $stmt = $pdo->prepare("
                        INSERT INTO department_approvers (office_id, approver_user_id, approval_level, campus_id, assigned_date, is_active)
                        VALUES (?, ?, ?, ?, CURRENT_DATE, 1)
                    ");
                    $stmt->execute([$officeId, $userId, $approvalLevel, $campusId]);
                    error_log("Created department approver for user $userId at office {$officeId} with level {$approvalLevel}");
                }
            } else {
                // Remove approver status if checkbox is unchecked or role is not office
                $stmt = $pdo->prepare("DELETE FROM department_approvers WHERE approver_user_id = ? AND office_id = ?");
                $stmt->execute([$userId, $officeId]);
                error_log("Removed department approver status for user $userId at office {$officeId}");
            }
        }

        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Error updating staff account: " . $e->getMessage());
        throw new Exception("Failed to update user account");
    }
}

/**
 * Delete a staff account
 */
function deleteStaffAccount($pdo, $userId) {
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            throw new Exception("User not found");
        }

        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Error deleting staff account: " . $e->getMessage());
        throw new Exception("Failed to delete user account");
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    if (!hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'get_users':
            case 'load_users':
                $filters = isset($_POST['filters']) ? json_decode($_POST['filters'], true) : [];
                
                // Add role filter if provided directly in POST
                if (!empty($_POST['role'])) {
                    $filters['role'] = $_POST['role'];
                }
                
                // Add search filter if provided
                if (!empty($_POST['search'])) {
                    $filters['search'] = $_POST['search'];
                }
                
                // Add campus filter if provided
                if (!empty($_POST['campus_id'])) {
                    $filters['campus_id'] = $_POST['campus_id'];
                }
                
                // Add status filter if provided
                if (isset($_POST['status'])) {
                    $filters['status'] = $_POST['status'];
                }
                
                $users = getAllUsers($pdo, $filters);
                echo json_encode([
                    'success' => true,
                    'data' => $users,
                    // Provide legacy key for dashboard.php compatibility
                    'users' => $users
                ]);
                break;

            case 'get_user_details':
                $userId = $_POST['user_id'] ?? '';
                if (empty($userId)) {
                    echo json_encode(['success' => false, 'message' => 'user_id is required']);
                    break;
                }
                $details = getUserDetails($pdo, $userId);
                echo json_encode([
                    'success' => true,
                    'data' => $details
                ]);
                break;

            case 'add_user':
            case 'create_staff_account':
                // Log incoming data for debugging
                error_log("Add user request received. POST data: " . print_r($_POST, true));
                
                // Create new user (support camelCase and snake_case payloads)
                $data = [
                    'username' => $_POST['username'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'full_name' => $_POST['full_name'] ?? ($_POST['fullName'] ?? ''),
                    'email' => $_POST['email'] ?? '',
                    'role' => isset($_POST['role']) ? strtolower($_POST['role']) : '',
                    'campus_id' => $_POST['campus_id'] ?? ($_POST['campusId'] ?? null),
                    'office_id' => !empty($_POST['office_id']) ? $_POST['office_id'] : null,
                    'is_approver' => isset($_POST['is_approver']) && ($_POST['is_approver'] === 'on' || $_POST['is_approver'] == 1),
                    'approval_level' => $_POST['approval_level'] ?? 'primary'
                ];

                error_log("Processed data: " . print_r($data, true));

                $result = createStaffAccount($pdo, $data);
                echo json_encode([
                    'success' => true,
                    'message' => 'User created successfully',
                    'user_id' => $result['id']
                ]);
                break;

            case 'edit_user':
                // Edit existing user
                $userId = $_POST['user_id'] ?? '';
                $data = [];

                if (!empty($_POST['username'])) $data['username'] = $_POST['username'];
                if (!empty($_POST['full_name']) || !empty($_POST['fullName'])) {
                    $data['full_name'] = $_POST['full_name'] ?? $_POST['fullName'];
                }
                if (!empty($_POST['email'])) $data['email'] = $_POST['email'];
                if (!empty($_POST['role'])) $data['role'] = strtolower($_POST['role']);
                if (isset($_POST['campus_id']) || isset($_POST['campusId'])) $data['campus_id'] = $_POST['campus_id'] ?? $_POST['campusId'];
                if (isset($_POST['office_id'])) $data['office_id'] = !empty($_POST['office_id']) ? $_POST['office_id'] : null;
                if (isset($_POST['is_active'])) $data['is_active'] = $_POST['is_active'];
                if (!empty($_POST['password'])) $data['password'] = $_POST['password'];
                if (isset($_POST['is_approver'])) $data['is_approver'] = ($_POST['is_approver'] === 'on' || $_POST['is_approver'] == 1);
                if (isset($_POST['approval_level'])) $data['approval_level'] = $_POST['approval_level'];

                updateStaffAccount($pdo, $userId, $data);
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
                break;

            case 'delete_user':
                // Delete user
                $userId = $_POST['user_id'] ?? '';
                deleteStaffAccount($pdo, $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
                break;

            case 'get_asset_details':
                $assetId = $_POST['asset_id'] ?? '';
                if (empty($assetId)) {
                    echo json_encode(['success' => false, 'message' => 'asset_id is required']);
                    break;
                }
                try {
                    $stmt = $pdo->prepare("
                        SELECT a.*, cat.category_name, c.campus_name
                        FROM assets a
                        LEFT JOIN categories cat ON a.category_id = cat.id
                        LEFT JOIN campuses c ON a.campus_id = c.id
                        WHERE a.id = ?
                    ");
                    $stmt->execute([$assetId]);
                    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$asset) {
                        echo json_encode(['success' => false, 'message' => 'Asset not found']);
                        break;
                    }
                    echo json_encode(['success' => true, 'data' => $asset]);
                } catch (PDOException $e) {
                    error_log("Error fetching asset details: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            case 'remove_user_asset':
                $assetId = $_POST['asset_id'] ?? '';
                if (empty($assetId)) {
                    echo json_encode(['success' => false, 'message' => 'asset_id is required']);
                    break;
                }
                $asset = fetchOne($pdo, "SELECT id, assigned_to FROM assets WHERE id = ?", [$assetId]);
                if (!$asset) {
                    echo json_encode(['success' => false, 'message' => 'Asset not found']);
                    break;
                }
                // Unassign the asset and mark as returned to pool
                executeQuery($pdo, "UPDATE assets SET assigned_to = NULL, assigned_email = NULL, assignment_date = NULL, unassigned_date = CURRENT_DATE, updated_at = NOW() WHERE id = ?", [$assetId]);
                // Close any open assignment history
                executeQuery($pdo, "UPDATE asset_assignments SET return_date = CURRENT_DATE WHERE asset_id = ? AND return_date IS NULL", [$assetId]);
                // Log activity
                logActivity($pdo, $assetId, 'UNASSIGNED', "Asset unassigned from {$asset['assigned_to']} by admin");
                echo json_encode(['success' => true, 'message' => 'Asset removed from user and returned to Assets list']);
                break;

            case 'transfer_user_asset':
                $assetId = $_POST['asset_id'] ?? '';
                $targetUserId = $_POST['target_user_id'] ?? '';
                if (empty($assetId) || empty($targetUserId)) {
                    echo json_encode(['success' => false, 'message' => 'asset_id and target_user_id are required']);
                    break;
                }
                $target = fetchOne($pdo, "SELECT id, full_name, email, is_active FROM users WHERE id = ?", [$targetUserId]);
                if (!$target || (isset($target['is_active']) && !$target['is_active'])) {
                    echo json_encode(['success' => false, 'message' => 'Target user not found or inactive']);
                    break;
                }
                // Close previous assignment history if open
                executeQuery($pdo, "UPDATE asset_assignments SET return_date = CURRENT_DATE WHERE asset_id = ? AND return_date IS NULL", [$assetId]);
                // Assign to new user
                executeQuery($pdo, "UPDATE assets SET assigned_to = ?, assigned_email = ?, assignment_date = CURRENT_DATE, unassigned_date = NULL, updated_at = NOW() WHERE id = ?", [$target['full_name'], $target['email'], $assetId]);
                // Record new assignment
                executeQuery($pdo, "INSERT INTO asset_assignments (asset_id, assigned_to, assigned_email, assignment_date) VALUES (?, ?, ?, CURRENT_DATE)", [$assetId, $target['full_name'], $target['email']]);
                logActivity($pdo, $assetId, 'TRANSFERRED', "Asset transferred to {$target['full_name']} by admin");
                echo json_encode(['success' => true, 'message' => 'Asset transferred successfully']);
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
