@echo off
REM ============================================================
REM  Opens the POS in Chrome with SILENT printing.
REM  print() goes straight to the Windows DEFAULT printer with
REM  no print dialog. Always open the POS through THIS file.
REM
REM  Setup once:
REM   1. Windows Settings > Printers > set your receipt printer
REM      (e.g. "XP-80C") as the DEFAULT printer, and turn OFF
REM      "Let Windows manage my default printer".
REM   2. Edit POS_URL below if your address is different.
REM ============================================================

set "POS_URL=http://localhost/MiniPos/"

set "CHROME=C:\Program Files\Google\Chrome\Application\chrome.exe"
if not exist "%CHROME%" set "CHROME=C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"

REM --user-data-dir forces a separate Chrome instance so --kiosk-printing actually
REM takes effect even if a normal Chrome is already open.
set "PROFILE=%LOCALAPPDATA%\MiniPosKiosk"

if exist "%CHROME%" (
    start "" "%CHROME%" --kiosk-printing --user-data-dir="%PROFILE%" --app=%POS_URL%
) else (
    REM Fallback to Microsoft Edge
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --kiosk-printing --user-data-dir="%PROFILE%" --app=%POS_URL%
)
