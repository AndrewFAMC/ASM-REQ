<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Verify CSRF token for non-GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listOffices($pdo);
            break;

        case 'get':
            getOffice($pdo);
            break;

        case 'create':
            createOffice($pdo);
            break;

        case 'update':
            updateOffice($pdo);
            break;

        case 'delete':
            deleteOffice($pdo);
            break;

        case 'get_campuses':
            getCampuses($pdo);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Office API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function listOffices($pdo) {
    $search = $_GET['search'] ?? '';
    $campus_id = $_GET['campus_id'] ?? '';

    $sql = "SELECT o.*, c.campus_name,
            (SELECT COUNT(*) FROM assets WHERE assigned_to = CAST(o.id AS CHAR CHARSET utf8mb4) COLLATE utf8mb4_unicode_ci) as asset_count,
            (SELECT COUNT(*) FROM users WHERE office_id = o.id) as user_count
            FROM offices o
            LEFT JOIN campuses c ON o.campus_id = c.id
            WHERE 1=1";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (o.office_name LIKE ? OR o.section_code LIKE ? OR o.floor LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if (!empty($campus_id)) {
        $sql .= " AND o.campus_id = ?";
        $params[] = $campus_id;
    }

    $sql .= " ORDER BY c.campus_name, o.office_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $offices]);
}

function getOffice($pdo) {
    $id = $_GET['id'] ?? 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Office ID is required']);
        return;
    }

    $stmt = $pdo->prepare("SELECT o.*, c.campus_name
                           FROM offices o
                           LEFT JOIN campuses c ON o.campus_id = c.id
                           WHERE o.id = ?");
    $stmt->execute([$id]);
    $office = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$office) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Office not found']);
        return;
    }

    echo json_encode(['success' => true, 'data' => $office]);
}

function createOffice($pdo) {
    $office_name = trim($_POST['office_name'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $section_code = trim($_POST['section_code'] ?? '');
    $campus_id = $_POST['campus_id'] ?? 0;
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($office_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Office name is required']);
        return;
    }

    if (empty($campus_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Campus is required']);
        return;
    }

    // Check if office name already exists in the same campus
    $stmt = $pdo->prepare("SELECT id FROM offices WHERE office_name = ? AND campus_id = ?");
    $stmt->execute([$office_name, $campus_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Office name already exists in this campus']);
        return;
    }

    // Check if section code already exists (if provided)
    if (!empty($section_code)) {
        $stmt = $pdo->prepare("SELECT id FROM offices WHERE section_code = ? AND campus_id = ?");
        $stmt->execute([$section_code, $campus_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Section code already exists in this campus']);
            return;
        }
    }

    // Insert office
    $stmt = $pdo->prepare("INSERT INTO offices (office_name, floor, section_code, campus_id, description)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$office_name, $floor, $section_code, $campus_id, $description]);

    $officeId = $pdo->lastInsertId();

    // Log activity
    logActivity($pdo, null, 'office_created', "Created office: $office_name", $campus_id);

    echo json_encode([
        'success' => true,
        'message' => 'Office created successfully',
        'office_id' => $officeId
    ]);
}

function updateOffice($pdo) {
    $id = $_POST['id'] ?? 0;
    $office_name = trim($_POST['office_name'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $section_code = trim($_POST['section_code'] ?? '');
    $campus_id = $_POST['campus_id'] ?? 0;
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Office ID is required']);
        return;
    }

    if (empty($office_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Office name is required']);
        return;
    }

    if (empty($campus_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Campus is required']);
        return;
    }

    // Check if office exists
    $stmt = $pdo->prepare("SELECT * FROM offices WHERE id = ?");
    $stmt->execute([$id]);
    $existingOffice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingOffice) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Office not found']);
        return;
    }

    // Check if office name already exists in the same campus (excluding current office)
    $stmt = $pdo->prepare("SELECT id FROM offices WHERE office_name = ? AND campus_id = ? AND id != ?");
    $stmt->execute([$office_name, $campus_id, $id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Office name already exists in this campus']);
        return;
    }

    // Check if section code already exists (if provided)
    if (!empty($section_code)) {
        $stmt = $pdo->prepare("SELECT id FROM offices WHERE section_code = ? AND campus_id = ? AND id != ?");
        $stmt->execute([$section_code, $campus_id, $id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Section code already exists in this campus']);
            return;
        }
    }

    // Update office
    $stmt = $pdo->prepare("UPDATE offices
                           SET office_name = ?, floor = ?, section_code = ?, campus_id = ?, description = ?
                           WHERE id = ?");
    $stmt->execute([$office_name, $floor, $section_code, $campus_id, $description, $id]);

    // Log activity
    logActivity($pdo, null, 'office_updated', "Updated office: $office_name", $campus_id);

    echo json_encode(['success' => true, 'message' => 'Office updated successfully']);
}

function deleteOffice($pdo) {
    $id = $_POST['id'] ?? 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Office ID is required']);
        return;
    }

    // Check if office exists
    $stmt = $pdo->prepare("SELECT * FROM offices WHERE id = ?");
    $stmt->execute([$id]);
    $office = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$office) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Office not found']);
        return;
    }

    // Check if office has assigned assets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE assigned_to = CAST(? AS CHAR CHARSET utf8mb4) COLLATE utf8mb4_unicode_ci");
    $stmt->execute([$id]);
    $assetCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($assetCount > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete office. It has $assetCount assigned asset(s). Please reassign or remove assets first."
        ]);
        return;
    }

    // Check if office has assigned users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE office_id = ?");
    $stmt->execute([$id]);
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($userCount > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete office. It has $userCount assigned user(s). Please reassign or remove users first."
        ]);
        return;
    }

    // Delete office
    $stmt = $pdo->prepare("DELETE FROM offices WHERE id = ?");
    $stmt->execute([$id]);

    // Log activity
    logActivity($pdo, null, 'office_deleted', "Deleted office: {$office['office_name']}", $office['campus_id']);

    echo json_encode(['success' => true, 'message' => 'Office deleted successfully']);
}

function getCampuses($pdo) {
    $stmt = $pdo->query("SELECT id, campus_name FROM campuses ORDER BY campus_name");
    $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $campuses]);
}
