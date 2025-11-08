Set WshShell = CreateObject("WScript.Shell")
WshShell.Run chr(34) & "C:\xampp\htdocs\AMS-REQ\cron\run_scheduler.bat" & Chr(34), 0
Set WshShell = Nothing
