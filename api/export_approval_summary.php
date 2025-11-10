<?php
/**
 * API Endpoint: Export Approval/Rejection Summary Report
 * Exports request approval statistics and details to CSV format
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
$filterStatus = $_GET['status'] ?? 'all'; // all, approved, rejected, pending
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Default: first day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Default: today
$reportType = $_GET['report_type'] ?? 'detailed'; // detailed or summary

try {
    if ($reportType === 'summary') {
        // Summary report - statistics by status, category, approver
        $sql = "
            SELECT
                COUNT(*) as total_requests,
                ar.status,
                c.category_name,
                cam.campus_name,
                COUNT(CASE WHEN ar.status LIKE '%approved%' THEN 1 END) as approved_count,
                COUNT(CASE WHEN ar.status = 'rejected' THEN 1 END) as rejected_count,
                COUNT(CASE WHEN ar.status = 'pending' OR ar.status LIKE '%review%' THEN 1 END) as pending_count,
                AVG(TIMESTAMPDIFF(HOUR, ar.request_date,
                    COALESCE(ar.final_approved_at, ar.department_approved_at, ar.custodian_reviewed_at, NOW())
                )) as avg_approval_hours
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            JOIN campuses cam ON ar.campus_id = cam.id
            WHERE ar.campus_id = ?
            AND ar.request_date >= ?
            AND ar.request_date <= ?
            GROUP BY ar.status, c.category_name, cam.campus_name
            ORDER BY total_requests DESC
        ";

        $params = [$campusId, $dateFrom, $dateTo . ' 23:59:59'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();

        if (empty($reports)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No approval data found for the selected period']);
            exit;
        }

        // Generate filename
        $filename = "approval_summary_{$dateFrom}_to_{$dateTo}";

        // CSV output
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Status',
            'Category',
            'Campus',
            'Total Requests',
            'Approved Count',
            'Rejected Count',
            'Pending Count',
            'Avg Approval Time (hours)'
        ]);

        foreach ($reports as $report) {
            fputcsv($output, [
                ucfirst(str_replace('_', ' ', $report['status'])),
                $report['category_name'] ?? 'All Categories',
                $report['campus_name'],
                $report['total_requests'],
                $report['approved_count'],
                $report['rejected_count'],
                $report['pending_count'],
                round($report['avg_approval_hours'], 2)
            ]);
        }

        fclose($output);
        exit;

    } else {
        // Detailed report - individual requests
        $sql = "
            SELECT
                ar.id as request_id,
                ar.status,
                ar.request_date,
                ar.quantity,
                ar.purpose,
                ar.expected_return_date,
                a.asset_name,
                COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
                c.category_name as category,
                requester.full_name as requester_name,
                requester.email as requester_email,
                custodian.full_name as custodian_reviewer,
                ar.custodian_reviewed_at,
                ar.custodian_review_notes,
                dept.full_name as department_approver,
                ar.department_approved_at,
                admin.full_name as final_approver,
                ar.final_approved_at,
                ar.admin_notes,
                ar.rejection_reason,
                cam.campus_name,
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
            JOIN campuses cam ON ar.campus_id = cam.id
            WHERE ar.campus_id = ?
            AND ar.request_date >= ?
            AND ar.request_date <= ?
        ";

        $params = [$campusId, $dateFrom, $dateTo . ' 23:59:59'];

        // Add status filter
        if ($filterStatus !== 'all') {
            if ($filterStatus === 'approved') {
                $sql .= " AND (ar.status LIKE '%approved%' OR ar.status = 'released' OR ar.status = 'returned')";
            } elseif ($filterStatus === 'pending') {
                $sql .= " AND (ar.status = 'pending' OR ar.status LIKE '%review%')";
            } else {
                $sql .= " AND ar.status = ?";
                $params[] = $filterStatus;
            }
        }

        $sql .= " ORDER BY ar.request_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();

        if (empty($reports)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No approval records found for the selected criteria']);
            exit;
        }

        // Generate filename
        $statusSuffix = $filterStatus !== 'all' ? '_' . $filterStatus : '';
        $filename = "approval_details{$statusSuffix}_{$dateFrom}_to_{$dateTo}";

        // CSV output
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Request ID',
            'Status',
            'Asset Name',
            'Asset Code',
            'Category',
            'Quantity',
            'Purpose',
            'Requester Name',
            'Requester Email',
            'Campus',
            'Request Date',
            'Expected Return Date',
            'Custodian Reviewer',
            'Custodian Review Date',
            'Custodian Notes',
            'Department Approver',
            'Department Approval Date',
            'Final Approver',
            'Final Approval Date',
            'Admin Notes',
            'Rejection Reason',
            'Approval Time (hours)'
        ]);

        foreach ($reports as $report) {
            fputcsv($output, [
                $report['request_id'],
                ucfirst(str_replace('_', ' ', $report['status'])),
                $report['asset_name'],
                $report['asset_code'],
                $report['category'] ?? 'N/A',
                $report['quantity'],
                $report['purpose'] ?? 'N/A',
                $report['requester_name'],
                $report['requester_email'],
                $report['campus_name'],
                $report['request_date'],
                $report['expected_return_date'] ?? 'N/A',
                $report['custodian_reviewer'] ?? 'Pending',
                $report['custodian_reviewed_at'] ?? 'N/A',
                $report['custodian_review_notes'] ?? 'N/A',
                $report['department_approver'] ?? 'N/A',
                $report['department_approved_at'] ?? 'N/A',
                $report['final_approver'] ?? 'N/A',
                $report['final_approved_at'] ?? 'N/A',
                $report['admin_notes'] ?? 'N/A',
                $report['rejection_reason'] ?? 'N/A',
                round($report['approval_time_hours'], 2)
            ]);
        }

        fclose($output);
        exit;
    }

} catch (Exception $e) {
    error_log("Error exporting approval summary: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
}
?>
