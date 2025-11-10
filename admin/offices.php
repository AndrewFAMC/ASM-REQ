<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$user = getUserInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Management - HCC Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/sweetalert-config.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <style>
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
        <div id="sidebar" class="w-64 bg-gray-800 text-gray-300 flex-shrink-0 flex flex-col">
            <div class="flex items-center justify-center h-20 border-b border-gray-700">
                <img src="../logo/1.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-white text-lg font-bold">Admin Panel</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <a href="admin_dashboard.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-home w-6"></i><span>Dashboard</span>
                </a>
                <a href="approve_requests.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-check-circle w-6"></i><span>Approve Requests</span>
                </a>
                <a href="users.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-users w-6"></i><span>User Management</span>
                </a>
                <a href="offices.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700 bg-gray-700">
                    <i class="fas fa-building w-6"></i><span>Office Management</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-2 rounded-md hover:bg-gray-700">
                    <i class="fas fa-chart-bar w-6"></i><span>Reports</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Office Management</h1>
                    <p class="text-sm text-gray-600 mt-1">Manage campus offices and departments</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php include __DIR__ . '/../includes/notification_center.php'; ?>

                    <button id="addOfficeBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i>Add Office
                    </button>
                    <a href="../profile.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-user-circle mr-1"></i> My Profile
                    </a>
                    <a href="../logout.php" class="text-sm text-red-600 hover:text-red-800 border border-red-200 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="searchInput" placeholder="Search offices..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                            <select id="campusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Campuses</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button id="applyFilters" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg mr-2">Apply Filters</button>
                            <button id="clearFilters" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">Clear</button>
                        </div>
                    </div>
                </div>

                <!-- Offices Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Offices</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Office Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Floor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assets</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="officesTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Offices will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div id="loadingIndicator" class="text-center text-gray-500">Loading offices...</div>
                        <div id="noOfficesMessage" class="text-center text-gray-500 hidden">No offices found.</div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Office Modal -->
    <div id="officeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add Office</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="officeForm">
                    <input type="hidden" id="officeId" name="id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Office Name <span class="text-red-500">*</span></label>
                            <input type="text" id="officeName" name="office_name" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., Finance Office, HR Department">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Section Code</label>
                            <input type="text" id="sectionCode" name="section_code"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., FIN-001, HR-002">
                            <p class="mt-1 text-xs text-gray-500">Optional: Unique identifier for the office</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Floor</label>
                            <input type="text" id="floor" name="floor"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g., 1st Floor, 2nd Floor, Ground Floor">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Campus <span class="text-red-500">*</span></label>
                            <select id="campusId" name="campus_id" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Campus</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Optional description or notes about this office"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" id="cancelBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</button>
                        <button type="submit" id="submitBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-save mr-1"></i>Save Office
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Office Details Modal -->
    <div id="viewOfficeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Office Details</h3>
                    <button id="closeViewModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="officeDetailsContent" class="space-y-4">
                    <!-- Office details will be loaded here -->
                </div>
                <div class="mt-6 flex justify-end">
                    <button id="closeViewModalBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let campuses = [];
        let offices = [];

        // DOM Elements
        const officeModal = document.getElementById('officeModal');
        const viewOfficeModal = document.getElementById('viewOfficeModal');
        const officeForm = document.getElementById('officeForm');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadCampuses();
            loadOffices();
            initializeEventListeners();
        });

        function initializeEventListeners() {
            // Add office button
            document.getElementById('addOfficeBtn').addEventListener('click', () => openOfficeModal());

            // Close modals
            document.getElementById('closeModal').addEventListener('click', () => closeOfficeModal());
            document.getElementById('cancelBtn').addEventListener('click', () => closeOfficeModal());
            document.getElementById('closeViewModal').addEventListener('click', () => closeViewOfficeModal());
            document.getElementById('closeViewModalBtn').addEventListener('click', () => closeViewOfficeModal());

            // Form submission
            officeForm.addEventListener('submit', handleFormSubmit);

            // Filters
            document.getElementById('applyFilters').addEventListener('click', loadOffices);
            document.getElementById('clearFilters').addEventListener('click', clearFilters);

            // Search on Enter
            document.getElementById('searchInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') loadOffices();
            });
        }

        async function loadCampuses() {
            try {
                const response = await fetch('../api/offices.php?action=get_campuses');
                const result = await response.json();

                if (result.success) {
                    campuses = result.data;
                    populateCampusDropdowns();
                }
            } catch (error) {
                console.error('Error loading campuses:', error);
                showAlert('error', 'Failed to load campuses');
            }
        }

        function populateCampusDropdowns() {
            const campusFilter = document.getElementById('campusFilter');
            const campusId = document.getElementById('campusId');

            campuses.forEach(campus => {
                const option1 = new Option(campus.campus_name, campus.id);
                const option2 = new Option(campus.campus_name, campus.id);
                campusFilter.add(option1);
                campusId.add(option2);
            });
        }

        async function loadOffices() {
            const search = document.getElementById('searchInput').value;
            const campus_id = document.getElementById('campusFilter').value;

            const params = new URLSearchParams({
                action: 'list',
                search: search,
                campus_id: campus_id
            });

            try {
                document.getElementById('loadingIndicator').classList.remove('hidden');
                document.getElementById('noOfficesMessage').classList.add('hidden');

                const response = await fetch(`../api/offices.php?${params}`);
                const result = await response.json();

                if (result.success) {
                    offices = result.data;
                    renderOfficesTable();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error loading offices:', error);
                showAlert('error', 'Failed to load offices');
            } finally {
                document.getElementById('loadingIndicator').classList.add('hidden');
            }
        }

        function renderOfficesTable() {
            const tbody = document.getElementById('officesTableBody');
            const noOfficesMessage = document.getElementById('noOfficesMessage');

            if (offices.length === 0) {
                tbody.innerHTML = '';
                noOfficesMessage.classList.remove('hidden');
                return;
            }

            noOfficesMessage.classList.add('hidden');

            tbody.innerHTML = offices.map(office => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(office.office_name)}</div>
                        ${office.description ? `<div class="text-xs text-gray-500">${escapeHtml(office.description).substring(0, 50)}...</div>` : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${office.section_code ? escapeHtml(office.section_code) : '<span class="text-gray-400">-</span>'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${office.floor ? escapeHtml(office.floor) : '<span class="text-gray-400">-</span>'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">${escapeHtml(office.campus_name || 'N/A')}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-box mr-1"></i> ${office.asset_count || 0}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-users mr-1"></i> ${office.user_count || 0}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <button onclick="viewOffice(${office.id})" class="text-blue-600 hover:text-blue-900" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editOffice(${office.id})" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteOffice(${office.id})" class="text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function openOfficeModal(office = null) {
            if (office) {
                modalTitle.textContent = 'Edit Office';
                document.getElementById('officeId').value = office.id;
                document.getElementById('officeName').value = office.office_name;
                document.getElementById('sectionCode').value = office.section_code || '';
                document.getElementById('floor').value = office.floor || '';
                document.getElementById('campusId').value = office.campus_id;
                document.getElementById('description').value = office.description || '';
                submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Update Office';
            } else {
                modalTitle.textContent = 'Add Office';
                officeForm.reset();
                submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Office';
            }
            officeModal.classList.remove('hidden');
        }

        function closeOfficeModal() {
            officeModal.classList.add('hidden');
            officeForm.reset();
        }

        function closeViewOfficeModal() {
            viewOfficeModal.classList.add('hidden');
        }

        async function handleFormSubmit(e) {
            e.preventDefault();

            const formData = new FormData(officeForm);
            const officeId = document.getElementById('officeId').value;
            const action = officeId ? 'update' : 'create';

            formData.append('csrf_token', csrfToken);

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...';

                const response = await fetch(`../api/offices.php?action=${action}`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    closeOfficeModal();
                    loadOffices();
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error saving office:', error);
                showAlert('error', 'Failed to save office');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = officeId ? '<i class="fas fa-save mr-1"></i>Update Office' : '<i class="fas fa-save mr-1"></i>Save Office';
            }
        }

        async function viewOffice(id) {
            try {
                const response = await fetch(`../api/offices.php?action=get&id=${id}`);
                const result = await response.json();

                if (result.success) {
                    const office = result.data;
                    document.getElementById('officeDetailsContent').innerHTML = `
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Office Name</label>
                                <p class="mt-1 text-sm text-gray-900">${escapeHtml(office.office_name)}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Section Code</label>
                                <p class="mt-1 text-sm text-gray-900">${office.section_code || '-'}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Floor</label>
                                <p class="mt-1 text-sm text-gray-900">${office.floor || '-'}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Campus</label>
                                <p class="mt-1 text-sm text-gray-900">${escapeHtml(office.campus_name)}</p>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <p class="mt-1 text-sm text-gray-900">${office.description || '-'}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Created</label>
                                <p class="mt-1 text-sm text-gray-900">${new Date(office.created_at).toLocaleString()}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                <p class="mt-1 text-sm text-gray-900">${new Date(office.updated_at).toLocaleString()}</p>
                            </div>
                        </div>
                    `;
                    viewOfficeModal.classList.remove('hidden');
                } else {
                    showAlert('error', result.message);
                }
            } catch (error) {
                console.error('Error viewing office:', error);
                showAlert('error', 'Failed to load office details');
            }
        }

        async function editOffice(id) {
            const office = offices.find(o => o.id == id);
            if (office) {
                openOfficeModal(office);
            }
        }

        async function deleteOffice(id) {
            const office = offices.find(o => o.id == id);
            if (!office) return;

            const result = await Swal.fire({
                title: 'Delete Office?',
                html: `Are you sure you want to delete <strong>${escapeHtml(office.office_name)}</strong>?<br><br>
                       <span class="text-sm text-gray-600">This action cannot be undone. The office must have no assigned assets or users.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('csrf_token', csrfToken);

                    const response = await fetch('../api/offices.php?action=delete', {
                        method: 'POST',
                        body: formData
                    });

                    const deleteResult = await response.json();

                    if (deleteResult.success) {
                        showAlert('success', deleteResult.message);
                        loadOffices();
                    } else {
                        showAlert('error', deleteResult.message);
                    }
                } catch (error) {
                    console.error('Error deleting office:', error);
                    showAlert('error', 'Failed to delete office');
                }
            }
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('campusFilter').value = '';
            loadOffices();
        }

        function showAlert(type, message) {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Success!' : 'Error!',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
