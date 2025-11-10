<?php
/**
 * API Endpoint: Get Report Data
 * Returns report data in JSON format for table display
 */

require_once dirname(__DIR__) . '/config.php';

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasRole('custodian') && !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

header('Content-Type: application/json');

$reportType = $_GET['report_type'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
$offset = ($page - 1) * $limit;

try {
    switch ($reportType) {
        case 'borrowing':
            $status = $_GET['status'] ?? 'all';
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            // Build query
            $sql = "
                SELECT SQL_CALC_FOUND_ROWS
                    ar.id,
                    ar.status,
                    ar.request_date,
                    ar.released_date,
                    ar.expected_return_date,
                    ar.returned_date,
                    ar.days_overdue,
                    ar.return_condition,
                    ar.quantity,
                    a.asset_name,
                    COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
                    c.category_name,
                    requester.full_name as requester_name,
                    requester.email as requester_email,
                    CASE
                        WHEN ar.status = 'released' AND ar.expected_return_date < CURDATE() THEN 'Overdue'
                        WHEN ar.status = 'returned' AND ar.returned_date > ar.expected_return_date THEN 'Returned Late'
                        WHEN ar.status = 'returned' THEN 'Returned On Time'
                        WHEN ar.status = 'released' THEN 'Currently Borrowed'
                        ELSE ar.status
                    END as borrowing_status
                FROM asset_requests ar
                JOIN assets a ON ar.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                JOIN users requester ON ar.requester_id = requester.id
                WHERE ar.campus_id = ?
                AND ar.status IN ('released', 'returned')
            ";

            $params = [$campusId];

            if ($status !== 'all') {
                if ($status === 'overdue') {
                    $sql .= " AND ar.status = 'released' AND ar.expected_return_date < CURDATE()";
                } else {
                    $sql .= " AND ar.status = ?";
                    $params[] = $status;
                }
            }

            if ($dateFrom) {
                $sql .= " AND ar.released_date >= ?";
                $params[] = $dateFrom;
            }
            if ($dateTo) {
                $sql .= " AND ar.released_date <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }

            $sql .= " ORDER BY ar.released_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            // Get total count
            $totalStmt = $pdo->query("SELECT FOUND_ROWS()");
            $total = (int)$totalStmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'approval':
            $reportTypeFilter = $_GET['report_type_filter'] ?? 'detailed';
            $status = $_GET['status'] ?? 'all';
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');

            if ($reportTypeFilter === 'summary') {
                // Summary statistics
                $sql = "
                    SELECT
                        ar.status,
                        c.category_name,
                        COUNT(*) as total_requests,
                        COUNT(CASE WHEN ar.status LIKE '%approved%' THEN 1 END) as approved_count,
                        COUNT(CASE WHEN ar.status = 'rejected' THEN 1 END) as rejected_count,
                        COUNT(CASE WHEN ar.status = 'pending' OR ar.status LIKE '%review%' THEN 1 END) as pending_count,
                        AVG(TIMESTAMPDIFF(HOUR, ar.request_date,
                            COALESCE(ar.final_approved_at, ar.department_approved_at, ar.custodian_reviewed_at, NOW())
                        )) as avg_approval_hours
                    FROM asset_requests ar
                    JOIN assets a ON ar.asset_id = a.id
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE ar.campus_id = ?
                    AND ar.request_date >= ?
                    AND ar.request_date <= ?
                    GROUP BY ar.status, c.category_name
                    ORDER BY total_requests DESC
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$campusId, $dateFrom, $dateTo . ' 23:59:59']);
                $data = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'type' => 'summary'
                ]);
            } else {
                // Detailed list
                $sql = "
                    SELECT SQL_CALC_FOUND_ROWS
                        ar.id,
                        ar.status,
                        ar.request_date,
                        ar.quantity,
                        ar.purpose,
                        a.asset_name,
                        c.category_name,
                        requester.full_name as requester_name,
                        custodian.full_name as custodian_name,
                        ar.custodian_reviewed_at,
                        dept.full_name as department_name,
                        ar.department_approved_at,
                        admin.full_name as admin_name,
                        ar.final_approved_at,
                        TIMESTAMPDIFF(HOUR, ar.request_date,
                            COALESCE(ar.final_approved_at, ar.department_approved_at, ar.custodian_reviewed_at, NOW())
                        ) as approval_time_hours
                    FROM asset_requests ar
                    JOIN assets a ON ar.asset_id = a.id
                    LEFT JOIN categories c ON a.category_id = c.id
                    JOIN users requester ON ar.requester_id = requester.id
                    LEFT JOIN users custodian ON ar.custodian_reviewed_by = custodian.id
                    LEFT JOIN users dept ON ar.department_approved_by = dept.id
                    LEFT JOIN users admin ON ar.final_approved_by = admin.id
                    WHERE ar.campus_id = ?
                    AND ar.request_date >= ?
                    AND ar.request_date <= ?
                ";

                $params = [$campusId, $dateFrom, $dateTo . ' 23:59:59'];

                if ($status !== 'all') {
                    if ($status === 'approved') {
                        $sql .= " AND (ar.status LIKE '%approved%' OR ar.status = 'released' OR ar.status = 'returned')";
                    } elseif ($status === 'pending') {
                        $sql .= " AND (ar.status = 'pending' OR ar.status LIKE '%review%')";
                    } else {
                        $sql .= " AND ar.status = ?";
                        $params[] = $status;
                    }
                }

                $sql .= " ORDER BY ar.request_date DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();

                $totalStmt = $pdo->query("SELECT FOUND_ROWS()");
                $total = (int)$totalStmt->fetchColumn();

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'type' => 'detailed',
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;

        case 'depreciation':
            $groupBy = $_GET['group_by'] ?? 'asset';
            $includeRetired = isset($_GET['include_retired']) && $_GET['include_retired'] === 'true';

            if ($groupBy === 'asset') {
                $sql = "
                    SELECT SQL_CALC_FOUND_ROWS
                        a.id,
                        a.asset_name,
                        COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
                        a.status,
                        c.category_name,
                        o.office_name,
                        a.quantity,
                        a.purchase_date,
                        a.original_value,
                        a.current_value,
                        a.depreciation_rate,
                        TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE()) as age_years,
                        ROUND((a.original_value - COALESCE(a.current_value, a.original_value)), 2) as total_depreciation,
                        ROUND(((a.original_value - COALESCE(a.current_value, a.original_value)) / a.original_value * 100), 2) as depreciation_percent
                    FROM assets a
                    LEFT JOIN categories c ON a.category_id = c.id
                    LEFT JOIN offices o ON a.office_id = o.id
                    WHERE a.campus_id = ?
                    AND a.original_value IS NOT NULL
                    AND a.original_value > 0
                ";

                $params = [$campusId];

                if (!$includeRetired) {
                    $sql .= " AND a.status != 'Retired'";
                }

                $sql .= " ORDER BY total_depreciation DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();

                $totalStmt = $pdo->query("SELECT FOUND_ROWS()");
                $total = (int)$totalStmt->fetchColumn();

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'type' => 'asset',
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            } elseif ($groupBy === 'category') {
                $sql = "
                    SELECT
                        c.category_name,
                        COUNT(a.id) as asset_count,
                        SUM(a.quantity) as total_quantity,
                        ROUND(SUM(a.original_value * a.quantity), 2) as total_original_value,
                        ROUND(SUM(COALESCE(a.current_value, a.original_value) * a.quantity), 2) as total_current_value,
                        ROUND(SUM((a.original_value - COALESCE(a.current_value, a.original_value)) * a.quantity), 2) as total_depreciation,
                        ROUND(AVG(a.depreciation_rate), 2) as avg_depreciation_rate
                    FROM assets a
                    LEFT JOIN categories c ON a.category_id = c.id
                    WHERE a.campus_id = ?
                    AND a.original_value IS NOT NULL
                    AND a.original_value > 0
                ";

                $params = [$campusId];

                if (!$includeRetired) {
                    $sql .= " AND a.status != 'Retired'";
                }

                $sql .= " GROUP BY c.category_name ORDER BY total_depreciation DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'type' => 'category'
                ]);
            } elseif ($groupBy === 'office') {
                $sql = "
                    SELECT
                        COALESCE(o.office_name, 'Unassigned') as office_name,
                        COUNT(a.id) as asset_count,
                        SUM(a.quantity) as total_quantity,
                        ROUND(SUM(a.original_value * a.quantity), 2) as total_original_value,
                        ROUND(SUM(COALESCE(a.current_value, a.original_value) * a.quantity), 2) as total_current_value,
                        ROUND(SUM((a.original_value - COALESCE(a.current_value, a.original_value)) * a.quantity), 2) as total_depreciation
                    FROM assets a
                    LEFT JOIN offices o ON a.office_id = o.id
                    WHERE a.campus_id = ?
                    AND a.original_value IS NOT NULL
                    AND a.original_value > 0
                ";

                $params = [$campusId];

                if (!$includeRetired) {
                    $sql .= " AND a.status != 'Retired'";
                }

                $sql .= " GROUP BY o.office_name ORDER BY total_depreciation DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'type' => 'office'
                ]);
            }
            break;

        case 'missing':
            $status = $_GET['status'] ?? 'all';

            $sql = "
                SELECT SQL_CALC_FOUND_ROWS
                    mar.id,
                    mar.status,
                    mar.reported_date,
                    mar.last_known_location,
                    mar.last_known_borrower,
                    a.asset_name,
                    COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
                    c.category_name,
                    reporter.full_name as reporter_name,
                    mar.resolved_date,
                    resolver.full_name as resolved_by_name
                FROM missing_assets_reports mar
                JOIN assets a ON mar.asset_id = a.id
                LEFT JOIN categories c ON a.category_id = c.id
                JOIN users reporter ON mar.reported_by = reporter.id
                LEFT JOIN users resolver ON mar.resolved_by = resolver.id
                WHERE mar.campus_id = ?
            ";

            $params = [$campusId];

            if ($status !== 'all') {
                $sql .= " AND mar.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY mar.reported_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();

            $totalStmt = $pdo->query("SELECT FOUND_ROWS()");
            $total = (int)$totalStmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;

        default:
            throw new Exception('Invalid report type');
    }

} catch (Exception $e) {
    error_log("Error getting report data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch report data: ' . $e->getMessage()
    ]);
}
?>
