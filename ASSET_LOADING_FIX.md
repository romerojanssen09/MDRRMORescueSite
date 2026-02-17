# Asset Loading Fix for Railway Deployment

## Issue
Website loads but CSS/JS assets are not being applied - page shows black screen with blue links.

## Root Cause
The `@vite` directive in Blade templates needs to detect production mode and use the built assets from `public/build/manifest.json` instead of trying to connect to Vite dev server.

## Solution Applied

### 1. Updated nixpacks.toml
Added commands to ensure production mode:
- Remove `public/hot` file (indicates Vite dev server)
- Clear view cache
- Cache views for production

### 2. Verify Environment
Ensure these are set in Railway:
- `APP_ENV=production`
- `APP_DEBUG=false`

### 3. Asset Files
Built assets exist at:
- `/public/build/assets/app-BEI4OL5h.css`
- `/public/build/assets/app-CKl8NZMC.js`
- `/public/build/manifest.json`

## How @vite Works

In development:
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```
Connects to Vite dev server at `http://localhost:5173`

In production (APP_ENV=production):
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```
Reads `public/build/manifest.json` and loads:
```html
<link rel="stylesheet" href="/build/assets/app-BEI4OL5h.css">
<script src="/build/assets/app-CKl8NZMC.js"></script>
```

## Verification Steps

After deployment:

1. **Check if hot file exists**:
   ```bash
   ls -la public/hot
   ```
   Should NOT exist in production

2. **Check manifest**:
   ```bash
   cat public/build/manifest.json
   ```
   Should show asset mappings

3. **Check environment**:
   ```bash
   php artisan config:show app.env
   ```
   Should show "production"

4. **View page source**:
   - Open https://mdrrmorescuesite-production.up.railway.app
   - View page source (Ctrl+U)
   - Look for `<link>` and `<script>` tags
   - Should reference `/build/assets/app-*.css` and `/build/assets/app-*.js`

5. **Check browser console**:
   - Open DevTools (F12)
   - Check Console for errors
   - Check Network tab - CSS/JS files should load with 200 status

## Troubleshooting

### Assets still not loading

1. **Clear all caches**:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Check file permissions**:
   ```bash
   chmod -R 755 public/build
   ```

3. **Verify manifest exists**:
   ```bash
   cat public/build/manifest.json
   ```

### Page shows "Vite manifest not found"

This means Laravel can't find the manifest file:
```bash
# Ensure manifest exists
ls -la public/build/manifest.json

# If missing, rebuild assets
npm run build
```

### CSS loads but styles don't apply

Check if Tailwind CSS is being purged correctly:
```bash
# Check if CSS file has content
wc -l public/build/assets/app-*.css
```

Should be more than a few lines. If it's tiny, Tailwind might have purged everything.

## Alternative: Manual Asset Loading

If @vite continues to have issues, you can manually load assets in production:

```blade
@if(app()->environment('production'))
    <link rel="stylesheet" href="{{ asset('build/assets/app-BEI4OL5h.css') }}">
    <script src="{{ asset('build/assets/app-CKl8NZMC.js') }}" defer></script>
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
```

But this requires updating the hash every time you rebuild assets.

## Expected Result

After fix:
- ✅ Welcome page loads with full styling
- ✅ Admin login page loads with full styling
- ✅ Dashboard loads with full styling
- ✅ All Tailwind CSS classes applied
- ✅ JavaScript functionality works
- ✅ No console errors

---

**Status**: Fix applied, ready for redeploy
**Next Step**: Push changes and redeploy to Railway
