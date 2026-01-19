<?php
/**
 * Office Assets - Detailed View with Individual Units
 * Shows all assets assigned to the office with individual unit tracking
 */

require_once dirname(__DIR__) . '/config.php';

// Generate CSRF token
generateCSRFToken();

// Require office user access
if (!isLoggedIn() || !hasRole('office')) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$officeId = $user['office_id'];

// Get specific tag if provided
$tagId = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : null;

// Get office details
$stmt = $pdo->prepare("SELECT * FROM offices WHERE id = ?");
$stmt->execute([$officeId]);
$office = $stmt->fetch();

if (!$office) {
    die('Office not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Details - <?= htmlspecialchars($office['office_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-gray-300">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Dept Head</span>
            </div>
            <nav class="mt-4 space-y-2 px-4">
                <a href="office_dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <?php
                // Check if user is a department approver
                $checkApprover = $pdo->prepare("SELECT COUNT(*) as is_approver FROM department_approvers WHERE approver_user_id = ? AND is_active = TRUE");
                $checkApprover->execute([$user['id']]);
                $isApprover = $checkApprover->fetch()['is_approver'] > 0;

                if ($isApprover):
                ?>
                <a href="approve_requests.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                    <?php
                    // Get pending approval count for dual-flow system
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM asset_requests ar
                        WHERE ar.status = 'office_review'
                        AND ar.request_source = 'office'
                        AND ar.target_office_id = (SELECT office_id FROM department_approvers WHERE approver_user_id = ? LIMIT 1)
                        AND ar.campus_id = ?
                    ");
                    $stmt->execute([$user['id'], $user['campus_id']]);
                    $pendingCount = (int)$stmt->fetch()['count'];

                    if ($pendingCount > 0):
                    ?>
                        <span class="ml-auto bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="release_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                    <?php
                    // Get approved requests ready for release
                    $releaseStmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM asset_requests ar
                        WHERE ar.status = 'approved'
                        AND ar.request_source = 'office'
                        AND ar.target_office_id = (SELECT office_id FROM department_approvers WHERE approver_user_id = ? LIMIT 1)
                        AND ar.campus_id = ?
                    ");
                    $releaseStmt->execute([$user['id'], $user['campus_id']]);
                    $releaseCount = (int)$releaseStmt->fetch()['count'];

                    if ($releaseCount > 0):
                    ?>
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $releaseCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="return_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-undo-alt w-6"></i>
                    <span>Accept Returns</span>
                    <?php
                    // Get released assets pending return
                    $returnStmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM asset_requests ar
                        WHERE ar.status = 'released'
                        AND ar.request_source = 'office'
                        AND ar.target_office_id = (SELECT office_id FROM department_approvers WHERE approver_user_id = ? LIMIT 1)
                        AND ar.campus_id = ?
                    ");
                    $returnStmt->execute([$user['id'], $user['campus_id']]);
                    $returnCount = (int)$returnStmt->fetch()['count'];

                    if ($returnCount > 0):
                    ?>
                        <span class="ml-auto bg-blue-500 text-white text-xs px-2 py-1 rounded-full font-bold"><?= $returnCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <a href="approval_history.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-history w-6"></i>
                    <span>Approval History</span>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <a href="request_from_custodian.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-paper-plane w-6"></i>
                    <span>Request from Custodian</span>
                </a>

                <a href="my_requests.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-clipboard-list w-6"></i>
                    <span>My Requests</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex items-center justify-between p-4 bg-white border-b">
                <div>
                    <h1 class="text-xl font-semibold">Asset Details</h1>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($office['office_name']) ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase">Total Units</p>
                                <p class="text-2xl font-bold text-gray-900" id="stat-total">0</p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-3">
                                <i class="fas fa-boxes text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase">Available</p>
                                <p class="text-2xl font-bold text-green-600" id="stat-available">0</p>
                            </div>
                            <div class="bg-green-100 rounded-full p-3">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase">In Use</p>
                                <p class="text-2xl font-bold text-blue-600" id="stat-inuse">0</p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-3">
                                <i class="fas fa-user-check text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 uppercase">Issues</p>
                                <p class="text-2xl font-bold text-red-600" id="stat-issues">0</p>
                            </div>
                            <div class="bg-red-100 rounded-full p-3">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assets List Container -->
                <div id="assets-list-container">
                    <!-- Assets will be loaded here -->
                </div>
            </main>
        </div>
    </div>

    <!-- Unit Details Modal -->
    <div id="unitDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-xl font-semibold" id="modalTitle">Unit Details</h3>
                <button onclick="closeUnitModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="p-6" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="p-4 border-t flex justify-end gap-3">
                <button onclick="closeUnitModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">
                    Close
                </button>
                <button onclick="reportIssue()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Report Issue
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentUnit = null;
        const officeId = <?= $officeId ?>;
        const specificTagId = <?= $tagId ?? 'null' ?>;

        document.addEventListener('DOMContentLoaded', function() {
            loadOfficeAssets();
        });

        async function loadOfficeAssets() {
            try {
                const url = specificTagId
                    ? `../api/asset_units.php?action=get_office_units&office_id=${officeId}&tag_id=${specificTagId}`
                    : `../api/asset_units.php?action=get_office_units&office_id=${officeId}`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    renderAssetsTables(data.tags);
                    updateStats(data.tags);
                } else {
                    throw new Error(data.message || 'Failed to load assets');
                }
            } catch (error) {
                console.error('Error loading assets:', error);
                document.getElementById('assets-list-container').innerHTML = `
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-exclamation-circle text-4xl text-red-400 mb-4"></i>
                        <p class="text-red-600">${error.message}</p>
                    </div>
                `;
            }
        }

        function renderAssetsTables(tags) {
            const container = document.getElementById('assets-list-container');

            if (tags.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600 text-lg">No assets with individual tracking assigned to this office yet.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = tags.map(tag => {
                const hasUnits = tag.units && tag.units.length > 0;

                return `
                    <div class="bg-white rounded-lg shadow-md mb-6">
                        <!-- Asset Header -->
                        <div class="bg-gray-50 border-b px-6 py-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">${tag.asset_name}</h3>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-tag mr-1"></i>Tag: <span class="font-mono">${tag.tag_number}</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="text-xs text-gray-600">Total Units</p>
                                        <p class="text-2xl font-bold text-blue-600">${tag.total_units || 0}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Units Table -->
                        ${hasUnits ? `
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200 text-gray-700 text-xs uppercase">
                                    <tr>
                                        <th class="py-3 px-4 text-left">Unit Code</th>
                                        <th class="py-3 px-4 text-center">Status</th>
                                        <th class="py-3 px-4 text-center">Condition</th>
                                        <th class="py-3 px-4 text-left">Location</th>
                                        <th class="py-3 px-4 text-left">Assigned To</th>
                                        <th class="py-3 px-4 text-left">Last Updated</th>
                                        <th class="py-3 px-4 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700 text-sm">
                                    ${tag.units.map(unit => `
                                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <span class="font-mono font-bold">${unit.code}</span>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="status-badge ${getStatusClass(unit.status)}">${unit.status}</span>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="inline-flex items-center">
                                                    <span class="w-2 h-2 rounded-full mr-2 ${getConditionDot(unit.condition)}"></span>
                                                    ${unit.condition}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">${unit.location || 'N/A'}</td>
                                            <td class="py-3 px-4">${unit.assigned_to_name || '-'}</td>
                                            <td class="py-3 px-4">${unit.updated_at ? new Date(unit.updated_at).toLocaleDateString() : 'N/A'}</td>
                                            <td class="py-3 px-4 text-center">
                                                <button onclick='viewUnitDetails(${JSON.stringify(unit)}, "${tag.asset_name}", "${tag.tag_number}")'
                                                        class="text-blue-600 hover:text-blue-800 mr-2" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick='quickReportIssue(${unit.id}, "${unit.code}")'
                                                        class="text-red-600 hover:text-red-800" title="Report Issue">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        ` : `
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-info-circle text-3xl mb-2"></i>
                            <p>This asset uses bulk tracking (no individual units)</p>
                        </div>
                        `}
                    </div>
                `;
            }).join('');
        }

        function getStatusClass(status) {
            const classes = {
                'Available': 'bg-blue-100 text-blue-800',
                'In Use': 'bg-green-100 text-green-800',
                'Damaged': 'bg-yellow-100 text-yellow-800',
                'Missing': 'bg-red-100 text-red-800',
                'Under Repair': 'bg-purple-100 text-purple-800',
                'Retired': 'bg-gray-100 text-gray-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }

        function getConditionDot(condition) {
            const classes = {
                'Excellent': 'bg-green-500',
                'Good': 'bg-blue-500',
                'Fair': 'bg-yellow-500',
                'Poor': 'bg-orange-500',
                'Non-functional': 'bg-red-500'
            };
            return classes[condition] || 'bg-gray-500';
        }

        function updateStats(tags) {
            let totalUnits = 0;
            let available = 0;
            let inUse = 0;
            let issues = 0;

            tags.forEach(tag => {
                if (tag.units && tag.units.length > 0) {
                    tag.units.forEach(unit => {
                        totalUnits++;
                        if (unit.status === 'Available') available++;
                        if (unit.status === 'In Use') inUse++;
                        if (['Damaged', 'Missing', 'Under Repair'].includes(unit.status)) issues++;
                    });
                }
            });

            document.getElementById('stat-total').textContent = totalUnits;
            document.getElementById('stat-available').textContent = available;
            document.getElementById('stat-inuse').textContent = inUse;
            document.getElementById('stat-issues').textContent = issues;
        }

        function viewUnitDetails(unit, assetName, tagNumber) {
            currentUnit = unit;
            document.getElementById('modalTitle').textContent = `${assetName} - ${unit.code}`;
            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border-b pb-2">
                            <p class="text-xs font-medium text-gray-500 uppercase">Unit Code</p>
                            <p class="text-lg font-mono font-bold mt-1">${unit.code}</p>
                        </div>
                        <div class="border-b pb-2">
                            <p class="text-xs font-medium text-gray-500 uppercase">Tag Number</p>
                            <p class="text-lg font-mono mt-1">${tagNumber}</p>
                        </div>
                        <div class="border-b pb-2">
                            <p class="text-xs font-medium text-gray-500 uppercase">Status</p>
                            <p class="mt-1"><span class="status-badge ${getStatusClass(unit.status)}">${unit.status}</span></p>
                        </div>
                        <div class="border-b pb-2">
                            <p class="text-xs font-medium text-gray-500 uppercase">Condition</p>
                            <p class="text-lg mt-1 flex items-center">
                                <span class="w-2 h-2 rounded-full mr-2 ${getConditionDot(unit.condition)}"></span>
                                ${unit.condition}
                            </p>
                        </div>
                        <div class="border-b pb-2">
                            <p class="text-xs font-medium text-gray-500 uppercase">Location</p>
                            <p class="text-lg mt-1">${unit.location || 'Not specified'}</p>
                        </div>
                        <div class="border-b pb-2">
                            <p class="text-xs font-medium text-gray-500 uppercase">Assigned To</p>
                            <p class="text-lg mt-1">${unit.assigned_to_name || 'Not assigned'}</p>
                        </div>
                    </div>
                    ${unit.notes ? `
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-xs font-medium text-gray-700 uppercase mb-1">Notes</p>
                        <p class="text-sm text-gray-700">${unit.notes}</p>
                    </div>
                    ` : ''}
                    <div class="bg-blue-50 border border-blue-200 rounded p-3">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                            Click "Report Issue" if this unit is damaged, missing, or needs maintenance.
                        </p>
                    </div>
                </div>
            `;
            document.getElementById('unitDetailsModal').classList.remove('hidden');
        }

        function closeUnitModal() {
            document.getElementById('unitDetailsModal').classList.add('hidden');
            currentUnit = null;
        }

        function quickReportIssue(unitId, unitCode) {
            Swal.fire({
                title: 'Report Issue',
                html: `
                    <div class="text-left">
                        <p class="mb-4">Report an issue with unit <strong class="font-mono">${unitCode}</strong></p>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Issue Type</label>
                        <select id="issue-type" class="w-full p-2 border border-gray-300 rounded mb-3 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select issue type...</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Missing">Missing</option>
                            <option value="Under Repair">Under Repair</option>
                        </select>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="issue-description" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500" rows="4" placeholder="Describe the issue..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Submit Report',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                preConfirm: () => {
                    const type = document.getElementById('issue-type').value;
                    const description = document.getElementById('issue-description').value;
                    if (!type) {
                        Swal.showValidationMessage('Please select an issue type');
                        return false;
                    }
                    return { type, description, unitId };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitIssueReport(result.value);
                }
            });
        }

        function reportIssue() {
            if (!currentUnit) return;
            quickReportIssue(currentUnit.id, currentUnit.code);
        }

        async function submitIssueReport(data) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_unit');
                formData.append('unit_id', data.unitId);
                formData.append('unit_status', data.type);
                formData.append('notes', data.description);

                const response = await fetch('../api/asset_units.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire('Success!', 'Issue reported successfully. The custodian will be notified.', 'success');
                    closeUnitModal();
                    loadOfficeAssets(); // Reload to show updated status
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire('Error!', error.message, 'error');
            }
        }
    </script>
</body>
</html>
