<?php
/**
 * Email Queue Class
 *
 * Manages the email_queue database table
 * Provides clean interface for adding, retrieving, and updating queued emails
 */

require_once __DIR__ . '/EmailConfig.php';

class EmailQueue {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Add an email to the queue
     *
     * @param string $recipientEmail Email address
     * @param string $recipientName Recipient's full name
     * @param string $subject Email subject line
     * @param string $bodyHtml Email body HTML content
     * @param string $priority 'high', 'normal', or 'low'
     * @param string|null $relatedType Type of related entity (e.g., 'request', 'missing_asset')
     * @param int|null $relatedId ID of related entity
     * @return int|false Inserted email ID or false on failure
     */
    public function add($recipientEmail, $recipientName, $subject, $bodyHtml,
                       $priority = 'normal', $relatedType = null, $relatedId = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_queue (
                    recipient_email, recipient_name, subject, body_html,
                    status, priority, related_type, related_id,
                    next_retry_at
                ) VALUES (
                    ?, ?, ?, ?,
                    'pending', ?, ?, ?,
                    NOW()
                )
            ");

            $result = $stmt->execute([
                $recipientEmail,
                $recipientName,
                $subject,
                $bodyHtml,
                $priority,
                $relatedType,
                $relatedId
            ]);

            if ($result) {
                $emailId = $this->pdo->lastInsertId();
                $this->logActivity('EMAIL_QUEUED', "Email queued for {$recipientEmail} - {$subject}");
                return $emailId;
            }

            return false;

        } catch (Exception $e) {
            error_log("Failed to queue email for {$recipientEmail}: " . $e->getMessage());
            $this->logActivity('EMAIL_QUEUE_FAILED', "Failed to queue email for {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending emails ready to be sent
     *
     * @param int $limit Maximum number of emails to retrieve
     * @return array Array of email records
     */
    public function getPending($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
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
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Failed to get pending emails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark an email as successfully sent
     *
     * @param int $emailId Email queue ID
     * @return bool Success status
     */
    public function markSent($emailId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE email_queue
                SET status = 'sent',
                    sent_at = NOW(),
                    error_message = NULL
                WHERE id = ?
            ");
            return $stmt->execute([$emailId]);

        } catch (Exception $e) {
            error_log("Failed to mark email #{$emailId} as sent: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark an email as failed and schedule retry
     *
     * @param int $emailId Email queue ID
     * @param string $errorMessage Error message from sending attempt
     * @param int $attempt Current attempt number
     * @return bool Success status
     */
    public function markFailed($emailId, $errorMessage, $attempt) {
        try {
            $maxAttempts = EmailConfig::$queue_max_attempts;
            $retryDelays = EmailConfig::$retry_delays;

            // Calculate next retry delay (exponential backoff)
            $nextRetryMinutes = $retryDelays[$attempt - 1] ?? $retryDelays[count($retryDelays) - 1];

            // Determine if permanently failed
            $newStatus = ($attempt >= $maxAttempts) ? 'failed' : 'pending';

            $stmt = $this->pdo->prepare("
                UPDATE email_queue
                SET attempts = ?,
                    error_message = ?,
                    next_retry_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                    status = ?
                WHERE id = ?
            ");

            $result = $stmt->execute([
                $attempt,
                $errorMessage,
                $nextRetryMinutes,
                $newStatus,
                $emailId
            ]);

            // Log permanent failures
            if ($newStatus === 'failed') {
                $stmt = $this->pdo->prepare("SELECT recipient_email FROM email_queue WHERE id = ?");
                $stmt->execute([$emailId]);
                $email = $stmt->fetchColumn();
                $this->logActivity('EMAIL_PERMANENTLY_FAILED',
                    "Email #{$emailId} to {$email} failed after {$attempt} attempts: {$errorMessage}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("Failed to mark email #{$emailId} as failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get queue statistics
     *
     * @return array Statistics about the email queue
     */
    public function getStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN priority = 'high' AND status = 'pending' THEN 1 ELSE 0 END) as `high_priority`
                FROM email_queue
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Failed to get queue stats: " . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'sent' => 0, 'failed' => 0, 'high_priority' => 0];
        }
    }

    /**
     * Log activity (wrapper for global logActivity function)
     */
    private function logActivity($action, $details) {
        if (function_exists('logActivity')) {
            logActivity($this->pdo, null, $action, $details);
        }
    }
}
