<?php
/**
 * Email Configuration Class
 *
 * Centralized email configuration - ONE PLACE for all SMTP settings
 * This replaces hardcoded credentials scattered across 9+ files
 *
 * For production: Move these to environment variables or secure config
 */

class EmailConfig {

    // ========== SMTP SETTINGS ==========
    // Gmail SMTP Configuration
    public static $smtp_host = 'smtp.gmail.com';
    public static $smtp_port = 587;
    public static $smtp_secure = 'tls'; // 'tls' or 'ssl'
    public static $smtp_user = 'mico.macapugay2004@gmail.com';
    public static $smtp_pass = 'gggm gqng fjgt ukfe';

    // Email sender details
    public static $from_email = 'mico.macapugay2004@gmail.com';
    public static $from_name = 'HCC Asset Management System';

    // ========== APPLICATION SETTINGS ==========
    public static $app_name = 'Holy Cross Colleges Asset Management';
    public static $app_url = 'http://localhost/AMS-REQ';

    // ========== FILE PATHS ==========
    public static $logo_path = __DIR__ . '/../../logo/1.png';

    // ========== EMAIL BEHAVIOR ==========
    public static $smtp_keep_alive = true;    // Keep connection open for better performance
    public static $smtp_timeout = 10;          // Timeout in seconds
    public static $smtp_debug = 0;             // 0=off, 1=client, 2=server, 3=detailed

    // ========== QUEUE SETTINGS ==========
    public static $queue_batch_size = 10;      // Process 10 emails per batch
    public static $queue_sleep_duration = 30;  // Sleep 30 seconds between batches
    public static $queue_max_attempts = 3;     // Retry failed emails 3 times

    // Retry delays in minutes (exponential backoff)
    public static $retry_delays = [5, 15, 60]; // 5min, 15min, 1hour

    /**
     * Get full URL for a path
     */
    public static function url($path = '') {
        return self::$app_url . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get logo path and check if it exists
     */
    public static function getLogo() {
        return file_exists(self::$logo_path) ? self::$logo_path : null;
    }
}
