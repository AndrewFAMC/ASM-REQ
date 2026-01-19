<?php
/**
 * My Requests - Track Asset Request Status
 * Shows user's asset requests with approval progress
 */

require_once '../config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$userId = $_SESSION['user_id'];

// Get user's requests with full details
$stmt = $pdo->prepare("
    SELECT
        ar.*,
        a.asset_name,
        COALESCE(a.serial_number, CONCAT('ID-', a.id)) as asset_code,
        c.campus_name,
        u.full_name as requester_name
    FROM asset_requests ar
    LEFT JOIN assets a ON ar.asset_id = a.id
    LEFT JOIN campuses c ON ar.campus_id = c.id
    LEFT JOIN users u ON ar.requester_id = u.id
    WHERE ar.requester_id = ?
    ORDER BY ar.request_date DESC
");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();

// Get statistics
$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'in_progress' => 0,
    'released' => 0,
    'overdue' => 0
];

foreach ($requests as $request) {
    if ($request['status'] === 'pending' || $request['status'] === 'approved_custodian') {
        $stats['in_progress']++;
    } elseif ($request['status'] === 'approved') {
        $stats['approved']++;
    } elseif ($request['status'] === 'rejected') {
        $stats['rejected']++;
    } elseif ($request['status'] === 'released') {
        $stats['released']++;
        // Check if overdue
        if (strtotime($request['expected_return_date']) < time()) {
            $stats['overdue']++;
        }
    }
}
$stats['pending'] = $stats['in_progress'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .request-card { transition: all 0.2s; }
        .request-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }

        /* Progress Bar Styles */
        .progress-container {
            position: relative;
            padding: 2rem 0;
        }
        .progress-line {
            position: absolute;
            top: 2.5rem;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #E5E7EB;
            z-index: 0;
        }
        .progress-line-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: #10B981;
            transition: width 0.3s;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }
        .progress-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .progress-step-circle {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: white;
            border: 3px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: all 0.3s;
        }
        .progress-step.completed .progress-step-circle {
            background: #10B981;
            border-color: #10B981;
            color: white;
        }
        .progress-step.current .progress-step-circle {
            background: #3B82F6;
            border-color: #3B82F6;
            color: white;
            animation: pulse-ring 2s infinite;
        }
        .progress-step.rejected .progress-step-circle {
            background: #EF4444;
            border-color: #EF4444;
            color: white;
        }
        .progress-step-label {
            margin-top: 0.75rem;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .progress-step.completed .progress-step-label {
            color: #10B981;
        }
        .progress-step.current .progress-step-label {
            color: #3B82F6;
        }
        .progress-step.rejected .progress-step-label {
            color: #EF4444;
        }
        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #4b5563; border-radius: 20px; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->

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
                <a href="my_requests.php" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700 tab-active">
                    <i class="fas fa-paper-plane w-6"></i><span>Full Request Details</span>
                </a>
            </nav>
        </div>


       

        <!-- Main content wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <h1 class="text-xl font-semibold text-gray-800">My Asset Requests</h1>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> My Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <!-- Active Assets Alert -->
            <?php if ($stats['released'] > 0): ?>
                <div class="<?= $stats['overdue'] > 0 ? 'bg-red-50 border-red-400' : 'bg-yellow-50 border-yellow-400' ?> border-l-4 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas <?= $stats['overdue'] > 0 ? 'fa-exclamation-circle text-red-600' : 'fa-exclamation-triangle text-yellow-600' ?> text-3xl"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold <?= $stats['overdue'] > 0 ? 'text-red-900' : 'text-yellow-900' ?>">
                                <?= $stats['overdue'] > 0 ? 'URGENT: Overdue Assets!' : 'You Have Assets to Return' ?>
                            </h3>
                            <p class="mt-1 text-sm <?= $stats['overdue'] > 0 ? 'text-red-700' : 'text-yellow-700' ?>">
                                You currently have <strong><?= $stats['released'] ?> asset(s)</strong> in your possession.
                                <?php if ($stats['overdue'] > 0): ?>
                                    <span class="text-red-900 font-bold"><?= $stats['overdue'] ?> of them are OVERDUE!</span>
                                <?php endif; ?>
                            </p>
                            <p class="mt-2 text-xs <?= $stats['overdue'] > 0 ? 'text-red-600' : 'text-yellow-600' ?>">
                                <i class="fas fa-info-circle"></i> Please return borrowed assets on time to avoid penalties. Visit the custodian office during business hours.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- In Progress -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                            <i class="fas fa-clock text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">In Progress</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['in_progress'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Approved -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Approved</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['approved'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-600">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Rejected</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['rejected'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests List -->
            <?php if (empty($requests)): ?>
                <div class="bg-white rounded-lg shadow-sm py-16 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-4 text-lg text-gray-600">No requests found</p>
                    <p class="mt-2 text-sm text-gray-500">You haven't submitted any asset requests yet</p>
                    <a href="dashboard.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        Browse Assets
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($requests as $request): ?>
                        <?php
                        // Determine progress based on request source
                        $progress = 0;
                        $currentStep = 'submitted';
                        $isRejected = $request['status'] === 'rejected';

                        if ($isRejected) {
                            $currentStep = 'rejected';
                        } elseif ($request['request_source'] === 'office') {
                            // Office Request Flow: Submitted -> Office Review -> Released -> Returned
                            if ($request['status'] === 'office_review') {
                                $progress = 33; // Step 2 of 4
                                $currentStep = 'office_review';
                            } elseif ($request['status'] === 'office_approved' || $request['status'] === 'approved') {
                                $progress = 66; // Step 3 of 4 (approved, ready to release)
                                $currentStep = 'approved';
                            } elseif ($request['status'] === 'released') {
                                $progress = 90;
                                $currentStep = 'released';
                            } elseif ($request['status'] === 'returned') {
                                $progress = 100;
                                $currentStep = 'returned';
                            }
                        } else {
                            // Custodian Request Flow: Submitted -> Custodian Review -> Admin Approval -> Released -> Returned
                            if ($request['status'] === 'pending') {
                                $progress = 25;
                                $currentStep = 'custodian';
                            } elseif ($request['status'] === 'custodian_review' || $request['status'] === 'department_review') {
                                $progress = 50;
                                $currentStep = 'admin';
                            } elseif ($request['status'] === 'approved') {
                                $progress = 75;
                                $currentStep = 'approved';
                            } elseif ($request['status'] === 'released') {
                                $progress = 90;
                                $currentStep = 'released';
                            } elseif ($request['status'] === 'returned') {
                                $progress = 100;
                                $currentStep = 'returned';
                            }
                        }

                        $borderColor = $isRejected ? 'border-red-500' : ($progress === 100 ? 'border-green-500' : ($progress >= 75 ? 'border-purple-500' : 'border-blue-500'));
                        ?>
                        <div class="request-card bg-white rounded-lg shadow-sm border-l-4 <?= $borderColor ?> p-6">
                            <!-- Request Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?= htmlspecialchars($request['asset_name']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Submitted: <?= date('F j, Y, g:i A', strtotime($request['request_date'])) ?>
                                    </p>
                                </div>
                                <div>
                                    <?php if ($isRejected): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i> Rejected
                                        </span>
                                    <?php elseif ($request['status'] === 'returned'): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-check-double mr-1"></i> Completed & Returned
                                        </span>
                                    <?php elseif ($request['status'] === 'released'): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 text-purple-800">
                                            <i class="fas fa-hand-holding mr-1"></i> In Use
                                        </span>
                                    <?php elseif ($request['status'] === 'approved'): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                            <i class="fas fa-clock mr-1"></i> In Progress
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Request Details -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 pb-6 border-b border-gray-200">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Asset Code</p>
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($request['asset_code']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Quantity</p>
                                    <p class="text-sm font-medium text-gray-900"><?= $request['quantity'] ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Campus</p>
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($request['campus_name']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Expected Return</p>
                                    <p class="text-sm font-medium text-gray-900"><?= date('M j, Y', strtotime($request['expected_return_date'])) ?></p>
                                </div>
                            </div>

                            <!-- Purpose -->
                            <div class="mb-6">
                                <p class="text-xs text-gray-500 mb-1">Purpose</p>
                                <p class="text-sm text-gray-700"><?= htmlspecialchars($request['purpose']) ?></p>
                            </div>

                            <?php if ($request['status'] === 'released'): ?>
                                <!-- Return Alert Banner -->
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h3 class="text-sm font-semibold text-yellow-800">Asset In Your Possession</h3>
                                            <div class="mt-2 text-sm text-yellow-700">
                                                <p>Please return this asset by <strong><?= date('F j, Y', strtotime($request['expected_return_date'])) ?></strong></p>
                                                <?php
                                                $daysUntilDue = ceil((strtotime($request['expected_return_date']) - time()) / 86400);
                                                if ($daysUntilDue < 0): ?>
                                                    <p class="mt-2 text-red-600 font-bold">
                                                        <i class="fas fa-exclamation-circle"></i> OVERDUE BY <?= abs($daysUntilDue) ?> DAYS! Please return immediately.
                                                    </p>
                                                <?php elseif ($daysUntilDue <= 2): ?>
                                                    <p class="mt-2 text-orange-600 font-semibold">
                                                        <i class="fas fa-clock"></i> DUE SOON - <?= $daysUntilDue ?> day(s) remaining
                                                    </p>
                                                <?php else: ?>
                                                    <p class="mt-2 text-green-600">
                                                        <i class="fas fa-check"></i> <?= $daysUntilDue ?> days remaining
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-3">
                                                <p class="text-xs text-yellow-600">
                                                    <i class="fas fa-info-circle"></i> Return to custodian office with this request ID: <strong>#<?= $request['id'] ?></strong>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!$isRejected): ?>
                                <!-- Progress Timeline -->
                                <div class="progress-container">
                                    <div class="progress-line">
                                        <div class="progress-line-fill" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="progress-steps">
                                        <!-- Step 1: Submitted -->
                                        <div class="progress-step completed">
                                            <div class="progress-step-circle">
                                                <i class="fas fa-paper-plane"></i>
                                            </div>
                                            <div class="progress-step-label">
                                                <div>Submitted</div>
                                                <div class="text-xs text-gray-500 mt-1"><?= date('M j', strtotime($request['request_date'])) ?></div>
                                            </div>
                                        </div>

                                        <?php if ($request['request_source'] === 'office'): ?>
                                            <!-- Office Request Flow: Step 2 - Office Review (Only step before release) -->
                                            <div class="progress-step <?= $progress >= 66 ? 'completed' : ($progress >= 33 ? 'current' : '') ?>">
                                                <div class="progress-step-circle">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                                <div class="progress-step-label">
                                                    <div>Office Review</div>
                                                    <?php if ($progress >= 66): ?>
                                                        <div class="text-xs text-gray-500 mt-1">Approved</div>
                                                    <?php elseif ($progress >= 33): ?>
                                                        <div class="text-xs text-gray-500 mt-1">Pending</div>
                                                    <?php else: ?>
                                                        <div class="text-xs text-gray-500 mt-1">Pending</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Custodian Request Flow: Step 2 - Custodian Review -->
                                            <div class="progress-step <?= $progress >= 50 ? 'completed' : ($progress >= 25 ? 'current' : '') ?>">
                                                <div class="progress-step-circle">
                                                    <i class="fas fa-user-check"></i>
                                                </div>
                                                <div class="progress-step-label">
                                                    <div>Custodian Review</div>
                                                    <?php if ($progress >= 50): ?>
                                                        <div class="text-xs text-gray-500 mt-1">Approved</div>
                                                    <?php elseif ($progress >= 25): ?>
                                                        <div class="text-xs text-gray-500 mt-1">Pending</div>
                                                    <?php else: ?>
                                                        <div class="text-xs text-gray-500 mt-1">Pending</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Custodian Request Flow: Step 3 - Admin Approval -->
                                            <div class="progress-step <?= $progress >= 75 ? 'completed' : ($progress >= 50 ? 'current' : '') ?>">
                                                <div class="progress-step-circle">
                                                    <i class="fas fa-user-shield"></i>
                                                </div>
                                                <div class="progress-step-label">
                                                    <div>Admin Approval</div>
                                                    <?php if ($progress >= 75): ?>
                                                        <div class="text-xs text-gray-500 mt-1">Approved</div>
                                                    <?php elseif ($progress >= 50): ?>
                                                        <div class="text-xs text-gray-500 mt-1">In Review</div>
                                                    <?php else: ?>
                                                        <div class="text-xs text-gray-500 mt-1">Pending</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Step 4: Released -->
                                        <div class="progress-step <?= $progress >= 90 ? 'completed' : ($progress >= 75 ? 'current' : '') ?>">
                                            <div class="progress-step-circle">
                                                <i class="fas fa-hand-holding"></i>
                                            </div>
                                            <div class="progress-step-label">
                                                <div>Asset Released</div>
                                                <?php if ($progress >= 90): ?>
                                                    <div class="text-xs text-gray-500 mt-1"><?= isset($request['released_date']) ? date('M j', strtotime($request['released_date'])) : 'Released' ?></div>
                                                <?php elseif ($progress >= 75): ?>
                                                    <div class="text-xs text-gray-500 mt-1">Ready</div>
                                                <?php else: ?>
                                                    <div class="text-xs text-gray-500 mt-1">Pending</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Step 5: Returned -->
                                        <div class="progress-step <?= $progress === 100 ? 'completed' : '' ?>">
                                            <div class="progress-step-circle">
                                                <i class="fas fa-undo"></i>
                                            </div>
                                            <div class="progress-step-label">
                                                <div>Returned</div>
                                                <?php if ($progress === 100): ?>
                                                    <div class="text-xs text-gray-500 mt-1"><?= isset($request['returned_date']) ? date('M j', strtotime($request['returned_date'])) : 'Complete' ?></div>
                                                <?php else: ?>
                                                    <div class="text-xs text-gray-500 mt-1">Pending</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Rejection Info -->
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex items-start">
                                        <i class="fas fa-exclamation-circle text-red-600 text-xl mt-1 mr-3"></i>
                                        <div>
                                            <p class="font-semibold text-red-900">Request Rejected</p>
                                            <p class="text-sm text-red-800 mt-1">
                                                This request was rejected on <?= date('F j, Y', strtotime($request['approval_date'] ?? $request['request_date'])) ?>.
                                            </p>
                                            <?php if (!empty($request['rejection_reason'])): ?>
                                                <div class="mt-3 bg-white border border-red-200 rounded p-3">
                                                    <p class="text-xs text-gray-600 mb-1">Reason:</p>
                                                    <p class="text-sm text-gray-900"><?= htmlspecialchars($request['rejection_reason']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Action Button -->
                            <div class="mt-6 flex items-center justify-end">
                                <button onclick="viewDetails(<?= $request['id'] ?>)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-info-circle mr-1"></i>View Full Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
    async function viewDetails(requestId) {
        try {
            const response = await fetch(`/AMS-REQ/api/requests.php?action=get_request&request_id=${requestId}`);
            const data = await response.json();

            if (data.success) {
                const request = data.request;
                const history = request.approval_history || [];

                let historyHtml = '';
                if (history.length > 0) {
                    historyHtml = '<div class="mt-4"><h4 class="font-semibold text-gray-900 mb-3">Approval History</h4><div class="space-y-3">';
                    history.forEach(approval => {
                        let badgeClass = 'bg-gray-100 text-gray-800';
                        let statusText = approval.status;

                        if (approval.status === 'approved') {
                            badgeClass = 'bg-green-100 text-green-800';
                            statusText = 'Approved';
                        } else if (approval.status === 'rejected') {
                            badgeClass = 'bg-red-100 text-red-800';
                            statusText = 'Rejected';
                        } else if (approval.status === 'released') {
                            badgeClass = 'bg-purple-100 text-purple-800';
                            statusText = 'Released';
                        } else if (approval.status === 'returned') {
                            badgeClass = 'bg-blue-100 text-blue-800';
                            statusText = 'Returned';
                        } else if (approval.status === 'late_return') {
                            badgeClass = 'bg-orange-100 text-orange-800';
                            statusText = 'Late Return';
                        }

                        historyHtml += `
                            <div class="border border-gray-200 rounded-lg p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="font-medium text-gray-900">${escapeHtml(approval.approver_name)}</p>
                                        <p class="text-sm text-gray-600">${approval.approver_role}</p>
                                    </div>
                                    <span class="px-2 py-1 rounded text-xs font-semibold ${badgeClass}">
                                        ${statusText}
                                    </span>
                                </div>
                                ${approval.comments ? `<p class="text-sm text-gray-700 italic">${escapeHtml(approval.comments)}</p>` : ''}
                                <p class="text-xs text-gray-500 mt-2">${new Date(approval.created_at).toLocaleString()}</p>
                            </div>
                        `;
                    });
                    historyHtml += '</div></div>';
                }

                Swal.fire({
                    title: 'Request Details',
                    html: `
                        <div class="text-left space-y-4">
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-2">Asset Information</h4>
                                <div class="bg-gray-50 rounded p-3 space-y-2">
                                    <p class="text-sm"><span class="font-medium">Asset:</span> ${escapeHtml(request.asset_name)}</p>
                                    <p class="text-sm"><span class="font-medium">Code:</span> ${escapeHtml(request.asset_code)}</p>
                                    <p class="text-sm"><span class="font-medium">Quantity:</span> ${request.quantity}</p>
                                    <p class="text-sm"><span class="font-medium">Expected Return:</span> ${new Date(request.expected_return_date).toLocaleDateString()}</p>
                                    <p class="text-sm"><span class="font-medium">Purpose:</span> ${escapeHtml(request.purpose)}</p>
                                </div>
                            </div>
                            ${historyHtml}
                        </div>
                    `,
                    width: '600px',
                    confirmButtonText: 'Close'
                });
            }
        } catch (error) {
            Swal.fire('Error', 'Failed to load request details', 'error');
        }
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    </script>
</body>
</html>
