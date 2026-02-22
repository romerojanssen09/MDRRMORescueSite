#!/bin/bash

echo "========================================"
echo "Switching to LOCAL environment..."
echo "========================================"
echo ""

cp local.env .env
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to copy local.env to .env"
    exit 1
fi

echo "✓ Copied local.env to .env"
echo ""

echo "Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "========================================"
echo "✓ Switched to LOCAL environment!"
echo "========================================"
echo ""
echo "Environment: LOCAL DEVELOPMENT"
echo "URL: http://localhost:8000"
echo "Debug: ENABLED"
echo "Database: Direct Connection (port 5432)"
echo ""
