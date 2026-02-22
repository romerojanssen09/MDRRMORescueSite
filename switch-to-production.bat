@echo off
echo ========================================
echo Switching to PRODUCTION environment...
echo ========================================
echo.

echo WARNING: This will switch to PRODUCTION mode!
echo - Debug mode will be DISABLED
echo - Error messages will be hidden
echo - Connection pooler will be used
echo.
set /p confirm="Are you sure? (Y/N): "
if /i not "%confirm%"=="Y" (
    echo Cancelled.
    pause
    exit /b 0
)

echo.
copy /Y production.env .env
if %errorlevel% neq 0 (
    echo ERROR: Failed to copy production.env to .env
    pause
    exit /b 1
)

echo ✓ Copied production.env to .env
echo.

echo Clearing Laravel caches...
call php artisan config:clear
call php artisan cache:clear
call php artisan route:clear
call php artisan view:clear

echo.
echo Caching configuration for production...
call php artisan config:cache

echo.
echo ========================================
echo ✓ Switched to PRODUCTION environment!
echo ========================================
echo.
echo Environment: PRODUCTION
echo URL: https://mdrrmorescuesite-production.up.railway.app
echo Debug: DISABLED
echo Database: Connection Pooler (port 6543)
echo.
echo IMPORTANT: Test thoroughly before deploying!
echo.
pause
