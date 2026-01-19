<?php
/**
 * Simple migration runner script
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=hcc_asset_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $migrationFile = __DIR__ . '/add_requester_office_field.sql';

    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }

    $sql = file_get_contents($migrationFile);

    // Execute the migration
    $pdo->exec($sql);

    echo "✓ Migration executed successfully: add_requester_office_field.sql\n";
    echo "✓ Added requester_office_id field to asset_requests table\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
