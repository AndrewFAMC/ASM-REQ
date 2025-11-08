<?php
/**
 * Create Test Assets
 * Automatically creates sample assets for testing
 */

require_once 'config.php';

if (!isLoggedIn()) {
    die('Please login first');
}

$user = getUserInfo();
$campusId = $user['campus_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Make sure we have a category
        $stmt = $pdo->query("SELECT id FROM categories LIMIT 1");
        $category = $stmt->fetch();

        if (!$category) {
            // Create a default category
            $pdo->query("INSERT INTO categories (category_name, created_at) VALUES ('Electronics', NOW())");
            $categoryId = $pdo->lastInsertId();
        } else {
            $categoryId = $category['id'];
        }

        // Create test assets
        $testAssets = [
            ['name' => 'Laptop Dell Inspiron 15', 'code' => 'LAP-001'],
            ['name' => 'Projector Epson EB-X41', 'code' => 'PROJ-001'],
            ['name' => 'Wireless Mouse Logitech', 'code' => 'MOUSE-001'],
            ['name' => 'Webcam HD Pro', 'code' => 'CAM-001'],
            ['name' => 'Portable Speaker JBL', 'code' => 'SPEAK-001']
        ];

        $created = 0;
        foreach ($testAssets as $asset) {
            // Check if asset code already exists
            $stmt = $pdo->prepare("SELECT id FROM assets WHERE asset_code = ?");
            $stmt->execute([$asset['code']]);

            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("
                    INSERT INTO assets
                    (asset_name, asset_code, category_id, campus_id, status, description, created_at)
                    VALUES (?, ?, ?, ?, 'Available', 'Test asset for demonstration', NOW())
                ");
                $stmt->execute([
                    $asset['name'],
                    $asset['code'],
                    $categoryId,
                    $campusId
                ]);
                $created++;
            }
        }

        $pdo->commit();

        echo "<!DOCTYPE html>";
        echo "<html><head><title>Assets Created</title>";
        echo "<style>body{font-family: Arial; padding: 40px; background: #f3f4f6;}";
        echo ".success{background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto;}";
        echo "h2{color: #065f46;} a{display: inline-block; margin: 10px 5px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;}";
        echo "a:hover{background: #2563eb;}</style></head><body>";
        echo "<div class='success'>";
        echo "<h2>✓ Success!</h2>";
        echo "<p>Created <strong>{$created}</strong> test asset(s) in Campus {$campusId}.</p>";
        echo "<p>You can now submit asset requests!</p>";
        echo "<a href='request_asset.php'>Go to Request Form</a>";
        echo "<a href='check_assets.php'>View Assets</a>";
        echo "<a href='employee_dashboard.php'>Dashboard</a>";
        echo "</div></body></html>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Error</title>";
        echo "<style>body{font-family: Arial; padding: 40px; background: #f3f4f6;}";
        echo ".error{background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto;}";
        echo "h2{color: #991b1b;} a{display: inline-block; margin: 10px 5px; padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 5px;}</style></head><body>";
        echo "<div class='error'>";
        echo "<h2>Error</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='check_assets.php'>Back</a>";
        echo "</div></body></html>";
    }
} else {
    header('Location: check_assets.php');
}
?>
