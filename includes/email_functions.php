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
?>
