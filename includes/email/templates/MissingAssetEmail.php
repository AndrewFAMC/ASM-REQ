<?php
/**
 * Missing Asset Email Template
 *
 * Sent when an asset is reported as missing
 * Different messaging for different roles:
 * - reporter: Confirmation that report was received
 * - custodian: Urgent investigation required
 * - admin: Review and assign investigation
 */

require_once __DIR__ . '/../EmailTemplate.php';
require_once __DIR__ . '/../EmailConfig.php';

class MissingAssetEmail {

    public function getSubject($role) {
        return match($role) {
            'reporter' => 'Missing Asset Report Confirmed',
            'custodian' => 'URGENT: Asset Reported Missing',
            'admin' => 'ALERT: Missing Asset Report',
            default => 'Missing Asset Notification'
        };
    }

    public function render($recipientName, $assetName, $assetCode, $reportId, $role, $details) {
        $roleConfig = $this->getRoleConfig($role);
        $actionUrl = $this->getActionUrl($role);

        // Extract report details
        $lastKnownLocation = $details['last_known_location'] ?? 'Unknown';
        $lastKnownBorrower = $details['last_known_borrower'] ?? 'Not specified';
        $lastSeenDate = isset($details['last_seen_date'])
            ? date('F j, Y', strtotime($details['last_seen_date']))
            : 'Unknown';
        $reportedBy = $details['reported_by_name'] ?? 'Unknown';
        $reportedDate = isset($details['reported_date'])
            ? date('F j, Y g:i A', strtotime($details['reported_date']))
            : date('F j, Y g:i A');
        $description = $details['description'] ?? 'No description provided';

        $content = "
            " . EmailTemplate::largeIcon($roleConfig['icon']) . "
            <div style='text-align: center;'>
                " . EmailTemplate::badge($roleConfig['badge_text'], $roleConfig['badge_color']) . "
            </div>
            <h1>{$roleConfig['title']}</h1>
            <p class='main-text'>{$roleConfig['message']}</p>

            " . EmailTemplate::detailsBox([
                'Report ID' => '#' . $reportId,
                'Asset Name' => htmlspecialchars($assetName),
                'Asset Code' => $assetCode,
                'Last Known Location' => $lastKnownLocation,
                'Last Known Borrower' => $lastKnownBorrower,
                'Last Seen Date' => $lastSeenDate,
                'Reported By' => $reportedBy,
                'Reported On' => $reportedDate
            ]) . "

            " . EmailTemplate::descriptionBox('Report Description:', $description) . "
        ";

        // Add action items for custodian/admin only
        if ($role === 'custodian' || $role === 'admin') {
            $content .= EmailTemplate::alertBox(
                '<strong>Immediate Actions Required:</strong>',
                [
                    'Review the missing asset report details',
                    'Contact the reporter and last known borrower',
                    'Check the last known location',
                    'Begin formal investigation process',
                    'Update report status in the system'
                ],
                'danger'
            );
        }

        $content .= "
            <div style='text-align: center; margin-bottom: 20px;'>
                " . EmailTemplate::button($roleConfig['action_text'], $actionUrl, $roleConfig['badge_color']) . "
            </div>

            <p style='text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px;'>
                This is a time-sensitive notification. Please take action as soon as possible.
            </p>
        ";

        return EmailTemplate::render($roleConfig['title'], $content);
    }

    private function getRoleConfig($role) {
        return match($role) {
            'reporter' => [
                'title' => 'Missing Asset Report Confirmed',
                'message' => 'Your missing asset report has been received and logged in the system.',
                'icon' => '✅',
                'badge_color' => '#16a34a',
                'badge_text' => 'Report Confirmed',
                'action_text' => 'View Report Status'
            ],
            'custodian' => [
                'title' => 'URGENT: Asset Reported Missing',
                'message' => 'An asset under your campus has been reported as missing. Immediate investigation required.',
                'icon' => '🚨',
                'badge_color' => '#dc2626',
                'badge_text' => 'Investigation Required',
                'action_text' => 'Start Investigation'
            ],
            'admin' => [
                'title' => 'ALERT: Missing Asset Report',
                'message' => 'A missing asset has been reported. Please review and assign investigation.',
                'icon' => '⚠️',
                'badge_color' => '#ea580c',
                'badge_text' => 'Admin Alert',
                'action_text' => 'Review Report'
            ],
            default => [
                'title' => 'Missing Asset Notification',
                'message' => 'You are being notified about a missing asset report.',
                'icon' => '📋',
                'badge_color' => '#3b82f6',
                'badge_text' => 'Notification',
                'action_text' => 'View Details'
            ]
        };
    }

    private function getActionUrl($role) {
        return match($role) {
            'reporter' => EmailConfig::url('employee/my_requests.php'),
            'custodian' => EmailConfig::url('custodian/missing_assets.php'),
            'admin' => EmailConfig::url('custodian/missing_assets.php'),
            default => EmailConfig::url('dashboard.php')
        };
    }
}
