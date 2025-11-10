<?php
/**
 * API Endpoint: Export Borrowing History Report
 * Exports borrowing and return history to CSV format
 */

require_once dirname(__DIR__) . '/config.php';

// Require login and appropriate access
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasRole('custodian') && !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Custodian or Admin role required.']);
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

// Get export parameters
$format = $_GET['format'] ?? 'csv';
$filterStatus = $_GET['status'] ?? 'all'; // all, released, returned, overdue
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Default: first day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Default: today

try {
    // Build query
    $sql = "
        SELECT
            ar.id as request_id,
            ar.status,
            ar.request_date,
            ar.released_date,
            ar.expected_return_date,
            ar.returned_date,
            ar.days_overdue,
            ar.return_condition,
            ar.return_notes,
            ar.late_return_remarks,
            ar.quantity,
            a.asset_name,
            COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
            c.category_name as category,
            requester.full_name as requester_name,
            requester.email as requester_email,
            released_by.full_name as released_by_name,
            returned_by.full_name as returned_by_name,
            cam.campus_name,
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
        LEFT JOIN users released_by ON ar.released_by = released_by.id
        LEFT JOIN users returned_by ON ar.returned_by = returned_by.id
        JOIN campuses cam ON ar.campus_id = cam.id
        WHERE ar.campus_id = ?
        AND ar.status IN ('released', 'returned')
    ";

    $params = [$campusId];

    // Add status filter
    if ($filterStatus !== 'all') {
        if ($filterStatus === 'overdue') {
            $sql .= " AND ar.status = 'released' AND ar.expected_return_date < CURDATE()";
        } else {
            $sql .= " AND ar.status = ?";
            $params[] = $filterStatus;
        }
    }

    // Add date range filter
    if ($dateFrom) {
        $sql .= " AND ar.released_date >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $sql .= " AND ar.released_date <= ?";
        $params[] = $dateTo . ' 23:59:59';
    }

    $sql .= " ORDER BY ar.released_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    if (empty($reports)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No borrowing records found for the selected criteria']);
        exit;
    }

    // Generate filename
    $date = date('Y-m-d');
    $statusSuffix = $filterStatus !== 'all' ? '_' . $filterStatus : '';
    $filename = "borrowing_history{$statusSuffix}_{$dateFrom}_to_{$dateTo}";

    if ($format === 'csv' || $format === 'excel') {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Add header row
        fputcsv($output, [
            'Request ID',
            'Borrowing Status',
            'Asset Name',
            'Asset Code',
            'Category',
            'Quantity',
            'Requester Name',
            'Requester Email',
            'Campus',
            'Request Date',
            'Released Date',
            'Released By',
            'Expected Return Date',
            'Actual Return Date',
            'Returned By',
            'Days Overdue',
            'Return Condition',
            'Return Notes',
            'Late Return Remarks'
        ]);

        // Add data rows
        foreach ($reports as $report) {
            fputcsv($output, [
                $report['request_id'],
                $report['borrowing_status'],
                $report['asset_name'],
                $report['asset_code'],
                $report['category'] ?? 'N/A',
                $report['quantity'],
                $report['requester_name'],
                $report['requester_email'],
                $report['campus_name'],
                $report['request_date'],
                $report['released_date'] ?? 'N/A',
                $report['released_by_name'] ?? 'N/A',
                $report['expected_return_date'] ?? 'N/A',
                $report['returned_date'] ?? 'Not yet returned',
                $report['returned_by_name'] ?? 'N/A',
                $report['days_overdue'] ?? '0',
                $report['return_condition'] ?? 'N/A',
                $report['return_notes'] ?? 'N/A',
                $report['late_return_remarks'] ?? 'N/A'
            ]);
        }

        fclose($output);
        exit;

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid export format']);
        exit;
    }

} catch (Exception $e) {
    error_log("Error exporting borrowing history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
}
?>
