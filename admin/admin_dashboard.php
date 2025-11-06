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

// Dashboard statistics
function getAdminStats($pdo) {
    try {
        $stats = [];

        // Total assets
        $stmt = $pdo->query("SELECT COUNT(*) as total_assets FROM assets");
        $stats['total_assets'] = $stmt->fetch()['total_assets'];

        // Active users (staff and custodian)
        $stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE role IN ('staff', 'custodian') AND is_active = TRUE");
        $stats['active_users'] = $stmt->fetch()['active_users'];

        // Pending requests
        $stmt = $pdo->query("SELECT COUNT(*) as pending_requests FROM asset_requests WHERE status = 'pending'");
        $stats['pending_requests'] = $stmt->fetch()['pending_requests'];

        // Total value
        $stmt = $pdo->query("SELECT COALESCE(SUM(value), 0) as total_value FROM assets");
        $stats['total_value'] = $stmt->fetch()['total_value'];

        // Assets by campus
        $stmt = $pdo->query("SELECT c.campus_name, COUNT(a.id) as count FROM campuses c LEFT JOIN assets a ON c.id = a.campus_id GROUP BY c.id, c.campus_name ORDER BY c.campus_name");
        $stats['assets_by_campus'] = $stmt->fetchAll();

        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting admin stats: " . $e->getMessage());
        throw new Exception("Failed to load admin statistics");
    }
}

function getRecentActivities($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("SELECT al.*, a.asset_name, u.full_name as performed_by_name FROM activity_log al LEFT JOIN assets a ON al.asset_id = a.id LEFT JOIN users u ON al.performed_by = u.username ORDER BY al.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting recent activities: " . $e->getMessage());
        throw new Exception("Failed to load recent activities");
    }
}
?>
