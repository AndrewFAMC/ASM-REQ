<?php
/**
 * API Endpoint: Dashboard Statistics
 * Returns quick statistics for reports dashboard
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

header('Content-Type: application/json');

try {
    $stats = [];

    // Total assets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE campus_id = ? AND status != 'Retired'");
    $stmt->execute([$campusId]);
    $stats['total_assets'] = (int)$stmt->fetchColumn();

    // Active borrowings
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE campus_id = ? AND status = 'released'");
    $stmt->execute([$campusId]);
    $stats['active_borrowings'] = (int)$stmt->fetchColumn();

    // Pending requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE campus_id = ? AND (status = 'pending' OR status LIKE '%review%')");
    $stmt->execute([$campusId]);
    $stats['pending_requests'] = (int)$stmt->fetchColumn();

    // Missing assets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM missing_assets_reports WHERE campus_id = ? AND status IN ('reported', 'investigating')");
    $stmt->execute([$campusId]);
    $stats['missing_assets'] = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch statistics'
    ]);
}
?>
