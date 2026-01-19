@echo off
REM ============================================
REM HCC Asset Management - Task Scheduler Setup
REM Automated Email Reminder System
REM ============================================

echo.
echo ========================================
echo HCC Asset Management System
echo Automated Reminder Task Setup
echo ========================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script must be run as Administrator!
    echo.
    echo Right-click this file and select "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo [1/5] Checking PHP installation...
if not exist "C:\xampp\php\php.exe" (
    echo ERROR: PHP not found at C:\xampp\php\php.exe
    echo Please install XAMPP first.
    pause
    exit /b 1
)
echo     OK: PHP found

echo.
echo [2/5] Checking cron script...
if not exist "%~dp0cron\check_overdue_assets.php" (
    echo ERROR: Cron script not found
    echo Expected: %~dp0cron\check_overdue_assets.php
    pause
    exit /b 1
)
echo     OK: Cron script found

echo.
echo [3/5] Testing cron script execution...
cd /d "%~dp0"
"C:\xampp\php\php.exe" "cron\check_overdue_assets.php" >nul 2>&1
if %errorLevel% neq 0 (
    echo WARNING: Script test failed (this may be normal if no data exists)
    echo.
) else (
    echo     OK: Script executed successfully
)

echo.
echo [4/5] Creating scheduled task...
echo.
echo Task Name: HCC Asset Return Reminders
echo Schedule: Daily at 8:00 AM
echo Script: %~dp0cron\check_overdue_assets.php
echo.

REM Delete existing task if it exists (suppress errors)
schtasks /delete /tn "HCC Asset Return Reminders" /f >nul 2>&1

REM Create the scheduled task
schtasks /create /tn "HCC Asset Return Reminders" ^
    /tr "\"C:\xampp\php\php.exe\" \"%~dp0cron\check_overdue_assets.php\"" ^
    /sc daily ^
    /st 08:00 ^
    /ru SYSTEM ^
    /rl HIGHEST ^
    /f

if %errorLevel% neq 0 (
    echo.
    echo ERROR: Failed to create scheduled task
    echo Please create it manually using the TASK_SCHEDULER_SETUP.md guide
    pause
    exit /b 1
)

echo     OK: Task created successfully

echo.
echo [5/5] Running test execution...
schtasks /run /tn "HCC Asset Return Reminders"
timeout /t 3 /nobreak >nul

echo.
echo ========================================
echo SETUP COMPLETE!
echo ========================================
echo.
echo The automated reminder system is now active.
echo.
echo Schedule: Daily at 8:00 AM
echo.
echo What happens daily:
echo   - Send 7-day advance notice reminders
echo   - Send 2-day upcoming return reminders
echo   - Send 1-day urgent reminders
echo   - Send same-day return reminders
echo   - Send overdue alerts with escalation
echo   - Auto-mark missing assets (60+ days overdue)
echo.
echo To verify:
echo   1. Check logs: %~dp0logs\
echo   2. Open Task Scheduler: taskschd.msc
echo   3. Find: HCC Asset Return Reminders
echo.
echo To manually run now:
echo   Right-click task in Task Scheduler
echo   Select "Run"
echo.
echo To disable:
echo   Right-click task in Task Scheduler
echo   Select "Disable"
echo.
echo To remove:
echo   schtasks /delete /tn "HCC Asset Return Reminders" /f
echo.
pause
