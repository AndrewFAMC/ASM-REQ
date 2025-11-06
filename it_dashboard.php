<?php
// IT Dashboard - Admin Account Management
require_once 'config.php';
require_once 'includes/it_dashboard_helpers.php'; // Include helper functions

// --- IT Admin Authentication ---
define('SESSION_TIMEOUT', 1800); // 30 minutes timeout

if (!isset($_SESSION['it_admin_logged_in']) || $_SESSION['it_admin_logged_in'] !== true) {
    header('Location: it_login.php');
    exit;
}

// Check for session timeout
if (isset($_SESSION['it_admin_login_time']) && (time() - $_SESSION['it_admin_login_time'] > SESSION_TIMEOUT)) {
    unset($_SESSION['it_admin_logged_in'], $_SESSION['it_admin_email'], $_SESSION['it_admin_login_time']);
    header('Location: it_login.php?session_expired=1');
    exit;
}

// Update session timestamp to keep it alive
$_SESSION['it_admin_login_time'] = time();

// User info for the header
$user = [
    'full_name' => 'IT Super Admin',
    'username' => 'ITAdmin',
    'email' => $_SESSION['it_admin_email'] ?? 'hccsuperadmin@gmail.com'
];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    try {
        switch ($_POST['action']) {
            // User Management Actions (for all roles)
            case 'add_user':
                // The addUser helper function now handles the transaction for all roles, including 'office'.
                $result = addUser($pdo, $_POST);
                $message = ($_POST['role'] === 'office') ? 'Office user and office created successfully.' : 'User added successfully.';
                echo json_encode(['success' => true, 'message' => $message, 'data' => $result]);
                break;
                
            case 'get_users':
                $filters = json_decode($_POST['filters'] ?? '{}', true);
                $users = getUsers($pdo, $filters);
                echo json_encode(['success' => true, 'data' => $users]);
                break;
                
            case 'delete_user':
                $result = deleteUser($pdo, $_POST['user_id']);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
                break;
                
            case 'update_user':
                $result = addUser($pdo, $_POST);
                echo json_encode(['success' => true, 'message' => 'User updated successfully.', 'data' => $result]);
                break;
                
            case 'toggle_user_status':
                $result = toggleUserStatus($pdo, $_POST['user_id']);
                echo json_encode(['success' => true, 'message' => 'User status updated successfully.', 'data' => $result]);
                break;
            
            // New monitoring actions
            case 'get_system_stats':
                $stats = getSystemStats();
                echo json_encode(['success' => true, 'data' => $stats]);
                break;

            case 'get_activity_logs':
                $page = $_POST['page'] ?? 1;
                $limit = $_POST['limit'] ?? 10;
                $search = $_POST['search'] ?? '';
                $logs = getActivityLogs($pdo, $page, $limit, $search);
                echo json_encode(['success' => true, 'data' => $logs]);
                break;

            case 'get_login_attempts':
                $logs = getLoginAttempts($pdo);
                echo json_encode(['success' => true, 'data' => $logs]);
                break;

            case 'get_connected_users':
                $users = getConnectedUsers($pdo);
                echo json_encode(['success' => true, 'data' => $users]);
                break;

            // Maintenance mode actions
            case 'get_maintenance_status':
                echo json_encode(['success' => true, 'data' => getMaintenanceStatus()]);
                break;

            case 'start_maintenance':
                $duration = (int)($_POST['duration'] ?? 300); // Default 5 mins
                $result = startMaintenance($duration);
                echo json_encode(['success' => true, 'message' => 'Maintenance mode started.', 'data' => $result]);
                break;

            case 'cancel_maintenance':
                $result = cancelMaintenance();
                echo json_encode(['success' => true, 'message' => 'Maintenance mode cancelled.', 'data' => $result]);
                break;

            case 'force_logout':
                $result = forceLogoutUser($pdo, $_POST['user_id']);
                echo json_encode(['success' => true, 'message' => 'User has been successfully logged out.']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch initial data for the dashboard
$campuses = fetchAll($pdo, "SELECT id, campus_name FROM campuses ORDER BY campus_name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Dashboard - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <style>
        :root {
            --primary-bg: #f3f4f6;
            --sidebar-bg: #1f2937;
            --sidebar-text: #d1d5db;
            --sidebar-hover-bg: #374151;
            --sidebar-active-bg: #4b5563;
        }
        .swal2-container { z-index: 9999; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #4b5563; border-radius: 20px; }
        .tab-active { border-color: #3b82f6; color: #3b82f6; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex-col transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 md:flex">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">IT Dashboard</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 sidebar-scroll overflow-y-auto">
                <a href="#" onclick="showTab('dashboard')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700 tab-active">
                    <i class="fas fa-tachometer-alt w-6"></i><span>Dashboard</span>
                </a>
                <a href="#" onclick="showTab('user-management')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-users-cog w-6"></i><span>User Management</span>
                </a>
                <a href="#" onclick="showTab('system-monitoring')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-heartbeat w-6"></i><span>System Monitoring</span>
                </a>
                <a href="#" onclick="showTab('activity-logs')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-history w-6"></i><span>Activity Logs</span>
                </a>
                <a href="#" onclick="showTab('maintenance')" class="tab-item flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-tools w-6"></i><span>Maintenance</span>
                </a>
            </nav>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <button id="open-sidebar" class="md:hidden text-gray-500 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-800">Welcome, IT Super Admin</h1>
                <div class="flex items-center space-x-4">
                    <a href="it_logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                
                <!-- Dashboard Tab -->
                <div id="dashboard-tab" class="tab-content">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">System Overview</h2>
                    <!-- Stats cards will be loaded here -->
                </div>

                <!-- User Management Tab -->
                <div id="user-management-tab" class="tab-content hidden">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-semibold text-gray-800">Manage Users</h2>
                            <button id="addUserBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add User
                            </button>
                        </div>
                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <input type="text" id="userSearch" placeholder="Search by name, email..." class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <select id="roleFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="custodian">Custodian</option> 
                                <option value="employee">Employee</option>
                                <option value="office">Office Head</option>
                            </select>
                            <select id="campusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Campuses</option>
                                <?php foreach ($campuses as $campus): ?>
                                    <option value="<?= $campus['id'] ?>"><?= htmlspecialchars($campus['campus_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <!-- User Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                    <tr>
                                        <th class="py-3 px-6 text-left">User</th>
                                        <th class="py-3 px-6 text-left">Role</th>
                                        <th class="py-3 px-6 text-center">Campus</th>
                                        <th class="py-3 px-6 text-center">Status</th>
                                        <th class="py-3 px-6 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody" class="text-gray-600 text-sm font-light">
                                    <!-- User rows will be injected here by JavaScript -->
                                </tbody>
                            </table>
                            <div id="users-loading" class="text-center py-4">Loading users...</div>
                        </div>
                    </div>
                </div>

                <!-- System Monitoring Tab -->
                <div id="system-monitoring-tab" class="tab-content hidden">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">System Monitoring</h2>
                    <!-- Monitoring widgets will be loaded here -->
                </div>

                <!-- Activity Logs Tab -->
                <div id="activity-logs-tab" class="tab-content hidden">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Activity Logs</h2>
                    <!-- Logs will be loaded here -->
                </div>

                <!-- Maintenance Tab -->
                <div id="maintenance-tab" class="tab-content hidden">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Maintenance Mode</h2>
                    <!-- Maintenance controls will be loaded here -->
                </div>

            </main>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 id="modalTitle" class="text-xl font-semibold">Add User</h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-800">&times;</button>
            </div>
            <form id="userForm">
                <input type="hidden" name="user_id" id="userId">
                <div class="space-y-4">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" id="full_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="role" id="role" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="employee">Employee</option>
                                <option value="admin">Admin</option>
                                <option value="custodian">Custodian</option>
                                <option value="office">Office Head</option>
                            </select>
                        </div>
                        <div>
                            <label for="campus_id" class="block text-sm font-medium text-gray-700">Campus</label>
                            <select name="campus_id" id="campus_id" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Campus</option>
                                <?php foreach ($campuses as $campus): ?>
                                    <option value="<?= $campus['id'] ?>"><?= htmlspecialchars($campus['campus_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div id="password-section">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" placeholder="Leave blank to keep unchanged" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">For new users, a default password ('user1234') will be set and they will be required to change it on first login.</p>
                    </div>
                </div>
                <!-- Office Specific Fields -->
                <div id="office-fields" class="hidden space-y-4 pt-4 mt-4 border-t border-gray-200">
                     <div>
                        <label for="office_name" class="block text-sm font-medium text-gray-700">Office Name</label>
                        <input type="text" name="office_name" id="office_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="office_floor" class="block text-sm font-medium text-gray-700">Office Floor</label>
                            <select name="office_floor" id="office_floor" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Floor</option>
                                <option value="1st">1st Floor</option>
                                <option value="2nd">2nd Floor</option>
                                <option value="3rd">3rd Floor</option>
                                <option value="4th">4th Floor</option>
                            </select>
                        </div>
                        <div>
                            <label for="office_section_code" class="block text-sm font-medium text-gray-700">Office Section/Code (Optional)</label>
                            <input type="text" name="office_section_code" id="office_section_code" placeholder="e.g., MIS, REG" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" id="cancelBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Save User</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Tab Navigation ---
    window.showTab = function(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        document.getElementById(tabId + '-tab').classList.remove('hidden');

        document.querySelectorAll('.tab-item').forEach(item => item.classList.remove('tab-active'));
        document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('tab-active');
    }

    // --- Sidebar Toggle ---
    const sidebar = document.getElementById('sidebar');
    document.getElementById('open-sidebar').addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
    });
    // Add a mechanism to close the sidebar on mobile, e.g., by clicking outside

    // --- API Helper ---
    async function apiRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', csrfToken);
        for (const key in data) {
            formData.append(key, data[key]);
        }
        try {
            const response = await fetch('it_dashboard.php', { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('API Request Error:', error);
            Swal.fire('Error', 'A network error occurred. Please try again.', 'error');
            return { success: false, message: 'Network error' };
        }
    }

    // --- User Management ---
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const usersTableBody = document.getElementById('usersTableBody');
    const usersLoading = document.getElementById('users-loading');

    async function loadUsers() {
        usersLoading.style.display = 'block';
        usersTableBody.innerHTML = '';
        const filters = {
            search: document.getElementById('userSearch').value,
            role: document.getElementById('roleFilter').value,
            campus_id: document.getElementById('campusFilter').value,
            is_active: document.getElementById('statusFilter').value,
        };
        const res = await apiRequest('get_users', { filters: JSON.stringify(filters) });
        usersLoading.style.display = 'none';
        if (res.success && res.data) {
            if (res.data.length === 0) {
                usersTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No users found.</td></tr>';
                return;
            }
            res.data.forEach(user => {
                const row = `
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="font-medium">${user.full_name}</span>
                            </div>
                        </td>
                        <td class="py-3 px-6 text-left">${user.role}</td>
                        <td class="py-3 px-6 text-center">${user.campus_name || 'N/A'}</td>
                        <td class="py-3 px-6 text-center">
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full ${user.is_active ? 'text-green-600 bg-green-200' : 'text-red-600 bg-red-200'}">
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center">
                                <button onclick="editUser(${user.id})" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110"><i class="fas fa-pencil-alt"></i></button>
                                <button onclick="deleteUser(${user.id})" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110"><i class="fas fa-trash-alt"></i></button>
                                <button onclick="toggleUserStatus(${user.id}, ${user.is_active ? 0 : 1})" class="w-4 mr-2 transform hover:text-yellow-500 hover:scale-110"><i class="fas ${user.is_active ? 'fa-toggle-on' : 'fa-toggle-off'}"></i></button>
                            </div>
                        </td>
                    </tr>`;
                usersTableBody.innerHTML += row;
            });
        }
    }

    // Initial load
    loadUsers();

    // Event Listeners for filters
    ['userSearch', 'roleFilter', 'campusFilter', 'statusFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', loadUsers);
    });
    document.getElementById('userSearch').addEventListener('keyup', loadUsers);

    // Modal controls
    document.getElementById('addUserBtn').addEventListener('click', () => {
        userForm.reset();
        document.getElementById('userId').value = '';
        document.getElementById('modalTitle').innerText = 'Add New User';
        document.getElementById('password-section').classList.remove('hidden');
        userModal.classList.remove('hidden');
    });

    // Show/hide office fields based on role selection
    document.getElementById('role').addEventListener('change', function() {
        const officeFields = document.getElementById('office-fields');
        if (this.value === 'office') {
            officeFields.classList.remove('hidden');
        } else {
            officeFields.classList.add('hidden');
        }
    });

    document.getElementById('closeModal').addEventListener('click', () => userModal.classList.add('hidden'));
    document.getElementById('cancelBtn').addEventListener('click', () => userModal.classList.add('hidden'));

    // Form submission
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = document.getElementById('userId').value;
        const action = userId ? 'update_user' : 'add_user';
        const formData = new FormData(userForm);
        const data = Object.fromEntries(formData.entries());

        const res = await apiRequest(action, data);
        if (res.success) {
            Swal.fire('Success', res.message, 'success');
            userModal.classList.add('hidden');
            loadUsers();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });
});
</script>

</body>
</html>
