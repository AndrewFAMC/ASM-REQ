<?php
/**
 * Inventory Tags API
 * Handles inventory tag operations
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];
$user = getUserInfo();

try {
    switch ($action) {
        case 'get_tag_details':
            $tagId = (int)($_GET['tag_id'] ?? 0);

            if (!$tagId) {
                throw new Exception('Tag ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT
                    it.*,
                    a.asset_name,
                    o.office_name,
                    cam.campus_name
                FROM inventory_tags it
                JOIN assets a ON it.asset_id = a.id
                LEFT JOIN offices o ON it.office_id = o.id
                LEFT JOIN campuses cam ON a.campus_id = cam.id
                WHERE it.id = ?
            ");
            $stmt->execute([$tagId]);
            $tag = $stmt->fetch();

            if (!$tag) {
                throw new Exception('Tag not found');
            }

            // Format date
            if ($tag['inventory_date']) {
                $tag['inventory_date'] = date('m/d/Y', strtotime($tag['inventory_date']));
            }

            echo json_encode([
                'success' => true,
                'tag' => $tag
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
