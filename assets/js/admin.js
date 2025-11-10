// Admin Dashboard JavaScript
// Handles AJAX requests, UI interactions, and data visualization

class AdminDashboard {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        this.currentPage = 'dashboard';
        this.charts = {};
        this.campuses = [];
        this.searchTimeout = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboardData();
        this.initializeCharts();
        this.loadCampuses();
        // Load users if on users page
        if (document.getElementById('usersTableBody')) {
            this.loadUsers();
            this.bindUserActionEvents();
        }
    }

    async loadCampuses() {
        try {
            // Use absolute path from site root to call offices API
            const url = '/AMS-REQ/api/offices.php?action=get_campuses';
            console.log('Loading campuses from:', url);

            const response = await fetch(url);
            console.log('Campuses response status:', response.status);

            const result = await response.json();
            console.log('Campuses API response:', result);

            if (result.success) {
                this.campuses = result.data;
                console.log('Loaded campuses:', this.campuses.length, this.campuses);
            } else {
                console.error('Failed to load campuses:', result.message);
            }
        } catch (error) {
            console.error('Failed to load campuses:', error);
        }
    }

    async loadOffices() {
        try {
            // Use absolute path from site root
            const url = '/AMS-REQ/api/offices.php?action=list';
            console.log('Loading offices from:', url);

            const response = await fetch(url);
            console.log('Response status:', response.status);

            const result = await response.json();
            console.log('Offices API response:', result);

            if (result.success) {
                this.offices = result.data;
                console.log('Loaded offices:', this.offices.length, this.offices);
                this.populateOfficeDropdown();
            } else {
                console.error('Failed to load offices:', result.message);
            }
        } catch (error) {
            console.error('Failed to load offices - Error:', error);
        }
    }

    populateOfficeDropdown(selectedCampusId = null) {
        const officeSelect = document.getElementById('officeId');
        const campusSelect = document.getElementById('campusId');

        if (!officeSelect) {
            console.error('Office dropdown element not found');
            return;
        }

        // Get selected campus ID from dropdown if not provided
        if (!selectedCampusId && campusSelect) {
            selectedCampusId = campusSelect.value;
        }

        // Clear existing options
        officeSelect.innerHTML = '<option value="">No Office / Not Assigned</option>';

        // Check if offices is defined and has items
        if (!this.offices || !Array.isArray(this.offices) || this.offices.length === 0) {
            console.warn('No offices available to populate dropdown');
            return;
        }

        // Filter offices by selected campus if campus is selected
        let filteredOffices = this.offices;
        if (selectedCampusId) {
            filteredOffices = this.offices.filter(office => office.campus_id == selectedCampusId);
            console.log('Filtering offices for campus ID:', selectedCampusId, '- Found:', filteredOffices.length);
        }

        if (filteredOffices.length === 0) {
            console.warn('No offices found for selected campus');
            return;
        }

        console.log('Populating dropdown with', filteredOffices.length, 'offices');

        // Add offices grouped by campus
        const officesByCampus = {};
        filteredOffices.forEach(office => {
            const campusName = office.campus_name || 'Unknown Campus';
            if (!officesByCampus[campusName]) {
                officesByCampus[campusName] = [];
            }
            officesByCampus[campusName].push(office);
        });

        // Add optgroups for each campus
        Object.keys(officesByCampus).sort().forEach(campusName => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = campusName;
            officesByCampus[campusName].forEach(office => {
                const option = document.createElement('option');
                option.value = office.id;
                option.textContent = office.office_name;
                optgroup.appendChild(option);
            });
            officeSelect.appendChild(optgroup);
        });

        console.log('Office dropdown populated successfully');
    }

    // User Management Methods
    async loadUsers(filters = {}) {
        const loading = document.getElementById('loadingIndicator');
        if (loading) loading.classList.remove('hidden');

        try {
            const response = await this.makeRequest('get_users', filters);
            if (response.success) {
                this.displayUsers(response.data);
            } else {
                showErrorAlert('Error', response.message || 'Failed to load users');
            }
        } catch (error) {
            console.error('Failed to load users:', error);
            showErrorAlert('Error', 'Failed to load users');
        }
    }

    displayUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        const loading = document.getElementById('loadingIndicator');
        const noUsers = document.getElementById('noUsersMessage');

        console.log('displayUsers called with', users.length, 'users');
        console.log('Users data:', users);

        if (!tbody) {
            console.error('usersTableBody element not found');
            return;
        }

        loading.classList.add('hidden');
        tbody.innerHTML = '';

        if (users.length === 0) {
            console.log('No users to display');
            noUsers.classList.remove('hidden');
            return;
        }

        noUsers.classList.add('hidden');

        let successCount = 0;
        let errorCount = 0;

        users.forEach((user, index) => {
            try {
                console.log(`Processing user ${index + 1}/${users.length}:`, user.username);
                const row = document.createElement('tr');
                row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-700">${user.full_name.charAt(0).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${user.full_name}</div>
                            <div class="text-sm text-gray-500">${user.username}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${this.getRoleBadgeClass(user.role)}">
                        ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${user.campus_name || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${user.office_name ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-building mr-1"></i>${user.office_name}</span>` : '<span class="text-gray-400">-</span>'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${user.approver_id ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-user-check mr-1"></i>${this.getApprovalLevelLabel(user.approval_level)}</span>` : '<span class="text-gray-400">-</span>'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${user.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${user.asset_count > 0 ? `<button class="text-blue-600 hover:text-blue-900 view-assets" data-user-id="${user.id}" data-user-name="${user.full_name}">${user.asset_count} asset(s)</button>` : '0'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button class="text-indigo-600 hover:text-indigo-900 mr-3 edit-user" data-user-id="${user.id}">Edit</button>
                    <button class="text-red-600 hover:text-red-900 delete-user" data-user-id="${user.id}">Delete</button>
                </td>
            `;
                tbody.appendChild(row);
                successCount++;
                console.log(`Successfully added user ${user.username} to table`);
            } catch (error) {
                errorCount++;
                console.error(`Error displaying user ${user.username}:`, error);
                console.error('User data:', user);
            }
        });

        console.log(`Display complete: ${successCount} success, ${errorCount} errors`);
        this.bindUserActionEvents();
    }

    getRoleBadgeClass(role) {
        const classes = {
            admin: 'bg-purple-100 text-purple-800',
            office: 'bg-blue-100 text-blue-800',
            employee: 'bg-indigo-100 text-indigo-800',
            custodian: 'bg-green-100 text-green-800',
            // Legacy roles (for backward compatibility)
            staff: 'bg-indigo-100 text-indigo-800',
            user: 'bg-gray-100 text-gray-800'
        };
        return classes[role] || classes.user;
    }

    getApprovalLevelLabel(level) {
        const labels = {
            primary: 'Primary',
            secondary: 'Secondary',
            backup: 'Backup'
        };
        return labels[level] || level;
    }

    toggleApproverFieldsVisibility(role) {
        const approverContainer = document.getElementById('isApprover')?.parentElement;
        const approvalLevelField = document.getElementById('approvalLevelField');

        if (role === 'office') {
            // Show approver fields for office role
            if (approverContainer) approverContainer.style.display = 'block';
        } else {
            // Hide approver fields for other roles
            if (approverContainer) approverContainer.style.display = 'none';
            if (approvalLevelField) approvalLevelField.style.display = 'none';
            // Uncheck the checkbox when hiding
            const isApproverCheckbox = document.getElementById('isApprover');
            if (isApproverCheckbox) isApproverCheckbox.checked = false;
        }
    }

    async createUser(data) {
        try {
            const response = await this.makeRequest('add_user', data);
            if (response.success) {
                showSuccessAlert('Success', 'User created successfully');
                this.loadUsers();
                this.closeUserModal();
            } else {
                showErrorAlert('Error', response.message || 'Failed to create user');
            }
        } catch (error) {
            console.error('Failed to create user:', error);
            showErrorAlert('Error', 'Failed to create user');
        }
    }

    async updateUser(userId, data) {
        try {
            data.user_id = userId;
            const response = await this.makeRequest('edit_user', data);
            if (response.success) {
                showSuccessAlert('Success', 'User updated successfully');
                this.loadUsers();
                this.closeUserModal();
            } else {
                showErrorAlert('Error', response.message || 'Failed to update user');
            }
        } catch (error) {
            console.error('Failed to update user:', error);
            showErrorAlert('Error', 'Failed to update user');
        }
    }

    async deleteUser(userId) {
        try {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                const response = await this.makeRequest('delete_user', { user_id: userId });
                if (response.success) {
                    showSuccessAlert('Deleted!', 'User has been deleted.');
                    this.loadUsers();
                } else {
                    showErrorAlert('Error', response.message || 'Failed to delete user');
                }
            }
        } catch (error) {
            console.error('Failed to delete user:', error);
            showErrorAlert('Error', 'Failed to delete user');
        }
    }

    async loadUserData(userId) {
        try {
            const response = await this.makeRequest('get_user_details', { user_id: userId });
            if (response.success) {
                const user = response.data.user;
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username;
                document.getElementById('fullName').value = user.full_name;
                document.getElementById('email').value = user.email || '';
                document.getElementById('role').value = user.role;
                document.getElementById('campusId').value = user.campus_id || '';
                document.getElementById('officeId').value = user.office_id || '';
                document.getElementById('isActive').checked = user.is_active == 1;
                document.getElementById('activeField').style.display = 'block';

                // Show/hide approver fields based on role
                this.toggleApproverFieldsVisibility(user.role);

                // Set approver status
                const isApprover = user.approver_id ? true : false;
                document.getElementById('isApprover').checked = isApprover;
                document.getElementById('approvalLevel').value = user.approval_level || 'primary';
                document.getElementById('approvalLevelField').style.display = isApprover ? 'block' : 'none';
            } else {
                showErrorAlert('Error', response.message || 'Failed to load user data');
            }
        } catch (error) {
            console.error('Failed to load user data:', error);
            showErrorAlert('Error', 'Failed to load user data');
        }
    }

    async openUserModal(userId = null) {
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        const title = document.getElementById('modalTitle');
        const activeField = document.getElementById('activeField');
        const defaultPasswordInfo = document.getElementById('defaultPasswordInfo');

        form.reset();
        activeField.style.display = 'none';
        document.getElementById('approvalLevelField').style.display = 'none';

        // Load offices for the dropdown (wait for it to complete)
        await this.loadOffices();

        if (userId) {
            title.textContent = 'Edit User';
            // Hide default password info when editing
            if (defaultPasswordInfo) defaultPasswordInfo.style.display = 'none';
            await this.loadUserData(userId);
        } else {
            title.textContent = 'Add User';
            document.getElementById('userId').value = '';
            document.getElementById('isApprover').checked = false;
            document.getElementById('approvalLevel').value = 'primary';
            // Hide approver fields by default (will show when office role is selected)
            this.toggleApproverFieldsVisibility('');
            // Show default password info when adding new user
            if (defaultPasswordInfo) defaultPasswordInfo.style.display = 'block';
        }

        modal.classList.remove('hidden');
    }

    closeUserModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    saveUser() {
        const form = document.getElementById('userForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Handle checkboxes (unchecked checkboxes don't appear in FormData)
        data.is_active = data.is_active === 'on' ? 1 : 0;
        data.is_approver = document.getElementById('isApprover').checked ? 1 : 0;

        // Validate campus and office selection for approvers
        if (data.is_approver && data.role === 'office') {
            if (!data.campus_id || data.campus_id === '') {
                showErrorAlert('Validation Error', 'Campus must be selected for office approvers');
                return;
            }
            if (!data.office_id || data.office_id === '') {
                showErrorAlert('Validation Error', 'Office must be selected for office approvers');
                return;
            }
        }

        // Get userId
        const userId = data.user_id;

        // Log the data being sent
        console.log('Form data being sent:', data);
        console.log('User ID:', userId);
        console.log('Is Approver:', data.is_approver);

        if (userId) {
            this.updateUser(userId, data);
        } else {
            this.createUser(data);
        }
    }

    applyFilters() {
        const filters = {
            search: document.getElementById('searchInput')?.value || '',
            role: document.getElementById('roleFilter')?.value || '',
            campus_id: document.getElementById('campusFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || ''
        };
        this.loadUsers(filters);
    }

    clearFilters() {
        ['searchInput', 'roleFilter', 'campusFilter', 'statusFilter'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        this.loadUsers();
    }

    bindUserActionEvents() {
        // Remove existing event listeners by cloning and replacing elements
        // This prevents duplicate event listeners
        
        // Add user button
        const addBtn = document.getElementById('addUserBtn');
        if (addBtn && !addBtn.dataset.bound) {
            addBtn.addEventListener('click', () => this.openUserModal());
            addBtn.dataset.bound = 'true';
        }

        // Edit buttons
        document.querySelectorAll('.edit-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = e.currentTarget.dataset.userId;
                this.openUserModal(userId);
            });
        });

        // Delete buttons
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = e.currentTarget.dataset.userId;
                this.deleteUser(userId);
            });
        });

        // Modal close
        const closeModal = document.getElementById('closeModal');
        if (closeModal && !closeModal.dataset.bound) {
            closeModal.addEventListener('click', () => this.closeUserModal());
            closeModal.dataset.bound = 'true';
        }

        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn && !cancelBtn.dataset.bound) {
            cancelBtn.addEventListener('click', () => this.closeUserModal());
            cancelBtn.dataset.bound = 'true';
        }

        // Form submit
        const userForm = document.getElementById('userForm');
        if (userForm && !userForm.dataset.bound) {
            userForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveUser();
            });
            userForm.dataset.bound = 'true';
        }

        // Campus change event - update offices dropdown
        const campusSelect = document.getElementById('campusId');
        if (campusSelect && !campusSelect.dataset.boundOfficeFilter) {
            campusSelect.addEventListener('change', () => {
                console.log('Campus changed, reloading offices for campus:', campusSelect.value);
                this.populateOfficeDropdown();
            });
            campusSelect.dataset.boundOfficeFilter = 'true';
        }

        // Role change event - show/hide approver section
        const roleSelect = document.getElementById('role');
        if (roleSelect && !roleSelect.dataset.boundApproverVisibility) {
            roleSelect.addEventListener('change', (e) => {
                this.toggleApproverFieldsVisibility(e.target.value);
            });
            roleSelect.dataset.boundApproverVisibility = 'true';
        }

        // Approver checkbox
        const isApproverCheckbox = document.getElementById('isApprover');
        if (isApproverCheckbox && !isApproverCheckbox.dataset.boundApprover) {
            isApproverCheckbox.addEventListener('change', (e) => {
                const approvalLevelField = document.getElementById('approvalLevelField');
                approvalLevelField.style.display = e.target.checked ? 'block' : 'none';
            });
            isApproverCheckbox.dataset.boundApprover = 'true';
        }

        // Filters
        const applyBtn = document.getElementById('applyFilters');
        if (applyBtn && !applyBtn.dataset.bound) {
            applyBtn.addEventListener('click', () => this.applyFilters());
            applyBtn.dataset.bound = 'true';
        }

        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn && !clearBtn.dataset.bound) {
            clearBtn.addEventListener('click', () => this.clearFilters());
            clearBtn.dataset.bound = 'true';
        }

        // Search debounce
        const searchInput = document.getElementById('searchInput');
        if (searchInput && !searchInput.dataset.bound) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.applyFilters(), 500);
            });
            searchInput.dataset.bound = 'true';
        }

        // View assets buttons
        document.querySelectorAll('.view-assets').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = e.currentTarget.dataset.userId;
                const userName = e.currentTarget.dataset.userName;
                this.viewUserAssets(userId, userName);
            });
        });
    }

    // Asset Management Methods
    async viewUserAssets(userId, userName) {
        const modal = document.getElementById('userAssetsModal');
        const title = document.getElementById('assetsModalTitle');
        const tbody = document.getElementById('userAssetsTableBody');
        const loading = document.getElementById('loadingAssets');
        const noAssets = document.getElementById('noAssetsMessage');

        title.textContent = `Assets assigned to ${userName}`;
        tbody.innerHTML = '';
        loading.classList.remove('hidden');
        noAssets.classList.add('hidden');
        modal.classList.remove('hidden');

        try {
            const response = await this.makeRequest('get_user_details', { user_id: userId });
            if (response.success && response.data.assets) {
                loading.classList.add('hidden');
                const assets = response.data.assets;

                if (assets.length === 0) {
                    noAssets.classList.remove('hidden');
                    return;
                }

                assets.forEach(asset => {
                    const row = document.createElement('tr');
                    row.classList.add('cursor-pointer', 'hover:bg-gray-50');
                    row.dataset.assetId = asset.id;
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${asset.asset_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${asset.serial_number || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${asset.category_name || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${this.getStatusBadgeClass(asset.status)}">
                                ${asset.status}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱${parseFloat(asset.value || 0).toLocaleString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900 view-asset-detail" data-asset-id="${asset.id}">
                                <i class="fas fa-eye mr-1"></i>View
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Bind click events to asset rows
                this.bindAssetRowEvents();
            } else {
                loading.classList.add('hidden');
                showErrorAlert('Error', response.message || 'Failed to load user assets');
            }
        } catch (error) {
            loading.classList.add('hidden');
            console.error('Failed to load user assets:', error);
            showErrorAlert('Error', 'Failed to load user assets');
        }

        // Bind close button
        this.bindAssetModalEvents();
    }

    getStatusBadgeClass(status) {
        const classes = {
            'Active': 'bg-green-100 text-green-800',
            'Available': 'bg-blue-100 text-blue-800',
            'Maintenance': 'bg-yellow-100 text-yellow-800',
            'Retired': 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }

    bindAssetRowEvents() {
        document.querySelectorAll('.view-asset-detail').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const assetId = e.currentTarget.dataset.assetId;
                this.viewAssetDetail(assetId);
            });
        });
    }

    async viewAssetDetail(assetId) {
        const modal = document.getElementById('assetDetailModal');
        const content = document.getElementById('assetDetailContent');

        modal.classList.remove('hidden');
        content.innerHTML = '<p class="text-center text-gray-500">Loading...</p>';

        try {
            const response = await this.makeRequest('get_asset_details', { asset_id: assetId });
            if (response.success && response.data) {
                const asset = response.data;
                content.innerHTML = `
                    <div class="border-b pb-2">
                        <p class="text-xs text-gray-500">Asset Name</p>
                        <p class="text-sm font-medium text-gray-900">${asset.asset_name}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-xs text-gray-500">Serial Number</p>
                        <p class="text-sm text-gray-900">${asset.serial_number || 'N/A'}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-xs text-gray-500">Category</p>
                        <p class="text-sm text-gray-900">${asset.category_name || 'N/A'}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-xs text-gray-500">Campus</p>
                        <p class="text-sm text-gray-900">${asset.campus_name || 'N/A'}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-xs text-gray-500">Status</p>
                        <p class="text-sm text-gray-900">${asset.status}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-xs text-gray-500">Value</p>
                        <p class="text-sm text-gray-900">₱${parseFloat(asset.value || 0).toLocaleString()}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-xs text-gray-500">Assigned To</p>
                        <p class="text-sm text-gray-900">${asset.assigned_to || 'Unassigned'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Description</p>
                        <p class="text-sm text-gray-900">${asset.description || 'No description'}</p>
                    </div>
                `;

                // Store asset ID for transfer/remove actions
                document.getElementById('transferAssetBtn').dataset.assetId = assetId;
                document.getElementById('removeAssetBtn').dataset.assetId = assetId;
                document.getElementById('transferAssetBtn').dataset.assetName = asset.asset_name;
            } else {
                content.innerHTML = '<p class="text-center text-red-500">Failed to load asset details</p>';
            }
        } catch (error) {
            console.error('Failed to load asset details:', error);
            content.innerHTML = '<p class="text-center text-red-500">Failed to load asset details</p>';
        }

        this.bindAssetDetailEvents();
    }

    bindAssetModalEvents() {
        const closeBtn = document.getElementById('closeAssetsModal');
        if (closeBtn && !closeBtn.dataset.bound) {
            closeBtn.addEventListener('click', () => {
                document.getElementById('userAssetsModal').classList.add('hidden');
            });
            closeBtn.dataset.bound = 'true';
        }
    }

    bindAssetDetailEvents() {
        const closeBtn = document.getElementById('closeAssetDetailModal');
        if (closeBtn && !closeBtn.dataset.bound) {
            closeBtn.addEventListener('click', () => {
                document.getElementById('assetDetailModal').classList.add('hidden');
            });
            closeBtn.dataset.bound = 'true';
        }

        const transferBtn = document.getElementById('transferAssetBtn');
        if (transferBtn && !transferBtn.dataset.bound) {
            transferBtn.addEventListener('click', (e) => {
                const assetId = e.currentTarget.dataset.assetId;
                const assetName = e.currentTarget.dataset.assetName;
                this.openTransferModal(assetId, assetName);
            });
            transferBtn.dataset.bound = 'true';
        }

        const removeBtn = document.getElementById('removeAssetBtn');
        if (removeBtn && !removeBtn.dataset.bound) {
            removeBtn.addEventListener('click', (e) => {
                const assetId = e.currentTarget.dataset.assetId;
                this.removeAsset(assetId);
            });
            removeBtn.dataset.bound = 'true';
        }
    }

    async openTransferModal(assetId, assetName) {
        const modal = document.getElementById('transferAssetModal');
        const select = document.getElementById('targetUserId');
        
        document.getElementById('transferAssetId').value = assetId;
        document.getElementById('transferAssetName').textContent = assetName;
        
        // Load active users for transfer
        try {
            const response = await this.makeRequest('get_users', { status: '1' });
            if (response.success) {
                select.innerHTML = '<option value="">Select User</option>';
                response.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.full_name} (${user.role})`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Failed to load users:', error);
        }

        // Hide asset detail modal
        document.getElementById('assetDetailModal').classList.add('hidden');
        modal.classList.remove('hidden');

        this.bindTransferModalEvents();
    }

    bindTransferModalEvents() {
        const closeBtn = document.getElementById('closeTransferModal');
        if (closeBtn && !closeBtn.dataset.bound) {
            closeBtn.addEventListener('click', () => {
                document.getElementById('transferAssetModal').classList.add('hidden');
            });
            closeBtn.dataset.bound = 'true';
        }

        const cancelBtn = document.getElementById('cancelTransferBtn');
        if (cancelBtn && !cancelBtn.dataset.bound) {
            cancelBtn.addEventListener('click', () => {
                document.getElementById('transferAssetModal').classList.add('hidden');
            });
            cancelBtn.dataset.bound = 'true';
        }

        const form = document.getElementById('transferAssetForm');
        if (form && !form.dataset.bound) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.transferAsset();
            });
            form.dataset.bound = 'true';
        }
    }

    async transferAsset() {
        const assetId = document.getElementById('transferAssetId').value;
        const targetUserId = document.getElementById('targetUserId').value;

        if (!targetUserId) {
            showErrorAlert('Error', 'Please select a user to transfer to');
            return;
        }

        try {
            const response = await this.makeRequest('transfer_user_asset', {
                asset_id: assetId,
                target_user_id: targetUserId
            });

            if (response.success) {
                showSuccessAlert('Success', 'Asset transferred successfully');
                document.getElementById('transferAssetModal').classList.add('hidden');
                document.getElementById('userAssetsModal').classList.add('hidden');
                this.loadUsers(); // Refresh user list
            } else {
                showErrorAlert('Error', response.message || 'Failed to transfer asset');
            }
        } catch (error) {
            console.error('Failed to transfer asset:', error);
            showErrorAlert('Error', 'Failed to transfer asset');
        }
    }

    async removeAsset(assetId) {
        try {
            const result = await Swal.fire({
                title: 'Remove Asset?',
                text: 'This will return the asset to the main Assets section.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, remove it!'
            });

            if (result.isConfirmed) {
                const response = await this.makeRequest('remove_user_asset', { asset_id: assetId });
                if (response.success) {
                    showSuccessAlert('Success', 'Asset removed and returned to Assets section');
                    document.getElementById('assetDetailModal').classList.add('hidden');
                    document.getElementById('userAssetsModal').classList.add('hidden');
                    this.loadUsers(); // Refresh user list
                } else {
                    showErrorAlert('Error', response.message || 'Failed to remove asset');
                }
            }
        } catch (error) {
            console.error('Failed to remove asset:', error);
            showErrorAlert('Error', 'Failed to remove asset');
        }
    }

    // Original methods (placeholders - add actual implementations if needed)
    loadDashboardData() {
        // Load dashboard data
        console.log('Loading dashboard data...');
    }

    initializeCharts() {
        // Initialize charts
        console.log('Initializing charts...');
    }

    showPage(page) {
        // Show page logic
        console.log(`Showing page: ${page}`);
    }

    toggleSidebar() {
        // Toggle sidebar logic
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('hidden');
            overlay.classList.toggle('hidden');
        }
    }

    bindEvents() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const href = item.getAttribute('href');
                if (href) window.location.href = href;
            });
        });

        // Mobile sidebar
        document.getElementById('openSidebar')?.addEventListener('click', () => this.toggleSidebar());
        document.getElementById('closeSidebar')?.addEventListener('click', () => this.toggleSidebar());
        document.getElementById('sidebarOverlay')?.addEventListener('click', () => this.toggleSidebar());
    }

    // AJAX helper - updated to point to correct path for user actions
    async makeRequest(action, data = {}) {
        const userActions = ['get_users', 'add_user', 'edit_user', 'delete_user', 'get_user_details', 
                             'remove_user_asset', 'transfer_user_asset', 'get_asset_details'];
        const url = userActions.includes(action)
            ? 'actions/user_actions.php' 
            : 'actions/data_retrieval.php';  // Assume data_retrieval for other actions

        const formData = new FormData();
        formData.append('action', action);
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });
        if (this.csrfToken) {
            formData.append('csrf_token', this.csrfToken);
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }
}

// Global functions if needed
function showSuccessAlert(title, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert(`${title}: ${message}`);
    }
}

function showErrorAlert(title, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message
        });
    } else {
        alert(`${title}: ${message}`);
    }
}
