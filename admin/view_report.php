<?php
/**
 * View Report in Table Format
 * Interactive table view with pagination, sorting, and CSV download
 */

require_once dirname(__DIR__) . '/config.php';

// Require admin or custodian access
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$role = strtolower($user['role'] ?? '');

if ($role !== 'admin' && $role !== 'custodian') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. Admin or Custodian access required.';
    exit;
}

$reportType = $_GET['type'] ?? 'borrowing';
$reportTitles = [
    'borrowing' => 'Borrowing History Report',
    'approval' => 'Approval Summary Report',
    'depreciation' => 'Depreciation Summary Report',
    'missing' => 'Missing Assets Report'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $reportTitles[$reportType] ?? 'Report' ?> - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table-container {
            overflow-x: auto;
        }
        table {
            border-collapse: separate;
            border-spacing: 0;
        }
        th {
            position: sticky;
            top: 0;
            background: #1f2937;
            color: white;
            z-index: 10;
        }
        tr:hover {
            background-color: #f9fafb;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Admin Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="admin_dashboard.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-home w-6"></i><span>Dashboard</span>
                </a>
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-check-circle w-6"></i><span>Approve Requests</span>
                </a>
                <a href="users.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-users w-6"></i><span>User Management</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-chart-bar w-6"></i><span>Reports</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div class="flex items-center space-x-4">
                    <a href="reports.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800"><?= $reportTitles[$reportType] ?? 'Report' ?></h1>
                        <p class="text-sm text-gray-600 mt-1">Interactive table view with export options</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="downloadCSV()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg inline-flex items-center">
                        <i class="fas fa-download mr-2"></i>Download CSV
                    </button>
                    <button onclick="printTable()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg inline-flex items-center">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i>
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Filters -->
                <div class="bg-white border-b px-4 py-4">
                    <div id="filtersContainer">
                        <!-- Filters will be loaded dynamically based on report type -->
                    </div>
                </div>

                <!-- Content -->
                <main class="flex-1 overflow-y-auto p-6">

            <!-- Loading State -->
            <div id="loadingState" class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-600">Loading report data...</p>
            </div>

            <!-- Table Container -->
            <div id="tableContainer" class="hidden bg-white rounded-lg shadow-sm">
                <!-- Table will be loaded here -->
            </div>

            <!-- Pagination -->
            <div id="paginationContainer" class="hidden mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span id="pageInfo"></span>
                </div>
                <div class="flex space-x-2" id="paginationButtons">
                    <!-- Pagination buttons will be loaded here -->
                </div>
            </div>

        </main>
    </div>

    <script>
    const reportType = '<?= $reportType ?>';
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};

    document.addEventListener('DOMContentLoaded', function() {
        loadFilters();
        loadReport();
    });

    function loadFilters() {
        const container = document.getElementById('filtersContainer');
        let html = '<div class="grid grid-cols-1 md:grid-cols-4 gap-4">';

        if (reportType === 'borrowing') {
            html += `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="filterStatus" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="all">All Status</option>
                        <option value="released">Currently Borrowed</option>
                        <option value="returned">Returned</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" id="filterDateFrom" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${getFirstDayOfMonth()}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" id="filterDateTo" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${getToday()}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Records Per Page</label>
                    <select id="filterLimit" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            `;
        } else if (reportType === 'approval') {
            html += `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select id="filterReportType" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="detailed">Detailed</option>
                        <option value="summary">Summary</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="filterStatus" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="all">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" id="filterDateFrom" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${getFirstDayOfMonth()}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" id="filterDateTo" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="${getToday()}">
                </div>
            `;
        } else if (reportType === 'depreciation') {
            html += `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Group By</label>
                    <select id="filterGroupBy" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="asset">By Asset</option>
                        <option value="category">By Category</option>
                        <option value="office">By Office</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Include Retired</label>
                    <select id="filterRetired" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="false">Exclude Retired</option>
                        <option value="true">Include Retired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Records Per Page</label>
                    <select id="filterLimit" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            `;
        } else if (reportType === 'missing') {
            html += `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="filterStatus" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="all">All Status</option>
                        <option value="reported">Reported</option>
                        <option value="investigating">Investigating</option>
                        <option value="found">Found</option>
                        <option value="lost">Confirmed Lost</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Records Per Page</label>
                    <select id="filterLimit" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            `;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    function getFirstDayOfMonth() {
        const date = new Date();
        return new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
    }

    function getToday() {
        return new Date().toISOString().split('T')[0];
    }

    function applyFilters() {
        currentPage = 1;
        loadReport();
    }

    async function loadReport(page = 1) {
        currentPage = page;

        // Show loading
        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('tableContainer').classList.add('hidden');
        document.getElementById('paginationContainer').classList.add('hidden');

        // Build filter params
        let params = new URLSearchParams({
            report_type: reportType,
            page: currentPage,
            limit: document.getElementById('filterLimit')?.value || 25
        });

        if (reportType === 'borrowing') {
            params.append('status', document.getElementById('filterStatus')?.value || 'all');
            params.append('date_from', document.getElementById('filterDateFrom')?.value || getFirstDayOfMonth());
            params.append('date_to', document.getElementById('filterDateTo')?.value || getToday());
        } else if (reportType === 'approval') {
            params.append('report_type_filter', document.getElementById('filterReportType')?.value || 'detailed');
            params.append('status', document.getElementById('filterStatus')?.value || 'all');
            params.append('date_from', document.getElementById('filterDateFrom')?.value || getFirstDayOfMonth());
            params.append('date_to', document.getElementById('filterDateTo')?.value || getToday());
        } else if (reportType === 'depreciation') {
            params.append('group_by', document.getElementById('filterGroupBy')?.value || 'asset');
            params.append('include_retired', document.getElementById('filterRetired')?.value || 'false');
        } else if (reportType === 'missing') {
            params.append('status', document.getElementById('filterStatus')?.value || 'all');
        }

        currentFilters = Object.fromEntries(params);

        try {
            const response = await fetch(`/AMS-REQ/api/get_report_data.php?${params.toString()}`);
            const data = await response.json();

            document.getElementById('loadingState').classList.add('hidden');

            if (data.success) {
                displayTable(data);
                if (data.pagination) {
                    displayPagination(data.pagination);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to load report data'
                });
            }
        } catch (error) {
            document.getElementById('loadingState').classList.add('hidden');
            console.error('Error loading report:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load report data'
            });
        }
    }

    function displayTable(data) {
        const container = document.getElementById('tableContainer');
        let html = '<div class="table-container"><table class="min-w-full divide-y divide-gray-200">';

        // Table headers based on report type
        html += '<thead><tr>';

        if (reportType === 'borrowing') {
            html += `
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Asset</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Requester</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Released Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Expected Return</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Returned Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Days Overdue</th>
            `;
        } else if (reportType === 'approval') {
            if (data.type === 'summary') {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Total Requests</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Approved</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Rejected</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Pending</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Avg Approval Time (hrs)</th>
                `;
            } else {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Asset</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Requester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Request Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Custodian</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Admin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Approval Time (hrs)</th>
                `;
            }
        } else if (reportType === 'depreciation') {
            if (data.type === 'asset') {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Asset Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Original Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Current Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Depreciation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Age (years)</th>
                `;
            } else {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">${data.type === 'category' ? 'Category' : 'Office'}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Asset Count</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Total Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Original Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Current Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Depreciation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Depreciation %</th>
                `;
            }
        } else if (reportType === 'missing') {
            html += `
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Asset</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Last Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Last Borrower</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Reported By</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Reported Date</th>
            `;
        }

        html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

        // Table rows
        data.data.forEach(row => {
            html += '<tr>';

            if (reportType === 'borrowing') {
                html += `
                    <td class="px-6 py-4 whitespace-nowrap text-sm">#${row.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${getStatusBadge(row.borrowing_status)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}<br><span class="text-gray-500 text-xs">${row.asset_code}</span></td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.requester_name)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${formatDate(row.released_date)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${formatDate(row.expected_return_date)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${formatDate(row.returned_date) || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm ${row.days_overdue > 0 ? 'text-red-600 font-semibold' : ''}">${row.days_overdue || '0'}</td>
                `;
            } else if (reportType === 'approval') {
                if (data.type === 'summary') {
                    html += `
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${getStatusBadge(row.status)}</td>
                        <td class="px-6 py-4 text-sm">${escapeHtml(row.category_name) || 'All'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">${row.total_requests}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">${row.approved_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">${row.rejected_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">${row.pending_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${parseFloat(row.avg_approval_hours).toFixed(1)}</td>
                    `;
                } else {
                    html += `
                        <td class="px-6 py-4 whitespace-nowrap text-sm">#${row.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${getStatusBadge(row.status)}</td>
                        <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}</td>
                        <td class="px-6 py-4 text-sm">${escapeHtml(row.requester_name)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${formatDate(row.request_date)}</td>
                        <td class="px-6 py-4 text-sm">${escapeHtml(row.custodian_name) || '-'}</td>
                        <td class="px-6 py-4 text-sm">${escapeHtml(row.admin_name) || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${row.approval_time_hours ? parseFloat(row.approval_time_hours).toFixed(1) : '-'}</td>
                    `;
                }
            } else if (reportType === 'depreciation') {
                if (data.type === 'asset') {
                    html += `
                        <td class="px-6 py-4 whitespace-nowrap text-sm">#${row.id}</td>
                        <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${getStatusBadge(row.status)}</td>
                        <td class="px-6 py-4 text-sm">${escapeHtml(row.category_name) || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">₱${parseFloat(row.original_value).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">₱${parseFloat(row.current_value).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">₱${parseFloat(row.total_depreciation).toFixed(2)} (${row.depreciation_percent}%)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${row.age_years}</td>
                    `;
                } else {
                    const depPercent = row.total_original_value > 0 ? ((row.total_depreciation / row.total_original_value) * 100).toFixed(2) : 0;
                    html += `
                        <td class="px-6 py-4 text-sm font-semibold">${escapeHtml(row[data.type === 'category' ? 'category_name' : 'office_name']) || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${row.asset_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${row.total_quantity}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">₱${parseFloat(row.total_original_value).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">₱${parseFloat(row.total_current_value).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">₱${parseFloat(row.total_depreciation).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">${depPercent}%</td>
                    `;
                }
            } else if (reportType === 'missing') {
                html += `
                    <td class="px-6 py-4 whitespace-nowrap text-sm">#${row.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${getStatusBadge(row.status)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}<br><span class="text-gray-500 text-xs">${row.asset_code}</span></td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.last_known_location) || '-'}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.last_known_borrower) || '-'}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.reporter_name)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${formatDate(row.reported_date)}</td>
                `;
            }

            html += '</tr>';
        });

        html += '</tbody></table></div>';

        if (data.data.length === 0) {
            html = '<div class="p-12 text-center text-gray-500">No data found for the selected criteria</div>';
        }

        container.innerHTML = html;
        container.classList.remove('hidden');
    }

    function displayPagination(pagination) {
        if (pagination.pages <= 1) return;

        const container = document.getElementById('paginationContainer');
        const buttonsContainer = document.getElementById('paginationButtons');

        document.getElementById('pageInfo').textContent =
            `${((pagination.page - 1) * pagination.limit) + 1}-${Math.min(pagination.page * pagination.limit, pagination.total)} of ${pagination.total}`;

        let html = '';

        // Previous button
        html += `<button onclick="loadReport(${pagination.page - 1})" ${pagination.page === 1 ? 'disabled' : ''}
            class="px-3 py-1 border rounded ${pagination.page === 1 ? 'bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-50'}">
            <i class="fas fa-chevron-left"></i>
        </button>`;

        // Page numbers
        for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.pages, pagination.page + 2); i++) {
            html += `<button onclick="loadReport(${i})"
                class="px-3 py-1 border rounded ${i === pagination.page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-50'}">
                ${i}
            </button>`;
        }

        // Next button
        html += `<button onclick="loadReport(${pagination.page + 1})" ${pagination.page === pagination.pages ? 'disabled' : ''}
            class="px-3 py-1 border rounded ${pagination.page === pagination.pages ? 'bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-50'}">
            <i class="fas fa-chevron-right"></i>
        </button>`;

        buttonsContainer.innerHTML = html;
        container.classList.remove('hidden');
        totalPages = pagination.pages;
    }

    function downloadCSV() {
        // Build URL for CSV export based on current filters
        let url = '';
        const params = new URLSearchParams(currentFilters);
        params.delete('page');
        params.delete('limit');
        params.set('format', 'csv');

        if (reportType === 'borrowing') {
            url = `/AMS-REQ/api/export_borrowing_history.php?${params.toString()}`;
        } else if (reportType === 'approval') {
            url = `/AMS-REQ/api/export_approval_summary.php?${params.toString()}`;
        } else if (reportType === 'depreciation') {
            url = `/AMS-REQ/api/export_depreciation_summary.php?${params.toString()}`;
        } else if (reportType === 'missing') {
            url = `/AMS-REQ/api/export_missing_assets.php?${params.toString()}`;
        }

        window.location.href = url;

        Swal.fire({
            icon: 'success',
            title: 'Download Started',
            text: 'Your CSV file is downloading...',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function printTable() {
        window.print();
    }

    function getStatusBadge(status) {
        const badges = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'approved': 'bg-green-100 text-green-800',
            'rejected': 'bg-red-100 text-red-800',
            'released': 'bg-blue-100 text-blue-800',
            'returned': 'bg-gray-100 text-gray-800',
            'Overdue': 'bg-red-100 text-red-800',
            'Returned Late': 'bg-orange-100 text-orange-800',
            'Returned On Time': 'bg-green-100 text-green-800',
            'Currently Borrowed': 'bg-blue-100 text-blue-800',
            'reported': 'bg-yellow-100 text-yellow-800',
            'investigating': 'bg-blue-100 text-blue-800',
            'found': 'bg-green-100 text-green-800',
            'lost': 'bg-red-100 text-red-800',
            'Available': 'bg-green-100 text-green-800',
            'Unavailable': 'bg-gray-100 text-gray-800',
            'Damaged': 'bg-red-100 text-red-800',
            'Under Repair': 'bg-yellow-100 text-yellow-800'
        };

        const statusText = status.replace(/_/g, ' ');
        const colorClass = badges[status] || 'bg-gray-100 text-gray-800';

        return `<span class="status-badge ${colorClass}">${statusText}</span>`;
    }

    function formatDate(dateString) {
        if (!dateString || dateString === 'null') return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    </script>
                </main>
            </div>
        </div>
    </div>
</body>
</html>
