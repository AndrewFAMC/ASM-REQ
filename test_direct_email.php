<?php
/**
 * Direct email test - bypasses all notification logic
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Direct Email Send Test</h2>";

// Load PHPMailer
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$testEmail = 'jemusubeley@gmail.com'; // Custodian email
$testName = 'Mico';

echo "<p>Attempting to send test email to: <strong>$testEmail</strong></p>";

$mail = new PHPMailer(true);

try {
    // Server Settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'mico.macapugay2004@gmail.com';
    $mail->Password   = 'gggm gqng fjgt ukfe';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Enable verbose debug output
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = function($str, $level) {
        echo "Debug level $level: $str<br>";
    };

    // Recipients
    $mail->setFrom('mico.macapugay2004@gmail.com', 'HCC Asset Management System');
    $mail->addAddress($testEmail, $testName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Direct Email Test - HCC Asset Management';
    $mail->Body    = '<h1>Test Email</h1><p>This is a direct email test from the asset management system.</p><p>If you receive this, the email system is working correctly.</p>';

    $mail->send();
    echo '<div style="color: green; font-weight: bold; margin-top: 20px;">✓ Email sent successfully!</div>';
    echo '<p>Check the email inbox for: ' . htmlspecialchars($testEmail) . '</p>';

} catch (Exception $e) {
    echo '<div style="color: red; font-weight: bold; margin-top: 20px;">✗ Email sending failed!</div>';
    echo '<p>Error: ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
    echo '<p>Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
