<?php
/**
 * Email Notification Test File
 *
 * This file tests the email notification system to ensure emails are sent correctly.
 * Access this file directly in your browser to send test emails.
 */

// Handle AJAX email sending FIRST before any HTML output
if (isset($_GET['action']) && $_GET['action'] === 'send' && isset($_GET['type'])) {
    // Start output buffering to catch any stray output
    ob_start();

    require_once 'config.php';

    // Clear any output that may have been generated
    ob_clean();

    header('Content-Type: application/json');

    $user = getUserInfo();
    $type = $_GET['type'];
    $success = false;
    $message = '';

    try {
        $recipientEmail = $user['email'];
        $recipientName = $user['full_name'];

        switch ($type) {
            case 'return_reminder':
                $htmlBody = getReturnReminderTemplate($recipientName, 'Test Laptop (HP EliteBook)', date('F j, Y', strtotime('+3 days')), 3);
                $success = sendNotificationEmail($recipientEmail, $recipientName, 'Asset Return Reminder - HCC', $htmlBody);
                break;

            case 'overdue_alert':
                $htmlBody = getOverdueAlertTemplate($recipientName, 'Test Projector (Epson EB-X41)', date('F j, Y', strtotime('-5 days')), 5);
                $success = sendNotificationEmail($recipientEmail, $recipientName, 'URGENT: Overdue Asset - HCC', $htmlBody);
                break;

            case 'approval_request':
                $htmlBody = getApprovalRequestTemplate($recipientName, 'Juan Dela Cruz', 'Test Monitor (Dell 24")', 2, 'TEST-123', 'admin');
                $success = sendNotificationEmail($recipientEmail, $recipientName, 'Approval Request - HCC Asset Management', $htmlBody);
                break;

            case 'request_approved':
                $htmlBody = getRequestApprovedTemplate($recipientName, 'Test Keyboard (Logitech MK270)', 1, 'TEST-456', 'Administrator');
                $success = sendNotificationEmail($recipientEmail, $recipientName, 'Request Approved - HCC Asset Management', $htmlBody);
                break;

            case 'request_rejected':
                $htmlBody = getRequestRejectedTemplate($recipientName, 'Test Mouse (Logitech M185)', 1, 'TEST-789', 'Department Head', 'Insufficient budget for this request at this time.');
                $success = sendNotificationEmail($recipientEmail, $recipientName, 'Request Rejected - HCC Asset Management', $htmlBody);
                break;

            case 'system_alert':
                $htmlBody = getSystemAlertTemplate($recipientName, 'System Maintenance Scheduled', 'The system will undergo scheduled maintenance on ' . date('F j, Y') . ' from 10:00 PM to 11:00 PM. Please save your work and log out before the maintenance window.', null);
                $success = sendNotificationEmail($recipientEmail, $recipientName, 'System Alert - HCC Asset Management', $htmlBody);
                break;

            default:
                throw new Exception('Invalid template type');
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Check error logs.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    ob_end_flush();
    exit;
}

require_once 'config.php';

// For normal page view
$user = getUserInfo();
$userId = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Notification Test - HCC Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-envelope-open-text mr-3 text-blue-600"></i>
                    Email Notification System Test
                </h1>

                <div class="bg-blue-50 border-l-4 border-blue-600 p-4 mb-6">
                    <p class="text-sm text-blue-800">
                        <strong>Current User:</strong> <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Test Email Templates</h2>

                    <!-- Return Reminder Test -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-bell text-yellow-500 mr-2"></i>
                            Return Reminder
                        </h3>
                        <p class="text-sm text-gray-600 mb-3">Tests the return reminder email template (3 days remaining)</p>
                        <button onclick="sendTestEmail('return_reminder')" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm">
                            Send Test Email
                        </button>
                        <span id="result-return_reminder" class="ml-3 text-sm"></span>
                    </div>

                    <!-- Overdue Alert Test -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                            Overdue Alert
                        </h3>
                        <p class="text-sm text-gray-600 mb-3">Tests the overdue alert email template (5 days overdue)</p>
                        <button onclick="sendTestEmail('overdue_alert')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
                            Send Test Email
                        </button>
                        <span id="result-overdue_alert" class="ml-3 text-sm"></span>
                    </div>

                    <!-- Approval Request Test -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-clipboard-check text-blue-500 mr-2"></i>
                            Approval Request
                        </h3>
                        <p class="text-sm text-gray-600 mb-3">Tests the approval request email template</p>
                        <button onclick="sendTestEmail('approval_request')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                            Send Test Email
                        </button>
                        <span id="result-approval_request" class="ml-3 text-sm"></span>
                    </div>

                    <!-- Request Approved Test -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Request Approved
                        </h3>
                        <p class="text-sm text-gray-600 mb-3">Tests the request approved email template</p>
                        <button onclick="sendTestEmail('request_approved')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm">
                            Send Test Email
                        </button>
                        <span id="result-request_approved" class="ml-3 text-sm"></span>
                    </div>

                    <!-- Request Rejected Test -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-times-circle text-red-500 mr-2"></i>
                            Request Rejected
                        </h3>
                        <p class="text-sm text-gray-600 mb-3">Tests the request rejected email template</p>
                        <button onclick="sendTestEmail('request_rejected')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
                            Send Test Email
                        </button>
                        <span id="result-request_rejected" class="ml-3 text-sm"></span>
                    </div>

                    <!-- System Alert Test -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">
                            <i class="fas fa-info-circle text-indigo-500 mr-2"></i>
                            System Alert
                        </h3>
                        <p class="text-sm text-gray-600 mb-3">Tests the system alert email template</p>
                        <button onclick="sendTestEmail('system_alert')" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">
                            Send Test Email
                        </button>
                        <span id="result-system_alert" class="ml-3 text-sm"></span>
                    </div>
                </div>

                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                        Test Instructions
                    </h3>
                    <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                        <li>Click any "Send Test Email" button above to send a test email to your account</li>
                        <li>Check your inbox at <strong><?= htmlspecialchars($user['email']) ?></strong></li>
                        <li>Test emails may take 1-2 minutes to arrive</li>
                        <li>Check your spam/junk folder if you don't see the email</li>
                        <li>Each template has different content to demonstrate various notification types</li>
                    </ul>
                </div>

                <div class="mt-6">
                    <a href="<?= $user['role'] === 'admin' ? 'admin/admin_dashboard.php' : 'employee_dashboard.php' ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function sendTestEmail(templateType) {
        const resultSpan = document.getElementById('result-' + templateType);
        resultSpan.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-600"></i> Sending...';

        fetch('test_email_notification.php?action=send&type=' + templateType)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultSpan.innerHTML = '<i class="fas fa-check-circle text-green-600"></i> <span class="text-green-600">Sent successfully!</span>';
                } else {
                    resultSpan.innerHTML = '<i class="fas fa-exclamation-circle text-red-600"></i> <span class="text-red-600">' + data.message + '</span>';
                }
            })
            .catch(error => {
                resultSpan.innerHTML = '<i class="fas fa-exclamation-circle text-red-600"></i> <span class="text-red-600">Error: ' + error.message + '</span>';
            });
    }
    </script>
</body>
</html>
