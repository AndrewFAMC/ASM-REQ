<?php
/**
 * Manage Individual Asset Units
 * Custodian can view and update status/condition of each unit
 */

require_once '../config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$role = strtolower($user['role'] ?? '');

if (!in_array($role, ['custodian', 'admin', 'super_admin'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This page is for custodians and admins only.';
    exit;
}

$campusId = $user['campus_id'];

// Get assets with individual tracking
$assetsStmt = $pdo->prepare("
    SELECT
        a.id,
        a.asset_name,
        a.quantity,
        c.category_name,
        COUNT(au.id) as unit_count,
        SUM(CASE WHEN au.unit_status = 'Available' THEN 1 ELSE 0 END) as available_count,
        SUM(CASE WHEN au.unit_status = 'In Use' THEN 1 ELSE 0 END) as in_use_count,
        SUM(CASE WHEN au.unit_status = 'Damaged' THEN 1 ELSE 0 END) as damaged_count,
        SUM(CASE WHEN au.unit_status = 'Missing' THEN 1 ELSE 0 END) as missing_count,
        SUM(CASE WHEN au.unit_status = 'Under Repair' THEN 1 ELSE 0 END) as repair_count,
        SUM(CASE WHEN au.unit_status = 'Disposed' THEN 1 ELSE 0 END) as disposed_count
    FROM assets a
    JOIN categories c ON a.category_id = c.id
    LEFT JOIN asset_units au ON a.id = au.asset_id
    WHERE a.campus_id = ?
        AND a.track_individually = TRUE
    GROUP BY a.id, a.asset_name, a.quantity, c.category_name
    ORDER BY a.asset_name
");
$assetsStmt->execute([$campusId]);
$assets = $assetsStmt->fetchAll();

// Get stats
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_units,
        SUM(CASE WHEN unit_status = 'Available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN unit_status = 'In Use' THEN 1 ELSE 0 END) as in_use,
        SUM(CASE WHEN unit_status = 'Damaged' THEN 1 ELSE 0 END) as damaged,
        SUM(CASE WHEN unit_status = 'Missing' THEN 1 ELSE 0 END) as missing,
        SUM(CASE WHEN unit_status = 'Under Repair' THEN 1 ELSE 0 END) as under_repair
    FROM asset_units au
    JOIN assets a ON au.asset_id = a.id
    WHERE a.campus_id = ?
");
$statsStmt->execute([$campusId]);
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Individual Units - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-available { background: #dcfce7; color: #166534; }
        .status-in-use { background: #dbeafe; color: #1e40af; }
        .status-damaged { background: #fef3c7; color: #92400e; }
        .status-missing { background: #fee2e2; color: #991b1b; }
        .status-under-repair { background: #e0e7ff; color: #3730a3; }
        .status-disposed { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../includes/custodian_nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-layer-group text-blue-600"></i>
                Manage Individual Units
            </h1>
            <p class="text-gray-600">View and update status of individual asset units</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-600">Total Units</div>
                <div class="text-2xl font-bold text-gray-800"><?= $stats['total_units'] ?? 0 ?></div>
            </div>
            <div class="bg-green-50 rounded-lg shadow p-4">
                <div class="text-sm text-green-600">Available</div>
                <div class="text-2xl font-bold text-green-700"><?= $stats['available'] ?? 0 ?></div>
            </div>
            <div class="bg-blue-50 rounded-lg shadow p-4">
                <div class="text-sm text-blue-600">In Use</div>
                <div class="text-2xl font-bold text-blue-700"><?= $stats['in_use'] ?? 0 ?></div>
            </div>
            <div class="bg-yellow-50 rounded-lg shadow p-4">
                <div class="text-sm text-yellow-600">Damaged</div>
                <div class="text-2xl font-bold text-yellow-700"><?= $stats['damaged'] ?? 0 ?></div>
            </div>
            <div class="bg-red-50 rounded-lg shadow p-4">
                <div class="text-sm text-red-600">Missing</div>
                <div class="text-2xl font-bold text-red-700"><?= $stats['missing'] ?? 0 ?></div>
            </div>
            <div class="bg-purple-50 rounded-lg shadow p-4">
                <div class="text-sm text-purple-600">Under Repair</div>
                <div class="text-2xl font-bold text-purple-700"><?= $stats['under_repair'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Assets List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Assets with Individual Tracking</h2>
            </div>

            <?php if (empty($assets)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-6xl mb-4 text-gray-300"></i>
                    <p class="text-lg">No assets with individual tracking found.</p>
                    <p class="text-sm mt-2">Add assets with quantity > 1 to enable individual tracking.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($assets as $asset): ?>
                        <div class="p-6 hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($asset['asset_name']) ?></h3>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($asset['category_name']) ?></p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <?= $asset['unit_count'] ?> units created
                                    </p>
                                </div>
                                <button onclick="viewUnits(<?= $asset['id'] ?>, '<?= htmlspecialchars($asset['asset_name'], ENT_QUOTES) ?>')"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                                    <i class="fas fa-list mr-2"></i>View Units
                                </button>
                            </div>

                            <!-- Status Summary -->
                            <div class="flex flex-wrap gap-2">
                                <?php if ($asset['available_count'] > 0): ?>
                                    <span class="status-badge status-available">
                                        <?= $asset['available_count'] ?> Available
                                    </span>
                                <?php endif; ?>
                                <?php if ($asset['in_use_count'] > 0): ?>
                                    <span class="status-badge status-in-use">
                                        <?= $asset['in_use_count'] ?> In Use
                                    </span>
                                <?php endif; ?>
                                <?php if ($asset['damaged_count'] > 0): ?>
                                    <span class="status-badge status-damaged">
                                        <?= $asset['damaged_count'] ?> Damaged
                                    </span>
                                <?php endif; ?>
                                <?php if ($asset['missing_count'] > 0): ?>
                                    <span class="status-badge status-missing">
                                        <?= $asset['missing_count'] ?> Missing
                                    </span>
                                <?php endif; ?>
                                <?php if ($asset['repair_count'] > 0): ?>
                                    <span class="status-badge status-under-repair">
                                        <?= $asset['repair_count'] ?> Under Repair
                                    </span>
                                <?php endif; ?>
                                <?php if ($asset['disposed_count'] > 0): ?>
                                    <span class="status-badge status-disposed">
                                        <?= $asset['disposed_count'] ?> Disposed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Units Modal -->
    <div id="unitsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">Asset Units</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[70vh]" id="unitsContainer">
                    <div class="flex items-center justify-center py-12">
                        <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentAssetId = null;

        async function viewUnits(assetId, assetName) {
            currentAssetId = assetId;
            document.getElementById('modalTitle').textContent = assetName + ' - Individual Units';
            document.getElementById('unitsModal').classList.remove('hidden');

            // Load units
            try {
                const response = await fetch(`../api/asset_units.php?action=get_units_for_asset&asset_id=${assetId}`);
                const data = await response.json();

                if (data.success && data.units) {
                    displayUnits(data.units);
                } else {
                    document.getElementById('unitsContainer').innerHTML = `
                        <div class="text-center text-red-600 py-8">
                            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                            <p>${data.message || 'Failed to load units'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('unitsContainer').innerHTML = `
                    <div class="text-center text-red-600 py-8">
                        <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                        <p>Error loading units</p>
                    </div>
                `;
            }
        }

        function displayUnits(units) {
            const container = document.getElementById('unitsContainer');

            if (units.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 py-8">No units found</p>';
                return;
            }

            let html = '<div class="space-y-3">';

            units.forEach(unit => {
                const statusClass = getStatusClass(unit.unit_status);
                const conditionColor = getConditionColor(unit.condition_rating);

                html += `
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="font-mono text-lg font-semibold text-gray-800">${unit.unit_code || 'N/A'}</h4>
                                    <span class="status-badge ${statusClass}">${unit.unit_status}</span>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${conditionColor}">
                                        ${unit.condition_rating || 'N/A'}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-1">
                                    <i class="fas fa-barcode text-gray-400 mr-2"></i>
                                    Serial: ${unit.unit_serial_number || 'N/A'}
                                </p>
                                ${unit.assigned_office_name ? `
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-building text-gray-400 mr-2"></i>
                                        Assigned to: ${unit.assigned_office_name}
                                    </p>
                                ` : ''}
                                ${unit.notes ? `
                                    <p class="text-sm text-gray-500 mt-2">
                                        <i class="fas fa-note-sticky text-gray-400 mr-2"></i>
                                        ${unit.notes}
                                    </p>
                                ` : ''}
                            </div>
                            <div class="flex gap-2">
                                <button onclick='editUnit(${JSON.stringify(unit)})'
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="viewHistory(${unit.id})"
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-sm transition">
                                    <i class="fas fa-history"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function getStatusClass(status) {
            const statusMap = {
                'Available': 'status-available',
                'In Use': 'status-in-use',
                'Damaged': 'status-damaged',
                'Missing': 'status-missing',
                'Under Repair': 'status-under-repair',
                'Disposed': 'status-disposed'
            };
            return statusMap[status] || 'bg-gray-100 text-gray-600';
        }

        function getConditionColor(condition) {
            const colorMap = {
                'Excellent': 'bg-green-100 text-green-800',
                'Good': 'bg-blue-100 text-blue-800',
                'Fair': 'bg-yellow-100 text-yellow-800',
                'Poor': 'bg-orange-100 text-orange-800',
                'Non-functional': 'bg-red-100 text-red-800'
            };
            return colorMap[condition] || 'bg-gray-100 text-gray-600';
        }

        async function editUnit(unit) {
            const { value: formValues } = await Swal.fire({
                title: 'Update Unit',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Code</label>
                            <input type="text" value="${unit.unit_code}" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="unit-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="Available" ${unit.unit_status === 'Available' ? 'selected' : ''}>Available</option>
                                <option value="In Use" ${unit.unit_status === 'In Use' ? 'selected' : ''}>In Use</option>
                                <option value="Damaged" ${unit.unit_status === 'Damaged' ? 'selected' : ''}>Damaged</option>
                                <option value="Missing" ${unit.unit_status === 'Missing' ? 'selected' : ''}>Missing</option>
                                <option value="Under Repair" ${unit.unit_status === 'Under Repair' ? 'selected' : ''}>Under Repair</option>
                                <option value="Disposed" ${unit.unit_status === 'Disposed' ? 'selected' : ''}>Disposed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Condition Rating</label>
                            <select id="condition-rating" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="Excellent" ${unit.condition_rating === 'Excellent' ? 'selected' : ''}>Excellent</option>
                                <option value="Good" ${unit.condition_rating === 'Good' ? 'selected' : ''}>Good</option>
                                <option value="Fair" ${unit.condition_rating === 'Fair' ? 'selected' : ''}>Fair</option>
                                <option value="Poor" ${unit.condition_rating === 'Poor' ? 'selected' : ''}>Poor</option>
                                <option value="Non-functional" ${unit.condition_rating === 'Non-functional' ? 'selected' : ''}>Non-functional</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea id="unit-notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">${unit.notes || ''}</textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Update',
                confirmButtonColor: '#2563eb',
                preConfirm: () => {
                    return {
                        unit_status: document.getElementById('unit-status').value,
                        condition_rating: document.getElementById('condition-rating').value,
                        notes: document.getElementById('unit-notes').value
                    };
                },
                width: '500px'
            });

            if (formValues) {
                await updateUnit(unit.id, formValues);
            }
        }

        async function updateUnit(unitId, data) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_unit');
                formData.append('unit_id', unitId);
                formData.append('unit_status', data.unit_status);
                formData.append('condition_rating', data.condition_rating);
                formData.append('notes', data.notes);

                const response = await fetch('../api/asset_units.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire('Success!', 'Unit updated successfully', 'success');
                    // Reload units
                    viewUnits(currentAssetId, '');
                } else {
                    Swal.fire('Error!', result.message || 'Failed to update unit', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error!', 'Failed to update unit', 'error');
            }
        }

        async function viewHistory(unitId) {
            try {
                const response = await fetch(`../api/asset_units.php?action=get_unit_history&unit_id=${unitId}`);
                const data = await response.json();

                if (data.success && data.history) {
                    let html = '<div class="text-left space-y-3">';

                    if (data.history.length === 0) {
                        html += '<p class="text-gray-500 text-center py-4">No history found</p>';
                    } else {
                        data.history.forEach(h => {
                            html += `
                                <div class="border-l-4 border-blue-500 pl-4 py-2">
                                    <p class="font-semibold text-gray-800">${h.action}</p>
                                    ${h.old_value ? `<p class="text-sm text-gray-600">From: ${h.old_value}</p>` : ''}
                                    ${h.new_value ? `<p class="text-sm text-gray-600">To: ${h.new_value}</p>` : ''}
                                    ${h.description ? `<p class="text-sm text-gray-500">${h.description}</p>` : ''}
                                    <p class="text-xs text-gray-400 mt-1">
                                        ${h.performed_by_name || 'System'} - ${h.created_at}
                                    </p>
                                </div>
                            `;
                        });
                    }

                    html += '</div>';

                    Swal.fire({
                        title: 'Unit History',
                        html: html,
                        width: '600px',
                        confirmButtonText: 'Close'
                    });
                } else {
                    Swal.fire('Error!', data.message || 'Failed to load history', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error!', 'Failed to load history', 'error');
            }
        }

        function closeModal() {
            document.getElementById('unitsModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('unitsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
