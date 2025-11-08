<?php
/**
 * Database Restoration Script
 *
 * This script restores the HCC Asset Management database from the SQL file.
 * Access this file in your browser to rebuild the database.
 */

// Database connection settings
$host = 'localhost';
$username = 'root';
$password = '';

// Path to SQL file
$sqlFile = __DIR__ . '/database/complete_schema.sql';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Restoration - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-database mr-3 text-blue-600"></i>
                    Database Restoration Tool
                </h1>

                <?php if (!isset($_POST['restore'])): ?>
                    <!-- Warning Notice -->
                    <div class="bg-red-50 border-l-4 border-red-600 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">WARNING</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>This will <strong>DELETE</strong> the existing database and recreate it from scratch.</p>
                                    <p class="mt-1">All current data will be lost. Make sure you have a backup if needed.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- What will be created -->
                    <div class="bg-blue-50 border-l-4 border-blue-600 p-4 mb-6">
                        <h3 class="text-sm font-medium text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>
                            What will be restored:
                        </h3>
                        <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside ml-4">
                            <li><strong>17 Tables:</strong> users, campuses, assets, categories, asset_requests, notifications, activity_logs, email_notifications, sms_notifications, missing_assets_reports, borrowing_chain, asset_movement_logs, department_approvers, system_settings, sessions, and 3 views</li>
                            <li><strong>3 Campuses:</strong> Main, Annex, Extension</li>
                            <li><strong>8 Categories:</strong> Computers, Office Equipment, Furniture, Audio/Visual, Networking, Mobile, Tools, Vehicles</li>
                            <li><strong>11 Sample Assets:</strong> Laptops, desktops, printers, projectors, furniture, networking equipment</li>
                            <li><strong>8 Sample Users:</strong> 1 Super Admin, 2 Admins, 2 Custodians, 3 Employees</li>
                            <li><strong>Default Settings:</strong> System configuration and preferences</li>
                            <li><strong>Advanced Features:</strong> Asset movement tracking, borrowing chains, missing asset reports, email/SMS queues</li>
                        </ul>
                    </div>

                    <!-- Sample Credentials -->
                    <div class="bg-green-50 border-l-4 border-green-600 p-4 mb-6">
                        <h3 class="text-sm font-medium text-green-800 mb-2">
                            <i class="fas fa-key mr-2"></i>
                            Sample Login Credentials (Password: <code class="bg-green-100 px-1">password123</code>):
                        </h3>
                        <div class="text-sm text-green-700 space-y-1 ml-4">
                            <p><strong>Super Admin:</strong> admin@hcc.edu</p>
                            <p><strong>Admin:</strong> john.admin@hcc.edu</p>
                            <p><strong>Custodian:</strong> pedro.custodian@hcc.edu</p>
                            <p><strong>Employee:</strong> juan.employee@hcc.edu</p>
                        </div>
                    </div>

                    <!-- Database Connection Info -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                        <h3 class="text-sm font-medium text-gray-800 mb-2">Database Connection:</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><strong>Host:</strong> <?= htmlspecialchars($host) ?></p>
                            <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
                            <p><strong>Database:</strong> hcc_asset_management</p>
                            <p><strong>SQL File:</strong> <?= file_exists($sqlFile) ? '<span class="text-green-600">✓ Found</span>' : '<span class="text-red-600">✗ Not Found</span>' ?></p>
                        </div>
                    </div>

                    <!-- Restore Button -->
                    <form method="POST" onsubmit="return confirm('Are you absolutely sure? This will DELETE all existing data!');">
                        <button type="submit" name="restore" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg text-lg transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Restore Database Now
                        </button>
                    </form>

                <?php else:
                    // Perform restoration
                    try {
                        // Check if SQL file exists
                        if (!file_exists($sqlFile)) {
                            throw new Exception("SQL file not found at: {$sqlFile}");
                        }

                        // Read SQL file
                        $sql = file_get_contents($sqlFile);
                        if ($sql === false) {
                            throw new Exception("Failed to read SQL file");
                        }

                        // Connect to MySQL (without database selection)
                        $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        echo '<div class="space-y-4">';

                        // Execute SQL statements
                        echo '<div class="bg-blue-50 border-l-4 border-blue-600 p-4">';
                        echo '<p class="text-blue-800"><i class="fas fa-spinner fa-spin mr-2"></i>Executing SQL statements...</p>';
                        echo '</div>';

                        // Split SQL into individual statements (handle multi-line statements)
                        $statements = array_filter(
                            array_map('trim', explode(';', $sql)),
                            function($stmt) {
                                return !empty($stmt) &&
                                       strpos($stmt, '--') !== 0 &&
                                       strpos($stmt, '/*') !== 0;
                            }
                        );

                        $successCount = 0;
                        $errors = [];

                        foreach ($statements as $statement) {
                            $statement = trim($statement);
                            if (!empty($statement)) {
                                try {
                                    $pdo->exec($statement);
                                    $successCount++;
                                } catch (PDOException $e) {
                                    // Skip non-critical errors like DROP DATABASE if not exists
                                    if (strpos($statement, 'DROP DATABASE') === false) {
                                        $errors[] = "Error: " . $e->getMessage() . " (Statement: " . substr($statement, 0, 100) . "...)";
                                    }
                                }
                            }
                        }

                        // Success message
                        echo '<div class="bg-green-50 border-l-4 border-green-600 p-4">';
                        echo '<h3 class="text-lg font-semibold text-green-800 mb-2">';
                        echo '<i class="fas fa-check-circle mr-2"></i>Database Restored Successfully!';
                        echo '</h3>';
                        echo '<p class="text-sm text-green-700">Executed ' . $successCount . ' SQL statements successfully.</p>';
                        echo '</div>';

                        // Show errors if any
                        if (!empty($errors)) {
                            echo '<div class="bg-yellow-50 border-l-4 border-yellow-600 p-4">';
                            echo '<h3 class="text-sm font-medium text-yellow-800 mb-2">Non-Critical Warnings:</h3>';
                            echo '<ul class="text-xs text-yellow-700 space-y-1 list-disc list-inside">';
                            foreach (array_slice($errors, 0, 5) as $error) {
                                echo '<li>' . htmlspecialchars($error) . '</li>';
                            }
                            if (count($errors) > 5) {
                                echo '<li>... and ' . (count($errors) - 5) . ' more</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }

                        // Verify database
                        $pdo = new PDO("mysql:host={$host};dbname=hcc_asset_management;charset=utf8mb4", $username, $password);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        echo '<div class="bg-blue-50 border-l-4 border-blue-600 p-4">';
                        echo '<h3 class="text-sm font-medium text-blue-800 mb-2">Database Statistics:</h3>';
                        echo '<div class="text-sm text-blue-700 grid grid-cols-2 gap-2 ml-4">';

                        $tables = [
                            'campuses', 'users', 'categories', 'assets', 'asset_requests',
                            'notifications', 'activity_logs', 'email_notifications',
                            'sms_notifications', 'missing_assets_reports', 'borrowing_chain',
                            'asset_movement_logs', 'department_approvers', 'system_settings', 'sessions'
                        ];
                        foreach ($tables as $table) {
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                $tableName = str_replace('_', ' ', ucwords($table, '_'));
                                echo '<p><strong>' . $tableName . ':</strong> ' . $count . ' records</p>';
                            } catch (PDOException $e) {
                                echo '<p><strong>' . ucfirst($table) . ':</strong> <span class="text-red-600">Error</span></p>';
                            }
                        }

                        echo '</div></div>';

                        echo '<div class="mt-6 space-y-3">';
                        echo '<a href="login.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-3 px-6 rounded-lg transition-colors">';
                        echo '<i class="fas fa-sign-in-alt mr-2"></i>Go to Login Page';
                        echo '</a>';
                        echo '<button onclick="window.location.reload()" class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">';
                        echo '<i class="fas fa-redo mr-2"></i>Restore Again';
                        echo '</button>';
                        echo '</div>';

                        echo '</div>';

                    } catch (Exception $e) {
                        echo '<div class="bg-red-50 border-l-4 border-red-600 p-4">';
                        echo '<h3 class="text-lg font-semibold text-red-800 mb-2">';
                        echo '<i class="fas fa-exclamation-circle mr-2"></i>Restoration Failed';
                        echo '</h3>';
                        echo '<p class="text-sm text-red-700 mb-2">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        echo '<button onclick="window.location.reload()" class="mt-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">';
                        echo '<i class="fas fa-redo mr-2"></i>Try Again';
                        echo '</button>';
                        echo '</div>';
                    }
                ?>
                <?php endif; ?>

                <!-- Instructions -->
                <?php if (!isset($_POST['restore'])): ?>
                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">
                        <i class="fas fa-question-circle mr-2 text-gray-600"></i>
                        Need Help?
                    </h3>
                    <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside ml-4">
                        <li>Make sure XAMPP MySQL is running</li>
                        <li>The database file is located at: <code class="bg-gray-100 px-1">database/complete_schema.sql</code></li>
                        <li>After restoration, you can log in with the sample credentials above</li>
                        <li>All passwords are: <code class="bg-gray-100 px-1">password123</code></li>
                        <li>You can restore multiple times - it will drop and recreate the database each time</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
