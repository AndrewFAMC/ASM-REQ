<?php
// Admin Data Retrieval Functions for HCC Asset Management System

// Reports and Analytics
function getReports($pdo, $type = 'assets', $filters = []) {
    try {
        switch ($type) {
            case 'assets':
                return getAssetReports($pdo, $filters);
            case 'users':
                return getUserReports($pdo, $filters);
            case 'requests':
                return getRequestReports($pdo, $filters);
            case 'financial':
                return getFinancialReports($pdo, $filters);
            default:
                throw new Exception("Invalid report type");
        }
    } catch (PDOException $e) {
        error_log("Error getting reports: " . $e->getMessage());
        throw new Exception("Failed to generate report");
    }
}

function getAssetReports($pdo, $filters = []) {
    $reports = [];

    // Asset summary
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_assets,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_assets,
            SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_assets,
            SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
            SUM(CASE WHEN status = 'Retired' THEN 1 ELSE 0 END) as retired_assets,
            COALESCE(SUM(value), 0) as total_value
        FROM assets
    ");
    $reports['summary'] = $stmt->fetch();

    // Assets by campus
    $stmt = $pdo->query("
        SELECT
            c.campus_name,
            COUNT(a.id) as total_assets,
            SUM(CASE WHEN a.status = 'Active' THEN 1 ELSE 0 END) as active_assets,
            COALESCE(SUM(a.value), 0) as total_value
        FROM campuses c
        LEFT JOIN assets a ON c.id = a.campus_id
        GROUP BY c.id, c.campus_name
        ORDER BY c.campus_name
    ");
    $reports['by_campus'] = $stmt->fetchAll();

    // Assets by category
    $stmt = $pdo->query("
        SELECT
            cat.category_name,
            COUNT(a.id) as total_assets,
            SUM(CASE WHEN a.status = 'Active' THEN 1 ELSE 0 END) as active_assets,
            COALESCE(SUM(a.value), 0) as total_value
        FROM categories cat
        LEFT JOIN assets a ON cat.id = a.category_id
        GROUP BY cat.id, cat.category_name
        ORDER BY cat.category_name
    ");
    $reports['by_category'] = $stmt->fetchAll();

    // Recent asset acquisitions (last 30 days)
    $stmt = $pdo->prepare("
        SELECT a.*, c.campus_name, cat.category_name
        FROM assets a
        LEFT JOIN campuses c ON a.campus_id = c.id
        LEFT JOIN categories cat ON a.category_id = cat.id
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $reports['recent_acquisitions'] = $stmt->fetchAll();

    return $reports;
}

function getUserReports($pdo, $filters = []) {
    $reports = [];

    // User summary
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_users,
            SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
            SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_users,
            SUM(CASE WHEN role = 'custodian' THEN 1 ELSE 0 END) as custodian_users
        FROM users
    ");
    $reports['summary'] = $stmt->fetch();

    // Users by campus
    $stmt = $pdo->query("
        SELECT
            c.campus_name,
            COUNT(u.id) as total_users,
            SUM(CASE WHEN u.is_active = TRUE THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN u.role = 'staff' THEN 1 ELSE 0 END) as staff_users,
            SUM(CASE WHEN u.role = 'custodian' THEN 1 ELSE 0 END) as custodian_users
        FROM campuses c
        LEFT JOIN users u ON c.id = u.campus_id
        GROUP BY c.id, c.campus_name
        ORDER BY c.campus_name
    ");
    $reports['by_campus'] = $stmt->fetchAll();

    // Recent user registrations (last 30 days)
    $stmt = $pdo->prepare("
        SELECT u.*, c.campus_name
        FROM users u
        LEFT JOIN campuses c ON u.campus_id = c.id
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $reports['recent_registrations'] = $stmt->fetchAll();

    return $reports;
}

function getRequestReports($pdo, $filters = []) {
    $reports = [];

    // Request summary
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
        FROM asset_requests
    ");
    $reports['summary'] = $stmt->fetch();

    // Requests by status over time (last 6 months)
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            status,
            COUNT(*) as count
        FROM asset_requests
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), status
        ORDER BY month DESC, status
    ");
    $stmt->execute();
    $reports['by_month'] = $stmt->fetchAll();

    // Requests by campus
    $stmt = $pdo->query("
        SELECT
            c.campus_name,
            COUNT(ar.id) as total_requests,
            SUM(CASE WHEN ar.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN ar.status = 'approved' THEN 1 ELSE 0 END) as approved_requests
        FROM campuses c
        LEFT JOIN asset_requests ar ON c.id = ar.campus_id
        GROUP BY c.id, c.campus_name
        ORDER BY c.campus_name
    ");
    $reports['by_campus'] = $stmt->fetchAll();

    // Pending requests details
    $stmt = $pdo->prepare("
        SELECT ar.*, u.full_name as requested_by_name, c.campus_name, cat.category_name
        FROM asset_requests ar
        LEFT JOIN users u ON ar.requested_by = u.id
        LEFT JOIN campuses c ON ar.campus_id = c.id
        LEFT JOIN categories cat ON ar.category_id = cat.id
        WHERE ar.status = 'pending'
        ORDER BY ar.created_at DESC
    ");
    $stmt->execute();
    $reports['pending_details'] = $stmt->fetchAll();

    return $reports;
}

function getFinancialReports($pdo, $filters = []) {
    $reports = [];

    // Financial summary
    $stmt = $pdo->query("
        SELECT
            COALESCE(SUM(value), 0) as total_asset_value,
            AVG(value) as average_asset_value,
            MIN(value) as min_asset_value,
            MAX(value) as max_asset_value
        FROM assets
        WHERE value > 0
    ");
    $reports['summary'] = $stmt->fetch();

    // Asset value by campus
    $stmt = $pdo->query("
        SELECT
            c.campus_name,
            COUNT(a.id) as asset_count,
            COALESCE(SUM(a.value), 0) as total_value,
            COALESCE(AVG(a.value), 0) as average_value
        FROM campuses c
        LEFT JOIN assets a ON c.id = a.campus_id
        GROUP BY c.id, c.campus_name
        ORDER BY total_value DESC
    ");
    $reports['by_campus'] = $stmt->fetchAll();

    // Asset value by category
    $stmt = $pdo->query("
        SELECT
            cat.category_name,
            COUNT(a.id) as asset_count,
            COALESCE(SUM(a.value), 0) as total_value,
            COALESCE(AVG(a.value), 0) as average_value
        FROM categories cat
        LEFT JOIN assets a ON cat.id = a.category_id
        GROUP BY cat.id, cat.category_name
        ORDER BY total_value DESC
    ");
    $reports['by_category'] = $stmt->fetchAll();

    // High-value assets (top 10)
    $stmt = $pdo->prepare("
        SELECT a.*, c.campus_name, cat.category_name
        FROM assets a
        LEFT JOIN campuses c ON a.campus_id = c.id
        LEFT JOIN categories cat ON a.category_id = cat.id
        WHERE a.value > 0
        ORDER BY a.value DESC
        LIMIT 10
    ");
    $stmt->execute();
    $reports['high_value_assets'] = $stmt->fetchAll();

    return $reports;
}

// Invoice Management
function generateInvoice($pdo, $data) {
    try {
        $pdo->beginTransaction();

        // Generate invoice number
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        // Insert invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (invoice_number, asset_id, amount, description, issued_by, issued_date, due_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, 'unpaid', NOW())
        ");
        $stmt->execute([
            $invoiceNumber,
            $data['asset_id'] ?? null,
            $data['amount'],
            $data['description'],
            $data['issued_by'],
            $data['due_date'] ?? date('Y-m-d', strtotime('+30 days'))
        ]);

        $invoiceId = $pdo->lastInsertId();

        // If this is for asset maintenance or purchase, link it
        if (!empty($data['asset_id'])) {
            // Log activity
            logActivity($pdo, $data['asset_id'], 'INVOICE_GENERATED', "Invoice generated: " . $invoiceNumber);
        }

        $pdo->commit();
        return ['id' => $invoiceId, 'invoice_number' => $invoiceNumber];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error generating invoice: " . $e->getMessage());
        throw new Exception("Failed to generate invoice");
    }
}

function getInvoices($pdo, $filters = []) {
    try {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['asset_id'])) {
            $where[] = "i.asset_id = ?";
            $params[] = $filters['asset_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "i.issued_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "i.issued_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT i.*, a.asset_name, u.full_name as issued_by_name
            FROM invoices i
            LEFT JOIN assets a ON i.asset_id = a.id
            LEFT JOIN users u ON i.issued_by = u.id
            $whereClause
            ORDER BY i.issued_date DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting invoices: " . $e->getMessage());
        throw new Exception("Failed to load invoices");
    }
}

// Utility function for logging activities
function logActivity($pdo, $assetId, $action, $description) {
    try {
        $user = getUserInfo();
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (asset_id, action, description, performed_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$assetId, $action, $description, $user['username'] ?? 'system']);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

// Export functions
function exportData($pdo, $type, $filters = []) {
    try {
        switch ($type) {
            case 'assets':
                return exportAssets($pdo, $filters);
            case 'users':
                return exportUsers($pdo, $filters);
            case 'requests':
                return exportRequests($pdo, $filters);
            case 'invoices':
                return exportInvoices($pdo, $filters);
            default:
                throw new Exception("Invalid export type");
        }
    } catch (PDOException $e) {
        error_log("Error exporting data: " . $e->getMessage());
        throw new Exception("Failed to export data");
    }
}

function exportAssets($pdo, $filters = []) {
    $assets = getAllAssets($pdo, $filters);

    $csv = "ID,Asset Name,Serial Number,Category,Campus,Location,Status,Purchase Date,Value,Assigned To,Created Date\n";

    foreach ($assets as $asset) {
        $csv .= sprintf(
            "%s,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",%s,\"%s\",\"%s\"\n",
            $asset['id'],
            $asset['asset_name'],
            $asset['serial_number'] ?? '',
            $asset['category_name'] ?? '',
            $asset['campus_name'] ?? '',
            $asset['location'] ?? '',
            $asset['status'],
            $asset['purchase_date'] ?? '',
            $asset['value'] ?? 0,
            $asset['assigned_to_name'] ?? '',
            $asset['created_at']
        );
    }

    return $csv;
}

function exportUsers($pdo, $filters = []) {
    $users = getUsers($pdo, $filters);

    $csv = "ID,Username,Full Name,Email,Role,Campus,Status,Created Date\n";

    foreach ($users as $user) {
        $csv .= sprintf(
            "%s,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
            $user['id'],
            $user['username'],
            $user['full_name'] ?? '',
            $user['email'] ?? '',
            $user['role'],
            $user['campus_name'] ?? '',
            $user['is_active'] ? 'Active' : 'Inactive',
            $user['created_at']
        );
    }

    return $csv;
}

function exportRequests($pdo, $filters = []) {
    $requests = getAssetRequests($pdo, $filters);

    $csv = "ID,Description,Requested By,Campus,Category,Priority,Status,Created Date,Approved Date\n";

    foreach ($requests as $request) {
        $csv .= sprintf(
            "%s,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
            $request['id'],
            $request['description'] ?? '',
            $request['requested_by_name'] ?? '',
            $request['campus_name'] ?? '',
            $request['category_name'] ?? '',
            $request['priority'] ?? 'Normal',
            $request['status'],
            $request['created_at'],
            $request['approved_at'] ?? ''
        );
    }

    return $csv;
}

function exportInvoices($pdo, $filters = []) {
    $invoices = getInvoices($pdo, $filters);

    $csv = "ID,Invoice Number,Asset,Amount,Description,Issued By,Issued Date,Due Date,Status\n";

    foreach ($invoices as $invoice) {
        $csv .= sprintf(
            "%s,\"%s\",\"%s\",%s,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
            $invoice['id'],
            $invoice['invoice_number'],
            $invoice['asset_name'] ?? '',
            $invoice['amount'],
            $invoice['description'] ?? '',
            $invoice['issued_by_name'] ?? '',
            $invoice['issued_date'],
            $invoice['due_date'] ?? '',
            $invoice['status']
        );
    }

    return $csv;
}

// Dashboard data for charts and analytics
function getDashboardData($pdo) {
    try {
        $data = [];

        // Asset status distribution
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count
            FROM assets
            GROUP BY status
            ORDER BY count DESC
        ");
        $data['asset_status'] = $stmt->fetchAll();

        // Monthly asset acquisitions (last 12 months)
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM assets
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute();
        $data['monthly_acquisitions'] = $stmt->fetchAll();

        // Request status distribution
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count
            FROM asset_requests
            GROUP BY status
            ORDER BY count DESC
        ");
        $data['request_status'] = $stmt->fetchAll();

        // Top categories by asset count
        $stmt = $pdo->query("
            SELECT cat.category_name, COUNT(a.id) as count
            FROM categories cat
            LEFT JOIN assets a ON cat.id = a.category_id
            GROUP BY cat.id, cat.category_name
            ORDER BY count DESC
            LIMIT 10
        ");
        $data['top_categories'] = $stmt->fetchAll();

        return $data;
    } catch (PDOException $e) {
        error_log("Error getting dashboard data: " . $e->getMessage());
        throw new Exception("Failed to load dashboard data");
    }
}
?>
