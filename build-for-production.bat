@echo off
echo ========================================
echo Building Assets for Production...
echo ========================================
echo.

echo Step 1: Installing dependencies...
call npm install
if %errorlevel% neq 0 (
    echo ERROR: npm install failed
    pause
    exit /b 1
)

echo.
echo Step 2: Building assets...
call npm run build
if %errorlevel% neq 0 (
    echo ERROR: npm run build failed
    pause
    exit /b 1
)

echo.
echo Step 3: Optimizing Laravel...
call php artisan optimize
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache

echo.
echo ========================================
echo ✓ Build Complete!
echo ========================================
echo.
echo Assets built in: public/build/
echo.
echo Next steps:
echo 1. Commit the built assets: git add public/build -f
echo 2. Push to production: git push origin main
echo.
pause
