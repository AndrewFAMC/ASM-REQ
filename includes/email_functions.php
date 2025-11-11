<?php
// email_functions.php - Handles sending emails using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure vendor/autoload.php is included. This should be at the top of your main scripts (like config.php).
// If not already present, you would add: require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sends an email notification for a newly created account.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $recipientEmail The email address of the new user.
 * @param string $recipientName The full name of the new user.
 * @param string $defaultPassword The default password assigned to the user.
 * @return bool True on success, false on failure.
 */
function sendAccountCreationEmail($pdo, $recipientEmail, $recipientName, $defaultPassword) {
    // Load credentials securely from environment variables
    // You would set these in your server environment (e.g., .env file, Apache config)
    $smtpUser = 'mico.macapugay2004@gmail.com'; // Your Gmail address
    $smtpPass = 'gggm gqng fjgt ukfe'; // Your Gmail App Password

    if (!$smtpPass) {
        error_log('SMTP_PASS environment variable is not set. Cannot send email.');
        // In a production environment, you might want to throw an exception or handle this more gracefully.
        return false;
    }

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

        // Recipients
        $mail->setFrom($smtpUser, 'HCC Asset Management System');
        $mail->addAddress($recipientEmail, $recipientName);

        // Embed the logo image using CID for better compatibility and to ensure it displays.
        $logoPath = __DIR__ . '/../logo/1.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'hcc_logo', 'logo.png');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to HCC Asset Management - Your Account Details';
        
        // Construct full URLs for assets
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        // Find the base path of the application from the script's directory
        $basePath = dirname(dirname($_SERVER['PHP_SELF'])); // Go up one level from /includes
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        
        $loginUrl = $protocol . $host . $basePath . '/login.php';
        $homeUrl = $protocol . $host . $basePath . '/index.php';
        $learnMoreUrl = $protocol . $host . $basePath . '/about.php';

        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Welcome to HCC Asset Management</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .credentials-box { background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .credentials-box p { margin: 0 0 15px; font-size: 15px; color: #555555; text-align: left; }
                    .credentials-box p:last-child { margin-bottom: 0; }
                    .credentials-box .password { font-size: 20px; color: #0071e3; letter-spacing: 1px; font-weight: 600; }
                    .warning { background-color: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 15px; border-radius: 8px; text-align: center; font-size: 14px; margin-bottom: 30px; }
                    .button { display: inline-block; background-color: #0071e3; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                    @media screen and (max-width: 600px) {
                        .content { padding: 30px 20px; }
                        h1 { font-size: 24px; }
                        p.main-text { font-size: 16px; }
                    }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <!-- Header -->
                                        <div style='text-align: center; padding: 40px 0 20px;'>
                                            <img src='cid:hcc_logo' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                                        </div>
                                        <!-- Main Content -->
                                        <div class='content'>
                                            <h1>Welcome, " . htmlspecialchars($recipientName) . ".</h1>
                                            <p class='main-text'>An account has been created for you in the Holy Cross Colleges Asset Management System. Here are your login details:</p>
                                            
                                            <!-- Credentials Section -->
                                            <div class='credentials-box'>
                                                <p><strong>Username / Email:</strong><br><span style='color: #000000;'>" . htmlspecialchars($recipientEmail) . "</span></p>
                                                <p><strong>Default Password:</strong><br><span class='password'>" . htmlspecialchars($defaultPassword) . "</span></p>
                                            </div>
                                            
                                            <!-- Security Warning -->
                                            <div class='warning'>
                                                <strong>Important:</strong> For security, you will be required to change this password upon your first login.
                                            </div>
                                            
                                            <!-- Call to Action -->
                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='" . $loginUrl . "' class='button'>Login to Your Account</a>
                                            </div>
                                        </div>
                                        <!-- Footer -->
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This email was sent from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved. <br>
                                                <a href='" . $homeUrl . "' class='footer-link'>Home</a> &middot; <a href='" . $learnMoreUrl . "' class='footer-link'>About the System</a>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                                    </div>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";


        $mail->send();
        logActivity($pdo, null, 'EMAIL_SENT', "Account creation email sent to {$recipientEmail}.");
        return true;
    } catch (Exception $e) {
        // Log detailed error for debugging
        error_log("Message could not be sent to {$recipientEmail}. Mailer Error: {$mail->ErrorInfo}");
        logActivity($pdo, null, 'EMAIL_FAILED', "Failed to send account creation email to {$recipientEmail}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an email notification for inventory tag generation to office users.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $officeId The ID of the office to notify.
 * @param string $assetName The name of the asset.
 * @param string $tagNumber The generated tag number(s).
 * @param string $custodianName The name of the custodian who generated the tag.
 * @return bool True on success, false on failure.
 */
function sendTagGenerationNotification($pdo, $officeId, $assetName, $tagNumber, $custodianName) {
    // Load credentials securely from environment variables
    $smtpUser = 'mico.macapugay2004@gmail.com'; // Your Gmail address
    $smtpPass = 'gggm gqng fjgt ukfe'; // Your Gmail App Password

    if (!$smtpPass) {
        error_log('SMTP_PASS environment variable is not set. Cannot send email.');
        return false;
    }

    // Query active users in the office
    $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE office_id = ? AND is_active = 1");
    $stmt->execute([$officeId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        error_log("No active users found in office ID {$officeId}. Cannot send notification.");
        return false;
    }

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

        // Recipients - Add all office users
        $mail->setFrom($smtpUser, 'HCC Asset Management System');
        foreach ($users as $user) {
            $mail->addAddress($user['email'], $user['full_name']);
        }

        // Embed the logo image
        $logoPath = __DIR__ . '/../logo/1.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'hcc_logo', 'logo.png');
        }

        $mail->isHTML(true);
        $mail->Subject = 'New Inventory Tag Generated - Action Required';

        // Construct full URLs
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $basePath = dirname(dirname($_SERVER['PHP_SELF']));
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        $loginUrl = $protocol . $host . $basePath . '/login.php';
        $homeUrl = $protocol . $host . $basePath . '/index.php';

        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>New Inventory Tag Generated</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .details-box { background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .details-box p { margin: 0 0 15px; font-size: 15px; color: #555555; text-align: left; }
                    .details-box p:last-child { margin-bottom: 0; }
                    .warning { background-color: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 15px; border-radius: 8px; text-align: center; font-size: 14px; margin-bottom: 30px; }
                    .button { display: inline-block; background-color: #0071e3; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                    @media screen and (max-width: 600px) {
                        .content { padding: 30px 20px; }
                        h1 { font-size: 24px; }
                        p.main-text { font-size: 16px; }
                    }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <!-- Header -->
                                        <div style='text-align: center; padding: 40px 0 20px;'>
                                            <img src='cid:hcc_logo' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                                        </div>
                                        <!-- Main Content -->
                                        <div class='content'>
                                            <h1>New Inventory Tag Generated</h1>
                                            <p class='main-text'>A new inventory tag has been generated for an asset assigned to your office. Please verify the asset details and confirm receipt.</p>

                                            <!-- Details Section -->
                                            <div class='details-box'>
                                                <p><strong>Asset Name:</strong><br>{$assetName}</p>
                                                <p><strong>Tag Number:</strong><br>{$tagNumber}</p>
                                                <p><strong>Assigned By:</strong><br>{$custodianName}</p>
                                                <p><strong>Generated Date:</strong><br>" . date('F j, Y') . "</p>
                                            </div>

                                            <!-- Action Required -->
                                            <div class='warning'>
                                                <strong>Action Required:</strong> Please log in to the system to verify the asset assignment and update its status.
                                            </div>

                                            <!-- Call to Action -->
                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='{$loginUrl}' class='button'>Login to Verify</a>
                                            </div>
                                        </div>
                                        <!-- Footer -->
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This email was sent from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved. <br>
                                                <a href='{$homeUrl}' class='footer-link'>Home</a>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        $mail->send();
        logActivity($pdo, null, 'EMAIL_SENT', "Tag generation notification sent to office ID {$officeId} for tag {$tagNumber}.");
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent to office ID {$officeId}. Mailer Error: {$mail->ErrorInfo}");
        logActivity($pdo, null, 'EMAIL_FAILED', "Failed to send tag generation notification to office ID {$officeId}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an email notification for request approval
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $recipientEmail The email address of the recipient.
 * @param string $recipientName The full name of the recipient.
 * @param int $requestId The request ID.
 * @param string $assetName The name of the asset.
 * @param string $status The status (approved/rejected).
 * @param string $message The notification message.
 * @return bool True on success, false on failure.
 */
function sendRequestNotificationEmail($pdo, $recipientEmail, $recipientName, $requestId, $assetName, $status, $message) {
    $smtpUser = 'mico.macapugay2004@gmail.com';
    $smtpPass = 'gggm gqng fjgt ukfe';

    if (!$smtpPass) {
        error_log('SMTP_PASS environment variable is not set. Cannot send email.');
        return false;
    }

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
        $mail->SMTPKeepAlive = true;  // Keep connection alive for better performance
        $mail->Timeout = 10;           // Reduce timeout from default 30s to 10s
        $mail->SMTPDebug = 0;          // Disable debug output for speed

        // Recipients
        $mail->setFrom($smtpUser, 'HCC Asset Management System');
        $mail->addAddress($recipientEmail, $recipientName);

        // Embed the logo image
        $logoPath = __DIR__ . '/../logo/1.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'hcc_logo', 'logo.png');
        }

        $mail->isHTML(true);
        $statusTitle = ($status === 'approved') ? 'Approved' : 'Rejected';
        $statusColor = ($status === 'approved') ? '#10b981' : '#ef4444';
        $mail->Subject = "Asset Request {$statusTitle} - Request #{$requestId}";

        // Construct full URLs
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $basePath = dirname(dirname($_SERVER['PHP_SELF']));
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        $loginUrl = $protocol . $host . $basePath . '/login.php';
        $requestsUrl = $protocol . $host . $basePath . '/my_requests.php';

        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Request {$statusTitle}</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .status-badge { display: inline-block; padding: 10px 20px; border-radius: 8px; background-color: {$statusColor}; color: white; font-weight: 600; margin-bottom: 20px; }
                    .details-box { background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .details-box p { margin: 0 0 15px; font-size: 15px; color: #555555; text-align: left; }
                    .details-box p:last-child { margin-bottom: 0; }
                    .button { display: inline-block; background-color: #0071e3; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <div style='text-align: center; padding: 40px 0 20px;'>
                                            <img src='cid:hcc_logo' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                                        </div>
                                        <div class='content'>
                                            <div style='text-align: center;'>
                                                <span class='status-badge'>Request {$statusTitle}</span>
                                            </div>
                                            <h1>Hi, " . htmlspecialchars($recipientName) . "</h1>
                                            <p class='main-text'>" . htmlspecialchars($message) . "</p>

                                            <div class='details-box'>
                                                <p><strong>Request ID:</strong><br>#{$requestId}</p>
                                                <p><strong>Asset:</strong><br>" . htmlspecialchars($assetName) . "</p>
                                                <p><strong>Status:</strong><br>{$statusTitle}</p>
                                                <p><strong>Date:</strong><br>" . date('F j, Y g:i A') . "</p>
                                            </div>

                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='{$requestsUrl}' class='button'>View My Requests</a>
                                            </div>
                                        </div>
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This email was sent from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        $mail->send();
        logActivity($pdo, null, 'EMAIL_SENT', "Request notification email sent to {$recipientEmail} for request #{$requestId}.");
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent to {$recipientEmail}. Mailer Error: {$mail->ErrorInfo}");
        logActivity($pdo, null, 'EMAIL_FAILED', "Failed to send request notification to {$recipientEmail}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Queues an email notification for asynchronous sending
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $recipientEmail The email address of the recipient.
 * @param string $recipientName The full name of the recipient.
 * @param int $requestId The request ID.
 * @param string $assetName The name of the asset.
 * @param string $status The status (approved/rejected/released/returned/late_return).
 * @param string $message The notification message.
 * @param string $priority Priority level (low/normal/high). Default: 'normal'.
 * @return bool True on success, false on failure.
 */
function queueRequestNotificationEmail($pdo, $recipientEmail, $recipientName, $requestId, $assetName, $status, $message, $priority = 'normal') {
    try {
        // Build email subject based on status
        $statusTitle = match($status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'released' => 'Released',
            'returned' => 'Returned',
            'late_return' => 'Returned Late',
            'pending_approval' => 'Pending Approval',
            default => 'Updated'
        };

        $subject = "Asset Request {$statusTitle} - Request #{$requestId}";

        // Build email body HTML (same as sendRequestNotificationEmail)
        $statusColor = match($status) {
            'approved' => '#10b981',
            'rejected' => '#ef4444',
            'released' => '#0071e3',
            'returned' => '#10b981',
            'late_return' => '#f59e0b',
            'pending_approval' => '#f59e0b',
            default => '#6b7280'
        };

        // Construct full URLs (handle CLI context)
        $protocol = "https://";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'assetmanagement.hcc.edu.ph';
        $basePath = '/AMS-REQ';

        $loginUrl = $protocol . $host . $basePath . '/login.php';
        $requestsUrl = $protocol . $host . $basePath . '/my_requests.php';

        $bodyHtml = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Request {$statusTitle}</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .status-badge { display: inline-block; padding: 10px 20px; border-radius: 8px; background-color: {$statusColor}; color: white; font-weight: 600; margin-bottom: 20px; }
                    .details-box { background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .details-box p { margin: 0 0 15px; font-size: 15px; color: #555555; text-align: left; }
                    .details-box p:last-child { margin-bottom: 0; }
                    .button { display: inline-block; background-color: #0071e3; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <div style='text-align: center; padding: 40px 0 20px;'>
                                            <img src='https://{$host}{$basePath}/logo/1.png' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                                        </div>
                                        <div class='content'>
                                            <div style='text-align: center;'>
                                                <span class='status-badge'>Request {$statusTitle}</span>
                                            </div>
                                            <h1>Hi, " . htmlspecialchars($recipientName) . "</h1>
                                            <p class='main-text'>" . htmlspecialchars($message) . "</p>

                                            <div class='details-box'>
                                                <p><strong>Request ID:</strong><br>#{$requestId}</p>
                                                <p><strong>Asset:</strong><br>" . htmlspecialchars($assetName) . "</p>
                                                <p><strong>Status:</strong><br>{$statusTitle}</p>
                                                <p><strong>Date:</strong><br>" . date('F j, Y g:i A') . "</p>
                                            </div>

                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='{$requestsUrl}' class='button'>View My Requests</a>
                                            </div>
                                        </div>
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This email was sent from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        // Insert into email queue
        $stmt = $pdo->prepare("
            INSERT INTO email_queue (
                recipient_email, recipient_name, subject, body_html,
                status, priority, related_type, related_id,
                next_retry_at
            ) VALUES (
                ?, ?, ?, ?,
                'pending', ?, 'request', ?,
                NOW()
            )
        ");

        $result = $stmt->execute([
            $recipientEmail,
            $recipientName,
            $subject,
            $bodyHtml,
            $priority,
            $requestId
        ]);

        if ($result) {
            logActivity($pdo, null, 'EMAIL_QUEUED', "Email queued for {$recipientEmail} - Request #{$requestId}");
            return true;
        }

        return false;

    } catch (Exception $e) {
        error_log("Failed to queue email for {$recipientEmail}. Error: {$e->getMessage()}");
        logActivity($pdo, null, 'EMAIL_QUEUE_FAILED', "Failed to queue email for {$recipientEmail}. Error: {$e->getMessage()}");
        return false;
    }
}

/**
 * Sends a return reminder email notification
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $recipientEmail The email address of the borrower.
 * @param string $recipientName The full name of the borrower.
 * @param string $assetName The name of the borrowed asset.
 * @param string $expectedReturnDate The expected return date (Y-m-d format).
 * @param int $daysUntilDue Days until the asset is due.
 * @param int $requestId The request/borrowing ID for tracking.
 * @param string $urgencyLevel Urgency level: 'advance_notice', 'upcoming', 'urgent', 'today'. Default: 'upcoming'.
 * @return bool True on success, false on failure.
 */
function sendReturnReminderEmail($pdo, $recipientEmail, $recipientName, $assetName, $expectedReturnDate, $daysUntilDue, $requestId, $urgencyLevel = 'upcoming') {
    $smtpUser = 'mico.macapugay2004@gmail.com';
    $smtpPass = 'gggm gqng fjgt ukfe';

    if (!$smtpPass) {
        error_log('SMTP_PASS environment variable is not set. Cannot send email.');
        return false;
    }

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
        $mail->addAddress($recipientEmail, $recipientName);

        // Embed the logo image
        $logoPath = __DIR__ . '/../logo/1.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'hcc_logo', 'logo.png');
        }

        // Determine urgency-based styling and messaging
        $urgencyConfig = match($urgencyLevel) {
            'advance_notice' => [
                'subject' => 'Upcoming Return: ' . $assetName,
                'badge_color' => '#0071e3',
                'badge_text' => 'Advance Notice',
                'icon' => '📅',
                'title' => 'Upcoming Asset Return',
                'message' => "This is an advance notice that you have borrowed an asset that will be due soon."
            ],
            'upcoming' => [
                'subject' => 'Reminder: Return ' . $assetName . ' in ' . $daysUntilDue . ' Days',
                'badge_color' => '#f59e0b',
                'badge_text' => 'Return Soon',
                'icon' => '⏰',
                'title' => 'Asset Return Reminder',
                'message' => "Please prepare to return the asset you borrowed within the next few days."
            ],
            'urgent' => [
                'subject' => 'URGENT: Return ' . $assetName . ' Tomorrow!',
                'badge_color' => '#ef4444',
                'badge_text' => 'Due Tomorrow',
                'icon' => '🚨',
                'title' => 'Urgent Return Reminder',
                'message' => "Your borrowed asset is due tomorrow. Please make arrangements to return it on time."
            ],
            'today' => [
                'subject' => 'ACTION REQUIRED: Return ' . $assetName . ' TODAY',
                'badge_color' => '#dc2626',
                'badge_text' => 'Due Today',
                'icon' => '⚠️',
                'title' => 'Asset Due Today',
                'message' => "Your borrowed asset is due for return TODAY. Please return it to the custodian office immediately."
            ],
            default => [
                'subject' => 'Return Reminder: ' . $assetName,
                'badge_color' => '#6b7280',
                'badge_text' => 'Reminder',
                'icon' => '📌',
                'title' => 'Asset Return Reminder',
                'message' => "Please remember to return the asset you borrowed."
            ]
        };

        $mail->isHTML(true);
        $mail->Subject = $urgencyConfig['subject'];

        // Format the return date nicely
        $formattedDate = date('l, F j, Y', strtotime($expectedReturnDate));

        // Days message
        $daysMessage = match(true) {
            $daysUntilDue == 0 => 'TODAY',
            $daysUntilDue == 1 => 'TOMORROW',
            $daysUntilDue > 1 => "in {$daysUntilDue} days",
            default => 'OVERDUE'
        };

        // Construct full URLs (handle CLI context)
        $protocol = "https://";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $basePath = '/AMS-REQ';

        $myRequestsUrl = $protocol . $host . $basePath . '/employee/my_requests.php';
        $contactUrl = $protocol . $host . $basePath . '/contact_custodian.php';

        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>{$urgencyConfig['title']}</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .status-badge { display: inline-block; padding: 10px 20px; border-radius: 8px; background-color: {$urgencyConfig['badge_color']}; color: white; font-weight: 600; margin-bottom: 20px; }
                    .icon-large { font-size: 64px; text-align: center; margin-bottom: 20px; }
                    .details-box { background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .details-box p { margin: 0 0 15px; font-size: 15px; color: #555555; text-align: left; }
                    .details-box p:last-child { margin-bottom: 0; }
                    .highlight-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
                    .return-date { font-size: 24px; font-weight: 700; color: {$urgencyConfig['badge_color']}; text-align: center; margin: 20px 0; }
                    .button { display: inline-block; background-color: #0071e3; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; margin: 5px; }
                    .button-secondary { background-color: #6b7280; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <div style='text-align: center; padding: 40px 0 20px;'>
                                            <img src='cid:hcc_logo' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                                        </div>
                                        <div class='content'>
                                            <div class='icon-large'>{$urgencyConfig['icon']}</div>
                                            <div style='text-align: center;'>
                                                <span class='status-badge'>{$urgencyConfig['badge_text']}</span>
                                            </div>
                                            <h1>Hi, " . htmlspecialchars($recipientName) . "</h1>
                                            <p class='main-text'>{$urgencyConfig['message']}</p>

                                            <div class='details-box'>
                                                <p><strong>Asset Borrowed:</strong><br>" . htmlspecialchars($assetName) . "</p>
                                                <p><strong>Request ID:</strong><br>#{$requestId}</p>
                                            </div>

                                            <div class='return-date'>
                                                Return Date: {$formattedDate}
                                            </div>

                                            <div style='text-align: center; font-size: 20px; font-weight: 600; color: {$urgencyConfig['badge_color']}; margin-bottom: 25px;'>
                                                Due {$daysMessage}
                                            </div>

                                            <div class='highlight-box'>
                                                <p style='margin: 0; font-size: 14px; color: #92400e;'><strong>What to do:</strong></p>
                                                <ul style='margin: 10px 0 0 0; padding-left: 20px; color: #92400e;'>
                                                    <li>Return the asset to the custodian office on or before the due date</li>
                                                    <li>Ensure the asset is in good condition</li>
                                                    <li>If you cannot return on time, contact the custodian immediately</li>
                                                </ul>
                                            </div>

                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='{$myRequestsUrl}' class='button'>View My Borrowed Assets</a>
                                                <a href='{$contactUrl}' class='button button-secondary'>Contact Custodian</a>
                                            </div>

                                            <p style='text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px;'>
                                                <strong>Note:</strong> Late returns may affect your future borrowing privileges.
                                            </p>
                                        </div>
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This is an automated reminder from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        $mail->send();
        logActivity($pdo, null, 'EMAIL_SENT', "Return reminder email sent to {$recipientEmail} for request #{$requestId} (Due: {$expectedReturnDate})");
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent to {$recipientEmail}. Mailer Error: {$mail->ErrorInfo}");
        logActivity($pdo, null, 'EMAIL_FAILED', "Failed to send return reminder to {$recipientEmail}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an overdue alert email notification with escalation support
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $recipientEmail The email address of the recipient.
 * @param string $recipientName The full name of the recipient.
 * @param string $assetName The name of the overdue asset.
 * @param string $expectedReturnDate The expected return date.
 * @param int $daysOverdue Days the asset is overdue.
 * @param int $requestId The request/borrowing ID.
 * @param string $recipientRole Role of recipient: 'borrower', 'custodian', 'admin'. Default: 'borrower'.
 * @return bool True on success, false on failure.
 */
function sendOverdueAlertEmail($pdo, $recipientEmail, $recipientName, $assetName, $expectedReturnDate, $daysOverdue, $requestId, $recipientRole = 'borrower') {
    $smtpUser = 'mico.macapugay2004@gmail.com';
    $smtpPass = 'gggm gqng fjgt ukfe';

    if (!$smtpPass) {
        error_log('SMTP_PASS environment variable is not set. Cannot send email.');
        return false;
    }

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
        $mail->addAddress($recipientEmail, $recipientName);

        // Embed the logo image
        $logoPath = __DIR__ . '/../logo/1.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'hcc_logo', 'logo.png');
        }

        // Determine severity level based on days overdue
        $severityConfig = match(true) {
            $daysOverdue <= 3 => [
                'badge_color' => '#f59e0b',
                'badge_text' => 'Overdue',
                'icon' => '⚠️',
                'severity' => 'Warning'
            ],
            $daysOverdue <= 7 => [
                'badge_color' => '#ef4444',
                'badge_text' => 'Seriously Overdue',
                'icon' => '🚨',
                'severity' => 'Urgent'
            ],
            $daysOverdue <= 30 => [
                'badge_color' => '#dc2626',
                'badge_text' => 'Critical',
                'icon' => '🔴',
                'severity' => 'Critical'
            ],
            default => [
                'badge_color' => '#7f1d1d',
                'badge_text' => 'Potentially Lost',
                'icon' => '❗',
                'severity' => 'Emergency'
            ]
        };

        // Role-specific messaging
        if ($recipientRole === 'borrower') {
            $subject = "OVERDUE: Return {$assetName} Immediately ({$daysOverdue} days overdue)";
            $title = "Overdue Asset - Action Required";
            $message = "The asset you borrowed is now {$daysOverdue} day(s) overdue. You must return it immediately to avoid further consequences.";
        } elseif ($recipientRole === 'custodian') {
            $subject = "Alert: Asset Overdue - {$assetName} ({$daysOverdue} days)";
            $title = "Overdue Asset Alert (Custodian)";
            $message = "An asset under your custody is overdue and requires follow-up with the borrower.";
        } else { // admin
            $subject = "Escalation: Asset Overdue - {$assetName} ({$daysOverdue} days)";
            $title = "Overdue Asset Escalation (Admin)";
            $message = "An asset is significantly overdue and requires administrative intervention.";
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Format the return date
        $formattedDate = date('l, F j, Y', strtotime($expectedReturnDate));

        // Construct full URLs
        $protocol = "https://";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $basePath = '/AMS-REQ';

        $actionUrl = $recipientRole === 'borrower'
            ? $protocol . $host . $basePath . '/employee/my_requests.php'
            : $protocol . $host . $basePath . '/custodian/return_assets.php';

        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>{$title}</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .status-badge { display: inline-block; padding: 10px 20px; border-radius: 8px; background-color: {$severityConfig['badge_color']}; color: white; font-weight: 600; margin-bottom: 20px; }
                    .icon-large { font-size: 64px; text-align: center; margin-bottom: 20px; }
                    .details-box { background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .details-box p { margin: 0 0 15px; font-size: 15px; color: #555555; text-align: left; }
                    .details-box p:last-child { margin-bottom: 0; }
                    .alert-box { background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
                    .overdue-highlight { font-size: 32px; font-weight: 700; color: {$severityConfig['badge_color']}; text-align: center; margin: 20px 0; }
                    .button { display: inline-block; background-color: #dc2626; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; margin: 5px; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <div style='text-align: center; padding: 40px 0 20px;'>
                                            <img src='cid:hcc_logo' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                                        </div>
                                        <div class='content'>
                                            <div class='icon-large'>{$severityConfig['icon']}</div>
                                            <div style='text-align: center;'>
                                                <span class='status-badge'>{$severityConfig['badge_text']}</span>
                                            </div>
                                            <h1>{$title}</h1>
                                            <p class='main-text'>{$message}</p>

                                            <div class='overdue-highlight'>
                                                {$daysOverdue} DAYS OVERDUE
                                            </div>

                                            <div class='details-box'>
                                                <p><strong>Asset:</strong><br>" . htmlspecialchars($assetName) . "</p>
                                                <p><strong>Request ID:</strong><br>#{$requestId}</p>
                                                <p><strong>Expected Return Date:</strong><br>{$formattedDate}</p>
                                                <p><strong>Days Overdue:</strong><br><span style='color: {$severityConfig['badge_color']}; font-weight: 700;'>{$daysOverdue} days</span></p>
                                            </div>

                                            <div class='alert-box'>
                                                <p style='margin: 0; font-size: 14px; color: #991b1b;'><strong>Immediate Action Required:</strong></p>
                                                <ul style='margin: 10px 0 0 0; padding-left: 20px; color: #991b1b;'>
                                                    " . ($recipientRole === 'borrower'
                                                        ? "<li>Return the asset to the custodian office immediately</li>
                                                           <li>Provide explanation for the delay</li>
                                                           <li>Late returns may result in suspension of borrowing privileges</li>"
                                                        : "<li>Contact the borrower immediately</li>
                                                           <li>Document the follow-up in the system</li>
                                                           <li>Escalate if asset cannot be recovered</li>") . "
                                                </ul>
                                            </div>

                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='{$actionUrl}' class='button'>Take Action Now</a>
                                            </div>

                                            <p style='text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px;'>
                                                <strong>Severity Level:</strong> {$severityConfig['severity']}
                                            </p>
                                        </div>
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This is an automated alert from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        $mail->send();
        logActivity($pdo, null, 'EMAIL_SENT', "Overdue alert email sent to {$recipientEmail} ({$recipientRole}) for request #{$requestId} ({$daysOverdue} days overdue)");
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent to {$recipientEmail}. Mailer Error: {$mail->ErrorInfo}");
        logActivity($pdo, null, 'EMAIL_FAILED', "Failed to send overdue alert to {$recipientEmail}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends a missing asset alert email notification
 * Used when an asset is reported as missing
 *
 * @param PDO $pdo The PDO database connection object
 * @param string $recipientEmail The email address of the recipient
 * @param string $recipientName The full name of the recipient
 * @param string $assetName The name of the missing asset
 * @param string $assetCode The asset code/tag number
 * @param int $reportId The missing asset report ID
 * @param string $recipientRole Role of recipient: 'reporter', 'custodian', 'admin'
 * @param array $reportDetails Additional report details
 * @return bool True on success, false on failure
 */
function sendMissingAssetAlertEmail($pdo, $recipientEmail, $recipientName, $assetName, $assetCode, $reportId, $recipientRole = 'custodian', $reportDetails = []) {
    $smtpUser = 'mico.macapugay2004@gmail.com';
    $smtpPass = 'gggm gqng fjgt ukfe';

    if (!$smtpPass) {
        error_log('SMTP password not configured. Cannot send email.');
        return false;
    }

    // Configure role-specific messaging
    $roleConfig = match($recipientRole) {
        'reporter' => [
            'title' => 'Missing Asset Report Confirmed',
            'message' => "Your missing asset report has been received and logged in the system.",
            'icon' => '✅',
            'badge_color' => '#16a34a',
            'badge_text' => 'Report Confirmed',
            'action_text' => 'View Report Status',
        ],
        'custodian' => [
            'title' => 'URGENT: Asset Reported Missing',
            'message' => "An asset under your campus has been reported as missing. Immediate investigation required.",
            'icon' => '🚨',
            'badge_color' => '#dc2626',
            'badge_text' => 'Investigation Required',
            'action_text' => 'Start Investigation',
        ],
        'admin' => [
            'title' => 'ALERT: Missing Asset Report',
            'message' => "A missing asset has been reported. Please review and assign investigation.",
            'icon' => '⚠️',
            'badge_color' => '#ea580c',
            'badge_text' => 'Admin Alert',
            'action_text' => 'Review Report',
        ],
        default => [
            'title' => 'Missing Asset Notification',
            'message' => "You are being notified about a missing asset report.",
            'icon' => '📋',
            'badge_color' => '#3b82f6',
            'badge_text' => 'Notification',
            'action_text' => 'View Details',
        ],
    };

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
        $mail->Timeout    = 5; // Reduced from 10 to 5 seconds for faster failures
        $mail->SMTPKeepAlive = true;
        $mail->SMTPDebug  = 0; // Disable debug output for speed

        // Recipients
        $mail->setFrom($smtpUser, 'HCC Asset Management System');
        $mail->addAddress($recipientEmail, $recipientName);

        // Embed logo
        $logoPath = dirname(__DIR__) . '/logo/1.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'hcc_logo', 'logo.png');
        }

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $roleConfig['title'];

        // Extract report details
        $lastKnownLocation = $reportDetails['last_known_location'] ?? 'Unknown';
        $lastKnownBorrower = $reportDetails['last_known_borrower'] ?? 'Not specified';
        $lastSeenDate = isset($reportDetails['last_seen_date'])
            ? date('F j, Y', strtotime($reportDetails['last_seen_date']))
            : 'Unknown';
        $reportedBy = $reportDetails['reported_by_name'] ?? 'Unknown';
        $reportedDate = isset($reportDetails['reported_date'])
            ? date('F j, Y g:i A', strtotime($reportDetails['reported_date']))
            : date('F j, Y g:i A');
        $description = $reportDetails['description'] ?? 'No description provided';

        // Determine action URL based on role
        $actionUrl = match($recipientRole) {
            'reporter' => 'http://localhost/AMS-REQ/employee/my_requests.php',
            'custodian' => 'http://localhost/AMS-REQ/custodian/missing_assets.php',
            'admin' => 'http://localhost/AMS-REQ/custodian/missing_assets.php',
            default => 'http://localhost/AMS-REQ/dashboard.php'
        };

        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>{$roleConfig['title']}</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .status-badge { display: inline-block; padding: 10px 20px; border-radius: 8px; background-color: {$roleConfig['badge_color']}; color: white; font-weight: 600; margin-bottom: 20px; }
                    .icon-large { font-size: 64px; text-align: center; margin-bottom: 20px; }
                    .details-box { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .details-box p { margin: 0 0 15px; font-size: 15px; color: #374151; text-align: left; }
                    .details-box p:last-child { margin-bottom: 0; }
                    .details-box strong { color: #111827; }
                    .alert-box { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
                    .description-box { background-color: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 15px; margin-bottom: 25px; }
                    .button { display: inline-block; background-color: {$roleConfig['badge_color']}; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; margin: 5px; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <div style='text-align: center; padding: 40px 0 20px;'>
                                            <img src='cid:hcc_logo' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                                        </div>
                                        <div class='content'>
                                            <div class='icon-large'>{$roleConfig['icon']}</div>
                                            <div style='text-align: center;'>
                                                <span class='status-badge'>{$roleConfig['badge_text']}</span>
                                            </div>
                                            <h1>{$roleConfig['title']}</h1>
                                            <p class='main-text'>{$roleConfig['message']}</p>

                                            <div class='details-box'>
                                                <p><strong>Report ID:</strong><br>#{$reportId}</p>
                                                <p><strong>Asset Name:</strong><br>" . htmlspecialchars($assetName) . "</p>
                                                <p><strong>Asset Code:</strong><br>{$assetCode}</p>
                                                <p><strong>Last Known Location:</strong><br>{$lastKnownLocation}</p>
                                                <p><strong>Last Known Borrower:</strong><br>{$lastKnownBorrower}</p>
                                                <p><strong>Last Seen Date:</strong><br>{$lastSeenDate}</p>
                                                <p><strong>Reported By:</strong><br>{$reportedBy}</p>
                                                <p><strong>Reported On:</strong><br>{$reportedDate}</p>
                                            </div>

                                            <div class='description-box'>
                                                <p style='margin: 0 0 10px; font-size: 14px; font-weight: 600; color: #92400e;'>Report Description:</p>
                                                <p style='margin: 0; font-size: 14px; color: #78350f; white-space: pre-wrap;'>" . htmlspecialchars($description) . "</p>
                                            </div>

                                            " . ($recipientRole === 'custodian' || $recipientRole === 'admin' ? "
                                            <div class='alert-box'>
                                                <p style='margin: 0 0 10px; font-size: 14px; color: #991b1b; font-weight: 600;'>Immediate Actions Required:</p>
                                                <ul style='margin: 0; padding-left: 20px; color: #991b1b; font-size: 14px;'>
                                                    <li>Review the missing asset report details</li>
                                                    <li>Contact the reporter and last known borrower</li>
                                                    <li>Check the last known location</li>
                                                    <li>Begin formal investigation process</li>
                                                    <li>Update report status in the system</li>
                                                </ul>
                                            </div>
                                            " : "") . "

                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='{$actionUrl}' class='button'>{$roleConfig['action_text']}</a>
                                            </div>

                                            <p style='text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px;'>
                                                This is a time-sensitive notification. Please take action as soon as possible.
                                            </p>
                                        </div>
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This is an automated alert from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        $mail->send();
        logActivity($pdo, null, 'EMAIL_SENT', "Missing asset alert email sent to {$recipientEmail} ({$recipientRole}) for report #{$reportId}");
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent to {$recipientEmail}. Mailer Error: {$mail->ErrorInfo}");
        logActivity($pdo, null, 'EMAIL_FAILED', "Failed to send missing asset alert to {$recipientEmail}. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Queues a missing asset alert email for asynchronous sending
 * This function returns immediately, allowing the background worker to send the email
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $recipientEmail The email address of the recipient.
 * @param string $recipientName The full name of the recipient.
 * @param string $assetName The name of the missing asset.
 * @param string $assetCode The asset code/barcode.
 * @param int $reportId The missing asset report ID.
 * @param string $recipientRole The role of the recipient (reporter/custodian/admin).
 * @param array $reportDetails Additional report details.
 * @return bool True on success, false on failure.
 */
function queueMissingAssetAlertEmail($pdo, $recipientEmail, $recipientName, $assetName, $assetCode, $reportId, $recipientRole = 'custodian', $reportDetails = []) {
    try {
        // Configure role-specific messaging
        $roleConfig = match($recipientRole) {
            'reporter' => [
                'title' => 'Missing Asset Report Confirmed',
                'message' => "Your missing asset report has been received and logged in the system.",
                'icon' => '✅',
                'badge_color' => '#16a34a',
                'badge_text' => 'Report Confirmed',
                'action_text' => 'View Report Status',
                'priority' => 'high' // Reporter gets confirmation fast
            ],
            'custodian' => [
                'title' => 'URGENT: Asset Reported Missing',
                'message' => "An asset under your campus has been reported as missing. Immediate investigation required.",
                'icon' => '🚨',
                'badge_color' => '#dc2626',
                'badge_text' => 'Investigation Required',
                'action_text' => 'Start Investigation',
                'priority' => 'high' // Urgent for custodians
            ],
            'admin' => [
                'title' => 'ALERT: Missing Asset Report',
                'message' => "A missing asset has been reported. Please review and assign investigation.",
                'icon' => '⚠️',
                'badge_color' => '#ea580c',
                'badge_text' => 'Admin Alert',
                'action_text' => 'Review Report',
                'priority' => 'normal' // Admins get informed but not urgent
            ],
            default => [
                'title' => 'Missing Asset Notification',
                'message' => "You are being notified about a missing asset report.",
                'icon' => '📋',
                'badge_color' => '#3b82f6',
                'badge_text' => 'Notification',
                'action_text' => 'View Details',
                'priority' => 'normal'
            ],
        };

        // Extract report details
        $lastKnownLocation = $reportDetails['last_known_location'] ?? 'Unknown';
        $lastKnownBorrower = $reportDetails['last_known_borrower'] ?? 'Not specified';
        $lastSeenDate = isset($reportDetails['last_seen_date'])
            ? date('F j, Y', strtotime($reportDetails['last_seen_date']))
            : 'Unknown';
        $reportedBy = $reportDetails['reported_by_name'] ?? 'Unknown';
        $reportedDate = isset($reportDetails['reported_date'])
            ? date('F j, Y g:i A', strtotime($reportDetails['reported_date']))
            : date('F j, Y g:i A');
        $description = $reportDetails['description'] ?? 'No description provided';

        // Determine action URL based on role
        $actionUrl = match($recipientRole) {
            'reporter' => 'http://localhost/AMS-REQ/employee/my_requests.php',
            'custodian' => 'http://localhost/AMS-REQ/custodian/missing_assets.php',
            'admin' => 'http://localhost/AMS-REQ/custodian/missing_assets.php',
            default => 'http://localhost/AMS-REQ/dashboard.php'
        };

        // Build the email HTML body
        $emailBody = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>{$roleConfig['title']}</title>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap' rel='stylesheet'>
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                    .email-wrapper { width: 100%; background-color: #f4f4f4; padding: 20px 0; }
                    .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 18px; overflow: hidden; }
                    .content { padding: 40px; }
                    .footer { padding: 30px 40px; text-align: center; font-size: 12px; color: #888888; background-color: #f9f9f9; border-top: 1px solid #e5e5e5; }
                    h1 { font-size: 28px; font-weight: 600; color: #000000; margin: 0 0 15px; text-align: center; }
                    p.main-text { font-size: 17px; color: #000000; line-height: 1.5; margin: 0 0 25px; text-align: center; }
                    .status-badge { display: inline-block; padding: 10px 20px; border-radius: 8px; background-color: {$roleConfig['badge_color']}; color: white; font-weight: 600; margin-bottom: 20px; }
                    .icon-large { font-size: 64px; text-align: center; margin-bottom: 20px; }
                    .details-box { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
                    .details-box p { margin: 0 0 15px; font-size: 15px; color: #374151; text-align: left; }
                    .details-box p:last-child { margin-bottom: 0; }
                    .details-box strong { color: #111827; }
                    .alert-box { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 25px; }
                    .description-box { background-color: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 15px; margin-bottom: 25px; }
                    .button { display: inline-block; background-color: {$roleConfig['badge_color']}; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; margin: 5px; }
                    .footer-link { color: #0071e3; text-decoration: none; }
                </style>
            </head>
            <body>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' class='email-wrapper'>
                    <tr>
                        <td align='center'>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='email-container'>
                                <tr>
                                    <td>
                                        <div class='content'>
                                            <div class='icon-large'>{$roleConfig['icon']}</div>
                                            <div style='text-align: center;'>
                                                <span class='status-badge'>{$roleConfig['badge_text']}</span>
                                            </div>
                                            <h1>{$roleConfig['title']}</h1>
                                            <p class='main-text'>{$roleConfig['message']}</p>

                                            <div class='details-box'>
                                                <p><strong>Report ID:</strong><br>#{$reportId}</p>
                                                <p><strong>Asset Name:</strong><br>" . htmlspecialchars($assetName) . "</p>
                                                <p><strong>Asset Code:</strong><br>{$assetCode}</p>
                                                <p><strong>Last Known Location:</strong><br>{$lastKnownLocation}</p>
                                                <p><strong>Last Known Borrower:</strong><br>{$lastKnownBorrower}</p>
                                                <p><strong>Last Seen Date:</strong><br>{$lastSeenDate}</p>
                                                <p><strong>Reported By:</strong><br>{$reportedBy}</p>
                                                <p><strong>Reported On:</strong><br>{$reportedDate}</p>
                                            </div>

                                            <div class='description-box'>
                                                <p style='margin: 0 0 10px; font-size: 14px; font-weight: 600; color: #92400e;'>Report Description:</p>
                                                <p style='margin: 0; font-size: 14px; color: #78350f; white-space: pre-wrap;'>" . htmlspecialchars($description) . "</p>
                                            </div>

                                            " . ($recipientRole === 'custodian' || $recipientRole === 'admin' ? "
                                            <div class='alert-box'>
                                                <p style='margin: 0 0 10px; font-size: 14px; color: #991b1b; font-weight: 600;'>Immediate Actions Required:</p>
                                                <ul style='margin: 0; padding-left: 20px; color: #991b1b; font-size: 14px;'>
                                                    <li>Review the missing asset report details</li>
                                                    <li>Contact the reporter and last known borrower</li>
                                                    <li>Check the last known location</li>
                                                    <li>Begin formal investigation process</li>
                                                    <li>Update report status in the system</li>
                                                </ul>
                                            </div>
                                            " : "") . "

                                            <div style='text-align: center; margin-bottom: 20px;'>
                                                <a href='{$actionUrl}' class='button'>{$roleConfig['action_text']}</a>
                                            </div>

                                            <p style='text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px;'>
                                                This is a time-sensitive notification. Please take action as soon as possible.
                                            </p>
                                        </div>
                                        <div class='footer'>
                                            <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                            <p style='margin: 0 0 10px; font-size: 12px; color: #888888; text-align: center;'>
                                                This is an automated alert from the HCC Asset Management System.
                                            </p>
                                            <p style='margin: 0; font-size: 12px; color: #888888; text-align: center;'>
                                                &copy; " . date('Y') . " Holy Cross Colleges. All rights reserved.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>";

        // Insert into email queue for async processing
        $stmt = $pdo->prepare("
            INSERT INTO email_queue (
                recipient_email, recipient_name, subject, body_html,
                status, priority, related_type, related_id,
                next_retry_at
            ) VALUES (
                ?, ?, ?, ?,
                'pending', ?, 'missing_asset_report', ?,
                NOW()
            )
        ");

        $stmt->execute([
            $recipientEmail,
            $recipientName,
            $roleConfig['title'],
            $emailBody,
            $roleConfig['priority'],
            $reportId
        ]);

        return true;
    } catch (Exception $e) {
        error_log("Failed to queue missing asset alert email: " . $e->getMessage());
        return false;
    }
}
?>
