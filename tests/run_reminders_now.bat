@echo off
REM ============================================
REM Manual Reminder System Test
REM Run this to test the reminder system anytime
REM ============================================

echo.
echo ========================================
echo HCC Asset Management
echo Manual Reminder System Test
echo ========================================
echo.
echo Running reminder check now...
echo.

cd /d "%~dp0"
"C:\xampp\php\php.exe" "cron\check_overdue_assets.php"

echo.
echo ========================================
echo DONE!
echo ========================================
echo.
echo Check the results:
echo   - Log file: logs\overdue_check_%date:~10,4%-%date:~4,2%-%date:~7,2%.log
echo   - Database: activity_log table
echo   - Email inbox: mico.macapugay2004@gmail.com
echo.
pause
