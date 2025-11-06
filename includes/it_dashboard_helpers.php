<?php

function addUser($pdo, $data) {
    $fullName = trim($data['full_name']);
    $email = trim($data['email']);
    $campusId = (int)$data['campus_id'];
    $role = trim($data['role']);
    $allowedRoles = ['admin', 'employee', 'custodian', 'office'];
    
    $defaultPassword = 'user1234';
    
    if (empty($fullName) || empty($email) || empty($campusId) || empty($role)) {
        throw new Exception('Name, email, campus, and role are required.');
    }
    if (!in_array($role, $allowedRoles)) {
        throw new Exception('Invalid user role specified.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format.');
    }
    if (fetchOne($pdo, "SELECT id FROM users WHERE email = ?", [$email])) {
        throw new Exception('Email already exists.');
    }
    
    $username = explode('@', $email)[0];
    $baseUsername = $username;
    $counter = 1;
    while (fetchOne($pdo, "SELECT id FROM users WHERE username = ?", [$username])) {
        $username = $baseUsername . $counter++;
    }
    
    $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    $pdo->beginTransaction();
    try {
        $officeId = null;
        if ($role === 'office') {
            $officeName = trim($data['office_name'] ?? '');
            if (empty($officeName)) {
                throw new Exception('Office Name is required for Office role.');
            }
            $officeFloor = trim($data['office_floor'] ?? '');
            $officeSectionCode = trim($data['office_section_code'] ?? null);

            // Check if office already exists in the campus
            $existingOffice = fetchOne($pdo, "SELECT id FROM offices WHERE office_name = ? AND campus_id = ?", [$officeName, $campusId]);

            if ($existingOffice) {
                $officeId = $existingOffice['id'];
                // Optionally, you could update the existing office details here if needed
            } else {
                // If not, create a new office record
                $officeSql = "INSERT INTO offices (office_name, floor, section_code, campus_id) VALUES (?, ?, ?, ?)";
                executeQuery($pdo, $officeSql, [$officeName, $officeFloor, $officeSectionCode, $campusId]);
                $officeId = $pdo->lastInsertId();
            }
        }

        $sql = "INSERT INTO users (username, full_name, email, password_hash, role, campus_id, office_id, is_active, force_password_change, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, TRUE, NOW())";
        
        executeQuery($pdo, $sql, [$username, $fullName, $email, $passwordHash, $role, $campusId, $officeId]);
        $userId = $pdo->lastInsertId();
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    logActivity($pdo, null, 'USER_CREATED', "New user created by IT Admin: {$fullName} ({$email}) with role {$role}.");
    sendAccountCreationEmail($pdo, $email, $fullName, $defaultPassword);

    return ['id' => $userId, 'username' => $username, 'email' => $email, 'default_password' => $defaultPassword];
}

function getUsers($pdo, $filters = []) {
    $sql = "SELECT u.id, u.username, u.full_name, u.email, u.role, u.campus_id, u.is_active, 
                   u.created_at, u.last_login, c.campus_name, c.campus_code
            FROM users u
            LEFT JOIN campuses c ON u.campus_id = c.id
            WHERE u.email != 'hccsuperadmin@gmail.com'";
    
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    if (!empty($filters['role'])) {
        $sql .= " AND u.role = ?";
        $params[] = $filters['role'];
    }
    if (!empty($filters['campus_id'])) {
        $sql .= " AND u.campus_id = ?";
        $params[] = $filters['campus_id'];
    }
    if (isset($filters['is_active']) && $filters['is_active'] !== '') {
        $sql .= " AND u.is_active = ?";
        $params[] = $filters['is_active'];
    }

    $sql .= " ORDER BY u.created_at DESC";
    return fetchAll($pdo, $sql, $params);
}

function deleteUser($pdo, $userId) {
    // Fetch user details including role and office_id
    $user = fetchOne($pdo, "SELECT full_name, email, role, office_id FROM users WHERE id = ?", [$userId]);
    if (!$user) throw new Exception('User not found.');

    $pdo->beginTransaction();
    try {
        // If the user is an 'office' user and has an associated office, delete the office record first.
        if ($user['role'] === 'office' && !empty($user['office_id'])) {
            // Note: Foreign key constraints should handle unlinking from other tables (e.g., inventory_tags)
            // by setting their office_id to NULL.
            executeQuery($pdo, "DELETE FROM offices WHERE id = ?", [$user['office_id']]);
        }

        // Now, delete the user record.
        executeQuery($pdo, "DELETE FROM users WHERE id = ?", [$userId]);

        $pdo->commit();
        logActivity($pdo, null, 'USER_DELETED', "User deleted by IT Admin: {$user['full_name']} ({$user['email']}). Associated office (if any) was also removed.");
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e; // Re-throw the exception to be caught by the main handler
    }
}

function updateUser($pdo, $data) {
    $userId = (int)$data['user_id'];
    $fullName = trim($data['full_name']);
    $email = trim($data['email']);
    $campusId = (int)$data['campus_id'];
    $role = trim($data['role']);
    $allowedRoles = ['admin', 'employee', 'custodian', 'office'];
    
    if (empty($fullName) || empty($email) || empty($campusId) || empty($role)) throw new Exception('All fields are required.');
    if (!in_array($role, $allowedRoles)) throw new Exception('Invalid user role specified.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email format.');
    
    if (fetchOne($pdo, "SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId])) {
        throw new Exception('Email already in use by another account.');
    }
    
    $sql = "UPDATE users SET full_name = ?, email = ?, campus_id = ?, role = ? WHERE id = ?";
    executeQuery($pdo, $sql, [$fullName, $email, $campusId, $role, $userId]);
    
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 8) throw new Exception('Password must be at least 8 characters.');
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        executeQuery($pdo, "UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?", [$passwordHash, $userId]);
    }
    
    logActivity($pdo, null, 'USER_UPDATED', "User updated by IT Admin: {$fullName} ({$email}).");
    return ['id' => $userId];
}

