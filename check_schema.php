<?php
require_once 'config.php';

if (!isLoggedIn()) {
    die('Please login first');
}

echo "<h2>Database Schema Check</h2>";
echo "<style>body{font-family: Arial; padding: 20px;} table{border-collapse: collapse; width: 100%; margin: 20px 0;} th, td{border: 1px solid #ddd; padding: 8px; text-align: left;} th{background: #4CAF50; color: white;} .error{color: red; font-weight: bold;} .success{color: green; font-weight: bold;}</style>";

// Check asset_requests table
echo "<h3>asset_requests table structure:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE asset_requests");
    $columns = $stmt->fetchAll();

    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check if we have the required columns
    $columnNames = array_column($columns, 'Field');
    $required = ['id', 'asset_id', 'status'];
    $hasUserId = in_array('user_id', $columnNames);
    $hasRequesterId = in_array('requester_id', $columnNames);
    $hasRequestedBy = in_array('requested_by', $columnNames);
    $hasPurpose = in_array('purpose', $columnNames);
    $hasQuantity = in_array('quantity', $columnNames);
    $hasExpectedReturn = in_array('expected_return_date', $columnNames);

    echo "<h3>Column Check:</h3>";
    echo "<ul>";
    echo "<li>User column: " . ($hasUserId ? "<span class='success'>user_id ✓</span>" : ($hasRequesterId ? "<span class='success'>requester_id ✓</span>" : ($hasRequestedBy ? "<span class='success'>requested_by ✓</span>" : "<span class='error'>MISSING ✗</span>"))) . "</li>";
    echo "<li>purpose: " . ($hasPurpose ? "<span class='success'>EXISTS ✓</span>" : "<span class='error'>MISSING ✗</span>") . "</li>";
    echo "<li>quantity: " . ($hasQuantity ? "<span class='success'>EXISTS ✓</span>" : "<span class='error'>MISSING ✗</span>") . "</li>";
    echo "<li>expected_return_date: " . ($hasExpectedReturn ? "<span class='success'>EXISTS ✓</span>" : "<span class='error'>MISSING ✗</span>") . "</li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Check assets table structure first
echo "<h3>assets table structure:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE assets");
    $assetColumns = $stmt->fetchAll();
    $assetColumnNames = array_column($assetColumns, 'Field');

    $hasAssetCode = in_array('asset_code', $assetColumnNames);
    $hasSerialNumber = in_array('serial_number', $assetColumnNames);

    echo "<p>Has asset_code: " . ($hasAssetCode ? "<span class='success'>YES ✓</span>" : "<span class='error'>NO ✗</span>") . "</p>";
    echo "<p>Has serial_number: " . ($hasSerialNumber ? "<span class='success'>YES ✓</span>" : "<span class='error'>NO ✗</span>") . "</p>";

    // Build dynamic query based on available columns
    $codeColumn = $hasAssetCode ? 'asset_code' : ($hasSerialNumber ? 'serial_number' : 'id');

    // Check for assets
    echo "<h3>Sample Assets:</h3>";
    $stmt = $pdo->query("SELECT id, asset_name, {$codeColumn} as code, status, campus_id FROM assets LIMIT 10");
    $assets = $stmt->fetchAll();
    echo "<table><tr><th>ID</th><th>Name</th><th>Code/Serial</th><th>Status</th><th>Campus</th></tr>";
    foreach ($assets as $asset) {
        echo "<tr><td>{$asset['id']}</td><td>{$asset['asset_name']}</td><td>{$asset['code']}</td><td>{$asset['status']}</td><td>{$asset['campus_id']}</td></tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p class='error'>Error checking assets: " . $e->getMessage() . "</p>";
}

// Count available assets per campus
echo "<h3>Available Assets by Campus:</h3>";
$stmt = $pdo->query("SELECT campus_id, status, COUNT(*) as count FROM assets GROUP BY campus_id, status");
$counts = $stmt->fetchAll();
echo "<table><tr><th>Campus ID</th><th>Status</th><th>Count</th></tr>";
foreach ($counts as $count) {
    echo "<tr><td>{$count['campus_id']}</td><td>{$count['status']}</td><td>{$count['count']}</td></tr>";
}
echo "</table>";

echo "<hr><a href='request_asset.php' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Back to Request Form</a>";
?>
