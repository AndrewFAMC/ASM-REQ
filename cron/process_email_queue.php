<?php
/**
 * Email Queue Background Worker
 *
 * Processes pending emails from the queue and sends them asynchronously.
 * This script should be run continuously or via cron/scheduled task.
 *
 * Usage:
 *   - Run continuously: php process_email_queue.php
 *   - Run via cron: * * * * * php /path/to/process_email_queue.php
 *
 * REFACTORED: Now uses the new centralized email system
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email/EmailConfig.php';
require_once __DIR__ . '/../includes/email/EmailQueue.php';
require_once __DIR__ . '/../includes/email/EmailSender.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuration from EmailConfig
$batchSize = EmailConfig::$queue_batch_size;
$sleepDuration = EmailConfig::$queue_sleep_duration;
$maxAttempts = EmailConfig::$queue_max_attempts;

echo "=== Email Queue Worker Started ===\n";
echo "Batch Size: {$batchSize}\n";
echo "Sleep Duration: {$sleepDuration}s\n";
echo "Max Attempts: {$maxAttempts}\n";
echo str_repeat('-', 60) . "\n\n";

// Initialize queue and sender
$queue = new EmailQueue($pdo);
$sender = new EmailSender($pdo);

// Main processing loop
while (true) {
    try {
        // Fetch pending emails
        $emails = $queue->getPending($batchSize);

        if (empty($emails)) {
            echo "[" . date('Y-m-d H:i:s') . "] No emails to process. Sleeping...\n";
            sleep($sleepDuration);
            continue;
        }

        echo "[" . date('Y-m-d H:i:s') . "] Processing " . count($emails) . " email(s)...\n";

        foreach ($emails as $email) {
            $emailId = $email['id'];
            $recipientEmail = $email['recipient_email'];

            try {
                // Send the email using new EmailSender
                $sent = $sender->sendFromQueue($email);

                if ($sent) {
                    // Mark as sent
                    $queue->markSent($emailId);
                    echo "  ✓ Sent to {$recipientEmail} (ID: {$emailId})\n";
                } else {
                    throw new Exception("Email sending failed");
                }

            } catch (Exception $e) {
                // Increment attempts and schedule retry
                $attempts = $email['attempts'] + 1;
                $errorMessage = $e->getMessage();

                $queue->markFailed($emailId, $errorMessage, $attempts);

                if ($attempts >= $maxAttempts) {
                    echo "  ✗ FAILED (permanently) to {$recipientEmail} (ID: {$emailId}): {$errorMessage}\n";
                } else {
                    $retryDelays = EmailConfig::$retry_delays;
                    $nextRetryMinutes = $retryDelays[$attempts - 1] ?? $retryDelays[count($retryDelays) - 1];
                    echo "  ⚠ Retry {$attempts}/{$maxAttempts} for {$recipientEmail} (ID: {$emailId}): {$errorMessage}\n";
                    echo "    Next retry in {$nextRetryMinutes} minute(s)\n";
                }
            }
        }

        echo "\n";
        sleep($sleepDuration);

    } catch (Exception $e) {
        echo "ERROR in main loop: " . $e->getMessage() . "\n";
        sleep($sleepDuration);
    }
}
