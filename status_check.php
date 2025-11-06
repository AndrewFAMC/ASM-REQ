<?php
// api/status_check.php

// Set the content type to JSON
header('Content-Type: application/json');

// Define the path to the maintenance helpers
require_once __DIR__ . '/../includes/it_dashboard_helpers.php';

try {
    // Get the current maintenance status
    $status = getMaintenanceStatus();

    // Return the status as a JSON object
    echo json_encode([
        'success' => true,
        'data' => $status
    ]);

} catch (Exception $e) {
    // In case of an error (e.g., file not readable), return an error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}