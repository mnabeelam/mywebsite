@echo off
setlocal
cd /d "%~dp0.."

echo.
echo ============================================
echo  PHP + Apache check for mywebsite
echo ============================================
echo.

powershell -NoProfile -Command "try { $c = (Invoke-WebRequest -Uri 'http://127.0.0.1/php/test.php' -UseBasicParsing).Content; if ($c -like 'PHP is working.*') { Write-Host 'OK: PHP is working on Apache port 80' -ForegroundColor Green; Write-Host $c } else { Write-Host 'PROBLEM: PHP test did not return expected text' -ForegroundColor Red; Write-Host $c } } catch { Write-Host 'PROBLEM: Cannot reach http://127.0.0.1/php/test.php' -ForegroundColor Red; Write-Host $_.Exception.Message }"

echo.
echo Open these URLs in your browser:
echo   http://127.0.0.1/index.html
echo   http://127.0.0.1/admin/index.php
echo   http://mywebsite.local/admin/index.php
echo.
pause
