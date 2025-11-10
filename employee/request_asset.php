<?php
/**
 * Asset Request Submission Page
 * Creates new asset requests that go through approval workflow
 */

require_once '../config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$userId = $_SESSION['user_id'];
$campusId = $user['campus_id'];

// Get available assets from user's campus
// Support both 'Available' and 'Active' status values
$stmt = $pdo->prepare("
    SELECT a.*, c.category_name
    FROM assets a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.campus_id = ?
    AND a.status IN ('Available', 'Active')
    ORDER BY a.asset_name
");
$stmt->execute([$campusId]);
$availableAssets = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        try {
            $assetId = (int)$_POST['asset_id'];
            $quantity = (int)$_POST['quantity'];
            $purpose = trim($_POST['purpose']);
            $expectedReturnDate = $_POST['expected_return_date'];

            // Validation
            if (empty($assetId) || empty($quantity) || empty($purpose) || empty($expectedReturnDate)) {
                throw new Exception("All fields are required");
            }

            if ($quantity < 1) {
                throw new Exception("Quantity must be at least 1");
            }

            if (strtotime($expectedReturnDate) <= time()) {
                throw new Exception("Expected return date must be in the future");
            }

            // Verify asset exists and is available
            $stmt = $pdo->prepare("SELECT * FROM assets WHERE id = ? AND campus_id = ? AND status IN ('Available', 'Active')");
            $stmt->execute([$assetId, $campusId]);
            $asset = $stmt->fetch();

            if (!$asset) {
                throw new Exception("Asset not found or not available");
            }

            // Create the request
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO asset_requests
                (requester_id, asset_id, campus_id, quantity, purpose, expected_return_date, status, request_date)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([
                $userId,
                $assetId,
                $campusId,
                $quantity,
                $purpose,
                $expectedReturnDate
            ]);

            $requestId = $pdo->lastInsertId();

            // Get ALL custodians for this campus
            $custodianStmt = $pdo->prepare("
                SELECT id, full_name, email FROM users
                WHERE role = 'custodian'
                AND campus_id = ?
                AND is_active = TRUE
                ORDER BY id ASC
            ");
            $custodianStmt->execute([$campusId]);
            $custodians = $custodianStmt->fetchAll();

            if (empty($custodians)) {
                throw new Exception("No custodian found for your campus. Please contact the administrator.");
            }

            // Create notification for custodian (email will be sent automatically by createNotification)
            $emailErrors = [];
            foreach ($custodians as $custodian) {
                try {
                    // Create in-app notification (this will automatically send email via config.php)
                    $notificationCreated = createNotification(
                        $pdo,
                        $custodian['id'],
                        NOTIFICATION_APPROVAL_REQUEST,
                        'New Asset Request',
                        "{$user['full_name']} has requested {$asset['asset_name']} (Qty: {$quantity}). Please review and approve.",
                        [
                            'related_type' => 'request',
                            'related_id' => $requestId,
                            'priority' => 'high',
                            'action_url' => '/AMS-REQ/custodian/approve_requests.php',
                            // Email template data
                            'requester_name' => $user['full_name'],
                            'asset_name' => $asset['asset_name'],
                            'quantity' => $quantity,
                            'request_id' => $requestId,
                            'role' => 'custodian'
                        ]
                    );

                    if (!$notificationCreated) {
                        throw new Exception("Failed to create notification");
                    }
                } catch (Exception $e) {
                    $emailErrors[] = "Failed to notify {$custodian['full_name']} ({$custodian['email']}): " . $e->getMessage();
                }
            }

            // If ALL notifications failed, throw exception and rollback
            if (count($emailErrors) === count($custodians)) {
                throw new Exception("Failed to send notification to any custodian: " . implode("; ", $emailErrors));
            }

            // If SOME notifications failed, log warning but continue
            if (!empty($emailErrors)) {
                error_log("Some custodian notifications failed: " . implode("; ", $emailErrors));
            }

            // Log activity
            logActivity($pdo, $assetId, 'REQUEST_SUBMITTED',
                "Asset request submitted by {$user['full_name']} for {$quantity} unit(s)");

            $pdo->commit();

            $success = "Request submitted successfully! An email notification has been sent to the custodian.";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Asset - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-step { display: none; }
        .form-step.active { display: block; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <!-- Header -->
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Request Asset</h1>
                    <p class="text-gray-600 mt-1">Submit a request to borrow an asset</p>
                </div>
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

         <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Employee Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <a href="dashboard.php" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-clipboard-list w-6"></i><span>My Requests</span>
                </a>
                <a href="dashboard.php" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-boxes w-6"></i><span>Available Assets</span>
                </a>
                <a href="my_requests.php" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-paper-plane w-6"></i><span>Full Request Details</span>
                </a>
            </nav>
        </div>


        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-600 text-xl mt-0.5 mr-3"></i>
                        <div>
                            <p class="font-semibold text-green-900"><?= htmlspecialchars($success) ?></p>
                            <div class="mt-3 flex space-x-3">
                                <a href="my_requests.php" class="text-green-700 hover:text-green-900 text-sm font-medium">
                                    <i class="fas fa-list mr-1"></i>View My Requests
                                </a>
                                <a href="request_asset.php" class="text-green-700 hover:text-green-900 text-sm font-medium">
                                    <i class="fas fa-plus mr-1"></i>Submit Another Request
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl mt-0.5 mr-3"></i>
                        <div>
                            <p class="font-semibold text-red-900">Error</p>
                            <p class="text-red-800 mt-1"><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Request Form -->
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-8">
                <form method="POST" action="" id="requestForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <!-- Asset Selection -->
                    <div class="mb-6">
                        <label for="asset_id" class="block text-sm font-semibold text-gray-700 mb-2">
                            Select Asset <span class="text-red-600">*</span>
                        </label>
                        <select name="asset_id" id="asset_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateAssetInfo()">
                            <option value="">-- Choose an asset --</option>
                            <?php foreach ($availableAssets as $asset):
                                $assetCode = $asset['asset_code'] ?? $asset['serial_number'] ?? 'ID-' . $asset['id'];
                            ?>
                                <option value="<?= $asset['id'] ?>"
                                        data-name="<?= htmlspecialchars($asset['asset_name']) ?>"
                                        data-code="<?= htmlspecialchars($assetCode) ?>"
                                        data-category="<?= htmlspecialchars($asset['category_name'] ?? 'Uncategorized') ?>"
                                        data-description="<?= htmlspecialchars($asset['description'] ?? '') ?>">
                                    <?= htmlspecialchars($asset['asset_name']) ?> - <?= htmlspecialchars($assetCode) ?>
                                    <?php if (!empty($asset['category_name'])): ?>
                                        (<?= htmlspecialchars($asset['category_name']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Asset Info Display -->
                    <div id="assetInfo" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-blue-900 mb-2">Asset Details</h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-blue-700 font-medium">Code:</span>
                                <span id="infoCode" class="text-blue-900"></span>
                            </div>
                            <div>
                                <span class="text-blue-700 font-medium">Category:</span>
                                <span id="infoCategory" class="text-blue-900"></span>
                            </div>
                        </div>
                        <div id="infoDescription" class="mt-2 text-sm text-blue-800"></div>
                    </div>

                    <!-- Quantity -->
                    <div class="mb-6">
                        <label for="quantity" class="block text-sm font-semibold text-gray-700 mb-2">
                            Quantity <span class="text-red-600">*</span>
                        </label>
                        <input type="number" name="quantity" id="quantity" min="1" value="1" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-1">Number of units you need</p>
                    </div>

                    <!-- Purpose -->
                    <div class="mb-6">
                        <label for="purpose" class="block text-sm font-semibold text-gray-700 mb-2">
                            Purpose / Justification <span class="text-red-600">*</span>
                        </label>
                        <textarea name="purpose" id="purpose" rows="4" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Describe why you need this asset and how it will be used..."></textarea>
                        <p class="text-sm text-gray-500 mt-1">Provide clear justification for your request</p>
                    </div>

                    <!-- Expected Return Date -->
                    <div class="mb-6">
                        <label for="expected_return_date" class="block text-sm font-semibold text-gray-700 mb-2">
                            Expected Return Date <span class="text-red-600">*</span>
                        </label>
                        <input type="date" name="expected_return_date" id="expected_return_date" required
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-1">When do you plan to return the asset?</p>
                    </div>

                    <!-- Important Information -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-yellow-900 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>Important Information
                        </h4>
                        <ul class="text-sm text-yellow-800 space-y-1 ml-6 list-disc">
                            <li>Your request will be reviewed by the custodian first</li>
                            <li>After custodian approval, it needs final approval from admin</li>
                            <li>You will receive notifications at each stage</li>
                            <li>You can track your request status in "My Requests"</li>
                            <li>Please return the asset on or before the expected return date</li>
                        </ul>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-3">
                        <a href="dashboard.php"
                           class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                            Cancel
                        </a>
                        <button type="submit" name="submit_request"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function updateAssetInfo() {
        const select = document.getElementById('asset_id');
        const option = select.options[select.selectedIndex];
        const infoDiv = document.getElementById('assetInfo');

        if (option.value) {
            document.getElementById('infoCode').textContent = option.dataset.code;
            document.getElementById('infoCategory').textContent = option.dataset.category;

            const description = option.dataset.description;
            if (description) {
                document.getElementById('infoDescription').innerHTML =
                    '<span class="text-blue-700 font-medium">Description:</span> ' + description;
            } else {
                document.getElementById('infoDescription').textContent = '';
            }

            infoDiv.classList.remove('hidden');
        } else {
            infoDiv.classList.add('hidden');
        }
    }

    // Form validation
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        const returnDate = document.getElementById('expected_return_date').value;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selected = new Date(returnDate);

        if (selected <= today) {
            e.preventDefault();
            Swal.fire('Invalid Date', 'Expected return date must be in the future', 'error');
            return false;
        }

        const purpose = document.getElementById('purpose').value.trim();
        if (purpose.length < 10) {
            e.preventDefault();
            Swal.fire('Invalid Purpose', 'Please provide a more detailed purpose (at least 10 characters)', 'error');
            return false;
        }
    });

    <?php if (isset($success)): ?>
    // Show success notification
    Swal.fire({
        icon: 'success',
        title: 'Request Submitted!',
        text: 'Your asset request has been submitted for approval.',
        confirmButtonText: 'View My Requests',
        showCancelButton: true,
        cancelButtonText: 'Submit Another'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'my_requests.php';
        } else if (result.isDismissed) {
            window.location.href = 'request_asset.php';
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>
