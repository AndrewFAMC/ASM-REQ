<?php
/**
 * Office My Requests - Track Asset Request Status
 * Shows office user's asset requests to custodian with approval progress
 */

require_once '../config.php';

// Require authentication
if (!isLoggedIn() || !validateSession($pdo)) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
$role = strtolower($user['role'] ?? '');

if ($role !== 'office') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This page is for Office users only.';
    exit;
}

$userId = $_SESSION['user_id'];
$officeId = $user['office_id'] ?? 0;

// Get office details
$office = fetchOne($pdo, "SELECT * FROM offices WHERE id = ?", [$officeId]);

// Get user's requests with full details
$stmt = $pdo->prepare("
    SELECT
        ar.*,
        a.asset_name,
        COALESCE(a.serial_number, CONCAT('ID-', a.id)) as asset_code,
        c.campus_name,
        u.full_name as requester_name,
        o.office_name as requester_office_name,
        custodian.full_name as custodian_name,
        admin.full_name as admin_name
    FROM asset_requests ar
    LEFT JOIN assets a ON ar.asset_id = a.id
    LEFT JOIN campuses c ON ar.campus_id = c.id
    LEFT JOIN users u ON ar.requester_id = u.id
    LEFT JOIN offices o ON ar.requester_office_id = o.id
    LEFT JOIN users custodian ON ar.custodian_reviewed_by = custodian.id
    LEFT JOIN users admin ON ar.final_approved_by = admin.id
    WHERE ar.requester_id = ? AND ar.requester_office_id = ?
    ORDER BY ar.request_date DESC
");
$stmt->execute([$userId, $officeId]);
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
    if (in_array($request['status'], ['pending', 'custodian_review'])) {
        $stats['in_progress']++;
    } elseif ($request['status'] === 'approved') {
        $stats['approved']++;
    } elseif ($request['status'] === 'rejected') {
        $stats['rejected']++;
    } elseif ($request['status'] === 'released') {
        $stats['released']++;
        // Check if overdue
        if ($request['expected_return_date'] && strtotime($request['expected_return_date']) < time()) {
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
    <title>My Requests - Office Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        .request-card { transition: all 0.2s; }
        .request-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }

        /* Progress Bar Styles */
        .progress-container { position: relative; padding: 2rem 0; }
        .progress-line {
            position: absolute; top: 2.5rem; left: 10%; right: 10%; height: 3px;
            background: #E5E7EB; z-index: 0;
        }
        .progress-line-fill {
            position: absolute; top: 0; left: 0; height: 100%;
            background: #10B981; transition: width 0.3s;
        }
        .progress-steps {
            display: flex; justify-content: space-between;
            position: relative; z-index: 1;
        }
        .progress-step {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; position: relative;
        }
        .progress-step-circle {
            width: 3rem; height: 3rem; border-radius: 50%;
            background: white; border: 3px solid #E5E7EB;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; transition: all 0.3s;
        }
        .progress-step.completed .progress-step-circle {
            background: #10B981; border-color: #10B981; color: white;
        }
        .progress-step.current .progress-step-circle {
            background: #3B82F6; border-color: #3B82F6; color: white;
            animation: pulse-ring 2s infinite;
        }
        .progress-step.rejected .progress-step-circle {
            background: #EF4444; border-color: #EF4444; color: white;
        }
        .progress-step-label {
            margin-top: 0.75rem; text-align: center;
            font-size: 0.875rem; font-weight: 500;
        }
        .progress-step.completed .progress-step-label { color: #10B981; }
        .progress-step.current .progress-step-label { color: #3B82F6; }
        .progress-step.rejected .progress-step-label { color: #EF4444; }
        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Dept Head</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="office_dashboard.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>

                <div class="border-t border-gray-700 my-2"></div>

                <?php
                $checkApprover = $pdo->prepare("SELECT COUNT(*) as is_approver FROM department_approvers WHERE approver_user_id = ? AND is_active = TRUE");
                $checkApprover->execute([$user['id']]);
                $isApprover = $checkApprover->fetch()['is_approver'] > 0;

                if ($isApprover):
                ?>
                <a href="approve_requests.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-check-circle w-6"></i>
                    <span>Approve Requests</span>
                </a>

                <a href="release_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-hand-holding w-6"></i>
                    <span>Release Assets</span>
                </a>

                <a href="return_assets.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-md">
                    <i class="fas fa-undo-alt w-6"></i>
                    <span>Accept Returns</span>
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

                <a href="my_requests.php" class="flex items-center px-4 py-3 text-white bg-gray-700 rounded-md">
                    <i class="fas fa-clipboard-list w-6"></i>
                    <span>My Requests</span>
                </a>
            </nav>
        </div>

        <!-- Main content wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">My Asset Requests</h1>
                    <p class="text-sm text-gray-600">Office: <?= htmlspecialchars($office['office_name']) ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

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
                    <a href="request_from_custodian.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        Request Assets
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($requests as $request): ?>
                        <?php
                        // Office-to-Custodian Transfer Request Flow (Permanent Transfer)
                        $progress = 0;
                        $currentStep = 'submitted';
                        $isRejected = $request['status'] === 'rejected';
                        $isCompleted = $request['status'] === 'released'; // Released means transferred to office

                        if ($isRejected) {
                            $currentStep = 'rejected';
                        } elseif ($request['status'] === 'pending') {
                            $progress = 33;
                            $currentStep = 'custodian';
                        } elseif ($request['status'] === 'custodian_review') {
                            $progress = 66;
                            $currentStep = 'admin';
                        } elseif ($request['status'] === 'approved' || $request['status'] === 'released') {
                            $progress = 100;
                            $currentStep = 'completed';
                        }

                        $borderColor = $isRejected ? 'border-red-500' : ($progress === 100 ? 'border-green-500' : 'border-blue-500');
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
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-building mr-1"></i>
                                        Requested by: <?= htmlspecialchars($office['office_name']) ?>
                                    </p>
                                </div>
                                <div>
                                    <?php if ($isRejected): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i> Rejected
                                        </span>
                                    <?php elseif ($request['status'] === 'released' || $request['status'] === 'approved'): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Transferred to Office
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                            <i class="fas fa-clock mr-1"></i> Pending Review
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Request Details -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6 pb-6 border-b border-gray-200">
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
                            </div>

                            <!-- Purpose -->
                            <div class="mb-6">
                                <p class="text-xs text-gray-500 mb-1">Purpose</p>
                                <p class="text-sm text-gray-700"><?= htmlspecialchars($request['purpose']) ?></p>
                            </div>

                            <!-- Progress Tracker -->
                            <?php if (!$isRejected): ?>
                            <div class="progress-container">
                                <div class="progress-line">
                                    <div class="progress-line-fill" style="width: <?= $progress ?>%"></div>
                                </div>
                                <div class="progress-steps">
                                    <!-- Step 1: Submitted -->
                                    <div class="progress-step completed">
                                        <div class="progress-step-circle">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="progress-step-label">Submitted</div>
                                    </div>

                                    <!-- Step 2: Custodian Review -->
                                    <div class="progress-step <?= $progress >= 33 ? 'completed' : '' ?> <?= $currentStep === 'custodian' ? 'current' : '' ?>">
                                        <div class="progress-step-circle">
                                            <i class="fas <?= $progress > 33 ? 'fa-check' : 'fa-user-shield' ?>"></i>
                                        </div>
                                        <div class="progress-step-label">
                                            Custodian Review
                                            <?php if ($request['custodian_name']): ?>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($request['custodian_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Step 3: Admin Approval -->
                                    <div class="progress-step <?= $progress >= 66 ? 'completed' : '' ?> <?= $currentStep === 'admin' ? 'current' : '' ?>">
                                        <div class="progress-step-circle">
                                            <i class="fas <?= $progress > 66 ? 'fa-check' : 'fa-user-tie' ?>"></i>
                                        </div>
                                        <div class="progress-step-label">
                                            Admin Approval
                                            <?php if ($request['admin_name']): ?>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($request['admin_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Step 4: Transferred -->
                                    <div class="progress-step <?= $progress === 100 ? 'completed' : '' ?> <?= $currentStep === 'completed' ? 'current' : '' ?>">
                                        <div class="progress-step-circle">
                                            <i class="fas <?= $progress === 100 ? 'fa-check' : 'fa-box-open' ?>"></i>
                                        </div>
                                        <div class="progress-step-label">
                                            <?= $progress === 100 ? 'Transferred' : 'Awaiting Transfer' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- Rejection Info -->
                            <div class="bg-red-50 border-l-4 border-red-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Request Rejected</h3>
                                        <?php if ($request['rejection_reason']): ?>
                                            <p class="mt-2 text-sm text-red-700">
                                                <strong>Reason:</strong> <?= htmlspecialchars($request['rejection_reason']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($request['rejected_at']): ?>
                                            <p class="mt-1 text-xs text-red-600">
                                                Rejected on <?= date('F j, Y', strtotime($request['rejected_at'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Notes Section -->
                            <?php if ($request['custodian_notes'] || $request['admin_notes']): ?>
                            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-blue-900 mb-2">
                                    <i class="fas fa-sticky-note mr-1"></i> Notes
                                </h4>
                                <?php if ($request['custodian_notes']): ?>
                                    <p class="text-sm text-blue-800 mb-2">
                                        <strong>Custodian:</strong> <?= htmlspecialchars($request['custodian_notes']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($request['admin_notes']): ?>
                                    <p class="text-sm text-blue-800">
                                        <strong>Admin:</strong> <?= htmlspecialchars($request['admin_notes']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
