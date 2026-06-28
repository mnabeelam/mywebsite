@echo off
title Push portfolio to GitHub
cd /d "%~dp0.."

echo.
echo  Portfolio -> GitHub (mnabeelam/mywebsite)
echo  ==========================================
echo.
echo  BEFORE running this, create the empty repo on GitHub:
echo    1. Open https://github.com/new
echo    2. Repository name: mywebsite
echo    3. Leave README / .gitignore UNCHECKED
echo    4. Click Create repository
echo.
pause

git remote -v 2>nul
if errorlevel 1 goto no_git

git remote get-url origin >nul 2>&1
if errorlevel 1 (
  git remote add origin https://github.com/mnabeelam/mywebsite.git
) else (
  git remote set-url origin https://github.com/mnabeelam/mywebsite.git
)

echo.
echo Pushing branch cleanup-phase-1 ...
git push -u origin cleanup-phase-1

if errorlevel 1 (
  echo.
  echo PUSH FAILED.
  echo - Did you create https://github.com/mnabeelam/mywebsite ?
  echo - Use Personal Access Token as password when Git asks to sign in.
  echo - Create token: https://github.com/settings/tokens
  pause
  exit /b 1
)

echo.
echo SUCCESS! Open: https://github.com/mnabeelam/mywebsite
echo.
echo On GitHub: Settings - General - Default branch - set cleanup-phase-1
echo   OR merge cleanup-phase-1 into main in Cursor Source Control.
pause
exit /b 0

:no_git
echo Not a git repo.
pause
exit /b 1
