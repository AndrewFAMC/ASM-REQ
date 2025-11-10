<?php
// Include database configuration
require_once 'config.php';

// Enforce authentication and role-based access (admin only)
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: login.php');
    exit;
}
$user = getUserInfo();
$role = strtolower($user['role'] ?? '');
if ($role !== 'admin') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. This page is for Admin users only.']);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied. This page is for Admin users only.';
    }
    exit;
}

// Bind user campus
$USER_CAMPUS_ID = (int)($user['campus_id'] ?? 0);

// Include admin dashboard functions
require_once 'admin/admin_dashboard.php';

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
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
            <p class="text-gray-600">Welcome, <?= htmlspecialchars($user['full_name']) ?></p>
        </div>

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
