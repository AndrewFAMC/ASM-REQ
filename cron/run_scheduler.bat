@echo off
REM ===================================================================
REM Asset Notification Scheduler - Windows Task Scheduler Runner
REM ===================================================================
REM
REM This batch file runs the asset notification scheduler
REM Configure Windows Task Scheduler to run this file daily
REM
REM Recommended Schedule: Daily at 8:00 AM
REM ===================================================================

echo Starting Asset Notification Scheduler...
echo.

REM Change to XAMPP PHP directory
cd C:\xampp\php

REM Run the PHP scheduler script
php.exe "C:\xampp\htdocs\AMS-REQ\cron\asset_notification_scheduler.php"

echo.
echo Scheduler execution completed.
echo Check logs at: C:\xampp\htdocs\AMS-REQ\logs\
echo.

REM Uncomment the line below if you want the window to stay open
REM pause
