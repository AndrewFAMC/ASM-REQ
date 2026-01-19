@echo off
REM Email Queue Worker - Windows Batch Script
REM Run this to start the background email processor

echo ===================================
echo Email Queue Background Worker
echo ===================================
echo.
echo Starting email queue processor...
echo Press Ctrl+C to stop
echo.

"C:\xampp\php\php.exe" "c:\xampp\htdocs\AMS-REQ\cron\process_email_queue.php"

pause
