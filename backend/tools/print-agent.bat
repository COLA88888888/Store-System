@echo off
REM ============================================================
REM  Shop-side PRINT AGENT — only needed when MiniPos is hosted
REM  off-site. Keeps running and prints jobs the server queues.
REM
REM  Setup once:
REM   1. Settings > Printers on the server: switch print mode to
REM      "ຜ່ານຕົວແທນພິມ" and copy the agent token shown there.
REM   2. Fill in POS_URL and AGENT_TOKEN below.
REM   3. Put a shortcut to this file in the Startup folder so it
REM      runs whenever the shop PC boots.
REM ============================================================

set "POS_URL=https://your-minipos-site.com"
set "AGENT_TOKEN=paste-token-here"

set "PHP=php"
where %PHP% >nul 2>nul || set "PHP=D:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe"

if "%AGENT_TOKEN%"=="paste-token-here" (
    echo Edit this file first: set POS_URL and AGENT_TOKEN.
    pause
    exit /b 1
)

echo Starting print agent against %POS_URL% ...
"%PHP%" "%~dp0print-agent.php" --url=%POS_URL% --token=%AGENT_TOKEN% --interval=3
pause
