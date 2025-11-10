<?php
/**
 * API Endpoint: Export Depreciation Summary Report
 * Exports asset depreciation data by category, department, or campus
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
$groupBy = $_GET['group_by'] ?? 'asset'; // asset, category, office, campus
$includeRetired = isset($_GET['include_retired']) && $_GET['include_retired'] === 'true';

try {
    if ($groupBy === 'asset') {
        // Detailed asset-level report
        $sql = "
            SELECT
                a.id as asset_id,
                a.asset_name,
                COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
                a.serial_number,
                a.status,
                a.quantity,
                a.purchase_date,
                a.original_value,
                a.current_value,
                a.value as market_value,
                a.depreciation_rate,
                a.last_depreciation_date,
                c.category_name,
                o.office_name,
                cam.campus_name,
                a.assigned_to,
                TIMESTAMPDIFF(MONTH, a.purchase_date, CURDATE()) as age_months,
                TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE()) as age_years,
                ROUND((a.original_value - COALESCE(a.current_value, a.original_value)), 2) as total_depreciation,
                ROUND(((a.original_value - COALESCE(a.current_value, a.original_value)) / a.original_value * 100), 2) as depreciation_percent,
                CASE
                    WHEN a.depreciation_rate > 0 AND TIMESTAMPDIFF(MONTH, COALESCE(a.last_depreciation_date, a.purchase_date), CURDATE()) >= 12
                    THEN 'Needs Update'
                    ELSE 'Current'
                END as depreciation_status
            FROM assets a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN offices o ON a.office_id = o.id
            JOIN campuses cam ON a.campus_id = cam.id
            WHERE a.campus_id = ?
            AND a.original_value IS NOT NULL
            AND a.original_value > 0
        ";

        $params = [$campusId];

        if (!$includeRetired) {
            $sql .= " AND a.status != 'Retired'";
        }

        $sql .= " ORDER BY total_depreciation DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();

        if (empty($reports)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No depreciation data found']);
            exit;
        }

        // Calculate totals
        $totalOriginalValue = array_sum(array_column($reports, 'original_value'));
        $totalCurrentValue = array_sum(array_column($reports, 'current_value'));
        $totalDepreciation = $totalOriginalValue - $totalCurrentValue;

        // Generate filename
        $filename = "depreciation_detail_" . date('Y-m-d');

        // CSV output
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Summary header
        fputcsv($output, ['DEPRECIATION SUMMARY REPORT']);
        fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
        fputcsv($output, ['Campus:', $reports[0]['campus_name'] ?? 'N/A']);
        fputcsv($output, ['Total Original Value:', number_format($totalOriginalValue, 2)]);
        fputcsv($output, ['Total Current Value:', number_format($totalCurrentValue, 2)]);
        fputcsv($output, ['Total Depreciation:', number_format($totalDepreciation, 2)]);
        fputcsv($output, ['Overall Depreciation %:', round(($totalDepreciation / $totalOriginalValue * 100), 2) . '%']);
        fputcsv($output, []); // Empty row

        // Data headers
        fputcsv($output, [
            'Asset ID',
            'Asset Name',
            'Asset Code',
            'Serial Number',
            'Status',
            'Category',
            'Office',
            'Assigned To',
            'Quantity',
            'Purchase Date',
            'Age (Years)',
            'Age (Months)',
            'Original Value',
            'Current Value',
            'Market Value',
            'Total Depreciation',
            'Depreciation %',
            'Depreciation Rate %',
            'Last Depreciation Date',
            'Depreciation Status'
        ]);

        foreach ($reports as $report) {
            fputcsv($output, [
                $report['asset_id'],
                $report['asset_name'],
                $report['asset_code'],
                $report['serial_number'] ?? 'N/A',
                $report['status'],
                $report['category_name'] ?? 'N/A',
                $report['office_name'] ?? 'Unassigned',
                $report['assigned_to'] ?? 'Unassigned',
                $report['quantity'],
                $report['purchase_date'],
                $report['age_years'],
                $report['age_months'],
                number_format($report['original_value'], 2),
                number_format($report['current_value'] ?? $report['original_value'], 2),
                number_format($report['market_value'], 2),
                number_format($report['total_depreciation'], 2),
                $report['depreciation_percent'] . '%',
                $report['depreciation_rate'] . '%',
                $report['last_depreciation_date'] ?? 'Never',
                $report['depreciation_status']
            ]);
        }

        fclose($output);
        exit;

    } elseif ($groupBy === 'category') {
        // Summary by category
        $sql = "
            SELECT
                c.category_name,
                COUNT(a.id) as asset_count,
                SUM(a.quantity) as total_quantity,
                ROUND(SUM(a.original_value * a.quantity), 2) as total_original_value,
                ROUND(SUM(COALESCE(a.current_value, a.original_value) * a.quantity), 2) as total_current_value,
                ROUND(SUM((a.original_value - COALESCE(a.current_value, a.original_value)) * a.quantity), 2) as total_depreciation,
                ROUND(AVG(a.depreciation_rate), 2) as avg_depreciation_rate,
                ROUND(AVG(TIMESTAMPDIFF(YEAR, a.purchase_date, CURDATE())), 2) as avg_age_years
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
        $reports = $stmt->fetchAll();

        if (empty($reports)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No depreciation data found']);
            exit;
        }

        // Generate filename
        $filename = "depreciation_by_category_" . date('Y-m-d');

        // CSV output
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['DEPRECIATION SUMMARY BY CATEGORY']);
        fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // Empty row

        fputcsv($output, [
            'Category',
            'Asset Count',
            'Total Quantity',
            'Total Original Value',
            'Total Current Value',
            'Total Depreciation',
            'Depreciation %',
            'Avg Depreciation Rate %',
            'Avg Age (Years)'
        ]);

        foreach ($reports as $report) {
            $depreciationPercent = $report['total_original_value'] > 0
                ? round(($report['total_depreciation'] / $report['total_original_value'] * 100), 2)
                : 0;

            fputcsv($output, [
                $report['category_name'] ?? 'Uncategorized',
                $report['asset_count'],
                $report['total_quantity'],
                number_format($report['total_original_value'], 2),
                number_format($report['total_current_value'], 2),
                number_format($report['total_depreciation'], 2),
                $depreciationPercent . '%',
                $report['avg_depreciation_rate'] . '%',
                $report['avg_age_years']
            ]);
        }

        fclose($output);
        exit;

    } elseif ($groupBy === 'office') {
        // Summary by office/department
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
        $reports = $stmt->fetchAll();

        if (empty($reports)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No depreciation data found']);
            exit;
        }

        // Generate filename
        $filename = "depreciation_by_office_" . date('Y-m-d');

        // CSV output
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['DEPRECIATION SUMMARY BY OFFICE/DEPARTMENT']);
        fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // Empty row

        fputcsv($output, [
            'Office/Department',
            'Asset Count',
            'Total Quantity',
            'Total Original Value',
            'Total Current Value',
            'Total Depreciation',
            'Depreciation %'
        ]);

        foreach ($reports as $report) {
            $depreciationPercent = $report['total_original_value'] > 0
                ? round(($report['total_depreciation'] / $report['total_original_value'] * 100), 2)
                : 0;

            fputcsv($output, [
                $report['office_name'],
                $report['asset_count'],
                $report['total_quantity'],
                number_format($report['total_original_value'], 2),
                number_format($report['total_current_value'], 2),
                number_format($report['total_depreciation'], 2),
                $depreciationPercent . '%'
            ]);
        }

        fclose($output);
        exit;
    }

} catch (Exception $e) {
    error_log("Error exporting depreciation summary: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
}
?>
