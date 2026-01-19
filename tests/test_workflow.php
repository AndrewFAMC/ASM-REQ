<?php
/**
 * Complete Approval Workflow Testing Page
 * Tests the entire asset request and approval process
 */

require_once 'config.php';

// Require admin access
if (!isLoggedIn() || !hasRole('admin')) {
    die('Access denied. Admin access required to run tests.');
}

$user = getUserInfo();
$testResults = [];

// Test Configuration
$testConfig = [
    'test_employee_id' => null,
    'test_custodian_id' => null,
    'test_admin_id' => null,
    'test_asset_id' => null,
    'test_request_id' => null,
    'campus_id' => 1
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Workflow Test - HCC AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .test-section { margin-bottom: 2rem; }
        .test-result { padding: 1rem; margin: 0.5rem 0; border-radius: 0.5rem; }
        .test-pass { background: #d1fae5; border-left: 4px solid #10b981; }
        .test-fail { background: #fee2e2; border-left: 4px solid #ef4444; }
        .test-info { background: #dbeafe; border-left: 4px solid #3b82f6; }
        .test-warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Approval Workflow Testing Dashboard</h1>
            <p class="text-gray-600">Comprehensive testing for the asset request and approval system</p>
            <div class="mt-4 flex space-x-3">
                <button onclick="runAllTests()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-play mr-2"></i>Run All Tests
                </button>
                <button onclick="resetTestData()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-trash mr-2"></i>Clean Test Data
                </button>
                <a href="employee_dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Test Results Container -->
        <div id="testResults" class="space-y-6">
            <!-- Results will be displayed here -->
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Access Links</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <a href="request_asset.php" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-plus-circle mr-2"></i>Request Asset Page
                </a>
                <a href="my_requests.php" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-list mr-2"></i>My Requests Page
                </a>
                <a href="custodian/approve_requests.php" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-user-check mr-2"></i>Custodian Approval
                </a>
                <a href="admin/approve_requests.php" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-user-shield mr-2"></i>Admin Approval
                </a>
                <a href="notifications_new.php" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-bell mr-2"></i>Notifications
                </a>
                <a href="test_notifications.php" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-vial mr-2"></i>Test Notifications
                </a>
            </div>
        </div>
    </div>

    <script>
    const csrfToken = '<?= generateCSRFToken() ?>';

    async function runAllTests() {
        const resultsContainer = document.getElementById('testResults');
        resultsContainer.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i><p class="mt-4 text-gray-600">Running tests...</p></div>';

        const tests = [
            { name: 'Database Schema Check', func: testDatabaseSchema },
            { name: 'User Roles Verification', func: testUserRoles },
            { name: 'Asset Availability Check', func: testAssetAvailability },
            { name: 'Notification System', func: testNotificationSystem },
            { name: 'API Endpoints', func: testAPIEndpoints },
            { name: 'Request Creation Flow', func: testRequestCreation },
            { name: 'Approval Workflow', func: testApprovalWorkflow }
        ];

        let allResults = '';

        for (const test of tests) {
            try {
                const result = await test.func();
                allResults += formatTestResult(test.name, result);
            } catch (error) {
                allResults += formatTestResult(test.name, {
                    status: 'fail',
                    message: `Test error: ${error.message}`,
                    details: []
                });
            }
        }

        resultsContainer.innerHTML = allResults;
    }

    async function testDatabaseSchema() {
        const response = await fetch('/AMS-REQ/api/test_database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_schema' })
        });

        const data = await response.json();

        return {
            status: data.success ? 'pass' : 'fail',
            message: data.success ? 'All required tables exist' : 'Missing tables detected',
            details: data.details || []
        };
    }

    async function testUserRoles() {
        const response = await fetch('/AMS-REQ/api/test_database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_users' })
        });

        const data = await response.json();

        return {
            status: data.success ? 'pass' : 'warning',
            message: `Found ${data.employee_count || 0} employees, ${data.custodian_count || 0} custodians, ${data.admin_count || 0} admins`,
            details: data.details || []
        };
    }

    async function testAssetAvailability() {
        const response = await fetch('/AMS-REQ/api/test_database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_assets' })
        });

        const data = await response.json();

        return {
            status: data.available_count > 0 ? 'pass' : 'warning',
            message: `Found ${data.available_count || 0} available assets`,
            details: data.details || []
        };
    }

    async function testNotificationSystem() {
        const response = await fetch('/AMS-REQ/api/notifications.php?action=get_count');
        const data = await response.json();

        return {
            status: data.success ? 'pass' : 'fail',
            message: data.success ? `Notification API working (${data.count} unread)` : 'Notification API failed',
            details: [
                `Unread notifications: ${data.count || 0}`,
                `API response time: ${response.headers.get('x-response-time') || 'N/A'}`
            ]
        };
    }

    async function testAPIEndpoints() {
        const endpoints = [
            { url: '/AMS-REQ/api/notifications.php?action=get_count', name: 'Notifications API' },
            { url: '/AMS-REQ/api/requests.php?action=get_pending_requests', name: 'Requests API' }
        ];

        const details = [];
        let allPass = true;

        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint.url);
                const data = await response.json();
                const status = data.success !== false ? '✓' : '✗';
                details.push(`${status} ${endpoint.name}: ${response.status}`);
                if (data.success === false) allPass = false;
            } catch (error) {
                details.push(`✗ ${endpoint.name}: Error - ${error.message}`);
                allPass = false;
            }
        }

        return {
            status: allPass ? 'pass' : 'warning',
            message: `Tested ${endpoints.length} API endpoints`,
            details: details
        };
    }

    async function testRequestCreation() {
        return {
            status: 'info',
            message: 'Manual test required',
            details: [
                '1. Go to "Request Asset" page',
                '2. Select an available asset',
                '3. Fill in quantity, purpose, and return date',
                '4. Submit the request',
                '5. Check if custodian receives notification',
                '6. Verify request appears in "My Requests"'
            ]
        };
    }

    async function testApprovalWorkflow() {
        return {
            status: 'info',
            message: 'Manual workflow test required',
            details: [
                'Step 1: Employee creates request',
                'Step 2: Custodian approves request',
                'Step 3: Admin gives final approval',
                'Step 4: Asset is released to employee',
                'Step 5: Employee receives approval notification',
                '',
                'Check each dashboard to verify the workflow'
            ]
        };
    }

    function formatTestResult(testName, result) {
        const statusClasses = {
            'pass': 'test-pass',
            'fail': 'test-fail',
            'warning': 'test-warning',
            'info': 'test-info'
        };

        const icons = {
            'pass': '<i class="fas fa-check-circle text-green-600"></i>',
            'fail': '<i class="fas fa-times-circle text-red-600"></i>',
            'warning': '<i class="fas fa-exclamation-triangle text-yellow-600"></i>',
            'info': '<i class="fas fa-info-circle text-blue-600"></i>'
        };

        const detailsHtml = result.details && result.details.length > 0
            ? '<ul class="mt-2 ml-6 text-sm space-y-1">' +
              result.details.map(d => `<li class="list-disc">${escapeHtml(d)}</li>`).join('') +
              '</ul>'
            : '';

        return `
            <div class="test-result ${statusClasses[result.status]}">
                <div class="flex items-start">
                    <div class="text-2xl mr-3">${icons[result.status]}</div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">${escapeHtml(testName)}</h3>
                        <p class="text-gray-700 mt-1">${escapeHtml(result.message)}</p>
                        ${detailsHtml}
                    </div>
                </div>
            </div>
        `;
    }

    async function resetTestData() {
        const result = await Swal.fire({
            title: 'Clean Test Data?',
            text: 'This will delete test notifications and requests. Continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clean data',
            confirmButtonColor: '#ef4444'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('/AMS-REQ/api/test_database.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'cleanup_test_data' })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('Cleaned!', 'Test data has been removed', 'success');
                } else {
                    throw new Error(data.message || 'Cleanup failed');
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
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
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Show instructions on load
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('testResults').innerHTML = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="font-semibold text-blue-900 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>Testing Instructions
                </h3>
                <div class="text-blue-800 space-y-2">
                    <p><strong>Automated Tests:</strong> Click "Run All Tests" to check database schema, APIs, and configurations.</p>
                    <p><strong>Manual Workflow Test:</strong></p>
                    <ol class="ml-6 list-decimal space-y-1">
                        <li>Login as an employee and submit a request via "Request Asset"</li>
                        <li>Login as a custodian and approve the request</li>
                        <li>Login as admin and give final approval</li>
                        <li>Check notifications at each step</li>
                        <li>Verify status updates in "My Requests"</li>
                    </ol>
                    <p class="mt-4"><strong>Note:</strong> Ensure you have users with employee, custodian, and admin roles in your database.</p>
                </div>
            </div>
        `;
    });
    </script>
</body>
</html>
