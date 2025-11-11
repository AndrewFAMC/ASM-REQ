<?php
/**
 * Overdue Alert Email Template
 *
 * Sent when borrowed assets are overdue
 * Supports different recipient roles:
 * - borrower: Asset borrower (urgent action required)
 * - custodian: Asset custodian (follow-up required)
 * - admin: System administrator (escalation)
 *
 * Severity escalates based on days overdue
 */

require_once __DIR__ . '/../EmailTemplate.php';
require_once __DIR__ . '/../EmailConfig.php';

class OverdueAlertEmail {

    public function getSubject($role, $assetName, $daysOverdue) {
        return match($role) {
            'borrower' => "OVERDUE: Return {$assetName} Immediately ({$daysOverdue} days overdue)",
            'custodian' => "Alert: Asset Overdue - {$assetName} ({$daysOverdue} days)",
            'admin' => "Escalation: Asset Overdue - {$assetName} ({$daysOverdue} days)",
            default => "Overdue Asset Alert - {$assetName}"
        };
    }

    public function render($recipientName, $assetName, $returnDate, $daysOverdue, $requestId, $role) {
        $severityConfig = $this->getSeverityConfig($daysOverdue);
        $roleConfig = $this->getRoleConfig($role);

        $formattedDate = date('l, F j, Y', strtotime($returnDate));
        $actionUrl = $this->getActionUrl($role);

        $content = "
            " . EmailTemplate::largeIcon($severityConfig['icon']) . "
            <div style='text-align: center;'>
                " . EmailTemplate::badge($severityConfig['badge_text'], $severityConfig['badge_color']) . "
            </div>
            <h1>{$roleConfig['title']}</h1>
            <p class='main-text'>{$roleConfig['message']}</p>

            " . EmailTemplate::highlightBox("{$daysOverdue} DAYS OVERDUE", $severityConfig['badge_color']) . "

            " . EmailTemplate::detailsBox([
                'Asset' => htmlspecialchars($assetName),
                'Request ID' => '#' . $requestId,
                'Expected Return Date' => $formattedDate,
                'Days Overdue' => "<span style='color: {$severityConfig['badge_color']}; font-weight: 700;'>{$daysOverdue} days</span>"
            ]) . "

            " . EmailTemplate::alertBox(
                '<strong>Immediate Action Required:</strong>',
                $roleConfig['action_items'],
                'danger'
            ) . "

            <div style='text-align: center; margin-bottom: 20px;'>
                " . EmailTemplate::button('Take Action Now', $actionUrl, '#dc2626') . "
            </div>

            <p style='text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px;'>
                <strong>Severity Level:</strong> {$severityConfig['severity']}
            </p>
        ";

        return EmailTemplate::render($roleConfig['title'], $content);
    }

    private function getSeverityConfig($daysOverdue) {
        return match(true) {
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
    }

    private function getRoleConfig($role) {
        return match($role) {
            'borrower' => [
                'title' => 'Overdue Asset - Action Required',
                'message' => 'The asset you borrowed is now overdue. You must return it immediately to avoid further consequences.',
                'action_items' => [
                    'Return the asset to the custodian office immediately',
                    'Provide explanation for the delay',
                    'Late returns may result in suspension of borrowing privileges'
                ]
            ],
            'custodian' => [
                'title' => 'Overdue Asset Alert (Custodian)',
                'message' => 'An asset under your custody is overdue and requires follow-up with the borrower.',
                'action_items' => [
                    'Contact the borrower immediately',
                    'Document the follow-up in the system',
                    'Escalate if asset cannot be recovered'
                ]
            ],
            'admin' => [
                'title' => 'Overdue Asset Escalation (Admin)',
                'message' => 'An asset is significantly overdue and requires administrative intervention.',
                'action_items' => [
                    'Review the asset history and borrower record',
                    'Coordinate with custodian for recovery',
                    'Consider disciplinary action if necessary',
                    'Update asset status if lost'
                ]
            ],
            default => [
                'title' => 'Overdue Asset Notification',
                'message' => 'An asset is overdue and requires attention.',
                'action_items' => [
                    'Review the asset details',
                    'Take appropriate action'
                ]
            ]
        };
    }

    private function getActionUrl($role) {
        return match($role) {
            'borrower' => EmailConfig::url('employee/my_requests.php'),
            'custodian' => EmailConfig::url('custodian/return_assets.php'),
            'admin' => EmailConfig::url('admin/overdue_assets.php'),
            default => EmailConfig::url('dashboard.php')
        };
    }
}
