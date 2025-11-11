@echo off
REM ========================================
REM AMS Email Worker - Uninstall Script
REM ========================================
REM This script removes the email worker from Windows Task Scheduler
REM ========================================

echo.
echo ========================================
echo  AMS Email Worker Uninstall
echo ========================================
echo.
echo This will REMOVE the automatic email worker.
echo Emails will no longer be sent automatically.
echo.
echo Press any key to continue or Ctrl+C to cancel...
pause >nul

echo.
echo [1/2] Checking for administrator privileges...

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

echo [2/2] Removing scheduled task...

schtasks /delete /tn "AMS Email Worker" /f

if %errorLevel% neq 0 (
    echo.
    echo WARNING: Task may not exist or was already removed.
    echo.
) else (
    echo     OK - Email worker removed successfully
)

echo.
echo ========================================
echo  Uninstall Complete!
echo ========================================
echo.
echo The email worker has been removed.
echo.
echo To enable it again, run: setup_email_worker.bat
echo.
echo ========================================
echo.
pause
