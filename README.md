# MDRRMO Admin Dashboard

Laravel-based admin dashboard for the MDRRMO Rescue App.

## 🚀 Quick Deploy to Railway

1. Push to GitHub
2. Connect Railway to your repository
3. Set root directory to `MDRRMOSite`
4. Configure environment variables (see RAILWAY_DEPLOYMENT.md)
5. Deploy!

## 📋 Important Notes

- **Database**: Connects to existing Supabase database (shared with mobile app)
- **No Migrations**: Database schema is managed by mobile app's Supabase migrations
- **Service Role**: Uses Supabase service_role key to bypass RLS policies
- **Size**: ~1 MB (dependencies installed during build)

## 🔑 Default Admin Credentials

After deployment, run:
```bash
php artisan db:seed --class=AdminUserSeeder
```

Login with:
- Email: `admin@mdrrmo.com`
- Password: `admin123`

⚠️ Change password immediately after first login!

## 📚 Documentation

- [Railway Deployment Guide](RAILWAY_DEPLOYMENT.md)
- [Deployment Checklist](DEPLOYMENT_CHECKLIST.md)
- [Cleanup Summary](CLEANUP_SUMMARY.md)

## 🛠️ Tech Stack

- Laravel 11
- PostgreSQL (Supabase)
- Tailwind CSS
- Leaflet Maps
- Real-time Subscriptions

## 📞 Support

For deployment issues, check the Railway logs and deployment documentation.
"# MDRRMORescueSite" 
