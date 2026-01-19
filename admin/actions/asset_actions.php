<?php
// Admin Asset Actions for HCC Asset Management System

// Get admin statistics
function getAdminStats($pdo) {
    try {
        $stats = [];

        // Total assets
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM assets");
        $stats['total_assets'] = $stmt->fetch()['total'];

        // Active users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = TRUE");
        $stats['active_users'] = $stmt->fetch()['total'];

        // Pending requests
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM asset_requests WHERE status = 'pending'");
        $stats['pending_requests'] = $stmt->fetch()['total'];

        // Total asset value
        $stmt = $pdo->query("SELECT COALESCE(SUM(value), 0) as total FROM assets");
        $stats['total_value'] = $stmt->fetch()['total'];

        // Assets by campus
        $stmt = $pdo->query("
            SELECT c.campus_name, COUNT(a.id) as count
            FROM campuses c
            LEFT JOIN assets a ON c.id = a.campus_id
            GROUP BY c.id, c.campus_name
            ORDER BY c.campus_name
        ");
        $stats['assets_by_campus'] = $stmt->fetchAll();

        // Assets by status
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count
            FROM assets
            GROUP BY status
            ORDER BY status
        ");
        $stats['assets_by_status'] = $stmt->fetchAll();

        // Assets by category
        $stmt = $pdo->query("
            SELECT cat.category_name, COUNT(a.id) as count
            FROM categories cat
            LEFT JOIN assets a ON cat.id = a.category_id
            GROUP BY cat.id, cat.category_name
            ORDER BY cat.category_name
        ");
        $stats['assets_by_category'] = $stmt->fetchAll();

        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting admin stats: " . $e->getMessage());
        throw new Exception("Failed to load admin statistics");
    }
}

// Get recent activities
function getRecentActivities($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT al.*, a.asset_name, u.full_name as performed_by_name, c.campus_name
            FROM activity_log al
            LEFT JOIN assets a ON al.asset_id = a.id
            LEFT JOIN users u ON al.performed_by = u.username
            LEFT JOIN campuses c ON a.campus_id = c.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting recent activities: " . $e->getMessage());
        throw new Exception("Failed to load recent activities");
    }
}

// Asset Requests Management
function getAssetRequests($pdo, $filters = []) {
    try {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "ar.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['campus_id'])) {
            $where[] = "ar.campus_id = ?";
            $params[] = $filters['campus_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "ar.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "ar.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT ar.*, u.full_name as requested_by_name, u.username, c.campus_name, cat.category_name
            FROM asset_requests ar
            LEFT JOIN users u ON ar.requested_by = u.id
            LEFT JOIN campuses c ON ar.campus_id = c.id
            LEFT JOIN categories cat ON ar.category_id = cat.id
            $whereClause
            ORDER BY ar.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting asset requests: " . $e->getMessage());
        throw new Exception("Failed to load asset requests");
    }
}

function approveAssetRequest($pdo, $requestId, $data) {
    try {
        $pdo->beginTransaction();

        // Get request details
        $stmt = $pdo->prepare("SELECT * FROM asset_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            throw new Exception("Request not found");
        }

        if ($request['status'] !== 'pending') {
            throw new Exception("Request is not in pending status");
        }

        // Update request status
        $stmt = $pdo->prepare("
            UPDATE asset_requests
            SET status = 'approved', approved_by = ?, approved_at = NOW(), admin_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$data['approved_by'], $data['admin_notes'] ?? '', $requestId]);

        // If specific asset was requested and it's available, assign it
        if (!empty($request['asset_id'])) {
            $stmt = $pdo->prepare("
                UPDATE assets
                SET assigned_to = ?, assigned_date = NOW(), status = 'Active'
                WHERE id = ? AND status = 'Available'
            ");
            $stmt->execute([$request['requested_by'], $request['asset_id']]);
        }

        // Log activity
        logActivity($pdo, $request['asset_id'] ?? null, 'REQUEST_APPROVED', "Asset request approved: " . $request['description']);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error approving asset request: " . $e->getMessage());
        throw $e;
    }
}

function rejectAssetRequestLegacy($pdo, $requestId, $data) {
    try {
        $stmt = $pdo->prepare("
            UPDATE asset_requests
            SET status = 'rejected', approved_by = ?, approved_at = NOW(), admin_notes = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$data['approved_by'], $data['admin_notes'] ?? '', $requestId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Request not found or not in pending status");
        }

        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Error rejecting asset request: " . $e->getMessage());
        throw new Exception("Failed to reject asset request");
    }
}

// User Management
function getUsers($pdo, $filters = []) {
    try {
        $where = [];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = "u.role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['campus_id'])) {
            $where[] = "u.campus_id = ?";
            $params[] = $filters['campus_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "u.is_active = ?";
            $params[] = $filters['status'] === 'active' ? 1 : 0;
        }

        if (!empty($filters['search'])) {
            $where[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT u.*, c.campus_name
            FROM users u
            LEFT JOIN campuses c ON u.campus_id = c.id
            $whereClause
            ORDER BY u.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting users: " . $e->getMessage());
        throw new Exception("Failed to load users");
    }
}

function createUser($pdo, $data) {
    try {
        $pdo->beginTransaction();

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists");
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, full_name, email, role, campus_id, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW())
        ");
        $stmt->execute([
            $data['username'],
            $passwordHash,
            $data['full_name'],
            $data['email'],
            $data['role'],
            $data['campus_id'] ?? null
        ]);

        $userId = $pdo->lastInsertId();

        $pdo->commit();
        return ['id' => $userId];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating user: " . $e->getMessage());
        throw $e;
    }
}

function updateUser($pdo, $userId, $data) {
    try {
        $pdo->beginTransaction();

        // Check if username or email conflicts with other users
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$data['username'], $data['email'], $userId]);
        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists");
        }

        // Build update query
        $updateFields = [];
        $params = [];

        if (isset($data['username'])) {
            $updateFields[] = "username = ?";
            $params[] = $data['username'];
        }

        if (isset($data['full_name'])) {
            $updateFields[] = "full_name = ?";
            $params[] = $data['full_name'];
        }

        if (isset($data['email'])) {
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

        if (isset($data['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = $data['is_active'];
        }

        if (!empty($data['password'])) {
            $updateFields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        $params[] = $userId;
        $stmt = $pdo->prepare("UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $stmt->execute($params);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating user: " . $e->getMessage());
        throw $e;
    }
}

function deleteUser($pdo, $userId) {
    try {
        $pdo->beginTransaction();

        // Check if user has assigned assets
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE assigned_to = ?");
        $stmt->execute([$userId]);
        if ($stmt->fetch()['count'] > 0) {
            throw new Exception("Cannot delete user with assigned assets. Please reassign assets first.");
        }

        // Soft delete by deactivating
        $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$userId]);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting user: " . $e->getMessage());
        throw $e;
    }
}

// Asset Management
function getAllAssets($pdo, $filters = []) {
    try {
        $where = [];
        $params = [];

        if (!empty($filters['campus_id'])) {
            $where[] = "a.campus_id = ?";
            $params[] = $filters['campus_id'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "a.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = "a.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(a.asset_name LIKE ? OR a.serial_number LIKE ? OR a.location LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT a.*, c.campus_name, cat.category_name, u.full_name as assigned_to_name
            FROM assets a
            LEFT JOIN campuses c ON a.campus_id = c.id
            LEFT JOIN categories cat ON a.category_id = cat.id
            LEFT JOIN users u ON a.assigned_to = u.id
            $whereClause
            ORDER BY a.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting all assets: " . $e->getMessage());
        throw new Exception("Failed to load assets");
    }
}

function createAsset($pdo, $data) {
    try {
        $pdo->beginTransaction();

        // Check if serial number already exists
        if (!empty($data['serial_number'])) {
            $stmt = $pdo->prepare("SELECT id FROM assets WHERE serial_number = ?");
            $stmt->execute([$data['serial_number']]);
            if ($stmt->fetch()) {
                throw new Exception("Serial number already exists");
            }
        }

        // Auto-generate serial number if not provided
        if (empty($data['serial_number'])) {
            $year = date('y'); // Two-digit year
            $campusCode = str_pad($data['campus_id'], 2, '0', STR_PAD_LEFT);
            $categoryCode = str_pad($data['category_id'], 2, '0', STR_PAD_LEFT);
            $uniqueId = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $data['serial_number'] = "HCC{$year}{$campusCode}{$categoryCode}{$uniqueId}";
        }

        // Get current user info for created_by
        $user = getUserInfo();
        $createdBy = $user['id'];

        // Handle assignment for admin-created assets
        $assignedTo = null; // Custodian-added assets are not pre-assigned
        $assignedEmail = $data['assigned_email'] ?? null;
        $assignmentDate = null;

        if (!empty($assignedTo)) {
            $assignmentDate = date('Y-m-d');
        }

        // Insert asset
        $stmt = $pdo->prepare("
            INSERT INTO assets (asset_name, serial_number, barcode, category_id, campus_id, location, purchase_date, value, status, description, remarks, assigned_to, assigned_email, assignment_date, created_by, created_at, quantity, inventory_date, supplier, article, size, counted_by, checked_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['asset_name'],
            $data['serial_number'],
            $data['serial_number'], // Barcode data is the serial number
            $data['category_id'],
            $data['campus_id'],
            $data['location'] ?? '',
            $data['purchase_date'] ?? null,
            $data['value'] ?? 0,
            $data['status'] ?? 'Inactive',
            $data['description'] ?? '', // Keep original description
            $data['remarks'] ?? '',
            $assignedTo,
            $assignedEmail,
            $assignmentDate,
            $createdBy,
            $data['quantity'] ?? 1,
            $data['inventory_date'] ?? null,
            $data['supplier'] ?? null,
            $data['article'] ?? null,
            $data['size'] ?? null,
            $data['counted_by'] ?? null,
            $data['checked_by'] ?? null
        ]);

        $assetId = $pdo->lastInsertId();
        $quantity = $data['quantity'] ?? 1;

        // Log activity
        logActivity($pdo, $assetId, 'CREATED', "Asset created: " . $data['asset_name']);

        // AUTO-CREATE INDIVIDUAL UNITS for quantity > 1
        if ($quantity > 1) {
            // Enable individual tracking
            $trackStmt = $pdo->prepare("UPDATE assets SET track_individually = TRUE WHERE id = ?");
            $trackStmt->execute([$assetId]);

            // Create units using stored procedure
            $unitsStmt = $pdo->prepare("CALL sp_create_units_for_asset(?, ?, ?)");
            $unitsStmt->execute([$assetId, $quantity, $createdBy]);

            logActivity($pdo, $assetId, 'UNITS_CREATED', "Automatically created {$quantity} individual units with unique serial numbers");
        }

        if (!empty($assignedTo)) {
            logActivity($pdo, $assetId, 'ASSIGNED', "Asset assigned to {$assignedTo}");

            // Add to assignment history
            $assignSql = "INSERT INTO asset_assignments (asset_id, assigned_to, assigned_email, assignment_date) VALUES (?, ?, ?, ?)";
            executeQuery($pdo, $assignSql, [$assetId, $assignedTo, $assignedEmail, $assignmentDate]);
        }

        $pdo->commit();
        return [
            'id' => $assetId,
            'barcode' => $data['serial_number'],
            'units_created' => $quantity > 1 ? $quantity : 0
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating asset: " . $e->getMessage());
        throw $e;
    }
}

function updateAsset($pdo, $assetId, $data) {
    try {
        $pdo->beginTransaction();

        // Check if serial number conflicts
        if (!empty($data['serial_number'])) {
            $stmt = $pdo->prepare("SELECT id FROM assets WHERE serial_number = ? AND id != ?");
            $stmt->execute([$data['serial_number'], $assetId]);
            if ($stmt->fetch()) {
                throw new Exception("Serial number already exists");
            }
        }

        // Build update query
        $updateFields = [];
        $params = [];

        if (isset($data['asset_name'])) {
            $updateFields[] = "asset_name = ?";
            $params[] = $data['asset_name'];
        }

        if (isset($data['serial_number'])) {
            $updateFields[] = "serial_number = ?";
            $params[] = $data['serial_number'];
        }

        if (isset($data['category_id'])) {
            $updateFields[] = "category_id = ?";
            $params[] = $data['category_id'];
        }

        if (isset($data['campus_id'])) {
            $updateFields[] = "campus_id = ?";
            $params[] = $data['campus_id'];
        }

        if (isset($data['location'])) {
            $updateFields[] = "location = ?";
            $params[] = $data['location'];
        }

        if (isset($data['purchase_date'])) {
            $updateFields[] = "purchase_date = ?";
            $params[] = $data['purchase_date'];
        }

        if (isset($data['value'])) {
            $updateFields[] = "value = ?";
            $params[] = $data['value'];
        }

        if (isset($data['status'])) {
            $updateFields[] = "status = ?";
            $params[] = $data['status'];
        }

        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        $params[] = $assetId;
        $stmt = $pdo->prepare("UPDATE assets SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $stmt->execute($params);

        // Log activity
        logActivity($pdo, $assetId, 'UPDATED', "Asset updated: " . ($data['asset_name'] ?? 'Unknown'));

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating asset: " . $e->getMessage());
        throw $e;
    }
}

// function deleteAsset($pdo, $assetId) {
//     try {
//         $pdo->beginTransaction();

//         // Check if asset is assigned
//         $stmt = $pdo->prepare("SELECT assigned_to FROM assets WHERE id = ?");
//         $stmt->execute([$assetId]);
//         $asset = $stmt->fetch();

//         if ($asset && $asset['assigned_to']) {
//             throw new Exception("Cannot delete assigned asset. Please unassign it first.");
//         }

//         // Delete asset
//         $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
//         $stmt->execute([$assetId]);

//         $pdo->commit();
//         return ['success' => true];
//     } catch (Exception $e) {
//         $pdo->rollBack();
//         error_log("Error deleting asset: " . $e->getMessage());
//         throw $e;
//     }
// }

// Campus Management
function getCampuses($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM campuses ORDER BY campus_name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting campuses: " . $e->getMessage());
        throw new Exception("Failed to load campuses");
    }
}

function createCampus($pdo, $data) {
    try {
        $pdo->beginTransaction();

        // Check if campus code already exists
        $stmt = $pdo->prepare("SELECT id FROM campuses WHERE campus_code = ?");
        $stmt->execute([$data['campus_code']]);
        if ($stmt->fetch()) {
            throw new Exception("Campus code already exists");
        }

        // Insert campus
        $stmt = $pdo->prepare("
            INSERT INTO campuses (campus_name, campus_code, address, contact_number, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['campus_name'],
            $data['campus_code'],
            $data['address'] ?? '',
            $data['contact_number'] ?? ''
        ]);

        $campusId = $pdo->lastInsertId();

        $pdo->commit();
        return ['id' => $campusId];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating campus: " . $e->getMessage());
        throw $e;
    }
}

function updateCampus($pdo, $campusId, $data) {
    try {
        $pdo->beginTransaction();

        // Check if campus code conflicts
        if (isset($data['campus_code'])) {
            $stmt = $pdo->prepare("SELECT id FROM campuses WHERE campus_code = ? AND id != ?");
            $stmt->execute([$data['campus_code'], $campusId]);
            if ($stmt->fetch()) {
                throw new Exception("Campus code already exists");
            }
        }

        // Build update query
        $updateFields = [];
        $params = [];

        if (isset($data['campus_name'])) {
            $updateFields[] = "campus_name = ?";
            $params[] = $data['campus_name'];
        }

        if (isset($data['campus_code'])) {
            $updateFields[] = "campus_code = ?";
            $params[] = $data['campus_code'];
        }

        if (isset($data['address'])) {
            $updateFields[] = "address = ?";
            $params[] = $data['address'];
        }

        if (isset($data['contact_number'])) {
            $updateFields[] = "contact_number = ?";
            $params[] = $data['contact_number'];
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        $params[] = $campusId;
        $stmt = $pdo->prepare("UPDATE campuses SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $stmt->execute($params);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating campus: " . $e->getMessage());
        throw $e;
    }
}

function deleteCampus($pdo, $campusId) {
    try {
        $pdo->beginTransaction();

        // Check if campus has assets
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE campus_id = ?");
        $stmt->execute([$campusId]);
        if ($stmt->fetch()['count'] > 0) {
            throw new Exception("Cannot delete campus with assigned assets. Please reassign assets first.");
        }

        // Check if campus has users
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE campus_id = ?");
        $stmt->execute([$campusId]);
        if ($stmt->fetch()['count'] > 0) {
            throw new Exception("Cannot delete campus with assigned users. Please reassign users first.");
        }

        // Delete campus
        $stmt = $pdo->prepare("DELETE FROM campuses WHERE id = ?");
        $stmt->execute([$campusId]);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting campus: " . $e->getMessage());
        throw $e;
    }
}

// Category Management
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting categories: " . $e->getMessage());
        throw new Exception("Failed to load categories");
    }
}

function createCategory($pdo, $data) {
    try {
        $pdo->beginTransaction();

        // Check if category name already exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE category_name = ?");
        $stmt->execute([$data['category_name']]);
        if ($stmt->fetch()) {
            throw new Exception("Category name already exists");
        }

        // Insert category
        $stmt = $pdo->prepare("
            INSERT INTO categories (category_name, description, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([
            $data['category_name'],
            $data['description'] ?? ''
        ]);

        $categoryId = $pdo->lastInsertId();

        $pdo->commit();
        return ['id' => $categoryId];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating category: " . $e->getMessage());
        throw $e;
    }
}

function updateCategory($pdo, $categoryId, $data) {
    try {
        $pdo->beginTransaction();

        // Check if category name conflicts
        if (isset($data['category_name'])) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE category_name = ? AND id != ?");
            $stmt->execute([$data['category_name'], $categoryId]);
            if ($stmt->fetch()) {
                throw new Exception("Category name already exists");
            }
        }

        // Build update query
        $updateFields = [];
        $params = [];

        if (isset($data['category_name'])) {
            $updateFields[] = "category_name = ?";
            $params[] = $data['category_name'];
        }

        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
        }

        if (empty($updateFields)) {
            throw new Exception("No fields to update");
        }

        $params[] = $categoryId;
        $stmt = $pdo->prepare("UPDATE categories SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $stmt->execute($params);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error updating category: " . $e->getMessage());
        throw $e;
    }
}

function deleteCategory($pdo, $categoryId) {
    try {
        $pdo->beginTransaction();

        // Check if category has assets
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        if ($stmt->fetch()['count'] > 0) {
            throw new Exception("Cannot delete category with assigned assets. Please reassign assets first.");
        }

        // Delete category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);

        $pdo->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting category: " . $e->getMessage());
        throw $e;
    }
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
?>
