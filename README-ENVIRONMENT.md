# 🚀 Quick Start - Environment Switching

## 📁 Files Overview

- **`.env`** - Active configuration (DO NOT commit to git)
- **`local.env`** - Local development template
- **`production.env`** - Production template

## ⚡ Quick Switch

### Windows Users

**Switch to Local:**
```bash
# Double-click this file:
switch-to-local.bat

# Or run in terminal:
.\switch-to-local.bat
```

**Switch to Production:**
```bash
# Double-click this file:
switch-to-production.bat

# Or run in terminal:
.\switch-to-production.bat
```

### Linux/Mac Users

**Switch to Local:**
```bash
./switch-to-local.sh
```

**Switch to Production:**
```bash
./switch-to-production.sh
```

## 📖 Full Documentation

See **[ENVIRONMENT-GUIDE.md](ENVIRONMENT-GUIDE.md)** for complete documentation including:
- Detailed environment comparison
- Troubleshooting guide
- Best practices
- Security notes

## 🔧 Manual Switch (if scripts don't work)

### To Local:
```bash
copy local.env .env
php artisan config:clear
php artisan cache:clear
```

### To Production:
```bash
copy production.env .env
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

## ⚠️ Important

1. **Always clear cache** after switching environments
2. **Never commit `.env`** to git
3. **Test in local** before deploying to production
4. **Keep backups** of local.env and production.env

## 🎯 Current Environment Check

```bash
php artisan env
```

## 📊 Key Differences

| | Local | Production |
|---|---|---|
| Debug | ✅ ON | ❌ OFF |
| URL | localhost:8000 | Railway URL |
| DB Port | 5432 (Direct) | 6543 (Pooler) |
| Logging | Verbose | Minimal |

---

**Need Help?** Check [ENVIRONMENT-GUIDE.md](ENVIRONMENT-GUIDE.md)
