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
 */

require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuration
$batchSize = 10;           // Process 10 emails at a time
$sleepDuration = 30;       // Sleep for 30 seconds between batches
$maxAttempts = 3;          // Maximum retry attempts

echo "=== Email Queue Worker Started ===\n";
echo "Batch Size: {$batchSize}\n";
echo "Sleep Duration: {$sleepDuration}s\n";
echo "Max Attempts: {$maxAttempts}\n";
echo str_repeat('-', 60) . "\n\n";

// Main processing loop
while (true) {
    try {
        // Fetch pending emails that are ready to be sent
        $stmt = $pdo->prepare("
            SELECT id, recipient_email, recipient_name, subject, body_html,
                   attempts, priority, related_type, related_id
            FROM email_queue
            WHERE status = 'pending'
              AND attempts < max_attempts
              AND (next_retry_at IS NULL OR next_retry_at <= NOW())
            ORDER BY
                CASE priority
                    WHEN 'high' THEN 1
                    WHEN 'normal' THEN 2
                    WHEN 'low' THEN 3
                END,
                created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$batchSize]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                // Send the email
                $sent = sendEmailFromQueue($pdo, $email);

                if ($sent) {
                    // Mark as sent
                    $updateStmt = $pdo->prepare("
                        UPDATE email_queue
                        SET status = 'sent',
                            sent_at = NOW(),
                            error_message = NULL
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$emailId]);

                    echo "  ✓ Sent to {$recipientEmail} (ID: {$emailId})\n";
                } else {
                    throw new Exception("Email sending failed");
                }

            } catch (Exception $e) {
                // Increment attempts and calculate next retry time
                $attempts = $email['attempts'] + 1;
                $errorMessage = $e->getMessage();

                // Calculate exponential backoff: 5 min, 15 min, 1 hour
                $retryDelays = [5, 15, 60]; // minutes
                $nextRetryMinutes = $retryDelays[$attempts - 1] ?? 60;

                $updateStmt = $pdo->prepare("
                    UPDATE email_queue
                    SET attempts = ?,
                        error_message = ?,
                        next_retry_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                        status = CASE WHEN ? >= max_attempts THEN 'failed' ELSE 'pending' END
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $attempts,
                    $errorMessage,
                    $nextRetryMinutes,
                    $attempts,
                    $emailId
                ]);

                if ($attempts >= $maxAttempts) {
                    echo "  ✗ FAILED (permanently) to {$recipientEmail} (ID: {$emailId}): {$errorMessage}\n";
                    logActivity($pdo, null, 'EMAIL_PERMANENTLY_FAILED',
                        "Email #{$emailId} to {$recipientEmail} failed after {$attempts} attempts: {$errorMessage}");
                } else {
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

/**
 * Sends an email from the queue using PHPMailer
 */
function sendEmailFromQueue($pdo, $emailData) {
    $smtpUser = 'mico.macapugay2004@gmail.com';
    $smtpPass = 'gggm gqng fjgt ukfe';

    $mail = new PHPMailer(true);

    try {
        // Server Settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Performance optimizations
        $mail->SMTPKeepAlive = true;
        $mail->Timeout = 10;
        $mail->SMTPDebug = 0;

        // Recipients
        $mail->setFrom($smtpUser, 'HCC Asset Management System');
        $mail->addAddress($emailData['recipient_email'], $emailData['recipient_name']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $emailData['subject'];
        $mail->Body = $emailData['body_html'];

        $mail->send();

        // Log success
        logActivity(
            $pdo,
            null,
            'EMAIL_SENT',
            "Email sent to {$emailData['recipient_email']} - {$emailData['subject']}"
        );

        return true;

    } catch (Exception $e) {
        error_log("Failed to send queued email #{$emailData['id']}: {$mail->ErrorInfo}");
        throw new Exception($mail->ErrorInfo);
    }
}

?>
