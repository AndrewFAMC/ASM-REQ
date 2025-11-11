<?php
/**
 * Send Return Reminders - Cron Job
 *
 * This script should be run daily via a cron job.
 * Sends reminders for assets that are due in 3 days or are overdue.
 *
 * Example cron job command:
 * 0 8 * * * /usr/bin/php /path/to/your/project/send_return_reminders.php
 *
 * REFACTORED: Now uses the new centralized email system
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email/Email.php';

function sendReturnReminders($pdo) {
    echo "Starting return reminder process...\n";

    // Get requests for assets that are due in 3 days or are overdue
    $reminder_days = 3;
    $due_date = date('Y-m-d', strtotime("+$reminder_days days"));

    $sql = "SELECT
                ar.id, ar.expected_return_date,
                a.asset_name, a.serial_number,
                u_staff.full_name as staff_name, u_staff.email as staff_email,
                u_custodian.full_name as custodian_name, u_custodian.email as custodian_email,
                u_admin.full_name as admin_name, u_admin.email as admin_email,
                c.campus_name
            FROM asset_requests ar
            JOIN assets a ON ar.asset_id = a.id
            JOIN users u_staff ON ar.requester_id = u_staff.id
            JOIN campuses c ON a.campus_id = c.id
            -- Find the custodian for the campus
            LEFT JOIN users u_custodian ON u_custodian.campus_id = a.campus_id AND u_custodian.role = 'custodian'
            -- Find an admin for the campus
            LEFT JOIN users u_admin ON u_admin.campus_id = a.campus_id AND u_admin.role = 'admin'
            WHERE ar.status = 'released'
              AND (ar.expected_return_date <= ? OR ar.expected_return_date < CURDATE())
              AND ar.reminder_sent = 0"; // Only send once

    $requests = fetchAll($pdo, $sql, [$due_date]);

    if (empty($requests)) {
        echo "No assets are due for reminders today.\n";
        return;
    }

    echo "Found " . count($requests) . " assets to send reminders for.\n";

    // Initialize Email system
    $email = new Email($pdo);

    foreach ($requests as $request) {
        $days_until_due = (new DateTime())->diff(new DateTime($request['expected_return_date']))->days;
        $is_overdue = new DateTime() > new DateTime($request['expected_return_date']);

        // Determine urgency level
        $urgency = 'upcoming';
        if ($is_overdue) {
            // Send overdue alert instead
            $days_overdue = abs($days_until_due);

            // Collect recipients
            $recipients = [];
            if (!empty($request['staff_email'])) {
                $recipients[] = ['email' => $request['staff_email'], 'name' => $request['staff_name'], 'role' => 'borrower'];
            }
            if (!empty($request['custodian_email'])) {
                $recipients[] = ['email' => $request['custodian_email'], 'name' => $request['custodian_name'], 'role' => 'custodian'];
            }
            if (!empty($request['admin_email'])) {
                $recipients[] = ['email' => $request['admin_email'], 'name' => $request['admin_name'], 'role' => 'admin'];
            }

            // Send overdue alerts
            foreach ($recipients as $recipient) {
                $email->sendOverdueAlert(
                    $recipient['email'],
                    $recipient['name'],
                    $request['asset_name'],
                    $request['expected_return_date'],
                    $days_overdue,
                    $request['id'],
                    $recipient['role']
                );
            }

            echo "Overdue alert queued for request ID {$request['id']} ({$days_overdue} days overdue).\n";

        } else {
            // Send return reminder
            if ($days_until_due == 0) {
                $urgency = 'today';
            } elseif ($days_until_due == 1) {
                $urgency = 'urgent';
            } elseif ($days_until_due >= 7) {
                $urgency = 'advance_notice';
            } else {
                $urgency = 'upcoming';
            }

            // Send to borrower
            if (!empty($request['staff_email'])) {
                $email->sendReturnReminder(
                    $request['staff_email'],
                    $request['staff_name'],
                    $request['asset_name'],
                    $request['expected_return_date'],
                    $days_until_due,
                    $request['id'],
                    $urgency
                );
                echo "Return reminder queued for request ID {$request['id']} (due in {$days_until_due} days).\n";
            }
        }

        // Mark as sent to prevent duplicate emails
        executeQuery($pdo, "UPDATE asset_requests SET reminder_sent = 1 WHERE id = ?", [$request['id']]);
    }

    echo "Reminder process finished.\n";
}

// Run the function
sendReturnReminders($pdo);
