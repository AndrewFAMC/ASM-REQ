<?php

// This script should be run daily via a cron job.
// Example cron job command:
// 0 8 * * * /usr/bin/php /path/to/your/project/cron_jobs/send_return_reminders.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    foreach ($requests as $request) {
        $days_until_due = (new DateTime())->diff(new DateTime($request['expected_return_date']))->days;
        $is_overdue = new DateTime() > new DateTime($request['expected_return_date']);

        $subject = $is_overdue 
            ? "Overdue Asset Return: {$request['asset_name']}"
            : "Asset Return Reminder: {$request['asset_name']} due in {$days_until_due} days";

        $body = "
            <p>Dear {$request['staff_name']},</p>
            <p>This is a reminder that the asset <strong>{$request['asset_name']}</strong> (S/N: {$request['serial_number']}) is due to be returned on <strong>" . date('F j, Y', strtotime($request['expected_return_date'])) . "</strong>.</p>";
        
        if ($is_overdue) {
            $body .= "<p style='color:red;'><strong>This asset is now overdue.</strong> Please return it to the custodian at the {$request['campus_name']} campus as soon as possible.</p>";
        } else {
            $body .= "<p>Please ensure the asset is returned to the custodian at the {$request['campus_name']} campus by the due date.</p>";
        }

        $body .= "<p>Thank you,<br>HCC Asset Management System</p>";

        // Collect recipients
        $recipients = [];
        if (!empty($request['staff_email'])) $recipients[] = $request['staff_email'];
        if (!empty($request['custodian_email'])) $recipients[] = $request['custodian_email'];
        if (!empty($request['admin_email'])) $recipients[] = $request['admin_email'];
        
        $unique_recipients = array_unique($recipients);

        try {
            $mail = new PHPMailer(true);
            // Server settings from your config.php or environment
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your-email@gmail.com'; // Replace with your app-specific password
            $mail->Password   = 'your-app-password';    // Replace with your app-specific password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom('no-reply@hcc.edu.ph', 'HCC Asset Management');
            foreach ($unique_recipients as $recipient_email) {
                $mail->addAddress($recipient_email);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            echo "Reminder sent for request ID {$request['id']}.\n";

            // Mark as sent to prevent duplicate emails
            executeQuery($pdo, "UPDATE asset_requests SET reminder_sent = 1 WHERE id = ?", [$request['id']]);

        } catch (Exception $e) {
            echo "Message could not be sent for request ID {$request['id']}. Mailer Error: {$mail->ErrorInfo}\n";
        }
    }
    echo "Reminder process finished.\n";
}

// Run the function
sendReturnReminders($pdo);