<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Worker Status - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-envelope text-blue-600"></i>
                Email Worker Status
            </h1>
            <p class="text-gray-600">Monitor and control the email queue background worker</p>
        </div>

        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Worker Status</h2>

            <div id="status-container" class="space-y-4">
                <div class="flex items-center justify-center py-8">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    <span class="ml-3 text-gray-600">Loading status...</span>
                </div>
            </div>
        </div>

        <!-- Queue Statistics -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Queue Statistics</h2>

            <div id="stats-container" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Stats will be loaded here -->
            </div>
        </div>

        <!-- Controls -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Controls</h2>

            <div class="flex flex-wrap gap-4">
                <button onclick="startWorker()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-play mr-2"></i>Start Worker
                </button>
                <button onclick="stopWorker()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-stop mr-2"></i>Stop Worker
                </button>
                <button onclick="refreshStatus()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-sync mr-2"></i>Refresh Status
                </button>
                <button onclick="autoStart()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-bolt mr-2"></i>Auto Start
                </button>
            </div>

            <div id="message-container" class="mt-4"></div>
        </div>

        <!-- Back Link -->
        <div class="mt-6 text-center">
            <a href="custodian_dashboard.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <script>
    // Auto-refresh every 10 seconds
    let autoRefreshInterval;

    function showMessage(message, type = 'info') {
        const container = document.getElementById('message-container');
        const colors = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
        };

        container.innerHTML = `
            <div class="border-l-4 p-4 ${colors[type] || colors.info}">
                <p>${message}</p>
            </div>
        `;

        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    function updateStatusDisplay(data) {
        const container = document.getElementById('status-container');
        const statusColor = data.worker_running ? 'green' : 'red';
        const statusIcon = data.worker_running ? 'fa-check-circle' : 'fa-times-circle';
        const statusText = data.worker_running ? 'Running' : 'Stopped';

        container.innerHTML = `
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="fas ${statusIcon} text-4xl text-${statusColor}-600"></i>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-800">${statusText}</p>
                        <p class="text-sm text-gray-600">${data.message}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Last updated</p>
                    <p class="text-sm font-semibold text-gray-700">${new Date().toLocaleTimeString()}</p>
                </div>
            </div>
        `;
    }

    function updateStatsDisplay(stats) {
        const container = document.getElementById('stats-container');

        container.innerHTML = `
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-3xl font-bold text-blue-600">${stats.pending || 0}</div>
                <div class="text-sm text-gray-600 mt-1">Pending</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-3xl font-bold text-green-600">${stats.sent || 0}</div>
                <div class="text-sm text-gray-600 mt-1">Sent</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-3xl font-bold text-red-600">${stats.failed || 0}</div>
                <div class="text-sm text-gray-600 mt-1">Failed</div>
            </div>
        `;

        if (stats.oldest_pending) {
            const oldestDate = new Date(stats.oldest_pending);
            const waitTime = Math.floor((new Date() - oldestDate) / 1000 / 60);

            if (waitTime > 5) {
                container.innerHTML += `
                    <div class="col-span-full bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                        <span class="ml-2 text-sm text-yellow-800">
                            Oldest email waiting ${waitTime} minutes
                        </span>
                    </div>
                `;
            }
        }
    }

    async function refreshStatus() {
        try {
            const response = await fetch('/AMS-REQ/api/email_worker_manager.php?action=status');
            const data = await response.json();

            if (data.success) {
                updateStatusDisplay(data);
                if (data.queue_stats) {
                    updateStatsDisplay(data.queue_stats);
                }
            } else {
                showMessage(data.message || 'Failed to get status', 'error');
            }
        } catch (error) {
            showMessage('Error connecting to server: ' + error.message, 'error');
        }
    }

    async function startWorker() {
        try {
            const response = await fetch('/AMS-REQ/api/email_worker_manager.php?action=start');
            const data = await response.json();

            showMessage(data.message, data.success ? 'success' : 'error');

            if (data.success) {
                setTimeout(refreshStatus, 1000);
            }
        } catch (error) {
            showMessage('Error: ' + error.message, 'error');
        }
    }

    async function stopWorker() {
        try {
            const response = await fetch('/AMS-REQ/api/email_worker_manager.php?action=stop');
            const data = await response.json();

            showMessage(data.message, data.success ? 'success' : 'error');

            if (data.success) {
                setTimeout(refreshStatus, 1000);
            }
        } catch (error) {
            showMessage('Error: ' + error.message, 'error');
        }
    }

    async function autoStart() {
        try {
            const response = await fetch('/AMS-REQ/api/email_worker_manager.php?action=auto_start');
            const data = await response.json();

            showMessage(data.message, data.success ? 'success' : 'info');

            setTimeout(refreshStatus, 1000);
        } catch (error) {
            showMessage('Error: ' + error.message, 'error');
        }
    }

    // Initial load and auto-refresh
    refreshStatus();
    autoRefreshInterval = setInterval(refreshStatus, 10000); // Refresh every 10 seconds
    </script>
</body>
</html>
