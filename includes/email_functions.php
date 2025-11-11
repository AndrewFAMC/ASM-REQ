<?php
/**
 * Email Functions - Backward Compatibility Layer
 *
 * This file maintains backward compatibility with existing code
 * All functions now use the new centralized email system internally
 *
 * MIGRATION NOTE: New code should use the Email class directly:
 *   $email = new Email($pdo);
 *   $email->sendAccountCreation($email, $name, $password);
 *
 * Old functions are kept here for backward compatibility
 */

require_once __DIR__ . '/email/Email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email notification for a newly created account.
 * LEGACY FUNCTION - Uses new Email system internally
 */
function sendAccountCreationEmail($pdo, $recipientEmail, $recipientName, $defaultPassword) {
    $email = new Email($pdo);
    $result = $email->sendAccountCreation($recipientEmail, $recipientName, $defaultPassword);
    return $result !== false;
}

/**
 * Sends an email notification for inventory tag generation to office users.
 * LEGACY FUNCTION - Uses new Email system internally
 */
function sendTagGenerationNotification($pdo, $officeId, $assetName, $tagNumber, $custodianName) {
    $email = new Email($pdo);
    $result = $email->sendTagGeneration($officeId, $assetName, $tagNumber, $custodianName);
    return $result > 0;
}

/**
 * Sends an email notification for request approval (SYNCHRONOUS - DEPRECATED)
 * LEGACY FUNCTION - Consider using queueRequestNotificationEmail() instead
 */
function sendRequestNotificationEmail($pdo, $recipientEmail, $recipientName, $requestId, $assetName, $status, $message) {
    // This was a direct-send function, now queues for consistency
    $email = new Email($pdo);
    $result = $email->sendRequestNotification($recipientEmail, $recipientName, $requestId, $assetName, $status, $message, 'high');
    return $result !== false;
}

/**
 * Queues an email notification for asynchronous sending
 * LEGACY FUNCTION - Uses new Email system internally
 */
function queueRequestNotificationEmail($pdo, $recipientEmail, $recipientName, $requestId, $assetName, $status, $message, $priority = 'normal') {
    $email = new Email($pdo);
    $result = $email->sendRequestNotification($recipientEmail, $recipientName, $requestId, $assetName, $status, $message, $priority);
    return $result !== false;
}

/**
 * Sends a return reminder email notification (SYNCHRONOUS - DEPRECATED)
 * LEGACY FUNCTION - Now queues for better performance
 */
function sendReturnReminderEmail($pdo, $recipientEmail, $recipientName, $assetName, $expectedReturnDate, $daysUntilDue, $requestId, $urgencyLevel = 'upcoming') {
    $email = new Email($pdo);
    $result = $email->sendReturnReminder($recipientEmail, $recipientName, $assetName, $expectedReturnDate, $daysUntilDue, $requestId, $urgencyLevel);
    return $result !== false;
}

/**
 * Sends an overdue alert email notification (SYNCHRONOUS - DEPRECATED)
 * LEGACY FUNCTION - Now queues for better performance
 */
function sendOverdueAlertEmail($pdo, $recipientEmail, $recipientName, $assetName, $expectedReturnDate, $daysOverdue, $requestId, $recipientRole = 'borrower') {
    $email = new Email($pdo);
    $result = $email->sendOverdueAlert($recipientEmail, $recipientName, $assetName, $expectedReturnDate, $daysOverdue, $requestId, $recipientRole);
    return $result !== false;
}

/**
 * Sends a missing asset alert email notification (SYNCHRONOUS - DEPRECATED)
 * LEGACY FUNCTION - Consider using queueMissingAssetAlertEmail() instead
 */
function sendMissingAssetAlertEmail($pdo, $recipientEmail, $recipientName, $assetName, $assetCode, $reportId, $recipientRole = 'custodian', $reportDetails = []) {
    $email = new Email($pdo);
    $result = $email->sendMissingAssetAlert($recipientEmail, $recipientName, $assetName, $assetCode, $reportId, $recipientRole, $reportDetails);
    return $result !== false;
}

/**
 * Queues a missing asset alert email for asynchronous sending
 * LEGACY FUNCTION - Uses new Email system internally
 */
function queueMissingAssetAlertEmail($pdo, $recipientEmail, $recipientName, $assetName, $assetCode, $reportId, $recipientRole = 'custodian', $reportDetails = []) {
    $email = new Email($pdo);
    $result = $email->sendMissingAssetAlert($recipientEmail, $recipientName, $assetName, $assetCode, $reportId, $recipientRole, $reportDetails);
    return $result !== false;
}

/**
 * MIGRATION NOTES:
 *
 * The following improvements have been made:
 * 1. All SMTP credentials centralized in EmailConfig.php
 * 2. All HTML templates use reusable components (no duplication)
 * 3. All emails go through queue system for better performance
 * 4. Backward compatibility maintained - existing code works without changes
 *
 * For NEW code, use the Email class directly:
 *
 * // Create instance
 * $email = new Email($pdo);
 *
 * // Send account creation email
 * $email->sendAccountCreation($email, $name, $password);
 *
 * // Send request notification
 * $email->sendRequestNotification($email, $name, $requestId, $assetName, $status, $message);
 *
 * // Send return reminder
 * $email->sendReturnReminder($email, $name, $assetName, $returnDate, $daysUntilDue, $requestId, $urgency);
 *
 * // Send overdue alert
 * $email->sendOverdueAlert($email, $name, $assetName, $returnDate, $daysOverdue, $requestId, $role);
 *
 * // Send missing asset alert
 * $email->sendMissingAssetAlert($email, $name, $assetName, $assetCode, $reportId, $role, $details);
 *
 * // Send tag generation notification
 * $email->sendTagGeneration($officeId, $assetName, $tagNumber, $custodianName);
 */
