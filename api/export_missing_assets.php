<?php
/**
 * API Endpoint: Export Missing Assets Report
 * Exports missing asset reports to CSV or Excel format
 */

require_once dirname(__DIR__) . '/config.php';

// Require login and custodian/admin access
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

// Get export format (default to csv)
$format = $_GET['format'] ?? 'csv';
$filterStatus = $_GET['status'] ?? 'all';

try {
    // Build query
    $sql = "
        SELECT
            mar.id as report_id,
            mar.status,
            mar.reported_date,
            mar.last_known_location,
            mar.last_known_borrower,
            mar.last_known_borrower_contact,
            mar.last_seen_date,
            mar.responsible_department,
            mar.description,
            mar.resolution_notes,
            mar.resolved_date,
            a.asset_name,
            COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
            c.category_name as category,
            reporter.full_name as reporter_name,
            reporter.email as reporter_email,
            resolver.full_name as resolved_by_name
        FROM missing_assets_reports mar
        JOIN assets a ON mar.asset_id = a.id
        LEFT JOIN categories c ON a.category_id = c.id
        JOIN users reporter ON mar.reported_by = reporter.id
        LEFT JOIN users resolver ON mar.resolved_by = resolver.id
        WHERE mar.campus_id = ?
    ";

    $params = [$campusId];

    if ($filterStatus !== 'all') {
        $sql .= " AND mar.status = ?";
        $params[] = $filterStatus;
    }

    $sql .= " ORDER BY mar.reported_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    if (empty($reports)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No missing asset reports found']);
        exit;
    }

    // Generate filename
    $date = date('Y-m-d');
    $statusSuffix = $filterStatus !== 'all' ? '_' . $filterStatus : '';
    $filename = "missing_assets_report{$statusSuffix}_{$date}";

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
            'Report ID',
            'Status',
            'Asset Name',
            'Asset Code',
            'Category',
            'Last Known Location',
            'Last Seen Date',
            'Reported Date',
            'Reported By',
            'Reporter Email',
            'Last Known Borrower',
            'Borrower Contact',
            'Responsible Department',
            'Description',
            'Investigation Notes',
            'Resolved By',
            'Resolved Date'
        ]);

        // Add data rows
        foreach ($reports as $report) {
            fputcsv($output, [
                $report['report_id'],
                ucfirst(str_replace('_', ' ', $report['status'])),
                $report['asset_name'],
                $report['asset_code'],
                $report['category'] ?? 'N/A',
                $report['last_known_location'],
                $report['last_seen_date'],
                $report['reported_date'],
                $report['reporter_name'],
                $report['reporter_email'],
                $report['last_known_borrower'] ?? 'N/A',
                $report['last_known_borrower_contact'] ?? 'N/A',
                $report['responsible_department'] ?? 'N/A',
                $report['description'],
                $report['resolution_notes'] ?? 'N/A',
                $report['resolved_by_name'] ?? 'N/A',
                $report['resolved_date'] ?? 'N/A'
            ]);
        }

        fclose($output);
        exit;

    } elseif ($format === 'pdf') {
        // For PDF, we'll use HTML to PDF conversion
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"{$filename}.pdf\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        // Generate HTML content
        $html = generatePDFHTML($reports, $filterStatus);

        // Use DomPDF or similar library here
        // For now, we'll generate a simple HTML that can be printed to PDF
        // You can add a PDF library later

        echo "PDF export requires a PDF library. Please use CSV/Excel export for now.";
        exit;

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid export format']);
        exit;
    }

} catch (Exception $e) {
    error_log("Error exporting missing assets: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
}

function generatePDFHTML($reports, $filterStatus) {
    $statusTitle = $filterStatus !== 'all' ? ucfirst(str_replace('_', ' ', $filterStatus)) : 'All';
    $date = date('F d, Y');

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Missing Assets Report</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { font-size: 18px; text-align: center; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .status-badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; }
            .status-reported { background: #FEF3C7; color: #92400E; }
            .status-investigating { background: #DBEAFE; color: #1E40AF; }
            .status-found { background: #D1FAE5; color: #065F46; }
            .status-lost { background: #FEE2E2; color: #991B1B; }
        </style>
    </head>
    <body>
        <h1>Missing Assets Report - {$statusTitle}</h1>
        <p style='text-align: center;'>Generated on {$date}</p>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Asset</th>
                    <th>Location</th>
                    <th>Reported By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($reports as $report) {
        $statusClass = 'status-' . str_replace('_', '-', $report['status']);
        $statusLabel = ucfirst(str_replace('_', ' ', $report['status']));

        $html .= "
                <tr>
                    <td>#{$report['report_id']}</td>
                    <td><span class='status-badge {$statusClass}'>{$statusLabel}</span></td>
                    <td>{$report['asset_name']} ({$report['asset_code']})</td>
                    <td>{$report['last_known_location']}</td>
                    <td>{$report['reporter_name']}</td>
                    <td>{$report['reported_date']}</td>
                </tr>";
    }

    $html .= "
            </tbody>
        </table>
    </body>
    </html>";

    return $html;
}
?>
