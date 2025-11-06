<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

// Get campuses for dropdown
$stmt = $pdo->query("SELECT id, campus_name FROM campuses ORDER BY campus_name");
$campuses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HCC Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/sweetalert-config.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
        /* Ensure SweetAlert2 popups are centered */
        .swal2-container {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }
    </style>

</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-semibold text-gray-900">User Management</h1>
                        <div class="flex items-center space-x-4">
                            <!-- Notification Bell -->
                            <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                            <button id="addUserBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i>Add User
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="searchInput" placeholder="Search users..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select id="roleFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                                <option value="custodian">Custodian</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                            <select id="campusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Campuses</option>
                                <?php foreach ($campuses as $campus): ?>
                                    <option value="<?= $campus['id'] ?>"><?= htmlspecialchars($campus['campus_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button id="applyFilters" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg mr-2">Apply Filters</button>
                        <button id="clearFilters" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">Clear</button>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Users</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assets</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Users will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div id="loadingIndicator" class="text-center text-gray-500">Loading users...</div>
                        <div id="noUsersMessage" class="text-center text-gray-500 hidden">No users found.</div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- User Assets Modal -->
    <div id="userAssetsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-5xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="assetsModalTitle" class="text-lg font-medium text-gray-900">User Assets</h3>
                    <button id="closeAssetsModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userAssetsTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Assets will be loaded here -->
                        </tbody>
                    </table>
                </div>
                <div id="loadingAssets" class="text-center py-4 text-gray-500">Loading assets...</div>
                <div id="noAssetsMessage" class="text-center py-4 text-gray-500 hidden">No assets assigned to this user.</div>
            </div>
        </div>
    </div>

    <!-- Asset Detail Modal -->
    <div id="assetDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Asset Details</h3>
                    <button id="closeAssetDetailModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="assetDetailContent" class="space-y-3">
                    <!-- Asset details will be loaded here -->
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button id="transferAssetBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-exchange-alt mr-2"></i>Transfer Asset
                    </button>
                    <button id="removeAssetBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-times mr-2"></i>Remove Asset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Asset Modal -->
    <div id="transferAssetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Transfer Asset</h3>
                    <button id="closeTransferModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="transferAssetForm">
                    <input type="hidden" id="transferAssetId" name="asset_id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Asset</label>
                            <p id="transferAssetName" class="mt-1 text-sm text-gray-900"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Transfer To</label>
                            <select id="targetUserId" name="target_user_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select User</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" id="cancelTransferBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add User</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="userForm">
                    <input type="hidden" id="userId" name="user_id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" id="username" name="username" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" id="fullName" name="full_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="email" name="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div id="defaultPasswordInfo" class="bg-blue-50 border border-blue-200 rounded-md p-3">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                Default passwords will be assigned:
                            </p>
                            <ul class="text-xs text-blue-700 mt-2 ml-4 list-disc">
                                <li><strong>Staff:</strong> staff1234</li>
                                <li><strong>Custodian:</strong> custodian1234</li>
                            </ul>
                            <p class="text-xs text-blue-600 mt-2">Users will be required to change their password on first login.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select id="role" name="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="user">User</option>
                                <option value="staff">Staff</option>
                                <option value="custodian">Custodian</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Campus</label>
                            <select id="campusId" name="campus_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Campus</option>
                                <?php foreach ($campuses as $campus): ?>
                                    <option value="<?= $campus['id'] ?>"><?= htmlspecialchars($campus['campus_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="activeField">
                            <label class="flex items-center">
                                <input type="checkbox" id="isActive" name="is_active" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" id="cancelBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // Initialize user management when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AdminDashboard !== 'undefined') {
                window.adminDashboard = new AdminDashboard();
                window.adminDashboard.loadUsers();
            }
        });
    </script>
</body>
</html>
