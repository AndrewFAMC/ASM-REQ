<?php
/**
 * Email Template Class
 *
 * Provides reusable HTML email templates and components
 * Eliminates duplication of 150+ lines of HTML across 8 email functions
 */

require_once __DIR__ . '/EmailConfig.php';

class EmailTemplate {

    /**
     * Main email wrapper with HCC branding
     *
     * @param string $title Email title (appears in header)
     * @param string $content Main email content (HTML)
     * @param string $logoUrl Optional logo URL (uses embedded CID by default)
     * @return string Complete HTML email
     */
    public static function render($title, $content, $logoUrl = 'cid:hcc_logo') {
        $year = date('Y');
        $homeUrl = EmailConfig::url();

        return "
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
                                <img src='{$logoUrl}' alt='HCC Logo' width='50' style='display: block; margin: 0 auto;' />
                            </div>
                            <!-- Main Content -->
                            <div class='content'>
                                {$content}
                            </div>
                            <!-- Footer -->
                            <div class='footer'>
                                <p style='font-family: \"Great Vibes\", cursive; font-size: 40px; color: #bda54f; margin: 0 0 20px 0;'>We Find Assets</p>
                                <p style='margin: 0 0 10px; font-size: 12px; color: #888888;'>
                                    This email was sent from the HCC Asset Management System.
                                </p>
                                <p style='margin: 0; font-size: 12px; color: #888888;'>
                                    &copy; {$year} Holy Cross Colleges. All rights reserved.<br>
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
    }

    /**
     * Create a colored badge (for status, urgency, etc.)
     */
    public static function badge($text, $color) {
        return "<span style='display: inline-block; padding: 10px 20px; border-radius: 8px; background-color: {$color}; color: white; font-weight: 600; margin-bottom: 20px;'>{$text}</span>";
    }

    /**
     * Create a button/CTA
     */
    public static function button($text, $url, $color = '#0071e3') {
        return "<a href='{$url}' style='display: inline-block; background-color: {$color}; color: #ffffff; font-size: 17px; font-weight: 500; text-decoration: none; padding: 14px 28px; border-radius: 980px; margin: 5px;'>{$text}</a>";
    }

    /**
     * Create a details box with key-value pairs
     *
     * @param array $items ['Label' => 'Value', ...]
     */
    public static function detailsBox($items) {
        $html = "<div style='background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 25px; margin-bottom: 25px;'>";

        $count = count($items);
        $index = 0;
        foreach ($items as $label => $value) {
            $index++;
            $marginBottom = ($index < $count) ? '15px' : '0';
            $html .= "<p style='margin: 0 0 {$marginBottom}; font-size: 15px; color: #555555; text-align: left;'>";
            $html .= "<strong>" . htmlspecialchars($label) . ":</strong><br>";
            $html .= "<span style='color: #000000;'>" . $value . "</span>";
            $html .= "</p>";
        }

        $html .= "</div>";
        return $html;
    }

    /**
     * Create a warning/alert box
     *
     * @param string $title Alert title
     * @param array $items List items (optional)
     * @param string $type 'warning' (yellow), 'danger' (red), 'info' (blue)
     */
    public static function alertBox($title, $items = [], $type = 'warning') {
        $colors = [
            'warning' => ['bg' => '#fef3c7', 'border' => '#fde68a', 'text' => '#92400e'],
            'danger' => ['bg' => '#fee2e2', 'border' => '#fca5a5', 'text' => '#991b1b'],
            'info' => ['bg' => '#dbeafe', 'border' => '#93c5fd', 'text' => '#1e40af']
        ];

        $color = $colors[$type] ?? $colors['warning'];

        $html = "<div style='background-color: {$color['bg']}; border: 1px solid {$color['border']}; border-left: 4px solid {$color['border']}; padding: 15px; border-radius: 8px; margin-bottom: 25px;'>";
        $html .= "<p style='margin: 0 0 10px; font-size: 14px; color: {$color['text']}; font-weight: 600;'>{$title}</p>";

        if (!empty($items)) {
            $html .= "<ul style='margin: 0; padding-left: 20px; color: {$color['text']}; font-size: 14px;'>";
            foreach ($items as $item) {
                $html .= "<li>" . htmlspecialchars($item) . "</li>";
            }
            $html .= "</ul>";
        }

        $html .= "</div>";
        return $html;
    }

    /**
     * Create a large icon display
     */
    public static function largeIcon($emoji) {
        return "<div style='font-size: 64px; text-align: center; margin-bottom: 20px;'>{$emoji}</div>";
    }

    /**
     * Create a highlighted date/text box
     */
    public static function highlightBox($text, $color = '#0071e3') {
        return "<div style='font-size: 24px; font-weight: 700; color: {$color}; text-align: center; margin: 20px 0;'>{$text}</div>";
    }

    /**
     * Create a description box (for longer text content)
     */
    public static function descriptionBox($title, $content, $bgColor = '#fffbeb', $borderColor = '#fbbf24') {
        return "
        <div style='background-color: {$bgColor}; border: 1px solid {$borderColor}; border-radius: 8px; padding: 15px; margin-bottom: 25px;'>
            <p style='margin: 0 0 10px; font-size: 14px; font-weight: 600; color: #92400e;'>{$title}</p>
            <p style='margin: 0; font-size: 14px; color: #78350f; white-space: pre-wrap;'>" . htmlspecialchars($content) . "</p>
        </div>";
    }
}
