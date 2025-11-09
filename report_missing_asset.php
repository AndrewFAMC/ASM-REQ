<?php
/**
 * Report Missing Asset Page
 * Allows employees and custodians to report missing assets immediately
 */

require_once __DIR__ . '/config.php';

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getUserInfo();
$campusId = $user['campus_id'];

// Get asset_id from URL if provided
$preSelectedAssetId = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : null;
$preSelectedAssetName = '';

// If asset is pre-selected, get its details
if ($preSelectedAssetId) {
    try {
        $stmt = $pdo->prepare("
            SELECT asset_name, COALESCE(barcode, serial_number, CONCAT('ID-', id)) as asset_code
            FROM assets
            WHERE id = ?
        ");
        $stmt->execute([$preSelectedAssetId]);
        $assetInfo = $stmt->fetch();
        if ($assetInfo) {
            $preSelectedAssetName = $assetInfo['asset_name'] . ' (' . $assetInfo['asset_code'] . ')';
        }
    } catch (PDOException $e) {
        error_log("Error fetching asset info: " . $e->getMessage());
    }
}

// Get all assets for dropdown (excluding already missing ones)
$assets = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.asset_name,
            COALESCE(a.barcode, a.serial_number, CONCAT('ID-', a.id)) as asset_code,
            c.category_name as category,
            a.location,
            a.status
        FROM assets a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.campus_id = ?
        AND a.status != 'Missing'
        ORDER BY a.asset_name ASC
    ");
    $stmt->execute([$campusId]);
    $assets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching assets: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Missing Asset - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="<?= hasRole('custodian') ? 'custodian/dashboard.php' : 'employee/dashboard.php' ?>"
                       class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-700">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-red-50 border-l-4 border-red-500 p-6 mb-6 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-red-900">Report Missing Asset</h1>
                    <p class="text-red-700 mt-1">
                        Use this form to immediately report a missing asset. All relevant parties will be notified.
                    </p>
                </div>
            </div>
        </div>

        <!-- Report Form -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <form id="reportMissingForm" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <!-- Asset Selection -->
                <div>
                    <label for="asset_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-box text-gray-500 mr-2"></i>
                        Which asset is missing? *
                    </label>
                    <select id="asset_id" name="asset_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        <option value="">-- Select Asset --</option>
                        <?php foreach ($assets as $asset): ?>
                            <option value="<?= $asset['id'] ?>"
                                    data-code="<?= htmlspecialchars($asset['asset_code']) ?>"
                                    data-category="<?= htmlspecialchars($asset['category']) ?>"
                                    data-location="<?= htmlspecialchars($asset['location']) ?>"
                                    <?= $preSelectedAssetId == $asset['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($asset['asset_name']) ?>
                                (<?= htmlspecialchars($asset['asset_code']) ?>)
                                - <?= htmlspecialchars($asset['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Asset Info Display -->
                <div id="assetInfoBox" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-2">Asset Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Asset Code:</span>
                            <span id="displayAssetCode" class="font-medium text-gray-900 ml-2">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Category:</span>
                            <span id="displayCategory" class="font-medium text-gray-900 ml-2">-</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-600">Last Known Location:</span>
                            <span id="displayLocation" class="font-medium text-gray-900 ml-2">-</span>
                        </div>
                    </div>
                </div>

                <!-- Last Seen Date -->
                <div>
                    <label for="last_seen_date" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar text-gray-500 mr-2"></i>
                        When did you last see this asset? *
                    </label>
                    <input type="date" id="last_seen_date" name="last_seen_date" required
                           max="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <!-- Last Known Location -->
                <div>
                    <label for="last_known_location" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-map-marker-alt text-gray-500 mr-2"></i>
                        Last Known Location *
                    </label>
                    <input type="text" id="last_known_location" name="last_known_location" required
                           placeholder="e.g., Room 205, Library, Main Office"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <!-- Last Known Borrower -->
                <div>
                    <label for="last_known_borrower" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-gray-500 mr-2"></i>
                        Last Known Borrower/User (if known)
                    </label>
                    <input type="text" id="last_known_borrower" name="last_known_borrower"
                           placeholder="e.g., Richard Santos"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <!-- Contact Information -->
                <div>
                    <label for="last_known_borrower_contact" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone text-gray-500 mr-2"></i>
                        Contact Number of Last Known Borrower (if available)
                    </label>
                    <input type="text" id="last_known_borrower_contact" name="last_known_borrower_contact"
                           placeholder="e.g., 09XX-XXX-XXXX or email@example.com"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <!-- Department -->
                <div>
                    <label for="responsible_department" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-building text-gray-500 mr-2"></i>
                        Department/Office Last Associated With
                    </label>
                    <input type="text" id="responsible_department" name="responsible_department"
                           placeholder="e.g., IT Department, Finance Office"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left text-gray-500 mr-2"></i>
                        Detailed Description *
                    </label>
                    <textarea id="description" name="description" rows="5" required
                              placeholder="Please provide as much detail as possible:&#10;- What happened?&#10;- How did you discover the asset was missing?&#10;- Have you checked any possible locations?&#10;- Any other relevant information?"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Be as detailed as possible to help with the investigation</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <a href="<?= hasRole('custodian') ? 'custodian/dashboard.php' : 'employee/dashboard.php' ?>"
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Submit Missing Asset Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="font-semibold text-yellow-900 mb-2">
                <i class="fas fa-info-circle mr-2"></i>What happens after you submit?
            </h3>
            <ul class="text-sm text-yellow-800 space-y-1">
                <li><i class="fas fa-check text-yellow-600 mr-2"></i>The asset will be marked as "Missing" in the system</li>
                <li><i class="fas fa-check text-yellow-600 mr-2"></i>Custodians and administrators will be immediately notified by email</li>
                <li><i class="fas fa-check text-yellow-600 mr-2"></i>An investigation will be initiated to locate the asset</li>
                <li><i class="fas fa-check text-yellow-600 mr-2"></i>You'll be contacted if more information is needed</li>
            </ul>
        </div>
    </div>

    <script>
        // Auto-fill location when asset is selected
        document.getElementById('asset_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const assetInfoBox = document.getElementById('assetInfoBox');

            if (this.value) {
                // Show asset info
                assetInfoBox.classList.remove('hidden');
                document.getElementById('displayAssetCode').textContent = selectedOption.dataset.code || '-';
                document.getElementById('displayCategory').textContent = selectedOption.dataset.category || '-';
                document.getElementById('displayLocation').textContent = selectedOption.dataset.location || '-';

                // Pre-fill location field
                document.getElementById('last_known_location').value = selectedOption.dataset.location || '';
            } else {
                assetInfoBox.classList.add('hidden');
            }
        });

        // Trigger change event if asset is pre-selected
        <?php if ($preSelectedAssetId): ?>
        document.getElementById('asset_id').dispatchEvent(new Event('change'));
        <?php endif; ?>

        // Form submission
        document.getElementById('reportMissingForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';

            try {
                // Add a timeout wrapper for the fetch request
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 10000); // 10 second timeout

                const response = await fetch('api/report_missing_asset.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData,
                    signal: controller.signal
                });

                clearTimeout(timeout);

                // Try to get the JSON response, but handle if connection was closed
                let result;
                try {
                    const text = await response.text();
                    result = text ? JSON.parse(text) : null;
                } catch (parseError) {
                    // If we can't parse the response, check if status was OK
                    if (response.ok) {
                        // Response was sent but connection closed - treat as success
                        result = { success: true, report_id: 'N/A' };
                    } else {
                        throw new Error('Failed to parse server response');
                    }
                }

                if (result && result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Report Submitted Successfully',
                        html: `
                            <p class="mb-2">Your missing asset report has been submitted.</p>
                            ${result.report_id !== 'N/A' ? `<p class="text-sm text-gray-600">Report ID: #${result.report_id}</p>` : ''}
                            <p class="text-sm text-gray-600">Custodians and administrators will be notified by email.</p>
                        `,
                        confirmButtonColor: '#dc2626'
                    });

                    // Redirect based on role
                    <?php if (hasRole('custodian')): ?>
                    window.location.href = 'custodian/missing_assets.php';
                    <?php else: ?>
                    window.location.href = 'employee/dashboard.php';
                    <?php endif; ?>
                } else {
                    throw new Error(result?.message || 'Failed to submit report');
                }
            } catch (error) {
                // Check if it's a timeout/abort error
                if (error.name === 'AbortError') {
                    // Timeout occurred - but request might have succeeded
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Request Taking Longer Than Expected',
                        html: `
                            <p class="mb-2">Your report is being processed.</p>
                            <p class="text-sm text-gray-600">Please check the Missing Assets page to confirm.</p>
                        `,
                        confirmButtonColor: '#dc2626'
                    });
                    <?php if (hasRole('custodian')): ?>
                    window.location.href = 'custodian/missing_assets.php';
                    <?php else: ?>
                    window.location.href = 'employee/dashboard.php';
                    <?php endif; ?>
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Failed',
                        text: error.message,
                        confirmButtonColor: '#dc2626'
                    });
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Submit Missing Asset Report';
                }
            }
        });

        // Set default last seen date to today
        document.getElementById('last_seen_date').value = '<?= date('Y-m-d') ?>';
    </script>
</body>
</html>
