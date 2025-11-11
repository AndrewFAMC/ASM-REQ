<?php
/**
 * Tag Generation Email Template
 *
 * Sent to office users when a new inventory tag is generated
 * for an asset assigned to their office
 */

require_once __DIR__ . '/../EmailTemplate.php';
require_once __DIR__ . '/../EmailConfig.php';

class TagGenerationEmail {

    public function getSubject() {
        return 'New Inventory Tag Generated - Action Required';
    }

    public function render($recipientName, $assetName, $tagNumber, $custodianName) {
        $loginUrl = EmailConfig::url('login.php');
        $generatedDate = date('F j, Y');

        $content = "
            <h1>New Inventory Tag Generated</h1>
            <p class='main-text'>A new inventory tag has been generated for an asset assigned to your office. Please verify the asset details and confirm receipt.</p>

            " . EmailTemplate::detailsBox([
                'Asset Name' => $assetName,
                'Tag Number' => $tagNumber,
                'Assigned By' => $custodianName,
                'Generated Date' => $generatedDate
            ]) . "

            " . EmailTemplate::alertBox(
                '<strong>Action Required:</strong> Please log in to the system to verify the asset assignment and update its status.',
                [],
                'warning'
            ) . "

            <div style='text-align: center; margin-bottom: 20px;'>
                " . EmailTemplate::button('Login to Verify', $loginUrl) . "
            </div>
        ";

        return EmailTemplate::render($this->getSubject(), $content);
    }
}
