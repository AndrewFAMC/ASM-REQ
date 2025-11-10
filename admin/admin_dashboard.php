<?php
// Include database configuration
require_once '../config.php';

// Enforce authentication and role-based access (admin only)
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}
$user = getUserInfo();
$role = strtolower($user['role'] ?? '');
if ($role !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This page is for Admin users only.';
    exit;
}

// Bind user campus
$USER_CAMPUS_ID = (int)($user['campus_id'] ?? 0);

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

// Get dashboard data
try {
    $stats = getAdminStats($pdo);
    $dashboardData = getDashboardData($pdo);
    $recentActivities = getRecentActivities($pdo, 10);
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = [];
    $dashboardData = [];
    $recentActivities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HCC AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Admin Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="admin_dashboard.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 bg-gray-700">
                    <i class="fas fa-home w-6"></i><span>Dashboard</span>
                </a>
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-check-circle w-6"></i><span>Approve Requests</span>
                </a>
                <a href="users.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-users w-6"></i><span>User Management</span>
                </a>
                <a href="offices.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Office Management</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-chart-bar w-6"></i><span>Reports</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Admin Dashboard</h1>
                    <p class="text-sm text-gray-600 mt-1">Welcome, <?= htmlspecialchars($user['full_name']) ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> My Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Content Area --><div class="flex-1 overflow-y-auto p-6">

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm">Total Assets</div>
                <div class="text-3xl font-bold text-blue-600"><?= number_format($stats['total_assets'] ?? 0) ?></div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm">Active Users</div>
                <div class="text-3xl font-bold text-green-600"><?= number_format($stats['active_users'] ?? 0) ?></div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm">Pending Requests</div>
                <div class="text-3xl font-bold text-orange-600"><?= number_format($stats['pending_requests'] ?? 0) ?></div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm">Total Value</div>
                <div class="text-3xl font-bold text-purple-600">₱<?= number_format($stats['total_value'] ?? 0, 2) ?></div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Asset Status Distribution -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Asset Status Distribution</h2>
                <canvas id="assetStatusChart"></canvas>
            </div>

            <!-- Request Status Distribution -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Request Status</h2>
                <canvas id="requestStatusChart"></canvas>
            </div>

            <!-- Top Categories -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Top Categories</h2>
                <canvas id="topCategoriesChart"></canvas>
            </div>

            <!-- Monthly Acquisitions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Monthly Acquisitions (Last 12 Months)</h2>
                <canvas id="monthlyAcquisitionsChart"></canvas>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Recent Activities</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performed By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($recentActivities)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No recent activities</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($activity['activity_type'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($activity['asset_name'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($activity['performed_by_name'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('M d, Y H:i', strtotime($activity['created_at'] ?? 'now')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assets by Campus -->
        <?php if (!empty($stats['assets_by_campus'])): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Assets by Campus</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($stats['assets_by_campus'] as $campus): ?>
                    <div class="border rounded-lg p-4">
                        <div class="text-gray-600 text-sm"><?= htmlspecialchars($campus['campus_name']) ?></div>
                        <div class="text-2xl font-bold text-blue-600"><?= number_format($campus['count']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Asset Status Chart
        const assetStatusData = <?= json_encode($dashboardData['asset_status'] ?? []) ?>;
        if (assetStatusData.length > 0) {
            new Chart(document.getElementById('assetStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: assetStatusData.map(d => d.status),
                    datasets: [{
                        data: assetStatusData.map(d => d.count),
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
                    }]
                }
            });
        }

        // Request Status Chart
        const requestStatusData = <?= json_encode($dashboardData['request_status'] ?? []) ?>;
        if (requestStatusData.length > 0) {
            new Chart(document.getElementById('requestStatusChart'), {
                type: 'pie',
                data: {
                    labels: requestStatusData.map(d => d.status),
                    datasets: [{
                        data: requestStatusData.map(d => d.count),
                        backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#EF4444']
                    }]
                }
            });
        }

        // Top Categories Chart
        const topCategoriesData = <?= json_encode($dashboardData['top_categories'] ?? []) ?>;
        if (topCategoriesData.length > 0) {
            new Chart(document.getElementById('topCategoriesChart'), {
                type: 'bar',
                data: {
                    labels: topCategoriesData.map(d => d.category_name),
                    datasets: [{
                        label: 'Assets',
                        data: topCategoriesData.map(d => d.count),
                        backgroundColor: '#3B82F6'
                    }]
                },
                options: {
                    indexAxis: 'y'
                }
            });
        }

        // Monthly Acquisitions Chart
        const monthlyData = <?= json_encode($dashboardData['monthly_acquisitions'] ?? []) ?>;
        if (monthlyData.length > 0) {
            new Chart(document.getElementById('monthlyAcquisitionsChart'), {
                type: 'line',
                data: {
                    labels: monthlyData.map(d => d.month),
                    datasets: [{
                        label: 'Acquisitions',
                        data: monthlyData.map(d => d.count),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                }
            });
        }
    </script>
</body>
</html>
