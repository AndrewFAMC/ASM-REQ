<?php
/**
 * Asset Request Email Template
 *
 * Sent for various asset request status updates:
 * - Approved
 * - Rejected
 * - Released
 * - Returned
 * - Late Return
 */

require_once __DIR__ . '/../EmailTemplate.php';
require_once __DIR__ . '/../EmailConfig.php';

class AssetRequestEmail {

    public function getSubject($status, $requestId) {
        $statusText = match($status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'released' => 'Released',
            'returned' => 'Returned',
            'late_return' => 'Returned Late',
            'pending_approval' => 'Pending Approval',
            default => 'Updated'
        };

        return "Asset Request {$statusText} - Request #{$requestId}";
    }

    public function render($recipientName, $requestId, $assetName, $status, $message) {
        $statusConfig = $this->getStatusConfig($status);
        $requestsUrl = EmailConfig::url('my_requests.php');

        $content = "
            <div style='text-align: center;'>
                " . EmailTemplate::badge($statusConfig['text'], $statusConfig['color']) . "
            </div>
            <h1>Hi, " . htmlspecialchars($recipientName) . "</h1>
            <p class='main-text'>" . htmlspecialchars($message) . "</p>

            " . EmailTemplate::detailsBox([
                'Request ID' => '#' . $requestId,
                'Asset' => htmlspecialchars($assetName),
                'Status' => $statusConfig['text'],
                'Date' => date('F j, Y g:i A')
            ]) . "

            <div style='text-align: center; margin-bottom: 20px;'>
                " . EmailTemplate::button('View My Requests', $requestsUrl) . "
            </div>
        ";

        return EmailTemplate::render($statusConfig['title'], $content);
    }

    private function getStatusConfig($status) {
        return match($status) {
            'approved' => [
                'text' => 'Approved',
                'color' => '#10b981',
                'title' => 'Request Approved'
            ],
            'rejected' => [
                'text' => 'Rejected',
                'color' => '#ef4444',
                'title' => 'Request Rejected'
            ],
            'released' => [
                'text' => 'Released',
                'color' => '#0071e3',
                'title' => 'Asset Released'
            ],
            'returned' => [
                'text' => 'Returned',
                'color' => '#10b981',
                'title' => 'Asset Returned'
            ],
            'late_return' => [
                'text' => 'Returned Late',
                'color' => '#f59e0b',
                'title' => 'Late Return Recorded'
            ],
            'pending_approval' => [
                'text' => 'Pending Approval',
                'color' => '#f59e0b',
                'title' => 'Request Pending'
            ],
            default => [
                'text' => 'Updated',
                'color' => '#6b7280',
                'title' => 'Request Updated'
            ]
        };
    }
}
