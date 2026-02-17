# MDRRMO Admin - Railway Deployment Guide

## 🚀 Quick Deployment Steps

### 1. Prerequisites
- Railway account (https://railway.app)
- GitHub repository with your code
- Production Supabase database already set up

### 2. Database Configuration
The admin site connects to the same Supabase database as the mobile app:
- **Host**: `aws-1-ap-south-1.pooler.supabase.com`
- **Port**: `6543` (Transaction Pooler)
- **Database**: `postgres`
- **Username**: `postgres.kbcdtmnqmismqjtyzmrp`
- **Password**: `MDRRMO_APP_RESCUE`

⚠️ **CRITICAL**: 
- The database schema is ALREADY SET UP by the mobile app's Supabase migrations
- The admin site does NOT run migrations
- All tables, policies, triggers, and functions already exist
- Admin site only reads/writes to existing database

### 3. Deploy to Railway

#### Option A: Deploy from GitHub (Recommended)
1. Go to https://railway.app
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your repository
5. Select the `MDRRMOSite` folder as the root directory

#### Option B: Deploy with Railway CLI
```bash
cd MDRRMOSite
npm install -g @railway/cli
railway login
railway init
railway up
```

### 4. Configure Environment Variables in Railway

Go to your Railway project → Variables tab and add:

```env
# App Configuration
APP_NAME="MDRRMO Admin"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.railway.app

# Generate new key with: php artisan key:generate --show
APP_KEY=base64:Oc7/MVHRQgqMLV+QKbnmiKNPseO9iDb8exsighJlzZ4=

# Database (Supabase Transaction Pooler)
DB_CONNECTION=pgsql
DB_HOST=aws-1-ap-south-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.kbcdtmnqmismqjtyzmrp
DB_PASSWORD=MDRRMO_APP_RESCUE
DB_SSLMODE=require

# Supabase API
SUPABASE_URL=https://bfhsgdzciuafiobxflce.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJmaHNnZHpjaXVhZmlvYnhmbGNlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzA2NjMwMTUsImV4cCI6MjA4NjIzOTAxNX0.Sna2YeX6cxknmuRas5UU3m_EcS7B4cpNCgG7WBt-HG0
SUPABASE_SERVICE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJmaHNnZHpjaXVhZmlvYnhmbGNlIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MDY2MzAxNSwiZXhwIjoyMDg2MjM5MDE1fQ.2iPBkpjxq4fE-auIoVmrstezzRBTNzhFuH-dh1flASA

# Mapbox
MAPBOX_ACCESS_TOKEN=pk.eyJ1Ijoicm9tZXJvamFuc3NlbjA5IiwiYSI6ImNsenFqOHVqdTFrcGoyaW44MTJqMm11ZDUifQ.KEBrpTsF6sUiUSKxhoN_VQ

# Session & Cache
SESSION_DRIVER=file
CACHE_STORE=file

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 5. Build Configuration

Railway should auto-detect Laravel. If not, create `railway.toml`:

```toml
[build]
builder = "nixpacks"

[deploy]
startCommand = "php artisan serve --host=0.0.0.0 --port=$PORT"
healthcheckPath = "/"
healthcheckTimeout = 100
restartPolicyType = "on_failure"
restartPolicyMaxRetries = 10
```

### 6. Post-Deployment Commands

After deployment, run these commands in Railway's terminal:

```bash
# Clear and optimize caches
php artisan optimize:clear
php artisan optimize

# Create admin user (if not exists)
php artisan db:seed --class=AdminUserSeeder
```

⚠️ **IMPORTANT**: 
- **DO NOT run `php artisan migrate`** - The database schema is already set up by the mobile app's Supabase migrations
- The admin site only connects to the existing database, it does NOT create or modify tables
- All database schema changes must be done through the mobile app's Supabase migrations

### 7. Create Admin User

The seeder will create an admin account:
- **Email**: `admin@mdrrmo.com`
- **Password**: `admin123`

⚠️ **IMPORTANT**: Change this password immediately after first login!

### 8. Verify Deployment

1. Visit your Railway app URL
2. Login with admin credentials
3. Check that:
   - Dashboard loads with real-time data
   - Reports page shows emergency reports
   - Teams page shows rescue teams
   - Map displays correctly

## 🔧 Troubleshooting

### Database Connection Issues
- Verify Supabase credentials are correct
- Check that SSL mode is set to `require`
- Ensure using Transaction Pooler port (6543) not direct connection (5432)

### Real-time Not Working
- Check browser console for errors
- Verify SUPABASE_ANON_KEY is correct
- Ensure Supabase real-time is enabled for tables

### 500 Errors
- Check Railway logs: `railway logs`
- Verify APP_KEY is set
- Run `php artisan config:clear`

### Assets Not Loading
- Run `npm run build` before deployment
- Check that `public/build` directory exists
- Verify APP_URL matches your Railway domain

## 📝 Important Notes

1. **No Laravel Migrations**: Database schema is managed by mobile app's Supabase migrations
2. **Service Role Key**: Admin uses service_role key to bypass RLS policies
3. **Transaction Pooler**: Using port 6543 for better connection handling
4. **Real-time**: Dashboard uses Supabase real-time subscriptions
5. **Security**: Always use HTTPS in production (Railway provides this automatically)

## 🔄 Updating the App

```bash
# Push changes to GitHub
git add .
git commit -m "Update admin site"
git push

# Railway will auto-deploy
```

Or manually trigger deployment in Railway dashboard.

## 📞 Support

If you encounter issues:
1. Check Railway logs
2. Verify environment variables
3. Test database connection
4. Check Supabase dashboard for API usage

---

**Last Updated**: February 2026
**Version**: 1.0.0
