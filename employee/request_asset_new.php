<?php
/**
 * Dual-Flow Asset Request Page
 * Employee can choose to request from Office or Custodian
 */

require_once '../config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$role = strtolower($user['role'] ?? '');

if ($role !== 'employee') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This page is for employees only.';
    exit;
}

$userId = $_SESSION['user_id'];
$campusId = $user['campus_id'];

// Get all offices for this campus
$officesStmt = $pdo->prepare("
    SELECT id, office_name, section_code
    FROM offices
    WHERE campus_id = ?
    ORDER BY office_name ASC
");
$officesStmt->execute([$campusId]);
$offices = $officesStmt->fetchAll();

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Asset - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <style>
        .source-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .source-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .source-card.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .progress-step {
            position: relative;
        }
        .progress-step::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            top: 50%;
            left: 50%;
            transform: translateY(-50%);
            z-index: -1;
        }
        .progress-step:last-child::after {
            display: none;
        }
        .progress-step.completed .progress-circle {
            background: #10b981;
            color: white;
        }
        .progress-step.active .progress-circle {
            background: #3b82f6;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Employee Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-home w-6"></i><span>Dashboard</span>
                </a>
                <a href="request_asset_new.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 bg-gray-700">
                    <i class="fas fa-plus-circle w-6"></i><span>Request Asset</span>
                </a>
                <a href="my_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-paper-plane w-6"></i><span>My Requests</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Request Asset</h1>
                    <p class="text-sm text-gray-600 mt-1">Submit a request to borrow an asset</p>
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

            <!-- Main scrollable content -->
            <main class="flex-1 overflow-y-auto p-6">
        <!-- Progress Bar -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="progress-step active flex-1">
                    <div class="flex flex-col items-center">
                        <div class="progress-circle w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold mb-2">
                            1
                        </div>
                        <span class="text-sm font-medium text-gray-900">Choose Source</span>
                    </div>
                </div>
                <div class="progress-step flex-1" id="progress-step-2">
                    <div class="flex flex-col items-center">
                        <div class="progress-circle w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold mb-2">
                            2
                        </div>
                        <span class="text-sm font-medium text-gray-600">Select Asset</span>
                    </div>
                </div>
                <div class="progress-step flex-1" id="progress-step-3">
                    <div class="flex flex-col items-center">
                        <div class="progress-circle w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold mb-2">
                            3
                        </div>
                        <span class="text-sm font-medium text-gray-600">Details</span>
                    </div>
                </div>
                <div class="progress-step flex-1" id="progress-step-4">
                    <div class="flex flex-col items-center">
                        <div class="progress-circle w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold mb-2">
                            4
                        </div>
                        <span class="text-sm font-medium text-gray-600">Review</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Choose Source -->
        <div id="step-1" class="step active">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Where would you like to request from?</h2>
                <p class="text-gray-600 mb-6">Choose the source for your asset request. Each has a different approval process.</p>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Office Request Card -->
                    <div class="source-card border-2 border-gray-200 rounded-lg p-6" onclick="selectSource('office')">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-building text-blue-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Request from Office/Department</h3>
                                <p class="text-sm text-gray-600 mb-3">Request assets from a specific office or department (MIS, HM Kitchen, Criminology, etc.)</p>
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-green-600">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Fast approval (Office Head only)</span>
                                    </div>
                                    <div class="flex items-center text-sm text-green-600">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Direct from department inventory</span>
                                    </div>
                                    <div class="flex items-center text-sm text-green-600">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Quick turnaround time</span>
                                    </div>
                                </div>
                                <button class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                                    Select Office Request
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Custodian Request Card -->
                    <div class="source-card border-2 border-gray-200 rounded-lg p-6" onclick="selectSource('custodian')">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-warehouse text-purple-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Request from Custodian (Central)</h3>
                                <p class="text-sm text-gray-600 mb-3">Request assets from the central inventory managed by the custodian</p>
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm text-blue-600">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Full approval process (Custodian + Admin)</span>
                                    </div>
                                    <div class="flex items-center text-sm text-blue-600">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Central inventory & shared assets</span>
                                    </div>
                                    <div class="flex items-center text-sm text-blue-600">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span>Complete audit trail</span>
                                    </div>
                                </div>
                                <button class="mt-4 w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium">
                                    Select Custodian Request
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2a: Select Office (for office requests) -->
        <div id="step-2a" class="step">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Select Office/Department</h2>
                <p class="text-gray-600 mb-6">Choose which office you want to request from</p>

                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <?php foreach ($offices as $office): ?>
                    <div class="office-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all"
                         onclick="selectOffice(<?= $office['id'] ?>, '<?= htmlspecialchars($office['office_name'], ENT_QUOTES) ?>')">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-building text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($office['office_name']) ?></h3>
                                <?php if (!empty($office['section_code'])): ?>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($office['section_code']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-between">
                    <button onclick="goToStep(1)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Back
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2b: Browse Assets -->
        <div id="step-2b" class="step">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Select Asset</h2>
                <p class="text-gray-600 mb-6">Choose the asset you want to request</p>

                <!-- Search and Filter -->
                <div class="mb-6">
                    <input type="text" id="assetSearch" placeholder="Search assets..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Loading -->
                <div id="assetsLoading" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    <p class="mt-2 text-gray-600">Loading assets...</p>
                </div>

                <!-- Assets List -->
                <div id="assetsList" class="hidden grid md:grid-cols-2 gap-4 mb-6"></div>

                <!-- No Assets -->
                <div id="noAssets" class="hidden text-center py-8">
                    <i class="fas fa-inbox text-4xl text-gray-400"></i>
                    <p class="mt-2 text-gray-600">No assets available from this source</p>
                </div>

                <div class="flex justify-between">
                    <button onclick="goBack()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Back
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Request Details -->
        <div id="step-3" class="step">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Request Details</h2>
                <p class="text-gray-600 mb-6">Provide details about your request</p>

                <form id="requestDetailsForm" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selected Asset</label>
                        <div id="selectedAssetDisplay" class="p-4 bg-gray-50 rounded-lg"></div>
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                            Purpose of Use <span class="text-red-500">*</span>
                        </label>
                        <textarea id="purpose" name="purpose" rows="4" required
                                  placeholder="Describe why you need this asset..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div>
                        <label for="expected_return_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Expected Return Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="expected_return_date" name="expected_return_date" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-between">
                        <button type="button" onclick="goToStep(2)"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <button type="button" onclick="reviewRequest()"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                            Continue <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Step 4: Review & Submit -->
        <div id="step-4" class="step">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Review Your Request</h2>
                <p class="text-gray-600 mb-6">Please review your request before submitting</p>

                <div id="reviewContent" class="space-y-6 mb-6"></div>

                <div class="flex justify-between">
                    <button onclick="goToStep(3)"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Back
                    </button>
                    <button onclick="submitRequest()"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Request
                    </button>
                </div>
            </div>
        </div>
            </main>
        </div>
    </div>

    <script>
    // State management
    const state = {
        currentStep: 1,
        requestSource: null,      // 'office' or 'custodian'
        targetOfficeId: null,      // For office requests
        targetOfficeName: null,
        selectedAsset: null,
        assets: [],
        formData: {}
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const campusId = <?= json_encode($campusId) ?>;

    // Set minimum date to tomorrow
    document.addEventListener('DOMContentLoaded', function() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const minDate = tomorrow.toISOString().split('T')[0];
        document.getElementById('expected_return_date').setAttribute('min', minDate);
    });

    function selectSource(source) {
        state.requestSource = source;

        if (source === 'office') {
            // Go to office selection
            goToStep('2a');
        } else {
            // Go directly to asset selection for custodian
            goToStep('2b');
            loadAssets();
        }
    }

    function selectOffice(officeId, officeName) {
        state.targetOfficeId = officeId;
        state.targetOfficeName = officeName;
        goToStep('2b');
        loadAssets();
    }

    async function loadAssets() {
        document.getElementById('assetsLoading').classList.remove('hidden');
        document.getElementById('assetsList').classList.add('hidden');
        document.getElementById('noAssets').classList.add('hidden');

        try {
            const url = state.requestSource === 'office'
                ? `../api/assets.php?action=get_office_assets&office_id=${state.targetOfficeId}`
                : `../api/assets.php?action=get_custodian_assets&campus_id=${campusId}`;

            const response = await fetch(url);
            const data = await response.json();

            document.getElementById('assetsLoading').classList.add('hidden');

            if (data.success && data.assets && data.assets.length > 0) {
                state.assets = data.assets;
                displayAssets(data.assets);
            } else {
                document.getElementById('noAssets').classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error loading assets:', error);
            document.getElementById('assetsLoading').classList.add('hidden');
            Swal.fire('Error', 'Failed to load assets', 'error');
        }
    }

    function displayAssets(assets) {
        const container = document.getElementById('assetsList');
        container.innerHTML = '';
        container.classList.remove('hidden');

        assets.forEach(asset => {
            const card = `
                <div class="border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all"
                     onclick='selectAsset(${JSON.stringify(asset)})'>
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-box text-gray-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">${escapeHtml(asset.asset_name)}</h3>
                            <p class="text-sm text-gray-600">${escapeHtml(asset.category_name || 'No category')}</p>
                            <p class="text-xs text-gray-500 mt-1">Available: ${asset.quantity || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML += card;
        });
    }

    function selectAsset(asset) {
        state.selectedAsset = asset;
        goToStep(3);

        // Display selected asset
        document.getElementById('selectedAssetDisplay').innerHTML = `
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-box text-blue-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">${escapeHtml(asset.asset_name)}</h3>
                    <p class="text-sm text-gray-600">${escapeHtml(asset.category_name || 'No category')}</p>
                </div>
            </div>
        `;
    }

    function reviewRequest() {
        // Validate form
        const form = document.getElementById('requestDetailsForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Collect form data
        state.formData = {
            quantity: document.getElementById('quantity').value,
            purpose: document.getElementById('purpose').value,
            expected_return_date: document.getElementById('expected_return_date').value
        };

        // Display review
        const approvalFlow = state.requestSource === 'office'
            ? `<span class="text-blue-600">Office Head (${state.targetOfficeName})</span>`
            : `<span class="text-purple-600">Custodian → Admin</span>`;

        document.getElementById('reviewContent').innerHTML = `
            <div class="border-l-4 border-blue-500 bg-blue-50 p-4 rounded">
                <h3 class="font-semibold text-gray-900 mb-2">Request Source</h3>
                <p class="text-gray-700">${state.requestSource === 'office' ? 'Office/Department' : 'Custodian (Central)'}</p>
                ${state.requestSource === 'office' ? `<p class="text-sm text-gray-600">Office: ${state.targetOfficeName}</p>` : ''}
            </div>

            <div class="border-l-4 border-green-500 bg-green-50 p-4 rounded">
                <h3 class="font-semibold text-gray-900 mb-2">Selected Asset</h3>
                <p class="text-gray-700 font-semibold">${escapeHtml(state.selectedAsset.asset_name)}</p>
                <p class="text-sm text-gray-600">${escapeHtml(state.selectedAsset.category_name || '')}</p>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Quantity</p>
                    <p class="text-lg font-semibold text-gray-900">${state.formData.quantity}</p>
                </div>
                <div class="border border-gray-200 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Expected Return</p>
                    <p class="text-lg font-semibold text-gray-900">${new Date(state.formData.expected_return_date).toLocaleDateString()}</p>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-2">Purpose</p>
                <p class="text-gray-900">${escapeHtml(state.formData.purpose)}</p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-semibold text-yellow-900 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Approval Process
                </h3>
                <p class="text-sm text-yellow-800">
                    Your request will be reviewed by: ${approvalFlow}
                </p>
            </div>
        `;

        goToStep(4);
    }

    async function submitRequest() {
        try {
            const requestData = {
                action: 'create_request',
                asset_id: state.selectedAsset.id,
                quantity: state.formData.quantity,
                purpose: state.formData.purpose,
                expected_return_date: state.formData.expected_return_date,
                request_source: state.requestSource,
                target_office_id: state.targetOfficeId,
                csrf_token: csrfToken,
                campus_id: campusId
            };

            const response = await fetch('../api/requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(requestData)
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Request Submitted!',
                    text: data.message || 'Your request has been submitted and is pending approval.',
                    confirmButtonText: 'View My Requests'
                }).then(() => {
                    window.location.href = 'my_requests.php';
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to submit request', 'error');
            }
        } catch (error) {
            console.error('Error submitting request:', error);
            Swal.fire('Error', 'Failed to submit request. Please try again.', 'error');
        }
    }

    function goToStep(step) {
        // Hide all steps
        document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));

        // Show target step
        if (step === '2a') {
            document.getElementById('step-2a').classList.add('active');
            updateProgress(2);
        } else if (step === '2b') {
            document.getElementById('step-2b').classList.add('active');
            updateProgress(2);
        } else {
            document.getElementById(`step-${step}`).classList.add('active');
            updateProgress(step);
        }

        state.currentStep = step;
        window.scrollTo(0, 0);
    }

    function goBack() {
        if (state.requestSource === 'office') {
            goToStep('2a');
        } else {
            goToStep(1);
        }
    }

    function updateProgress(step) {
        // Remove active/completed from all
        document.querySelectorAll('.progress-step').forEach(el => {
            el.classList.remove('active', 'completed');
        });

        // Mark completed steps
        for (let i = 1; i < step; i++) {
            document.getElementById(`progress-step-${i}`)?.classList.add('completed');
        }

        // Mark current step
        document.getElementById(`progress-step-${step}`)?.classList.add('active');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Search functionality
    document.getElementById('assetSearch')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const filteredAssets = state.assets.filter(asset =>
            asset.asset_name.toLowerCase().includes(searchTerm) ||
            (asset.category_name && asset.category_name.toLowerCase().includes(searchTerm))
        );
        displayAssets(filteredAssets);
    });
    </script>
</body>
</html>
