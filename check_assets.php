<?php
/**
 * Asset Diagnostic Tool
 * Checks why assets aren't showing in request form
 */

require_once 'config.php';

if (!isLoggedIn()) {
    die('Please login first');
}

$user = getUserInfo();
$campusId = $user['campus_id'];

echo "<h2>Asset Diagnostic Tool</h2>";
echo "<p>Logged in as: {$user['full_name']} (Campus ID: {$campusId})</p>";
echo "<hr>";

// Check 1: Total assets in database
echo "<h3>1. Total Assets in Database</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM assets");
$totalAssets = $stmt->fetch()['count'];
echo "Total assets: <strong>{$totalAssets}</strong><br>";

// Check 2: Assets by status
echo "<h3>2. Assets by Status</h3>";
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status");
$statusCounts = $stmt->fetchAll();
echo "<table border='1' cellpadding='5'><tr><th>Status</th><th>Count</th></tr>";
foreach ($statusCounts as $row) {
    echo "<tr><td>{$row['status']}</td><td>{$row['count']}</td></tr>";
}
echo "</table><br>";

// Check 3: Assets in your campus
echo "<h3>3. Assets in Your Campus (Campus ID: {$campusId})</h3>";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE campus_id = ?");
$stmt->execute([$campusId]);
$campusAssets = $stmt->fetch()['count'];
echo "Assets in your campus: <strong>{$campusAssets}</strong><br>";

// Check 4: Available assets in your campus
echo "<h3>4. Available Assets in Your Campus</h3>";
$stmt = $pdo->prepare("
    SELECT a.*, c.category_name
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.campus_id = ?
    AND a.status = 'Available'
    ORDER BY a.asset_name
");
$stmt->execute([$campusId]);
$availableAssets = $stmt->fetchAll();

echo "Available assets count: <strong>" . count($availableAssets) . "</strong><br>";

if (count($availableAssets) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Asset Name</th><th>Code</th><th>Category</th><th>Status</th></tr>";
    foreach ($availableAssets as $asset) {
        echo "<tr>";
        echo "<td>{$asset['id']}</td>";
        echo "<td>{$asset['asset_name']}</td>";
        echo "<td>{$asset['asset_code']}</td>";
        echo "<td>{$asset['category_name']}</td>";
        echo "<td>{$asset['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>NO AVAILABLE ASSETS FOUND!</strong></p>";

    // Show what we can create
    echo "<h3>Solution: Create Test Assets</h3>";
    echo "<form method='POST' action='create_test_assets.php'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "Create 5 Test Assets in Campus {$campusId}";
    echo "</button>";
    echo "</form>";
}

// Check 5: All campus IDs
echo "<h3>5. All Campuses</h3>";
$stmt = $pdo->query("SELECT * FROM campuses");
$campuses = $stmt->fetchAll();
echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Campus Name</th><th>Campus Code</th></tr>";
foreach ($campuses as $campus) {
    echo "<tr><td>{$campus['id']}</td><td>{$campus['campus_name']}</td><td>{$campus['campus_code']}</td></tr>";
}
echo "</table><br>";

echo "<hr>";
echo "<a href='request_asset.php' style='padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Back to Request Form</a>";
echo " ";
echo "<a href='employee_dashboard.php' style='padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 5px;'>Back to Dashboard</a>";
?>
