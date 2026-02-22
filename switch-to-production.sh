#!/bin/bash

echo "========================================"
echo "Switching to PRODUCTION environment..."
echo "========================================"
echo ""

echo "WARNING: This will switch to PRODUCTION mode!"
echo "- Debug mode will be DISABLED"
echo "- Error messages will be hidden"
echo "- Connection pooler will be used"
echo ""
read -p "Are you sure? (Y/N): " confirm
if [[ ! $confirm =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

echo ""
cp production.env .env
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to copy production.env to .env"
    exit 1
fi

echo "✓ Copied production.env to .env"
echo ""

echo "Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "Caching configuration for production..."
php artisan config:cache

echo ""
echo "========================================"
echo "✓ Switched to PRODUCTION environment!"
echo "========================================"
echo ""
echo "Environment: PRODUCTION"
echo "URL: https://mdrrmorescuesite-production.up.railway.app"
echo "Debug: DISABLED"
echo "Database: Connection Pooler (port 6543)"
echo ""
echo "IMPORTANT: Test thoroughly before deploying!"
echo ""