function toggleUserStatus($pdo, $userId) {
    $user = fetchOne($pdo, "SELECT is_active, full_name FROM users WHERE id = ?", [$userId]);
    if (!$user) throw new Exception('User not found.');
    
    $newStatus = !$user['is_active'];
    executeQuery($pdo, "UPDATE users SET is_active = ? WHERE id = ?", [$newStatus, $userId]);
    
    $statusText = $newStatus ? 'enabled' : 'disabled';
    logActivity($pdo, null, 'USER_STATUS_CHANGED', "User account {$statusText} by IT Admin: {$user['full_name']}.");
    return ['is_active' => $newStatus];
}

/**
 * Forces a specific user to log out by deleting their session.
 * @param PDO $pdo
 * @param int $adminId
 * @return bool // FIX: The parameter name is misleading. It should be $userId
 */
function forceLogoutUser($pdo, $userId) {
    $user = fetchOne($pdo, "SELECT full_name, email FROM users WHERE id = ?", [$userId]);
    if (!$user) throw new Exception('User not found');

    executeQuery($pdo, "DELETE FROM user_sessions WHERE user_id = ?", [$userId]);
    logActivity($pdo, null, 'USER_FORCED_LOGOUT', "Forced logout for user by IT Admin: {$user['full_name']} ({$user['email']})");
    return true;
}

function getSystemStats() {
    // --- CPU Load ---
    $cpu_load = [0, 0, 0];
    if (function_exists('sys_getloadavg')) {
        $cpu_load = sys_getloadavg();
    }

    // --- Memory Usage ---
    $mem_total = function_exists('memory_get_peak_usage') ? memory_get_peak_usage(true) : memory_get_usage(true);
    $mem_limit = ini_get('memory_limit');
    if (preg_match('/^(\d+)(.)$/', $mem_limit, $matches)) {
        if ($matches[2] == 'G') {
            $mem_limit = $matches[1] * 1024 * 1024 * 1024;
        } else if ($matches[2] == 'M') {
            $mem_limit = $matches[1] * 1024 * 1024;
        } else if ($matches[2] == 'K') {
            $mem_limit = $matches[1] * 1024;
        }
    }
    $mem_percent = ($mem_limit > 0) ? ($mem_total / $mem_limit) * 100 : 0;

    // --- Disk Usage ---
    $disk_free = disk_free_space('/');
    $disk_total = disk_total_space('/');
    $disk_used = $disk_total - $disk_free;
    $disk_percent = ($disk_total > 0) ? ($disk_used / $disk_total) * 100 : 0;

    // --- Uptime ---
    $uptime = 'N/A';
    if (function_exists('shell_exec') && strpos(ini_get('disable_functions'), 'shell_exec') === false) {
        $uptime_str = shell_exec('uptime -p');
        if ($uptime_str) {
            $uptime = trim(str_replace('up ', '', $uptime_str));
        }
    }

    return [
        'cpu_load' => round($cpu_load[0] * 100, 2), // Use 1-minute average as a percentage
        'memory' => [
            'used' => round($mem_total / (1024 * 1024), 2),
            'limit' => round($mem_limit / (1024 * 1024), 2),
            'percent' => round($mem_percent, 2)
        ],
        'disk' => [
            'used' => round($disk_used / (1024 * 1024 * 1024), 2),
            'total' => round($disk_total / (1024 * 1024 * 1024), 2),
            'percent' => round($disk_percent, 2)
        ],
        'uptime' => $uptime
    ];
}

