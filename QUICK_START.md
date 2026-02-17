# Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Install Dependencies
```bash
composer install
npm install
```

### Step 2: Configure Environment
```bash
copy .env.example .env
```

Edit `.env` and add your Supabase credentials:
```
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key
```

### Step 3: Setup Database
Run these SQL commands in Supabase SQL Editor:

```sql
-- Enable realtime
ALTER PUBLICATION supabase_realtime ADD TABLE rescue_teams;
ALTER PUBLICATION supabase_realtime ADD TABLE emergency_reports;
ALTER PUBLICATION supabase_realtime ADD TABLE rescuer_profiles;
```

Then run all migration files in `database/migrations/` folder.

### Step 4: Build & Run
```bash
npm run build
php artisan serve
```

### Step 5: Access Dashboard
Open http://127.0.0.1:8000

**Default Login:**
- Username: `admin`
- Password: `admin123`

## ✅ You're Ready!

The system is now running with:
- Real-time dashboard updates
- Emergency reports management
- Team coordination
- Push notifications

## 📱 Mobile App Setup

```bash
cd ../MDRRMORescueApp
npm install
npx expo start
```

Scan QR code with Expo Go app to test!
