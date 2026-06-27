@echo off
setlocal
cd /d "%~dp0.."

where php >nul 2>&1
if errorlevel 1 (
  echo.
  echo PHP is NOT installed or not in PATH.
  echo Install XAMPP or Laragon, then run this script again.
  echo.
  pause
  exit /b 1
)

echo.
echo ============================================
echo  Portfolio local server starting...
echo ============================================
echo.
echo  Homepage:  http://127.0.0.1:8080/index.html
echo  Admin:     http://127.0.0.1:8080/admin/index.php
echo  PHP test:  http://127.0.0.1:8080/php/test.php
echo.
echo  Keep this window OPEN while testing the site.
echo  Press Ctrl+C to stop the server.
echo ============================================
echo.

php -S 127.0.0.1:8080
pause
