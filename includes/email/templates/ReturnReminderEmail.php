<?php
/**
 * Return Reminder Email Template
 *
 * Sent to remind borrowers to return assets
 * Supports multiple urgency levels:
 * - advance_notice: 7+ days before due
 * - upcoming: 3-6 days before due
 * - urgent: 1-2 days before due
 * - today: Due today
 */

require_once __DIR__ . '/../EmailTemplate.php';
require_once __DIR__ . '/../EmailConfig.php';

class ReturnReminderEmail {

    public function getSubject($urgency, $assetName, $daysUntilDue) {
        return match($urgency) {
            'advance_notice' => 'Upcoming Return: ' . $assetName,
            'upcoming' => 'Reminder: Return ' . $assetName . ' in ' . $daysUntilDue . ' Days',
            'urgent' => 'URGENT: Return ' . $assetName . ' Tomorrow!',
            'today' => 'ACTION REQUIRED: Return ' . $assetName . ' TODAY',
            default => 'Return Reminder: ' . $assetName
        };
    }

    public function render($recipientName, $assetName, $returnDate, $daysUntilDue, $requestId, $urgency) {
        $urgencyConfig = $this->getUrgencyConfig($urgency);
        $myRequestsUrl = EmailConfig::url('employee/my_requests.php');
        $contactUrl = EmailConfig::url('contact_custodian.php');

        $formattedDate = date('l, F j, Y', strtotime($returnDate));
        $daysMessage = $this->getDaysMessage($daysUntilDue);

        $content = "
            " . EmailTemplate::largeIcon($urgencyConfig['icon']) . "
            <div style='text-align: center;'>
                " . EmailTemplate::badge($urgencyConfig['badge_text'], $urgencyConfig['badge_color']) . "
            </div>
            <h1>Hi, " . htmlspecialchars($recipientName) . "</h1>
            <p class='main-text'>{$urgencyConfig['message']}</p>

            " . EmailTemplate::detailsBox([
                'Asset Borrowed' => htmlspecialchars($assetName),
                'Request ID' => '#' . $requestId
            ]) . "

            " . EmailTemplate::highlightBox("Return Date: {$formattedDate}", $urgencyConfig['badge_color']) . "

            <div style='text-align: center; font-size: 20px; font-weight: 600; color: {$urgencyConfig['badge_color']}; margin-bottom: 25px;'>
                Due {$daysMessage}
            </div>

            " . EmailTemplate::alertBox(
                '<strong>What to do:</strong>',
                [
                    'Return the asset to the custodian office on or before the due date',
                    'Ensure the asset is in good condition',
                    'If you cannot return on time, contact the custodian immediately'
                ],
                'warning'
            ) . "

            <div style='text-align: center; margin-bottom: 20px;'>
                " . EmailTemplate::button('View My Borrowed Assets', $myRequestsUrl) . "
                " . EmailTemplate::button('Contact Custodian', $contactUrl, '#6b7280') . "
            </div>

            <p style='text-align: center; font-size: 13px; color: #6b7280; margin-top: 30px;'>
                <strong>Note:</strong> Late returns may affect your future borrowing privileges.
            </p>
        ";

        return EmailTemplate::render($urgencyConfig['title'], $content);
    }

    private function getUrgencyConfig($urgency) {
        return match($urgency) {
            'advance_notice' => [
                'title' => 'Upcoming Asset Return',
                'badge_color' => '#0071e3',
                'badge_text' => 'Advance Notice',
                'icon' => '📅',
                'message' => 'This is an advance notice that you have borrowed an asset that will be due soon.'
            ],
            'upcoming' => [
                'title' => 'Asset Return Reminder',
                'badge_color' => '#f59e0b',
                'badge_text' => 'Return Soon',
                'icon' => '⏰',
                'message' => 'Please prepare to return the asset you borrowed within the next few days.'
            ],
            'urgent' => [
                'title' => 'Urgent Return Reminder',
                'badge_color' => '#ef4444',
                'badge_text' => 'Due Tomorrow',
                'icon' => '🚨',
                'message' => 'Your borrowed asset is due tomorrow. Please make arrangements to return it on time.'
            ],
            'today' => [
                'title' => 'Asset Due Today',
                'badge_color' => '#dc2626',
                'badge_text' => 'Due Today',
                'icon' => '⚠️',
                'message' => 'Your borrowed asset is due for return TODAY. Please return it to the custodian office immediately.'
            ],
            default => [
                'title' => 'Asset Return Reminder',
                'badge_color' => '#6b7280',
                'badge_text' => 'Reminder',
                'icon' => '📌',
                'message' => 'Please remember to return the asset you borrowed.'
            ]
        };
    }

    private function getDaysMessage($days) {
        return match(true) {
            $days == 0 => 'TODAY',
            $days == 1 => 'TOMORROW',
            $days > 1 => "in {$days} days",
            default => 'OVERDUE'
        };
    }
}
