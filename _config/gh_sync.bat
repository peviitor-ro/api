@echo off
rem Log in to GitHub CLI (if not already logged in)
gh auth login

rem Navigate to the PHP repository directory
cd C:\php\api

rem Fetch updates from the remote repository
gh repo sync

rem Pull changes from the remote repository
gh repo pull

rem Pause to see the output
pause
