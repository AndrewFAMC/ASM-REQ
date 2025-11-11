@echo off
REM ========================================
REM AMS Email Worker - Auto Setup Script
REM ========================================
REM This script sets up Windows Task Scheduler to automatically
REM process email queue every minute in the background.
REM ========================================

echo.
echo ========================================
echo  AMS Email Worker Setup
echo ========================================
echo.
echo This will set up automatic email processing.
echo The email worker will run every minute in the background.
echo.
echo Press any key to continue or Ctrl+C to cancel...
pause >nul

echo.
echo [1/3] Checking for administrator privileges...

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo.
    echo ERROR: This script requires administrator privileges!
    echo.
    echo Please right-click on this file and select "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo     OK - Running as administrator
echo.

echo [2/3] Creating scheduled task...

REM Delete existing task if it exists (ignore errors)
schtasks /delete /tn "AMS Email Worker" /f >nul 2>&1

REM Create new task
schtasks /create /tn "AMS Email Worker" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\AMS-REQ\cron\process_email_queue.php" /sc minute /mo 1 /ru SYSTEM /f

if %errorLevel% neq 0 (
    echo.
    echo ERROR: Failed to create scheduled task!
    echo.
    pause
    exit /b 1
)

echo     OK - Task created successfully
echo.

echo [3/3] Testing the email worker...

REM Run the task immediately to test
schtasks /run /tn "AMS Email Worker" >nul 2>&1

echo     OK - Email worker started
echo.
echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo The email worker is now running automatically.
echo It will process pending emails every minute.
echo.
echo What happens next:
echo   - Worker runs every 1 minute
echo   - Processes all pending emails
echo   - Continues even after restart
echo.
echo To verify it's working:
echo   1. Open Task Scheduler (taskschd.msc)
echo   2. Look for "AMS Email Worker"
echo   3. Check "Last Run Time" and "Last Run Result"
echo.
echo To remove the worker later, run: uninstall_email_worker.bat
echo.
echo ========================================
echo.
pause
