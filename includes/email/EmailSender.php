<?php
/**
 * Email Sender Class
 *
 * Wrapper around PHPMailer - handles actual email sending
 * Centralizes PHPMailer configuration (no more duplication!)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/EmailConfig.php';

class EmailSender {

    private $mail;
    private $pdo;

    /**
     * Constructor - Initialize PHPMailer with configuration
     */
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with settings from EmailConfig
     */
    private function configure() {
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host       = EmailConfig::$smtp_host;
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = EmailConfig::$smtp_user;
        $this->mail->Password   = EmailConfig::$smtp_pass;
        $this->mail->SMTPSecure = EmailConfig::$smtp_secure === 'ssl'
                                  ? PHPMailer::ENCRYPTION_SMTPS
                                  : PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = EmailConfig::$smtp_port;

        // Performance & behavior settings
        $this->mail->SMTPKeepAlive = EmailConfig::$smtp_keep_alive;
        $this->mail->Timeout = EmailConfig::$smtp_timeout;
        $this->mail->SMTPDebug = EmailConfig::$smtp_debug;

        // Default sender
        $this->mail->setFrom(EmailConfig::$from_email, EmailConfig::$from_name);
    }

    /**
     * Send email immediately (synchronous)
     *
     * @param string $toEmail Recipient email address
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlBody Email body (HTML)
     * @param bool $embedLogo Whether to embed the HCC logo
     * @return bool Success status
     */
    public function sendNow($toEmail, $toName, $subject, $htmlBody, $embedLogo = true) {
        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            // Set recipient
            $this->mail->addAddress($toEmail, $toName);

            // Embed logo if requested
            if ($embedLogo) {
                $logoPath = EmailConfig::getLogo();
                if ($logoPath && file_exists($logoPath)) {
                    $this->mail->addEmbeddedImage($logoPath, 'hcc_logo', 'logo.png');
                }
            }

            // Set content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;

            // Send
            $this->mail->send();

            // Log success
            $this->logActivity('EMAIL_SENT', "Email sent to {$toEmail} - {$subject}");

            return true;

        } catch (Exception $e) {
            $errorMsg = $this->mail->ErrorInfo;
            error_log("Failed to send email to {$toEmail}: {$errorMsg}");
            $this->logActivity('EMAIL_FAILED', "Failed to send email to {$toEmail}: {$errorMsg}");
            return false;
        }
    }

    /**
     * Send email from queue data
     *
     * @param array $emailData Email data from queue (id, recipient_email, subject, body_html, etc.)
     * @return bool Success status
     * @throws Exception on failure (for retry logic)
     */
    public function sendFromQueue($emailData) {
        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            // Set recipient
            $this->mail->addAddress($emailData['recipient_email'], $emailData['recipient_name']);

            // Note: Queue emails should have full HTML with absolute URLs for logos
            // No need to embed logo - it should be a full URL in the HTML

            // Set content
            $this->mail->isHTML(true);
            $this->mail->Subject = $emailData['subject'];
            $this->mail->Body = $emailData['body_html'];

            // Send
            $this->mail->send();

            // Log success
            $this->logActivity('EMAIL_SENT', "Email sent to {$emailData['recipient_email']} - {$emailData['subject']}");

            return true;

        } catch (Exception $e) {
            $errorMsg = $this->mail->ErrorInfo;
            error_log("Failed to send queued email #{$emailData['id']}: {$errorMsg}");
            // Don't log to activity table here - queue processor will handle it
            throw new Exception($errorMsg);
        }
    }

    /**
     * Test email configuration
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection() {
        try {
            // Try to connect to SMTP server
            $this->mail->smtpConnect();
            $this->mail->smtpClose();
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }

    /**
     * Log activity (wrapper for global logActivity function)
     */
    private function logActivity($action, $details) {
        if ($this->pdo && function_exists('logActivity')) {
            logActivity($this->pdo, null, $action, $details);
        }
    }
}
