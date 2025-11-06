// Admin Dashboard JavaScript
// Handles AJAX requests, UI interactions, and data visualization

class AdminDashboard {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        this.currentPage = 'dashboard';
        this.charts = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboardData();
        this.initializeCharts();
        this.loadCampuses();
    }

    async loadCampuses() {
        try {
            const response = await this.makeRequest('get_campuses');
            if (response.success) {
                this.campuses = response.data;
            }
        } catch (error) {
            console.error('Failed to load campuses:', error);
        }
    }

    bindEvents() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.currentTarget.getAttribute('onclick').match(/showPage\('(\w+)'\)/)?.[1];
                if (page) this.showPage(page);
            });
        });

        // Mobile sidebar
        document.getElementById('openSidebar')?.addEventListener('click', () => this.toggleSidebar());
        document.getElementById('closeSidebar')?.addEventListener('click', () => this.toggleSidebar());
        document.getElementById('sidebarOverlay')?.addEventListener('click', () => this.toggleSidebar());

        // Profile dropdown
        document.getElementById('profileButton')?.addEventListener('click', (e) => this.toggleProfileDropdown(e));

        // User management events
        document.getElementById('openUserModal')?.addEventListener('click', () => this.openUserModal());
        document.getElementById('userSearch')?.addEventListener('input', () => this.loadUsers());
        document.getElementById('userRoleFilter')?.addEventListener('change', () => this.loadUsers());
        document.getElementById('userCampusFilter')?.addEventListener('change', () => this.loadUsers());
        document.getElementById('userStatusFilter')?.addEventListener('change', () => this.loadUsers());

        // Modal close events
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });

        // Form submissions
        document.querySelectorAll('.admin-form').forEach(form => {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        });
    }

    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', this.csrfToken);

        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }

    showPage(page) {
        // Hide all pages
        document.querySelectorAll('.page-content').forEach(p => p.style.display = 'none');

        // Show selected page
        const pageElement = document.getElementById(page + 'Page');
        if (pageElement) {
            pageElement.style.display = 'block';
        }

        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active', 'bg-blue-50', 'text-blue-700');
        });
        const activeNav = document.querySelector(`[onclick="showPage('${page}')"]`);
        if (activeNav) {
            activeNav.classList.add('active', 'bg-blue-50', 'text-blue-700');
        }

        // Update page title
        const titles = {
            dashboard: 'Dashboard',
            assets: 'Assets',
            users: 'Users',
            requests: 'Requests',
            campuses: 'Campuses',
            categories: 'Categories',
            reports: 'Reports',
            invoices: 'Invoices'
        };
        document.getElementById('pageTitle').textContent = titles[page] || 'Dashboard';

        this.currentPage = page;

        // Load page data
        switch (page) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'users':
                this.loadUsers();
                break;
            case 'assets':
                this.loadAssets();
                break;
            case 'requests':
                this.loadRequests();
                break;
            case 'campuses':
                this.loadCampusesList();
                break;
            case 'categories':
                this.loadCategories();
                break;
            case 'reports':
                this.loadReports();
                break;
            case 'invoices':
                this.loadInvoices();
                break;
        }
    }

    async loadDashboardData() {
        try {
            const [stats, activities] = await Promise.all([
                this.makeRequest('get_admin_stats'),
                this.makeRequest('get_recent_activities')
            ]);

            if (stats.success) {
                this.updateDashboardStats(stats.data);
            }

            if (activities.success) {
                this.renderRecentActivities(activities.data);
            }
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
        }
    }

    updateDashboardStats(stats) {
        document.getElementById('dashTotalAssets').textContent = stats.total_assets || 0;
        document.getElementById('dashActiveUsers').textContent = stats.active_users || 0;
        document.getElementById('dashPendingRequests').textContent = stats.pending_requests || 0;
        document.getElementById('dashTotalValue').textContent = '₱' + (stats.total_value || 0).toLocaleString();
    }

    renderRecentActivities(activities) {
        const container = document.getElementById('recentActivities');
        if (!container) return;

        if (activities.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-4">No recent activities</p>';
            return;
        }

        container.innerHTML = activities.map(activity => `
            <div class="flex items-start space-x-3 py-3 border-b border-gray-100 last:border-b-0">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900">${activity.description || ''}</p>
                    <p class="text-xs text-gray-500">${this.formatDate(activity.created_at)}</p>
                </div>
            </div>
        `).join('');
    }

    initializeCharts() {
        // Initialize charts if Chart.js is loaded
        if (typeof Chart !== 'undefined') {
            this.loadChartData();
        }
    }

    async loadChartData() {
        try {
            const response = await this.makeRequest('get_admin_stats');
            if (response.success) {
                this.renderCharts(response.data);
            }
        } catch (error) {
            console.error('Failed to load chart data:', error);
        }
    }

    renderCharts(data) {
        // Campus distribution chart
        const campusChart = document.getElementById('campusChart');
        if (campusChart && data.assets_by_campus) {
            new Chart(campusChart, {
                type: 'doughnut',
                data: {
                    labels: data.assets_by_campus.map(c => c.campus_name),
                    datasets: [{
                        data: data.assets_by_campus.map(c => c.count),
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    // User Management Functions
    async loadUsers() {
        try {
            const filters = {
                search: document.getElementById('userSearch')?.value || '',
                role: document.getElementById('userRoleFilter')?.value || '',
                campus_id: document.getElementById('userCampusFilter')?.value || '',
                status: document.getElementById('userStatusFilter')?.value || ''
            };

            const response = await this.makeRequest('get_staff_with_assets', { filters: JSON.stringify(filters) });
            if (response.success) {
                this.renderUsersTable(response.data);
            }
        } catch (error) {
            console.error('Failed to load users:', error);
            this.showError('Failed to load users');
        }
    }

    renderUsersTable(users) {
        const tbody = document.querySelector('#usersTable tbody');
        const showing = document.getElementById('usersShowing');
        const total = document.getElementById('usersTotal');

        if (!tbody) return;

        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found</td></tr>';
            if (showing) showing.textContent = '0';
            if (total) total.textContent = '0';
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-sm font-medium text-white">${(user.full_name || user.username).charAt(0).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${user.full_name || user.username}</div>
                            <div class="text-sm text-gray-500">${user.email}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${user.role === 'staff' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">
                        ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${user.campus_name || 'N/A'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${user.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(user.created_at)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="adminDashboard.viewUserDetails(${user.id})" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                    <button onclick="adminDashboard.editUser(${user.id})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                    <button onclick="adminDashboard.deleteUser(${user.id})" class="text-red-600 hover:text-red-900">Delete</button>
                </td>
            </tr>
        `).join('');

        if (showing) showing.textContent = users.length;
        if (total) total.textContent = users.length; // For now, assuming no pagination
    }

    async viewUserDetails(userId) {
        try {
            const response = await this.makeRequest('get_user_details', { user_id: userId });
            if (response.success) {
                this.showUserDetailsModal(response.data);
            }
        } catch (error) {
            console.error('Failed to load user details:', error);
            this.showError('Failed to load user details');
        }
    }

    showUserDetailsModal(data) {
        const { user, assets, asset_stats, recent_activities } = data;

        // Update user info
        document.getElementById('detailFullName').textContent = user.full_name || user.username;
        document.getElementById('detailUsername').textContent = user.username;
        document.getElementById('detailEmail').textContent = user.email || 'N/A';
        document.getElementById('detailRole').textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
        document.getElementById('detailCampus').textContent = user.campus_name || 'N/A';
        document.getElementById('detailStatus').textContent = user.is_active ? 'Active' : 'Inactive';
        document.getElementById('detailCreated').textContent = this.formatDate(user.created_at);
        document.getElementById('detailLastLogin').textContent = user.last_login ? this.formatDate(user.last_login) : 'Never';

        // Update asset stats
        const statsContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.gap-6');
        if (statsContainer) {
            // This would be the asset stats cards, but since it's not in the modal, perhaps update separately
        }

        // Update assigned assets
        const assetsContainer = document.getElementById('userAssets');
        if (assetsContainer) {
            if (assets.length === 0) {
                assetsContainer.innerHTML = '<p class="text-gray-500">No assets assigned</p>';
            } else {
                assetsContainer.innerHTML = assets.map(asset => `
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">${asset.asset_name}</p>
                            <p class="text-xs text-gray-500">${asset.category_name} • ${asset.campus_name}</p>
                        </div>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${asset.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                            ${asset.status}
                        </span>
                    </div>
                `).join('');
            }
        }

        // Show modal
        document.getElementById('userDetailModal').style.display = 'block';
    }

    openUserModal(userId = null) {
        const modal = document.getElementById('userModal');
        const title = document.getElementById('userModalTitle');
        const form = modal.querySelector('form');
        const actionInput = form.querySelector('input[name="action"]');

        if (userId) {
            title.textContent = 'Edit User';
            actionInput.value = 'update_staff_account';
            // Load user data for editing
            this.loadUserForEdit(userId);
        } else {
            title.textContent = 'Add New User';
            actionInput.value = 'create_staff_account';
            form.reset();
        }

        modal.style.display = 'block';
    }

    async loadUserForEdit(userId) {
        try {
            const response = await this.makeRequest('get_user_details', { user_id: userId });
            if (response.success) {
                const user = response.data.user;
                const form = document.querySelector('#userModal form');

                // Split full_name into first and last name
                const nameParts = (user.full_name || '').split(' ');
                const firstName = nameParts[0] || '';
                const lastName = nameParts.slice(1).join(' ') || '';

                form.user_id.value = user.id;
                form.first_name.value = firstName;
                form.last_name.value = lastName;
                form.username.value = user.username;
                form.email.value = user.email || '';
                form.role.value = user.role;
                form.campus_id.value = user.campus_id || '';
                form.is_active.value = user.is_active ? '1' : '0';
            }
        } catch (error) {
            console.error('Failed to load user for edit:', error);
            this.showError('Failed to load user data');
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const action = formData.get('action');

        try {
            const data = Object.fromEntries(formData);
            const response = await this.makeRequest(action, data);

            if (response.success) {
                this.showSuccess(response.message || 'Operation completed successfully');
                this.closeModal();
                if (action.includes('user') || action.includes('staff')) {
                    this.loadUsers();
                }
            } else {
                this.showError(response.message || 'Operation failed');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showError('An error occurred');
        }
    }

    async editUser(userId) {
        this.openUserModal(userId);
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await this.makeRequest('delete_staff_account', { user_id: userId });
            if (response.success) {
                this.showSuccess('User deleted successfully');
                this.loadUsers();
            } else {
                this.showError(response.message || 'Failed to delete user');
            }
        } catch (error) {
            console.error('Failed to delete user:', error);
            this.showError('Failed to delete user');
        }
    }

    // Utility functions
    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    toggleProfileDropdown(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('profileDropdown');
        dropdown.classList.toggle('hidden');
    }

    closeModal() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    }

    showInfo(message) {
        Swal.fire({
            icon: 'info',
            title: 'Info',
            text: message
        });
    }

    // Placeholder functions for other pages
    loadAssets() { /* Implement asset loading */ }
    loadRequests() { /* Implement request loading */ }
    loadCampusesList() { /* Implement campus loading */ }
    loadCategories() { /* Implement category loading */ }
    loadReports() { /* Implement report loading */ }
    loadInvoices() { /* Implement invoice loading */ }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminDashboard = new AdminDashboard();
});

// Close profile dropdown when clicking outside
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('profileDropdown');
    const button = document.getElementById('profileButton');
    if (dropdown && button && !dropdown.contains(e.target) && !button.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
