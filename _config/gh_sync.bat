@echo off


rem Navigate to the PHP repository directory
cd C:\php\api

rem Fetch updates from the remote repository
gh repo sync

rem Pull changes from the remote repository
gh repo pull --force

rem Pause to see the output
pause
