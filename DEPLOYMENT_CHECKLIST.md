# 🚀 MDRRMO Admin - Deployment Checklist

## Pre-Deployment Cleanup ✅

- [x] Removed all Laravel migrations (database managed by mobile app)
- [x] Removed Supabase folder from admin site
- [x] Updated .env with production database credentials
- [x] Updated .env.example for reference
- [x] Created Railway deployment configuration files

## Files Cleaned/Removed

### Removed:
- ❌ `database/migrations/*.php` - All Laravel migrations
- ❌ `database/migrations/*.sql` - All SQL migrations
- ❌ `supabase/` - Entire Supabase folder (not needed in admin)

### Kept:
- ✅ `database/seeders/AdminUserSeeder.php` - For creating admin user
- ✅ `database/seeders/DatabaseSeeder.php` - Laravel default

## Configuration Files Created

1. ✅ `RAILWAY_DEPLOYMENT.md` - Complete deployment guide
2. ✅ `railway.toml` - Railway configuration
3. ✅ `nixpacks.toml` - Build configuration
4. ✅ `.env` - Updated with production credentials
5. ✅ `.env.example` - Template for production

## Database Configuration

### Production Database (Supabase)
```
Host: aws-1-ap-south-1.pooler.supabase.com
Port: 6543 (Transaction Pooler)
Database: postgres
Username: postgres.kbcdtmnqmismqjtyzmrp
Password: MDRRMO_APP_RESCUE
SSL Mode: require
```

### Important Notes:
- ✅ Using Transaction Pooler (port 6543) for better performance
- ✅ Same database as mobile app
- ✅ Admin uses service_role key to bypass RLS
- ✅ No migrations needed (schema managed by mobile app)

## Railway Deployment Steps

### 1. Push to GitHub
```bash
git add .
git commit -m "Prepare admin site for production deployment"
git push origin main
```

### 2. Create Railway Project
1. Go to https://railway.app
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your repository
5. Set root directory to `MDRRMOSite`

### 3. Configure Environment Variables
Copy all variables from `.env` to Railway:
- APP_NAME
- APP_ENV=production
- APP_DEBUG=false
- APP_KEY (generate new: `php artisan key:generate --show`)
- APP_URL (use Railway provided URL)
- DB_* (all database variables)
- SUPABASE_* (all Supabase variables)
- MAPBOX_ACCESS_TOKEN

### 4. Deploy
Railway will automatically:
- Install PHP 8.2 and Node.js 20
- Run `composer install`
- Run `npm ci && npm run build`
- Start the application

### 5. Post-Deployment
Run in Railway terminal:
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan db:seed --class=AdminUserSeeder
```

### 6. Test Deployment
- [ ] Visit Railway URL
- [ ] Login with admin@mdrrmo.com / admin123
- [ ] Check dashboard loads
- [ ] Verify real-time updates work
- [ ] Test reports page
- [ ] Test teams page
- [ ] Test map functionality
- [ ] Change admin password

## Security Checklist

- [ ] APP_DEBUG=false in production
- [ ] APP_ENV=production
- [ ] Strong APP_KEY generated
- [ ] Admin password changed from default
- [ ] HTTPS enabled (automatic on Railway)
- [ ] Database uses SSL (DB_SSLMODE=require)
- [ ] Service role key kept secret
- [ ] No sensitive data in logs

## Performance Optimization

- [ ] Config cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Assets built for production (`npm run build`)
- [ ] Using Transaction Pooler for database
- [ ] File-based sessions and cache (no database overhead)

## Monitoring

After deployment, monitor:
- [ ] Railway logs for errors
- [ ] Database connection count in Supabase
- [ ] API usage in Supabase dashboard
- [ ] Response times
- [ ] Real-time subscription status

## Rollback Plan

If deployment fails:
1. Check Railway logs
2. Verify environment variables
3. Test database connection
4. Rollback to previous deployment in Railway
5. Check Supabase status

## Support Contacts

- Railway Support: https://railway.app/help
- Supabase Support: https://supabase.com/support
- Mapbox Support: https://support.mapbox.com

---

**Deployment Date**: _____________
**Deployed By**: _____________
**Railway URL**: _____________
**Status**: ⬜ Pending | ⬜ In Progress | ⬜ Completed | ⬜ Failed

## Notes:
_____________________________________________
_____________________________________________
_____________________________________________
