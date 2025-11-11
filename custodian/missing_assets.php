<?php
/**
 * Missing Assets Management Page
 * Custodians can view, investigate, and resolve missing asset reports
 */

require_once dirname(__DIR__) . '/config.php';

// Require custodian access
if (!isLoggedIn() || !hasRole('custodian')) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

// Get statistics
$stats = [
    'reported' => 0,
    'investigating' => 0,
    'found' => 0,
    'permanently_lost' => 0
];

try {
    foreach ($stats as $status => $count) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM missing_assets_reports
            WHERE status = ?
            AND campus_id = ?
        ");
        $stmt->execute([$status, $campusId]);
        $stats[$status] = (int)$stmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Error fetching missing asset statistics: " . $e->getMessage());
}

// Get filter from URL
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';


// Get statistics for release/return badges
$campusId = $user['campus_id'];
$pendingReleaseCount = 0;
$pendingReturnCount = 0;
$overdueCount = 0;

try {
    // Count pending releases (approved but not yet released)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'approved' AND campus_id = ?");
    $stmt->execute([$campusId]);
    $pendingReleaseCount = (int)$stmt->fetchColumn();

    // Count pending returns (released but not yet returned)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'released' AND campus_id = ?");
    $stmt->execute([$campusId]);
    $pendingReturnCount = (int)$stmt->fetchColumn();

    // Count overdue returns
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM asset_requests WHERE status = 'released' AND campus_id = ? AND expected_return_date < CURDATE()");
    $stmt->execute([$campusId]);
    $overdueCount = (int)$stmt->fetchColumn();

    // Count missing assets reports (active investigations)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM missing_assets_reports WHERE campus_id = ? AND status IN ('reported', 'investigating')");
    $stmt->execute([$campusId]);
    $missingAssetsCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching custodian statistics: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missing Assets - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }
        .badge-reported {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-investigating {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-found {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-permanently-lost {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <div class="flex h-screen bg-gray-200">

    <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Custodian Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <!-- Simplified Navigation -->
                <a href="dashboard.php" onclick="showTab('manage-assets')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-box-open w-6"></i><span>Manage Assets</span>
                </a>
                <a href="dashboard.php" onclick="showTab('offices')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Offices</span>
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-700 my-2"></div>

                <!-- Quick Scan Update -->
                <a href="quick_scan_update.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-barcode-read w-6"></i>
                    <span>Quick Scan Update</span>
                </a>

                <!-- Approve Requests -->
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                </a>

                <!-- Release Assets -->
                <a href="release_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                    <?php if ($pendingReleaseCount > 0): ?>
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingReleaseCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Return Assets -->
                <a href="return_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-undo w-6"></i>
                    <span>Return Assets</span>
                    <?php if ($pendingReturnCount > 0): ?>
                        <span class="ml-auto <?= $overdueCount > 0 ? 'bg-red-500' : 'bg-yellow-500' ?> text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingReturnCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-700 my-2"></div>

                <!-- Missing Assets -->
                <a href="missing_assets.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-search w-6"></i>
                    <span>Missing Assets</span>
                    <?php if ($missingAssetsCount > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $missingAssetsCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- Approval History -->
                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 text-gray-300">
                    <i class="fas fa-history w-6"></i>
                    <span>Approval History</span>
                </a>
            </nav>
        </div>

    <div class="flex-1 flex flex-col overflow-hidden">
         <!-- Header -->
         <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Missing Assets</h2>
                    <p class="text-gray-600 mt-1">Track and manage missing asset reports</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-7xl mx-auto">

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Reported</p>
                                <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $stats['reported'] ?></p>
                            </div>
                            <div class="bg-yellow-100 rounded-full p-3">
                                <i class="fas fa-exclamation-circle text-yellow-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Investigating</p>
                                <p class="text-3xl font-bold text-blue-600 mt-2"><?= $stats['investigating'] ?></p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-3">
                                <i class="fas fa-search text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Found</p>
                                <p class="text-3xl font-bold text-green-600 mt-2"><?= $stats['found'] ?></p>
                            </div>
                            <div class="bg-green-100 rounded-full p-3">
                                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Permanently Lost</p>
                                <p class="text-3xl font-bold text-gray-600 mt-2"><?= $stats['permanently_lost'] ?></p>
                            </div>
                            <div class="bg-red-100 rounded-full p-3">
                                <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-file-export text-green-600 mr-2"></i>Export Reports
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">Download missing asset reports for record-keeping and auditing</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button onclick="exportReport('csv')"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                                <i class="fas fa-file-csv mr-2"></i>Export to CSV
                            </button>
                            <button onclick="exportReport('excel')"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                                <i class="fas fa-file-excel mr-2"></i>Export to Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <a href="?status=all"
                               class="<?= $filterStatus === 'all' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>
                                      whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                All Reports
                            </a>
                            <a href="?status=reported"
                               class="<?= $filterStatus === 'reported' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>
                                      whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Reported (<?= $stats['reported'] ?>)
                            </a>
                            <a href="?status=investigating"
                               class="<?= $filterStatus === 'investigating' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>
                                      whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                <i class="fas fa-search mr-1"></i>
                                Investigating (<?= $stats['investigating'] ?>)
                            </a>
                            <a href="?status=found"
                               class="<?= $filterStatus === 'found' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>
                                      whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                <i class="fas fa-check-circle mr-1"></i>
                                Found (<?= $stats['found'] ?>)
                            </a>
                            <a href="?status=permanently_lost"
                               class="<?= $filterStatus === 'permanently_lost' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>
                                      whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                <i class="fas fa-times-circle mr-1"></i>
                                Permanently Lost (<?= $stats['permanently_lost'] ?>)
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Missing Assets List -->
                <div id="missingAssetsList" class="space-y-4">
                    <!-- Loading indicator -->
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">Loading missing asset reports...</p>
                    </div>
                </div>

            </div> <!-- Close max-w-7xl -->
        </main>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-lg rounded-lg bg-white my-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-file-alt text-red-600 mr-2"></i>
                    Missing Asset Report Details
                </h3>
                <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div id="detailsModalContent" class="space-y-6">
                <!-- Loading indicator -->
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                    <p class="text-gray-500 mt-2">Loading details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Investigation Modal -->
    <div id="investigationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>
                    Investigation Details
                </h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div id="modalContent" class="space-y-4">
                <!-- Dynamic content loaded here -->
            </div>
        </div>
    </div>

    <script>
        let currentFilter = '<?= $filterStatus ?>';

        // Load missing assets
        async function loadMissingAssets() {
            try {
                const params = new URLSearchParams({
                    action: 'get_missing_assets',
                    status: currentFilter
                });

                const response = await fetch(`../api/missing_assets.php?${params}`);
                const result = await response.json();

                if (result.success) {
                    displayMissingAssets(result.data);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                document.getElementById('missingAssetsList').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Error loading missing assets: ${error.message}
                    </div>
                `;
            }
        }

        // Export missing assets report
        function exportReport(format) {
            // Build export URL with current filter
            const url = `../api/export_missing_assets.php?format=${format}&status=${currentFilter}`;

            // Show loading notification
            Swal.fire({
                title: 'Generating Report',
                html: 'Please wait while we prepare your export...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Create a temporary link and trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = ''; // Filename will be set by the server
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Close loading after a short delay
            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Export Started',
                    text: 'Your download should begin shortly.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 1000);
        }

        function displayMissingAssets(reports) {
            const container = document.getElementById('missingAssetsList');

            if (reports.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                        <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500 text-lg">No missing asset reports found</p>
                        <p class="text-gray-400 text-sm mt-2">
                            ${currentFilter === 'all' ? 'Great! No assets are currently reported as missing.' : 'No reports match this filter.'}
                        </p>
                    </div>
                `;
                return;
            }

            container.innerHTML = reports.map(report => {
                const statusBadge = getStatusBadge(report.status);
                const daysOpen = Math.floor((new Date() - new Date(report.reported_date)) / (1000 * 60 * 60 * 24));

                return `
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-bold text-gray-900">
                                        ${report.asset_name}
                                    </h3>
                                    ${statusBadge}
                                    ${daysOpen > 7 ? `<span class="text-xs text-red-600 font-semibold"><i class="fas fa-clock mr-1"></i>${daysOpen} days open</span>` : ''}
                                </div>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-barcode mr-1"></i>
                                    Asset Code: <span class="font-medium">${report.asset_code}</span>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-tag mr-1"></i>
                                    ${report.category}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Report #${report.id}</p>
                                <p class="text-xs text-gray-500">${formatDate(report.reported_date)}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            <div>
                                <p class="text-gray-600"><i class="fas fa-user text-gray-400 mr-2"></i>Reported By:</p>
                                <p class="font-medium">${report.reporter_name}</p>
                            </div>
                            <div>
                                <p class="text-gray-600"><i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>Last Known Location:</p>
                                <p class="font-medium">${report.last_known_location || 'Unknown'}</p>
                            </div>
                            ${report.last_known_borrower ? `
                                <div>
                                    <p class="text-gray-600"><i class="fas fa-user-tag text-gray-400 mr-2"></i>Last Known Borrower:</p>
                                    <p class="font-medium">${report.last_known_borrower}</p>
                                </div>
                            ` : ''}
                            ${report.last_seen_date ? `
                                <div>
                                    <p class="text-gray-600"><i class="fas fa-calendar text-gray-400 mr-2"></i>Last Seen:</p>
                                    <p class="font-medium">${formatDate(report.last_seen_date)}</p>
                                </div>
                            ` : ''}
                        </div>

                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-1"><i class="fas fa-file-alt text-gray-400 mr-2"></i>Description:</p>
                            <p class="text-sm text-gray-800 bg-gray-50 p-3 rounded">${report.description}</p>
                        </div>

                        ${report.resolution_notes ? `
                            <div class="mb-4 bg-green-50 border border-green-200 rounded p-3">
                                <p class="text-sm text-green-800 font-medium mb-1"><i class="fas fa-check-circle mr-2"></i>Resolution Notes:</p>
                                <p class="text-sm text-green-700">${report.resolution_notes}</p>
                                ${report.resolved_by_name ? `
                                    <p class="text-xs text-green-600 mt-2">
                                        Resolved by ${report.resolved_by_name} on ${formatDate(report.resolved_date)}
                                    </p>
                                ` : ''}
                            </div>
                        ` : ''}

                        <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                            <button onclick="viewDetails(${report.id})"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                            ${report.status === 'reported' ? `
                                <button onclick="startInvestigation(${report.id})"
                                        class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">
                                    <i class="fas fa-search mr-2"></i>Start Investigation
                                </button>
                            ` : ''}
                            ${report.status === 'investigating' ? `
                                <button onclick="markAsFound(${report.id})"
                                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                                    <i class="fas fa-check-circle mr-2"></i>Mark as Found
                                </button>
                                <button onclick="markAsLost(${report.id})"
                                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                                    <i class="fas fa-times-circle mr-2"></i>Mark as Permanently Lost
                                </button>
                            ` : ''}
                            <button onclick="addNote(${report.id})"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-sm">
                                <i class="fas fa-sticky-note mr-2"></i>Add Note
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function getStatusBadge(status) {
            const badges = {
                'reported': '<span class="badge badge-reported"><i class="fas fa-exclamation-circle mr-1"></i>Reported</span>',
                'investigating': '<span class="badge badge-investigating"><i class="fas fa-search mr-1"></i>Investigating</span>',
                'found': '<span class="badge badge-found"><i class="fas fa-check-circle mr-1"></i>Found</span>',
                'permanently_lost': '<span class="badge badge-permanently-lost"><i class="fas fa-times-circle mr-1"></i>Permanently Lost</span>'
            };
            return badges[status] || '';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        async function startInvestigation(reportId) {
            const result = await Swal.fire({
                title: 'Start Investigation',
                html: `
                    <textarea id="investigationNotes" class="w-full px-3 py-2 border rounded" rows="4"
                              placeholder="Enter initial investigation notes..."></textarea>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Start Investigation',
                confirmButtonColor: '#eab308',
                preConfirm: () => {
                    const notes = document.getElementById('investigationNotes').value;
                    if (!notes) {
                        Swal.showValidationMessage('Please enter investigation notes');
                    }
                    return { notes };
                }
            });

            if (result.isConfirmed) {
                await updateReportStatus(reportId, 'investigating', result.value.notes);
            }
        }

        async function markAsFound(reportId) {
            const result = await Swal.fire({
                title: 'Mark as Found',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Where was it found?</label>
                            <input type="text" id="foundLocation" class="w-full px-3 py-2 border rounded"
                                   placeholder="e.g., Found in storage room">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resolution notes:</label>
                            <textarea id="resolutionNotes" class="w-full px-3 py-2 border rounded" rows="4"
                                      placeholder="Describe how it was found and current condition..."></textarea>
                        </div>
                    </div>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Mark as Found',
                confirmButtonColor: '#16a34a',
                preConfirm: () => {
                    const location = document.getElementById('foundLocation').value;
                    const notes = document.getElementById('resolutionNotes').value;
                    if (!notes) {
                        Swal.showValidationMessage('Please enter resolution notes');
                    }
                    return { location, notes };
                }
            });

            if (result.isConfirmed) {
                await updateReportStatus(reportId, 'found', result.value.notes, result.value.location);
            }
        }

        async function markAsLost(reportId) {
            const result = await Swal.fire({
                title: 'Mark as Permanently Lost',
                html: `
                    <p class="mb-4 text-sm text-gray-600">This action indicates the asset cannot be recovered.</p>
                    <textarea id="lostNotes" class="w-full px-3 py-2 border rounded" rows="4"
                              placeholder="Explain why the asset is considered permanently lost..."></textarea>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Mark as Permanently Lost',
                confirmButtonColor: '#dc2626',
                preConfirm: () => {
                    const notes = document.getElementById('lostNotes').value;
                    if (!notes) {
                        Swal.showValidationMessage('Please enter explanation');
                    }
                    return { notes };
                }
            });

            if (result.isConfirmed) {
                await updateReportStatus(reportId, 'permanently_lost', result.value.notes);
            }
        }

        async function addNote(reportId) {
            const result = await Swal.fire({
                title: 'Add Investigation Note',
                html: `
                    <textarea id="noteText" class="w-full px-3 py-2 border rounded" rows="4"
                              placeholder="Enter your investigation note..."></textarea>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Add Note',
                confirmButtonColor: '#3b82f6',
                preConfirm: () => {
                    const note = document.getElementById('noteText').value;
                    if (!note) {
                        Swal.showValidationMessage('Please enter a note');
                    }
                    return { note };
                }
            });

            if (result.isConfirmed) {
                // Add note via API
                await fetch('../api/missing_assets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        action: 'add_note',
                        report_id: reportId,
                        note: result.value.note
                    })
                });

                Swal.fire('Note Added', 'Investigation note has been added successfully.', 'success');
            }
        }

        async function updateReportStatus(reportId, status, notes, foundLocation = null) {
            try {
                const response = await fetch('../api/missing_assets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        report_id: reportId,
                        status: status,
                        notes: notes,
                        found_location: foundLocation
                    })
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire('Success', 'Report status updated successfully', 'success');
                    loadMissingAssets();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }

        async function viewDetails(reportId) {
            // Show the details modal
            document.getElementById('detailsModal').classList.remove('hidden');

            // Load the report details
            try {
                const response = await fetch(`../api/missing_assets.php?action=get_report_details&report_id=${reportId}`);
                const result = await response.json();

                if (result.success) {
                    displayReportDetails(result.data);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                document.getElementById('detailsModalContent').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Error loading report details: ${error.message}
                    </div>
                `;
            }
        }

        function displayReportDetails(data) {
            const report = data.report;
            const borrowingHistory = data.borrowing_history || [];
            const activityLogs = data.activity_logs || [];

            const statusColors = {
                'reported': 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'investigating': 'bg-blue-100 text-blue-800 border-blue-300',
                'found': 'bg-green-100 text-green-800 border-green-300',
                'permanently_lost': 'bg-red-100 text-red-800 border-red-300'
            };

            const statusIcons = {
                'reported': 'fa-exclamation-circle',
                'investigating': 'fa-search',
                'found': 'fa-check-circle',
                'permanently_lost': 'fa-times-circle'
            };

            const statusClass = statusColors[report.status] || 'bg-gray-100 text-gray-800 border-gray-300';
            const statusIcon = statusIcons[report.status] || 'fa-circle';

            let html = `
                <!-- Report Header -->
                <div class="bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">${report.asset_name}</h4>
                            <p class="text-gray-600">Asset Code: <span class="font-semibold">${report.asset_code}</span></p>
                            <p class="text-gray-600">Category: <span class="font-semibold">${report.category || 'N/A'}</span></p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-4 py-2 rounded-full border-2 ${statusClass}">
                                <i class="fas ${statusIcon} mr-2"></i>
                                ${report.status.replace('_', ' ').toUpperCase()}
                            </span>
                            <p class="text-sm text-gray-500 mt-2">Report #${report.id}</p>
                        </div>
                    </div>
                </div>

                <!-- Report Information Grid -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-user text-blue-600 mr-2"></i>
                                Reported By
                            </h5>
                            <p class="text-gray-900 font-medium">${report.reporter_name}</p>
                            <p class="text-sm text-gray-600">${report.reporter_email}</p>
                            ${report.reporter_phone ? `<p class="text-sm text-gray-600">${report.reporter_phone}</p>` : ''}
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="far fa-clock mr-1"></i>
                                ${new Date(report.reported_date).toLocaleString()}
                            </p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-calendar-alt text-green-600 mr-2"></i>
                                Last Seen
                            </h5>
                            <p class="text-gray-900 font-medium">${new Date(report.last_seen_date).toLocaleDateString()}</p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                                Last Known Location
                            </h5>
                            <p class="text-gray-900">${report.last_known_location}</p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        ${report.last_known_borrower ? `
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-user-circle text-purple-600 mr-2"></i>
                                Last Known Borrower
                            </h5>
                            <p class="text-gray-900 font-medium">${report.last_known_borrower}</p>
                            ${report.last_known_borrower_contact ? `<p class="text-sm text-gray-600">${report.last_known_borrower_contact}</p>` : ''}
                        </div>
                        ` : ''}

                        ${report.responsible_department ? `
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-building text-indigo-600 mr-2"></i>
                                Responsible Department
                            </h5>
                            <p class="text-gray-900">${report.responsible_department}</p>
                        </div>
                        ` : ''}

                        ${report.resolved_by_name ? `
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-user-check text-green-600 mr-2"></i>
                                Resolved By
                            </h5>
                            <p class="text-gray-900 font-medium">${report.resolved_by_name}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="far fa-clock mr-1"></i>
                                ${new Date(report.resolved_date).toLocaleString()}
                            </p>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Description -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-align-left text-gray-600 mr-2"></i>
                        Description
                    </h5>
                    <p class="text-gray-700 whitespace-pre-wrap">${report.description}</p>
                </div>

                <!-- Resolution Notes (if any) -->
                ${report.resolution_notes ? `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h5 class="font-semibold text-blue-900 mb-3 flex items-center">
                        <i class="fas fa-sticky-note text-blue-600 mr-2"></i>
                        Investigation Notes
                    </h5>
                    <p class="text-gray-700 whitespace-pre-wrap text-sm">${report.resolution_notes}</p>
                </div>
                ` : ''}

                <!-- Borrowing History -->
                ${borrowingHistory.length > 0 ? `
                <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-history text-purple-600 mr-2"></i>
                        Borrowing History
                    </h5>
                    <div class="space-y-2">
                        ${borrowingHistory.map(h => `
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded border border-gray-200">
                                <div>
                                    <p class="font-medium text-gray-900">${h.requester_name}</p>
                                    <p class="text-sm text-gray-600">${h.requester_email}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Status: <span class="font-semibold">${h.status}</span></p>
                                    <p class="text-xs text-gray-500">${new Date(h.request_date).toLocaleDateString()}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}

                <!-- Activity Logs -->
                ${activityLogs.length > 0 ? `
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-list text-gray-600 mr-2"></i>
                        Activity Log
                    </h5>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        ${activityLogs.map(log => `
                            <div class="flex items-start p-2 hover:bg-gray-50 rounded">
                                <i class="fas fa-circle text-gray-400 text-xs mt-1.5 mr-3"></i>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-700">${log.description}</p>
                                    <p class="text-xs text-gray-500">${new Date(log.created_at).toLocaleString()}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            `;

            document.getElementById('detailsModalContent').innerHTML = html;
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        function closeModal() {
            document.getElementById('investigationModal').classList.add('hidden');
        }

        // Load on page load
        loadMissingAssets();
    </script>
</body>
</html>
