<?php
/**
 * Account Creation Email Template
 *
 * Sent when a new user account is created
 * Provides login credentials and welcome message
 */

require_once __DIR__ . '/../EmailTemplate.php';
require_once __DIR__ . '/../EmailConfig.php';

class AccountCreationEmail {

    public function getSubject() {
        return 'Welcome to HCC Asset Management - Your Account Details';
    }

    public function render($recipientName, $recipientEmail, $defaultPassword) {
        $loginUrl = EmailConfig::url('login.php');
        $learnMoreUrl = EmailConfig::url('about.php');

        $content = "
            <h1>Welcome, " . htmlspecialchars($recipientName) . ".</h1>
            <p class='main-text'>An account has been created for you in the Holy Cross Colleges Asset Management System. Here are your login details:</p>

            " . EmailTemplate::detailsBox([
                'Username / Email' => '<span style="color: #000000;">' . htmlspecialchars($recipientEmail) . '</span>',
                'Default Password' => '<span style="font-size: 20px; color: #0071e3; letter-spacing: 1px; font-weight: 600;">' . htmlspecialchars($defaultPassword) . '</span>'
            ]) . "

            " . EmailTemplate::alertBox(
                '<strong>Important:</strong> For security, you will be required to change this password upon your first login.',
                [],
                'warning'
            ) . "

            <div style='text-align: center; margin-bottom: 20px;'>
                " . EmailTemplate::button('Login to Your Account', $loginUrl) . "
            </div>
        ";

        return EmailTemplate::render($this->getSubject(), $content);
    }
}
