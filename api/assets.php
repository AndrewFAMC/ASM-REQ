<?php
/**
 * Assets API Endpoint
 *
 * Handles fetching available assets by source:
 * - Get office-specific assets
 * - Get custodian (central) assets
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$user = getUserInfo();
$userRole = strtolower($user['role']);

try {
    switch ($action) {
        // Get assets from a specific office
        case 'get_office_assets':
            $officeId = (int)($_GET['office_id'] ?? 0);

            if (!$officeId) {
                throw new Exception('Office ID is required');
            }

            // Get office info
            $stmt = $pdo->prepare("
                SELECT id, office_name, campus_id
                FROM offices
                WHERE id = ?
            ");
            $stmt->execute([$officeId]);
            $office = $stmt->fetch();

            if (!$office) {
                throw new Exception('Office not found');
            }

            // Get available assets from this office
            // Assets are associated with offices through assignment or location
            $stmt = $pdo->prepare("
                SELECT
                    a.id,
                    a.asset_name,
                    a.serial_number,
                    a.description,
                    a.status,
                    a.quantity,
                    a.value,
                    c.category_name,
                    c.id as category_id,
                    o.office_name,
                    a.inactive_quantity,
                    (a.quantity - COALESCE(a.inactive_quantity, 0)) as available_quantity
                FROM assets a
                JOIN categories c ON a.category_id = c.id
                LEFT JOIN offices o ON CAST(a.assigned_to AS UNSIGNED) = o.id
                WHERE CAST(a.assigned_to AS UNSIGNED) = ?
                    AND a.status IN ('Available', 'Unavailable')
                    AND (a.quantity - COALESCE(a.inactive_quantity, 0)) > 0
                ORDER BY a.asset_name ASC
            ");
            $stmt->execute([$officeId]);
            $assets = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'office' => $office,
                'assets' => $assets,
                'count' => count($assets)
            ]);
            break;

        // Get assets from custodian (central inventory)
        case 'get_custodian_assets':
            $campusId = $user['campus_id'];

            // Get available assets from custodian/central inventory
            // These are assets not assigned to any specific office
            $stmt = $pdo->prepare("
                SELECT
                    a.id,
                    a.asset_name,
                    a.serial_number,
                    a.description,
                    a.status,
                    a.quantity,
                    a.value,
                    c.category_name,
                    c.id as category_id,
                    a.inactive_quantity,
                    (a.quantity - COALESCE(a.inactive_quantity, 0)) as available_quantity,
                    'Central Inventory' as location
                FROM assets a
                JOIN categories c ON a.category_id = c.id
                WHERE a.campus_id = ?
                    AND (a.assigned_to IS NULL OR a.assigned_to = '')
                    AND a.status IN ('Available', 'Unavailable')
                    AND (a.quantity - COALESCE(a.inactive_quantity, 0)) > 0
                ORDER BY a.asset_name ASC
            ");
            $stmt->execute([$campusId]);
            $assets = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'source' => 'custodian',
                'campus_id' => $campusId,
                'assets' => $assets,
                'count' => count($assets)
            ]);
            break;

        // Get list of offices (for office selection)
        case 'get_offices':
            $campusId = $user['campus_id'];

            $stmt = $pdo->prepare("
                SELECT
                    o.id,
                    o.office_name,
                    o.section_code,
                    COUNT(DISTINCT a.id) as asset_count,
                    SUM(a.quantity - COALESCE(a.inactive_quantity, 0)) as total_available_quantity
                FROM offices o
                LEFT JOIN assets a ON o.id = CAST(a.assigned_to AS UNSIGNED)
                    AND a.status IN ('Available', 'Unavailable')
                    AND (a.quantity - COALESCE(a.inactive_quantity, 0)) > 0
                WHERE o.campus_id = ?
                GROUP BY o.id, o.office_name, o.section_code
                HAVING asset_count > 0
                ORDER BY o.office_name ASC
            ");
            $stmt->execute([$campusId]);
            $offices = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'offices' => $offices,
                'count' => count($offices)
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