function getActivityLogs($pdo, $page = 1, $limit = 10, $search = '') {
    $offset = ($page - 1) * $limit;
    $search_param = "%{$search}%";

    $where_clause = '';
    $params = [];
    if (!empty($search)) {
        $where_clause = "WHERE action LIKE ? OR description LIKE ? OR performed_by LIKE ?";
        $params = [$search_param, $search_param, $search_param];
    }

    $total_records = fetchOne($pdo, "SELECT COUNT(*) as count FROM activity_log {$where_clause}", $params)['count'];
    $total_pages = ceil($total_records / $limit);

    $log_sql = "SELECT * FROM activity_log {$where_clause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $log_params = array_merge($params, [$limit, $offset]);
    $logs = fetchAll($pdo, $log_sql, $log_params);

    return [
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records
        ]
    ];
}

// --- Maintenance Mode Functions ---

define('MAINTENANCE_FILE', __DIR__ . '/../maintenance.json');

/**
 * Gets the current maintenance status from the JSON file.
 * @return array
 */
function getMaintenanceStatus() {
    if (!file_exists(MAINTENANCE_FILE)) {
        // If the file doesn't exist, create it.
        file_put_contents(MAINTENANCE_FILE, json_encode(['status' => 'inactive', 'end_time' => 0, 'message' => 'The system is currently under maintenance.']));
    }
    $status = json_decode(file_get_contents(MAINTENANCE_FILE), true);
    if (isset($status['status']) && $status['status'] === 'active' && time() > $status['end_time']) {
        // Maintenance has expired, so update the file
        $status['status'] = 'inactive';
        file_put_contents(MAINTENANCE_FILE, json_encode($status, JSON_PRETTY_PRINT));
    }
    return $status;
}

/**
 * Starts the maintenance mode.
 * @param int $durationInSeconds
 * @return array
 */
function startMaintenance($durationInSeconds) {
    $endTime = time() + $durationInSeconds;
    $status = ['status' => 'active', 'end_time' => $endTime, 'message' => 'The system is currently under maintenance.'];
    if (@file_put_contents(MAINTENANCE_FILE, json_encode($status, JSON_PRETTY_PRINT)) === false) {
        throw new Exception("Could not write to maintenance file. Please check server permissions for the file: " . MAINTENANCE_FILE);
    }
    return $status;
}

/**
 * Cancels the maintenance mode.
 */
function cancelMaintenance() {
    $status = ['status' => 'inactive', 'end_time' => 0, 'message' => 'The system is currently under maintenance.'];
    if (@file_put_contents(MAINTENANCE_FILE, json_encode($status, JSON_PRETTY_PRINT)) === false) {
        throw new Exception("Could not write to maintenance file. Please check server permissions for the file: " . MAINTENANCE_FILE);
    }
    return $status;
}

function getLoginAttempts($pdo, $limit = 10) {
    $sql = "SELECT * FROM login_attempts ORDER BY attempted_at DESC LIMIT ?";
    return fetchAll($pdo, $sql, [$limit]);
}

function getConnectedUsers($pdo) {
    $sql = "SELECT u.full_name, u.email, s.ip_address, s.user_agent, s.created_at as login_time
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.expires_at > NOW()
            ORDER BY s.created_at DESC";
    return fetchAll($pdo, $sql);
}

?>