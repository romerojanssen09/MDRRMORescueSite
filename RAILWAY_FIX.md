# Railway Deployment Fix

## Issue
Build was failing with error: "View path not found" when running `php artisan optimize:clear`

## Root Cause
The `php artisan optimize:clear` command tries to clear the views cache, but the storage directory structure wasn't being created during the build process.

## Solution Applied

### 1. Updated nixpacks.toml Build Phase
Changed from:
```toml
[phases.build]
cmds = [
    "php artisan optimize:clear",
    "php artisan optimize"
]
```

To:
```toml
[phases.build]
cmds = [
    "mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache",
    "chmod -R 775 storage bootstrap/cache",
    "php artisan config:clear || true",
    "php artisan route:clear || true",
    "php artisan cache:clear || true",
    "php artisan config:cache",
    "php artisan route:cache"
]
```

### 2. Updated .railwayignore
Ensured storage directory structure is included (with .gitignore files) while excluding cached files.

## Changes Made

1. **Create directories first** - Ensures all required Laravel directories exist before running artisan commands
2. **Set permissions** - Makes storage and bootstrap/cache writable
3. **Individual clear commands** - Clears each cache type separately with `|| true` to continue on errors
4. **Cache config and routes** - Optimizes for production

## Why This Works

- Laravel needs the storage directory structure to exist before it can clear caches
- The `mkdir -p` command creates all necessary directories
- The `|| true` allows commands to fail gracefully if there's nothing to clear
- Caching config and routes improves production performance

## Next Steps

1. Commit these changes to your repository
2. Push to Railway
3. Railway will automatically trigger a new build
4. The build should now complete successfully

## Verification

After deployment, check:
- [ ] Build completes without errors
- [ ] Application starts successfully
- [ ] Admin dashboard is accessible
- [ ] Database connection works
- [ ] All routes are accessible

---

**Fixed**: February 17, 2026
**Status**: Ready for deployment
