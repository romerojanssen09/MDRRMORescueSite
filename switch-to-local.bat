@echo off
echo ========================================
echo Switching to LOCAL environment...
echo ========================================
echo.

copy /Y local.env .env
if %errorlevel% neq 0 (
    echo ERROR: Failed to copy local.env to .env
    pause
    exit /b 1
)

echo ✓ Copied local.env to .env
echo.

echo Clearing Laravel caches...
call php artisan config:clear
call php artisan cache:clear
call php artisan route:clear
call php artisan view:clear

echo.
echo ========================================
echo ✓ Switched to LOCAL environment!
echo ========================================
echo.
echo Environment: LOCAL DEVELOPMENT
echo URL: http://localhost:8000
echo Debug: ENABLED
echo Database: Direct Connection (port 5432)
echo.
pause
