<?php
/**
 * Email Facade Class
 *
 * Main interface for sending emails throughout the application
 * Provides clean, easy-to-use API for all email types
 *
 * Usage:
 *   $email = new Email($pdo);
 *   $email->sendAccountCreation('user@email.com', 'John Doe', 'password123');
 */

require_once __DIR__ . '/EmailConfig.php';
require_once __DIR__ . '/EmailQueue.php';
require_once __DIR__ . '/EmailSender.php';
require_once __DIR__ . '/templates/AccountCreationEmail.php';
require_once __DIR__ . '/templates/AssetRequestEmail.php';
require_once __DIR__ . '/templates/ReturnReminderEmail.php';
require_once __DIR__ . '/templates/OverdueAlertEmail.php';
require_once __DIR__ . '/templates/MissingAssetEmail.php';
require_once __DIR__ . '/templates/TagGenerationEmail.php';

class Email {

    private $pdo;
    private $queue;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->queue = new EmailQueue($pdo);
    }

    /**
     * Send account creation email (queued)
     *
     * @param string $email User's email address
     * @param string $name User's full name
     * @param string $password Default password
     * @return int|false Queue ID or false on failure
     */
    public function sendAccountCreation($email, $name, $password) {
        $template = new AccountCreationEmail();
        $html = $template->render($name, $email, $password);

        return $this->queue->add(
            $email,
            $name,
            $template->getSubject(),
            $html,
            'normal',
            'account_creation',
            null
        );
    }

    /**
     * Send asset request notification (queued)
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param int $requestId Request ID
     * @param string $assetName Asset name
     * @param string $status Status (approved/rejected/released/returned/late_return)
     * @param string $message Notification message
     * @param string $priority Priority level (high/normal/low)
     * @return int|false Queue ID or false on failure
     */
    public function sendRequestNotification($email, $name, $requestId, $assetName,
                                           $status, $message, $priority = 'normal') {
        $template = new AssetRequestEmail();
        $html = $template->render($name, $requestId, $assetName, $status, $message);

        return $this->queue->add(
            $email,
            $name,
            $template->getSubject($status, $requestId),
            $html,
            $priority,
            'request',
            $requestId
        );
    }

    /**
     * Send return reminder email (queued)
     *
     * @param string $email Borrower email
     * @param string $name Borrower name
     * @param string $assetName Asset name
     * @param string $returnDate Expected return date (Y-m-d)
     * @param int $daysUntilDue Days until due
     * @param int $requestId Request ID
     * @param string $urgency Urgency level (advance_notice/upcoming/urgent/today)
     * @return int|false Queue ID or false on failure
     */
    public function sendReturnReminder($email, $name, $assetName, $returnDate,
                                      $daysUntilDue, $requestId, $urgency = 'upcoming') {
        $template = new ReturnReminderEmail();
        $html = $template->render($name, $assetName, $returnDate, $daysUntilDue, $requestId, $urgency);

        return $this->queue->add(
            $email,
            $name,
            $template->getSubject($urgency, $assetName, $daysUntilDue),
            $html,
            'high', // Return reminders are always high priority
            'reminder',
            $requestId
        );
    }

    /**
     * Send overdue alert email (queued)
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $assetName Asset name
     * @param string $returnDate Expected return date
     * @param int $daysOverdue Days overdue
     * @param int $requestId Request ID
     * @param string $role Recipient role (borrower/custodian/admin)
     * @return int|false Queue ID or false on failure
     */
    public function sendOverdueAlert($email, $name, $assetName, $returnDate,
                                    $daysOverdue, $requestId, $role = 'borrower') {
        $template = new OverdueAlertEmail();
        $html = $template->render($name, $assetName, $returnDate, $daysOverdue, $requestId, $role);

        return $this->queue->add(
            $email,
            $name,
            $template->getSubject($role, $assetName, $daysOverdue),
            $html,
            'high', // Overdue alerts are always high priority
            'overdue',
            $requestId
        );
    }

    /**
     * Send missing asset alert email (queued)
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $assetName Asset name
     * @param string $assetCode Asset code/tag
     * @param int $reportId Missing asset report ID
     * @param string $role Recipient role (reporter/custodian/admin)
     * @param array $details Additional report details
     * @return int|false Queue ID or false on failure
     */
    public function sendMissingAssetAlert($email, $name, $assetName, $assetCode,
                                         $reportId, $role = 'custodian', $details = []) {
        $template = new MissingAssetEmail();
        $html = $template->render($name, $assetName, $assetCode, $reportId, $role, $details);

        return $this->queue->add(
            $email,
            $name,
            $template->getSubject($role),
            $html,
            'high', // Missing asset alerts are always high priority
            'missing_asset',
            $reportId
        );
    }

    /**
     * Send tag generation notification to office users (queued)
     *
     * @param int $officeId Office ID
     * @param string $assetName Asset name
     * @param string $tagNumber Tag number(s)
     * @param string $custodianName Custodian who generated the tag
     * @return int Number of emails queued
     */
    public function sendTagGeneration($officeId, $assetName, $tagNumber, $custodianName) {
        // Get active users in the office
        $stmt = $this->pdo->prepare("
            SELECT email, full_name
            FROM users
            WHERE office_id = ? AND is_active = 1
        ");
        $stmt->execute([$officeId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($users)) {
            error_log("No active users found in office ID {$officeId}. Cannot send tag notification.");
            return 0;
        }

        $template = new TagGenerationEmail();
        $queued = 0;

        foreach ($users as $user) {
            $html = $template->render($user['full_name'], $assetName, $tagNumber, $custodianName);

            $result = $this->queue->add(
                $user['email'],
                $user['full_name'],
                $template->getSubject(),
                $html,
                'normal',
                'tag_generation',
                $officeId
            );

            if ($result) {
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Get queue statistics
     *
     * @return array Statistics about email queue
     */
    public function getQueueStats() {
        return $this->queue->getStats();
    }

    /**
     * Send email immediately (bypass queue) - USE SPARINGLY!
     * Only for absolutely critical emails that can't wait
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $subject Subject
     * @param string $htmlBody HTML body
     * @return bool Success status
     */
    public function sendNow($email, $name, $subject, $htmlBody) {
        $sender = new EmailSender($this->pdo);
        return $sender->sendNow($email, $name, $subject, $htmlBody);
    }
}
