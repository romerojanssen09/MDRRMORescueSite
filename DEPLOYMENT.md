# Deployment Guide

## 🌐 Production Deployment

### Prerequisites
- VPS or shared hosting with PHP 8.2+
- Domain name
- SSL certificate
- Supabase production project

### Backend Deployment (Laravel)

#### 1. Prepare Server
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### 2. Upload Files
```bash
# Clone or upload your project
git clone your-repo.git /var/www/mdrrmo
cd /var/www/mdrrmo
```

#### 3. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

#### 4. Configure Environment
```bash
cp .env.example .env
nano .env
```

Update production values:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

SUPABASE_URL=your_production_supabase_url
SUPABASE_KEY=your_production_anon_key
```

#### 5. Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/mdrrmo
sudo chmod -R 755 /var/www/mdrrmo
sudo chmod -R 775 /var/www/mdrrmo/storage
sudo chmod -R 775 /var/www/mdrrmo/bootstrap/cache
```

#### 6. Configure Web Server

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/mdrrmo/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 7. Enable SSL
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

#### 8. Restart Services
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### Mobile App Deployment

#### 1. Configure EAS Build
```bash
cd MDRRMORescueApp
npm install -g eas-cli
eas login
eas build:configure
```

#### 2. Update Production Config
Edit `app.json`:
```json
{
  "expo": {
    "name": "MDRRMO Rescue",
    "slug": "mdrrmo-rescue",
    "version": "1.0.0",
    "extra": {
      "eas": {
        "projectId": "your-project-id"
      }
    }
  }
}
```

Update `.env`:
```
EXPO_PUBLIC_SUPABASE_URL=your_production_url
EXPO_PUBLIC_SUPABASE_ANON_KEY=your_production_key
```

#### 3. Build for Android
```bash
eas build --platform android --profile production
```

#### 4. Build for iOS
```bash
eas build --platform ios --profile production
```

#### 5. Submit to Stores
```bash
# Google Play Store
eas submit --platform android

# Apple App Store
eas submit --platform ios
```

## 🔒 Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong database passwords
- [ ] Enable SSL/HTTPS
- [ ] Configure firewall rules
- [ ] Set up regular backups
- [ ] Enable Supabase RLS policies
- [ ] Use environment variables for secrets
- [ ] Configure CORS properly
- [ ] Set up monitoring and logging

## 📊 Performance Optimization

### Laravel
```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### Database
- Enable Supabase connection pooling
- Add indexes for frequently queried columns
- Use database query caching

### Frontend
- Minify CSS/JS assets
- Enable browser caching
- Use CDN for static assets
- Optimize images

## 🔄 Updates & Maintenance

### Updating Backend
```bash
git pull origin main
composer install --no-dev
npm install
npm run build
php artisan config:clear
php artisan cache:clear
```

### Updating Mobile App
```bash
# Update version in app.json
# Build new version
eas build --platform all
# Submit to stores
eas submit --platform all
```

## 📱 Push Notifications in Production

1. Configure Expo push notification credentials
2. Update Supabase Edge Function with production URL
3. Test notifications thoroughly
4. Monitor notification delivery rates

## 🐛 Troubleshooting

### 500 Internal Server Error
- Check Laravel logs: `storage/logs/laravel.log`
- Verify file permissions
- Check PHP error logs

### Real-time Not Working
- Verify Supabase realtime is enabled
- Check RLS policies
- Verify WebSocket connections

### Mobile App Crashes
- Check Expo logs
- Verify API endpoints
- Test on multiple devices

## 📞 Support

For deployment issues, check:
- Laravel documentation: https://laravel.com/docs
- Expo documentation: https://docs.expo.dev
- Supabase documentation: https://supabase.com/docs

---

**Remember:** Always test in staging environment before deploying to production!
