<?php
/**
 * Reports Dashboard
 * Unified interface for generating various system reports
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

$campusId = $user['campus_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .report-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid transparent;
        }
        .report-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stat-card {
            background: linear-gradient(135deg, var(--from-color), var(--to-color));
        }

        /* Enhanced table styling */
        #reportTable table {
            border-collapse: separate;
            border-spacing: 0;
        }

        #reportTable thead th {
            background: linear-gradient(to bottom, #1f2937, #111827);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #reportTable tbody tr {
            transition: all 0.2s ease;
        }

        #reportTable tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        #reportTable tbody tr:hover {
            background-color: #eff6ff !important;
            transform: scale(1.005);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        #reportTable tbody td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Search bar styling */
        .search-container {
            position: relative;
        }

        .search-container input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
                <a href="offices.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Office Management</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 bg-gray-700">
                    <i class="fas fa-chart-bar w-6"></i><span>Reports</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Reports</h1>
                    <p class="text-sm text-gray-600 mt-1">Generate and export various reports</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> My Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <!-- Total Assets Card -->
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform transition hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-blue-100 text-sm font-medium">Total Assets</p>
                                    <p id="totalAssets" class="text-3xl font-bold mt-2">--</p>
                                </div>
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <i class="fas fa-box text-4xl text-white"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Active Borrowings Card -->
                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform transition hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-green-100 text-sm font-medium">Active Borrowings</p>
                                    <p id="activeBorrowings" class="text-3xl font-bold mt-2">--</p>
                                </div>
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <i class="fas fa-hand-holding text-4xl text-white"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Requests Card -->
                        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform transition hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-yellow-100 text-sm font-medium">Pending Requests</p>
                                    <p id="pendingRequests" class="text-3xl font-bold mt-2">--</p>
                                </div>
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <i class="fas fa-clock text-4xl text-white"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Missing Assets Card -->
                        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white transform transition hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-red-100 text-sm font-medium">Missing Assets</p>
                                    <p id="missingAssets" class="text-3xl font-bold mt-2">--</p>
                                </div>
                                <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                    <i class="fas fa-exclamation-triangle text-4xl text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Categories -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Borrowing History Report -->
                        <div class="report-card bg-white rounded-xl shadow-md hover:shadow-xl p-6 border-l-4 border-blue-500" onclick="openReportModal('borrowing')">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl p-4">
                                        <i class="fas fa-exchange-alt text-3xl text-blue-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">Borrowing History</h3>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Track asset borrowing, returns, and overdue items with detailed timeline
                                        </p>
                                    </div>
                                </div>
                                <span class="bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full h-fit">Popular</span>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-calendar mr-1"></i>Date Range
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-filter mr-1"></i>Status Filter
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Overdue Tracking
                                </span>
                            </div>
                        </div>

                        <!-- Approval Summary Report -->
                        <div class="report-card bg-white rounded-xl shadow-md hover:shadow-xl p-6 border-l-4 border-green-500" onclick="openReportModal('approval')">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0 bg-gradient-to-br from-green-100 to-green-200 rounded-xl p-4">
                                        <i class="fas fa-check-circle text-3xl text-green-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">Approval Summary</h3>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Track approval statistics, response times, and decision history
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-list mr-1"></i>Detailed/Summary
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-clock mr-1"></i>Approval Times
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-user-check mr-1"></i>Approver Stats
                                </span>
                            </div>
                        </div>

                        <!-- Depreciation Summary Report -->
                        <div class="report-card bg-white rounded-xl shadow-md hover:shadow-xl p-6 border-l-4 border-yellow-500" onclick="openReportModal('depreciation')">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-xl p-4">
                                        <i class="fas fa-chart-line text-3xl text-yellow-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">Depreciation Summary</h3>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Asset valuation reports showing original vs current values
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-box mr-1"></i>By Asset
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-th-large mr-1"></i>By Category
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-building mr-1"></i>By Office
                                </span>
                            </div>
                        </div>

                        <!-- Missing Assets Report -->
                        <div class="report-card bg-white rounded-xl shadow-md hover:shadow-xl p-6 border-l-4 border-red-500" onclick="openReportModal('missing')">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0 bg-gradient-to-br from-red-100 to-red-200 rounded-xl p-4">
                                        <i class="fas fa-search text-3xl text-red-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">Missing Assets</h3>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Track missing asset investigations with location and borrower details
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-filter mr-1"></i>Status Filter
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-clipboard mr-1"></i>Investigation Notes
                                </span>
                                <span class="bg-gray-50 text-gray-600 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-tasks mr-1"></i>Resolution Tracking
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- Report Table Section -->
                    <div id="reportTableSection" class="hidden mt-8">
                        <!-- Enhanced Header -->
                        <div class="bg-white rounded-t-xl shadow-sm border-b border-gray-200">
                            <div class="flex items-center justify-between p-6">
                                <div class="flex items-center space-x-4">
                                    <button onclick="closeReportTable()" class="text-gray-400 hover:text-gray-600 transition">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                    <div>
                                        <h3 id="reportTableTitle" class="text-2xl font-bold text-gray-800">Report</h3>
                                        <p class="text-sm text-gray-500 mt-1">Viewing <span id="reportCount" class="font-semibold text-gray-700">0</span> records</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <button onclick="refreshReportData()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors inline-flex items-center">
                                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                                    </button>
                                    <button onclick="printReport()" class="px-4 py-2 text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors inline-flex items-center">
                                        <i class="fas fa-print mr-2"></i>Print
                                    </button>
                                    <button onclick="downloadCurrentReport()" class="px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm inline-flex items-center">
                                        <i class="fas fa-download mr-2"></i>Download CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filters Section -->
                            <div class="px-6 pb-6">
                                <!-- Search Bar -->
                                <div class="search-container mb-4">
                                    <div class="relative">
                                        <input type="text" id="tableSearch" placeholder="Search in table results..."
                                               onkeyup="searchInTable()"
                                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                        <i class="fas fa-search absolute left-4 top-4 text-gray-400"></i>
                                        <button onclick="clearSearch()" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Filters -->
                                <div id="reportFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <!-- Filters will be loaded dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div id="reportLoading" class="hidden bg-white rounded-lg shadow-sm p-12 text-center">
                            <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-600">Loading report data...</p>
                        </div>

                        <!-- Table Container -->
                        <div id="reportTable" class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <!-- Table will be loaded here -->
                        </div>

                        <!-- Pagination Footer -->
                        <div id="reportPagination" class="hidden bg-white rounded-b-xl shadow-sm border-t border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span id="reportPageInfo" class="font-semibold text-gray-900"></span>
                                </div>
                                <div class="flex space-x-2" id="reportPaginationButtons">
                                    <!-- Pagination buttons -->
                                </div>
                            </div>
                        </div>
                    </div>

        </main>
    </div>

    <!-- Report Configuration Modal (Simplified) -->
    <div id="reportModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900" id="modalTitle">Generate Report</h3>
                    <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div id="modalContent" class="p-6">
                <!-- Content will be loaded dynamically -->
            </div>

                    <div class="p-6 border-t border-gray-200 bg-gray-50 flex justify-between">
                <button onclick="viewTableReport()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                    <i class="fas fa-table mr-2"></i>View Table
                </button>
                <div class="flex space-x-3">
                    <button onclick="closeReportModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg">
                        Cancel
                    </button>
                    <button onclick="generateReport()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <i class="fas fa-download mr-2"></i>Download CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentReportType = null;

    // Load statistics on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadStatistics();
    });

    async function loadStatistics() {
        try {
            const response = await fetch('/AMS-REQ/api/dashboard_stats.php');
            const data = await response.json();

            if (data.success) {
                document.getElementById('totalAssets').textContent = data.stats.total_assets || '0';
                document.getElementById('activeBorrowings').textContent = data.stats.active_borrowings || '0';
                document.getElementById('pendingRequests').textContent = data.stats.pending_requests || '0';
                document.getElementById('missingAssets').textContent = data.stats.missing_assets || '0';
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    function openReportModal(reportType) {
        currentReportType = reportType;

        // Scroll to table section
        document.getElementById('reportTableSection').classList.remove('hidden');
        document.getElementById('reportTableSection').scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Update title
        const titles = {
            'borrowing': 'Borrowing History Report',
            'approval': 'Approval Summary Report',
            'depreciation': 'Depreciation Summary Report',
            'missing': 'Missing Assets Report'
        };
        document.getElementById('reportTableTitle').textContent = titles[reportType];

        // Load filters and data
        loadReportFilters(reportType);
        loadReportData(1);

        return; // Skip modal logic below

        const modal = document.getElementById('reportModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');

        // Set title and content based on report type
        if (reportType === 'borrowing') {
            modalTitle.textContent = 'Generate Borrowing History Report';
            modalContent.innerHTML = getBorrowingReportForm();
        } else if (reportType === 'approval') {
            modalTitle.textContent = 'Generate Approval Summary Report';
            modalContent.innerHTML = getApprovalReportForm();
        } else if (reportType === 'depreciation') {
            modalTitle.textContent = 'Generate Depreciation Summary';
            modalContent.innerHTML = getDepreciationReportForm();
        } else if (reportType === 'missing') {
            modalTitle.textContent = 'Generate Missing Assets Report';
            modalContent.innerHTML = getMissingAssetsReportForm();
        }

        // Set default dates to current month
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        if (dateFrom && dateTo) {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            dateFrom.value = firstDay.toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
        }

        modal.classList.remove('hidden');
    }

    function closeReportModal() {
        document.getElementById('reportModal').classList.add('hidden');
        currentReportType = null;
    }

    function getBorrowingReportForm() {
        return `
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Filter</label>
                    <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="all">All Status</option>
                        <option value="released">Currently Borrowed</option>
                        <option value="returned">Returned</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" id="dateFrom" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" id="dateTo" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Export Format</label>
                    <select id="exportFormat" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="csv">CSV (Excel Compatible)</option>
                    </select>
                </div>
            </div>
        `;
    }

    function getApprovalReportForm() {
        return `
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select id="reportTypeSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="detailed">Detailed (All Requests)</option>
                        <option value="summary">Summary (Statistics)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Filter</label>
                    <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="all">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" id="dateFrom" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" id="dateTo" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Export Format</label>
                    <select id="exportFormat" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="csv">CSV (Excel Compatible)</option>
                    </select>
                </div>
            </div>
        `;
    }

    function getDepreciationReportForm() {
        return `
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Group By</label>
                    <select id="groupBy" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="asset">By Asset (Detailed)</option>
                        <option value="category">By Category (Summary)</option>
                        <option value="office">By Office/Department (Summary)</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="includeRetired" class="mr-2">
                    <label for="includeRetired" class="text-sm text-gray-700">Include Retired Assets</label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Export Format</label>
                    <select id="exportFormat" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="csv">CSV (Excel Compatible)</option>
                    </select>
                </div>
            </div>
        `;
    }

    function getMissingAssetsReportForm() {
        return `
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Filter</label>
                    <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="all">All Status</option>
                        <option value="reported">Reported</option>
                        <option value="investigating">Investigating</option>
                        <option value="found">Found</option>
                        <option value="lost">Confirmed Lost</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Export Format</label>
                    <select id="exportFormat" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="csv">CSV (Excel Compatible)</option>
                    </select>
                </div>
            </div>
        `;
    }

    function generateReport() {
        const format = document.getElementById('exportFormat')?.value || 'csv';
        let url = '';

        if (currentReportType === 'borrowing') {
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            url = `/AMS-REQ/api/export_borrowing_history.php?format=${format}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}`;
        } else if (currentReportType === 'approval') {
            const reportType = document.getElementById('reportTypeSelect').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            url = `/AMS-REQ/api/export_approval_summary.php?format=${format}&report_type=${reportType}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}`;
        } else if (currentReportType === 'depreciation') {
            const groupBy = document.getElementById('groupBy').value;
            const includeRetired = document.getElementById('includeRetired').checked;
            url = `/AMS-REQ/api/export_depreciation_summary.php?format=${format}&group_by=${groupBy}&include_retired=${includeRetired}`;
        } else if (currentReportType === 'missing') {
            const status = document.getElementById('statusFilter').value;
            url = `/AMS-REQ/api/export_missing_assets.php?format=${format}&status=${status}`;
        }

        // Download the file
        window.location.href = url;

        // Show success message
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Report Generated!',
                text: 'Your report is downloading now.',
                timer: 2000,
                showConfirmButton: false
            });
            closeReportModal();
        }, 500);
    }

    // New inline table functions
    let currentPage = 1;
    let currentFilters = {};

    function loadReportFilters(reportType) {
        const container = document.getElementById('reportFilters');
        let html = '';

        const firstDay = new Date();
        firstDay.setDate(1);
        const today = new Date().toISOString().split('T')[0];
        const firstDayStr = firstDay.toISOString().split('T')[0];

        if (reportType === 'borrowing') {
            html = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="reportFilterStatus" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="all">All Status</option>
                        <option value="released">Currently Borrowed</option>
                        <option value="returned">Returned</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" id="reportFilterDateFrom" value="${firstDayStr}" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" id="reportFilterDateTo" value="${today}" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                    <select id="reportFilterLimit" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            `;
        } else if (reportType === 'approval') {
            html = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select id="reportFilterReportType" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="detailed">Detailed</option>
                        <option value="summary">Summary</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="reportFilterStatus" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="all">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" id="reportFilterDateFrom" value="${firstDayStr}" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" id="reportFilterDateTo" value="${today}" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
            `;
        } else if (reportType === 'depreciation') {
            html = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Group By</label>
                    <select id="reportFilterGroupBy" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="asset">By Asset</option>
                        <option value="category">By Category</option>
                        <option value="office">By Office</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Include Retired</label>
                    <select id="reportFilterRetired" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="false">Exclude Retired</option>
                        <option value="true">Include Retired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                    <select id="reportFilterLimit" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            `;
        } else if (reportType === 'missing') {
            html = `
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="reportFilterStatus" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="all">All Status</option>
                        <option value="reported">Reported</option>
                        <option value="investigating">Investigating</option>
                        <option value="found">Found</option>
                        <option value="lost">Confirmed Lost</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                    <select id="reportFilterLimit" onchange="applyReportFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    function applyReportFilters() {
        currentPage = 1;
        loadReportData(1);
    }

    async function loadReportData(page = 1) {
        currentPage = page;

        // Show loading
        document.getElementById('reportLoading').classList.remove('hidden');
        document.getElementById('reportTable').innerHTML = '';
        document.getElementById('reportPagination').classList.add('hidden');

        // Build params
        let params = new URLSearchParams({
            report_type: currentReportType,
            page: currentPage,
            limit: document.getElementById('reportFilterLimit')?.value || 25
        });

        if (currentReportType === 'borrowing') {
            params.append('status', document.getElementById('reportFilterStatus')?.value || 'all');
            params.append('date_from', document.getElementById('reportFilterDateFrom')?.value || '');
            params.append('date_to', document.getElementById('reportFilterDateTo')?.value || '');
        } else if (currentReportType === 'approval') {
            params.append('report_type_filter', document.getElementById('reportFilterReportType')?.value || 'detailed');
            params.append('status', document.getElementById('reportFilterStatus')?.value || 'all');
            params.append('date_from', document.getElementById('reportFilterDateFrom')?.value || '');
            params.append('date_to', document.getElementById('reportFilterDateTo')?.value || '');
        } else if (currentReportType === 'depreciation') {
            params.append('group_by', document.getElementById('reportFilterGroupBy')?.value || 'asset');
            params.append('include_retired', document.getElementById('reportFilterRetired')?.value || 'false');
        } else if (currentReportType === 'missing') {
            params.append('status', document.getElementById('reportFilterStatus')?.value || 'all');
        }

        currentFilters = Object.fromEntries(params);

        try {
            const response = await fetch(`/AMS-REQ/api/get_report_data.php?${params.toString()}`);
            const data = await response.json();

            document.getElementById('reportLoading').classList.add('hidden');

            if (data.success) {
                displayReportTable(data);
                if (data.pagination) {
                    displayReportPagination(data.pagination);
                }
            } else {
                document.getElementById('reportTable').innerHTML = `
                    <div class="p-12 text-center text-gray-500">
                        ${data.message || 'No data found'}
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('reportLoading').classList.add('hidden');
            document.getElementById('reportTable').innerHTML = `
                <div class="p-12 text-center text-red-500">
                    <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
                    <p>Failed to load report data</p>
                </div>
            `;
            console.error('Error loading report:', error);
        }
    }

    function displayReportTable(data) {
        const container = document.getElementById('reportTable');

        // Update record count
        const totalRecords = data.pagination?.total || data.data?.length || 0;
        document.getElementById('reportCount').textContent = totalRecords;

        let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';

        // Generate table based on report type (reusing the logic from view_report.php)
        html += '<thead class="bg-gray-800 text-white"><tr>';

        if (currentReportType === 'borrowing') {
            html += `
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Asset</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Requester</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Released</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Expected Return</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Returned</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Overdue</th>
            `;
        } else if (currentReportType === 'approval') {
            if (data.type === 'summary') {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Approved</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Rejected</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Pending</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Avg Time (hrs)</th>
                `;
            } else {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Asset</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Requester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Custodian</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Admin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Time (hrs)</th>
                `;
            }
        } else if (currentReportType === 'depreciation') {
            if (data.type === 'asset') {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Asset</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Original</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Current</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Depreciation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Age</th>
                `;
            } else {
                html += `
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">${data.type === 'category' ? 'Category' : 'Office'}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Assets</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Original</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Current</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Depreciation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">%</th>
                `;
            }
        } else if (currentReportType === 'missing') {
            html += `
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Asset</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Borrower</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Reporter</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase">Date</th>
            `;
        }

        html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

        // Add rows
        data.data.forEach(row => {
            html += '<tr class="hover:bg-gray-50">';

            if (currentReportType === 'borrowing') {
                html += `
                    <td class="px-6 py-4 text-sm">#${row.id}</td>
                    <td class="px-6 py-4 text-sm">${getStatusBadge(row.borrowing_status)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.requester_name)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">${formatDate(row.released_date)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">${formatDate(row.expected_return_date)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">${formatDate(row.returned_date) || '-'}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap ${row.days_overdue > 0 ? 'text-red-600 font-bold' : ''}">${row.days_overdue || '0'}</td>
                `;
            } else if (currentReportType === 'approval' && data.type === 'summary') {
                html += `
                    <td class="px-6 py-4 text-sm">${getStatusBadge(row.status)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.category_name) || 'All'}</td>
                    <td class="px-6 py-4 text-sm font-bold">${row.total_requests}</td>
                    <td class="px-6 py-4 text-sm text-green-600">${row.approved_count}</td>
                    <td class="px-6 py-4 text-sm text-red-600">${row.rejected_count}</td>
                    <td class="px-6 py-4 text-sm text-yellow-600">${row.pending_count}</td>
                    <td class="px-6 py-4 text-sm">${parseFloat(row.avg_approval_hours).toFixed(1)}</td>
                `;
            } else if (currentReportType === 'approval') {
                html += `
                    <td class="px-6 py-4 text-sm">#${row.id}</td>
                    <td class="px-6 py-4 text-sm">${getStatusBadge(row.status)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.requester_name)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">${formatDate(row.request_date)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.custodian_name) || '-'}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.admin_name) || '-'}</td>
                    <td class="px-6 py-4 text-sm">${row.approval_time_hours ? parseFloat(row.approval_time_hours).toFixed(1) : '-'}</td>
                `;
            } else if (currentReportType === 'depreciation' && data.type === 'asset') {
                html += `
                    <td class="px-6 py-4 text-sm">#${row.id}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}</td>
                    <td class="px-6 py-4 text-sm">${getStatusBadge(row.status)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.category_name) || '-'}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">₱${parseFloat(row.original_value).toFixed(2)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap text-green-600">₱${parseFloat(row.current_value).toFixed(2)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap text-red-600">₱${parseFloat(row.total_depreciation).toFixed(2)}</td>
                    <td class="px-6 py-4 text-sm">${row.age_years}y</td>
                `;
            } else if (currentReportType === 'depreciation') {
                const depPercent = row.total_original_value > 0 ? ((row.total_depreciation / row.total_original_value) * 100).toFixed(1) : 0;
                html += `
                    <td class="px-6 py-4 text-sm font-semibold">${escapeHtml(row[data.type === 'category' ? 'category_name' : 'office_name']) || 'N/A'}</td>
                    <td class="px-6 py-4 text-sm">${row.asset_count}</td>
                    <td class="px-6 py-4 text-sm">${row.total_quantity}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">₱${parseFloat(row.total_original_value).toFixed(2)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap text-green-600">₱${parseFloat(row.total_current_value).toFixed(2)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap text-red-600">₱${parseFloat(row.total_depreciation).toFixed(2)}</td>
                    <td class="px-6 py-4 text-sm">${depPercent}%</td>
                `;
            } else if (currentReportType === 'missing') {
                html += `
                    <td class="px-6 py-4 text-sm">#${row.id}</td>
                    <td class="px-6 py-4 text-sm">${getStatusBadge(row.status)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.asset_name)}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.last_known_location) || '-'}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.last_known_borrower) || '-'}</td>
                    <td class="px-6 py-4 text-sm">${escapeHtml(row.reporter_name)}</td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">${formatDate(row.reported_date)}</td>
                `;
            }

            html += '</tr>';
        });

        html += '</tbody></table></div>';

        if (data.data.length === 0) {
            html = '<div class="p-12 text-center text-gray-500">No data found</div>';
        }

        container.innerHTML = html;
    }

    function displayReportPagination(pagination) {
        if (pagination.pages <= 1) return;

        document.getElementById('reportPageInfo').textContent =
            `${((pagination.page - 1) * pagination.limit) + 1}-${Math.min(pagination.page * pagination.limit, pagination.total)} of ${pagination.total}`;

        let html = '';
        html += `<button onclick="loadReportData(${pagination.page - 1})" ${pagination.page === 1 ? 'disabled' : ''}
            class="px-3 py-1 border rounded text-sm ${pagination.page === 1 ? 'bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-50'}">
            <i class="fas fa-chevron-left"></i>
        </button>`;

        for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.pages, pagination.page + 2); i++) {
            html += `<button onclick="loadReportData(${i})"
                class="px-3 py-1 border rounded text-sm ${i === pagination.page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-50'}">
                ${i}
            </button>`;
        }

        html += `<button onclick="loadReportData(${pagination.page + 1})" ${pagination.page === pagination.pages ? 'disabled' : ''}
            class="px-3 py-1 border rounded text-sm ${pagination.page === pagination.pages ? 'bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-50'}">
            <i class="fas fa-chevron-right"></i>
        </button>`;

        document.getElementById('reportPaginationButtons').innerHTML = html;
        document.getElementById('reportPagination').classList.remove('hidden');
    }

    function downloadCurrentReport() {
        const params = new URLSearchParams(currentFilters);
        params.delete('page');
        params.delete('limit');
        params.set('format', 'csv');

        let url = '';
        if (currentReportType === 'borrowing') {
            url = `/AMS-REQ/api/export_borrowing_history.php?${params.toString()}`;
        } else if (currentReportType === 'approval') {
            url = `/AMS-REQ/api/export_approval_summary.php?${params.toString()}`;
        } else if (currentReportType === 'depreciation') {
            url = `/AMS-REQ/api/export_depreciation_summary.php?${params.toString()}`;
        } else if (currentReportType === 'missing') {
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

    function printReport() {
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
        return `<span class="px-2 py-1 rounded-full text-xs font-semibold ${colorClass}">${statusText}</span>`;
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

    // Close report table
    function closeReportTable() {
        document.getElementById('reportTableSection').classList.add('hidden');
        document.getElementById('tableSearch').value = '';
        currentReportType = null;
        currentPage = 1;
        currentFilters = {};
    }

    // Refresh report data
    function refreshReportData() {
        if (currentReportType) {
            loadReportData(currentPage);
            Swal.fire({
                icon: 'success',
                title: 'Refreshed',
                text: 'Report data has been refreshed',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }

    // Search in table
    function searchInTable() {
        const searchTerm = document.getElementById('tableSearch').value.toLowerCase();
        const table = document.querySelector('#reportTable table');

        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update count if searching
        if (searchTerm) {
            document.getElementById('reportCount').textContent = `${visibleCount} (filtered)`;
        }
    }

    // Clear search
    function clearSearch() {
        document.getElementById('tableSearch').value = '';
        searchInTable();

        // Restore original count
        const totalRecords = currentFilters.total || 0;
        document.getElementById('reportCount').textContent = totalRecords;
    }
    </script>
            </main>
        </div>
    </div>
</body>
</html>
