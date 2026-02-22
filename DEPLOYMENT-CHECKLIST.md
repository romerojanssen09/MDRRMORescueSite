# 🚀 Production Deployment Checklist

## Before Deploying to Production

### 1. Build Assets
```bash
# Install dependencies
npm install

# Build for production
npm run build
```

This creates optimized CSS/JS files in `public/build/`.

### 2. Switch to Production Environment
```bash
# Windows
.\switch-to-production.bat

# Linux/Mac
./switch-to-production.sh

# Or manually
copy production.env .env
php artisan config:clear
php artisan config:cache
```

### 3. Optimize Laravel
```bash
php artisan optimize
php artisan route:cache
php artisan view:cache
```

### 4. Set Permissions (Linux/Mac only)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Verify Configuration
```bash
php artisan config:show
php artisan about
```

## Railway Deployment

Railway automatically runs these commands (check `railway.toml`):

```toml
[build]
builder = "nixpacks"

[deploy]
startCommand = "php artisan migrate --force && php artisan optimize && php artisan serve --host=0.0.0.0 --port=$PORT"
```

### Railway Environment Variables

Make sure these are set in Railway dashboard:

- `APP_KEY` - Your application key
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_HOST` - Supabase pooler host
- `DB_PORT=6543`
- `DB_DATABASE=postgres`
- `DB_USERNAME` - Your database username
- `DB_PASSWORD` - Your database password
- `SUPABASE_URL` - Your Supabase project URL
- `SUPABASE_ANON_KEY` - Your Supabase anon key
- `SUPABASE_SERVICE_KEY` - Your Supabase service key

## Post-Deployment Verification

### 1. Check Homepage
Visit: `https://mdrrmorescuesite-production.up.railway.app`

Should show:
- ✅ Styled homepage with MDRRMO branding
- ✅ Download button for mobile app
- ✅ Admin login link

### 2. Check Admin Login
Visit: `https://mdrrmorescuesite-production.up.railway.app/admin/login`

Should show:
- ✅ Login form with proper styling
- ✅ No console errors
- ✅ Can login with admin credentials

### 3. Check Dashboard
After login, should show:
- ✅ Dashboard with statistics
- ✅ Real-time updates working
- ✅ Map displaying correctly
- ✅ No JavaScript errors in console

### 4. Check Realtime
- ✅ Console shows "SUBSCRIBED" for all channels
- ✅ Updating a report triggers realtime update
- ✅ Dashboard refreshes automatically

## Common Production Issues

### Issue: Blank/Unstyled Page

**Cause**: Assets not built or Vite not configured

**Solution**:
```bash
npm run build
git add public/build -f
git commit -m "Add built assets"
git push
```

### Issue: 500 Error

**Cause**: Missing APP_KEY or wrong permissions

**Solution**:
```bash
php artisan key:generate
php artisan config:cache
```

### Issue: Database Connection Error

**Cause**: Wrong database credentials or host

**Solution**: Check Railway environment variables match production.env

### Issue: Realtime Not Working

**Cause**: Wrong Supabase credentials

**Solution**: Verify SUPABASE_URL and SUPABASE_ANON_KEY in Railway

## Asset Building Options

### Option 1: Build Locally (Recommended)
```bash
npm run build
git add public/build -f
git commit -m "Build assets for production"
git push
```

### Option 2: Build on Railway
Add to `railway.toml`:
```toml
[build]
builder = "nixpacks"

[build.nixpacksPlan]
phases.setup.nixPkgs = ["nodejs", "php82"]
phases.install.cmds = ["npm install"]
phases.build.cmds = ["npm run build"]
```

### Option 3: Use CDN (Quick Fix)
If assets won't build, you can use Tailwind CDN temporarily:

In `resources/views/layouts/admin.blade.php`, add:
```html
<script src="https://cdn.tailwindcss.com"></script>
```

## Rollback Procedure

If deployment fails:

```bash
# 1. Switch back to local
.\switch-to-local.bat

# 2. Revert git changes
git reset --hard HEAD~1

# 3. Or rollback on Railway
# Go to Railway dashboard → Deployments → Rollback
```

## Performance Optimization

After deployment:

```bash
# Enable OPcache (in php.ini)
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000

# Enable Redis cache (if available)
CACHE_STORE=redis
SESSION_DRIVER=redis
```

## Monitoring

Check these regularly:

- [ ] Application logs: `storage/logs/laravel.log`
- [ ] Railway logs: Railway dashboard
- [ ] Database performance: Supabase dashboard
- [ ] Realtime connections: Browser console
- [ ] Error rates: Laravel Telescope (if installed)

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] HTTPS enabled (Railway does this automatically)
- [ ] CORS configured properly
- [ ] Rate limiting enabled
- [ ] SQL injection protection (Laravel handles this)

---

**Last Updated**: February 22, 2026
**Deployment Platform**: Railway
**Laravel Version**: 12.51.0
