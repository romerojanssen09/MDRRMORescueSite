# Environment Configuration Guide

This guide explains how to switch between local development and production environments for the MDRRMO Admin site.

## 📁 Environment Files

The project has three environment files:

1. **`.env`** - Active configuration (used by Laravel)
2. **`local.env`** - Local development settings (backup/template)
3. **`production.env`** - Production settings (backup/template)

## 🔄 Switching Environments

### Switch to Local Development

```bash
# Windows (PowerShell)
Copy-Item local.env .env -Force
php artisan config:clear
php artisan cache:clear

# Windows (CMD)
copy /Y local.env .env
php artisan config:clear
php artisan cache:clear

# Linux/Mac
cp local.env .env
php artisan config:clear
php artisan cache:clear
```

### Switch to Production

```bash
# Windows (PowerShell)
Copy-Item production.env .env -Force
php artisan config:clear
php artisan cache:clear
php artisan config:cache

# Windows (CMD)
copy /Y production.env .env
php artisan config:clear
php artisan cache:clear
php artisan config:cache

# Linux/Mac
cp production.env .env
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

## 📊 Environment Comparison

| Setting | Local Development | Production |
|---------|------------------|------------|
| **APP_ENV** | `local` | `production` |
| **APP_DEBUG** | `true` | `false` |
| **APP_URL** | `http://localhost:8000` | `https://mdrrmorescuesite-production.up.railway.app` |
| **LOG_LEVEL** | `debug` | `error` |
| **DB_HOST** | `db.kbcdtmnqmismqjtyzmrp.supabase.co` | `aws-1-ap-south-1.pooler.supabase.com` |
| **DB_PORT** | `5432` (Direct) | `6543` (Pooler) |

## 🔑 Key Differences

### Local Development (`local.env`)
- **Debug Mode**: Enabled - Shows detailed error messages
- **Database**: Direct connection to Supabase (port 5432)
- **Logging**: Verbose (debug level)
- **URL**: localhost:8000
- **Purpose**: Development and testing

### Production (`production.env`)
- **Debug Mode**: Disabled - Hides error details from users
- **Database**: Connection pooler for better performance (port 6543)
- **Logging**: Minimal (error level only)
- **URL**: Railway production URL
- **Purpose**: Live deployment

## 🚀 Deployment Workflow

### 1. Local Development
```bash
# Start with local environment
cp local.env .env
php artisan config:clear
php artisan serve

# Make your changes and test
# ...

# Commit changes to git
git add .
git commit -m "Your changes"
```

### 2. Deploy to Production
```bash
# Switch to production config
cp production.env .env
php artisan config:clear
php artisan config:cache

# Push to production (Railway)
git push origin main

# Railway will automatically deploy
```

### 3. Back to Local Development
```bash
# Switch back to local
cp local.env .env
php artisan config:clear
```

## ⚠️ Important Notes

### Always Clear Cache After Switching
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Never Commit `.env`
The `.env` file is in `.gitignore` and should NEVER be committed to git. Only commit `local.env` and `production.env` as templates.

### Database Connections

**Local (Direct Connection)**
- Host: `db.kbcdtmnqmismqjtyzmrp.supabase.co`
- Port: `5432`
- Best for: Development, debugging, direct queries
- Limit: 60 concurrent connections

**Production (Connection Pooler)**
- Host: `aws-1-ap-south-1.pooler.supabase.com`
- Port: `6543`
- Best for: Production, high traffic
- Limit: 200+ concurrent connections (pooled)

### Supabase Configuration
Both environments use the same Supabase project:
- URL: `https://kbcdtmnqmismqjtyzmrp.supabase.co`
- Keys: Same for both (anon and service keys)
- Realtime: Works in both environments

## 🛠️ Quick Commands

### Check Current Environment
```bash
php artisan env
# or
php artisan about
```

### View Current Config
```bash
php artisan config:show
```

### Test Database Connection
```bash
php artisan migrate:status
```

### Clear Everything
```bash
php artisan optimize:clear
```

## 🔍 Troubleshooting

### Issue: Changes not taking effect
**Solution**: Clear all caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Issue: Database connection error
**Solution**: Check you're using the right environment
```bash
# For local development
DB_HOST=db.kbcdtmnqmismqjtyzmrp.supabase.co
DB_PORT=5432

# For production
DB_HOST=aws-1-ap-south-1.pooler.supabase.com
DB_PORT=6543
```

### Issue: Realtime not working
**Solution**: Verify Supabase credentials are correct
```bash
# Check .env file has:
SUPABASE_URL=https://kbcdtmnqmismqjtyzmrp.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Issue: "Authentication service not configured"
**Solution**: Clear config cache
```bash
php artisan config:clear
```

## 📝 Environment Variables Checklist

Before switching environments, verify these are set correctly:

- [ ] `APP_ENV` (local or production)
- [ ] `APP_DEBUG` (true for local, false for production)
- [ ] `APP_URL` (correct URL for environment)
- [ ] `DB_HOST` (direct for local, pooler for production)
- [ ] `DB_PORT` (5432 for local, 6543 for production)
- [ ] `LOG_LEVEL` (debug for local, error for production)
- [ ] `SUPABASE_URL` (same for both)
- [ ] `SUPABASE_ANON_KEY` (same for both)
- [ ] `SUPABASE_SERVICE_KEY` (same for both)

## 🎯 Best Practices

1. **Always work in local environment** for development
2. **Test thoroughly** before switching to production
3. **Clear caches** after every environment switch
4. **Never debug in production** (keep APP_DEBUG=false)
5. **Use connection pooler** in production for better performance
6. **Keep environment files backed up** (local.env, production.env)
7. **Never commit sensitive keys** to public repositories

## 🔐 Security Notes

- The `.env` file contains sensitive credentials
- Never share your `.env` file publicly
- Never commit `.env` to git
- Rotate keys if they are exposed
- Use different keys for staging/production if possible

## 📞 Support

If you encounter issues:
1. Check this guide first
2. Verify environment file is correct
3. Clear all caches
4. Check Laravel logs: `storage/logs/laravel.log`
5. Check browser console for JavaScript errors

---

**Last Updated**: February 22, 2026
**Project**: MDRRMO Rescue Admin Site
**Laravel Version**: 12.51.0
