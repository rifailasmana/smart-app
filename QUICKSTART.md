# SmartOrder - Quick Start Guide

Get up and running with SmartOrder in 5 minutes!

## 🚀 Quick Start (5 minutes)

### 1. Setup (1 min)
```bash
cd c:\laragon\www\smart-app
composer install
```

### 2. Database (1 min)
```bash
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan migrate:fresh --seed --seeder=WarungSeeder
```

### 3. Start Server (30 seconds)
```bash
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan serve --host=0.0.0.0 --port=8080
```

### 4. Open Browser (30 seconds)
- **Landing Page**: http://localhost:8080
- **Login**: http://localhost:8080/login
- **Register**: http://localhost:8080/register

### 5. Test with Demo Account
- **Email**: kasir@bali.local
- **Password**: password
- **Access**: http://localhost:8080/dashboard/kasir

## 📱 Test All Roles

Open 4 browser tabs and login with different accounts:

| Tab | Role | Email | Dashboard |
|-----|------|-------|-----------|
| 1 | Owner | owner@bali.local | /dashboard/owner |
| 2 | Kasir | kasir@bali.local | /dashboard/kasir |
| 3 | Kitchen | dapur@bali.local | /dashboard/kitchen |
| 4 | Waiter | waiter@bali.local | /dashboard/waiter |

## 🍜 Create Test Order

1. Open new tab: http://localhost:8080/menu?warung=BALI&meja=1
2. Add items to cart
3. Select payment method
4. Click Order
5. Get order code (e.g., BALI-MON0915-001)
6. Watch it update in real-time across all dashboards!

## 📊 Database

- **Database**: smartorder
- **Users**: 4 demo accounts (all password: password)
- **Menu Items**: 12 items pre-loaded
- **Tables**: 4 demo tables

Verify seeding:
```bash
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan tinker
DB::table('users')->count()     # Should return 4
DB::table('warungs')->count()   # Should return 1
DB::table('menu_items')->count() # Should return 12
```

## 🔑 Key Files

**Controllers** (app/Http/Controllers/)
- `LandingController.php` - Homepage
- `OrderController.php` - Customer orders
- `DashboardController.php` - Staff dashboards
- `Auth/RegisterController.php` - Registration

**Models** (app/Models/)
- `User.php` - Users with roles
- `Warung.php` - Restaurants
- `Order.php` - Orders
- `MenuItem.php` - Menu items

**Routes** (routes/web.php)
- Public routes
- Auth routes
- Dashboard routes
- API endpoints

**Views** (resources/views/)
- `landing/index.blade.php` - Homepage
- `auth/login.blade.php` - Login form
- `auth/register.blade.php` - Register form
- `customer/menu.blade.php` - Customer menu
- `dashboard/*.blade.php` - Staff dashboards

## 🛠️ Common Commands

```bash
# Start server
php artisan serve --host=0.0.0.0 --port=8080

# Fresh database
php artisan migrate:fresh --seed --seeder=WarungSeeder

# View logs
tail -f storage/logs/laravel.log

# Access database shell
php artisan tinker

# Clear cache
php artisan cache:clear
php artisan config:clear
```

## 📝 Test Workflows

### Customer Flow
```
1. Visit /menu?warung=BALI&meja=1
2. Add 2 items to cart
3. Select QRIS payment
4. Click Order
5. Get order code
6. View status page (updates every 2 sec via SSE)
```

### Kitchen Flow
```
1. Login as dapur@bali.local
2. See pending orders on left
3. Click "Mulai Masak" (order moves to right)
4. Click "Siap Antar" (status updates to ready)
5. Order disappears (now waiter's turn)
```

### Kasir Flow
```
1. Login as kasir@bali.local
2. See pending payments
3. Click "Bayar"
4. Confirm amount
5. Order marked as paid
6. Order removed from list
```

### Waiter Flow
```
1. Login as waiter@bali.local
2. See ready orders
3. Click "Serah ke Customer"
4. Order marked as served
5. Order removed from list
```

### Owner Flow
```
1. Login as owner@bali.local
2. View today's stats
3. See menu items
4. View all orders
5. Manage items (future)
```

## 🎯 Key Features to Test

- [ ] Order code generates correctly (BALI-MON0915-001 format)
- [ ] Admin fee calculated for QRIS (1%)
- [ ] Status updates in real-time
- [ ] Kitchen sees orders immediately
- [ ] Order status matches across all dashboards
- [ ] Logout clears session
- [ ] Protected routes require login
- [ ] Owner can't see other warung's orders

## 🐛 Troubleshooting

### Server won't start
```bash
# Use full PHP path
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan serve --host=0.0.0.0 --port=8080
```

### Database error
```bash
# Check database exists and is connected
php artisan tinker
DB::connection()->getPdo()

# Re-seed if needed
php artisan migrate:fresh --seed --seeder=WarungSeeder
```

### SSE not updating
- Refresh browser
- Check server is still running
- Check browser console for errors
- Verify route `/order-status/stream` is accessible

### CSRF token error
- Ensure `@csrf` in all POST forms
- Check meta tag in page head
- Clear browser cache

## 📚 Full Documentation

- **README.md** - Comprehensive setup guide
- **TEST_RESULTS.md** - Complete test report
- **routes/web.php** - All API endpoints
- **app/Http/Controllers/** - Controller documentation

## 🚀 Next Steps

1. **Customize styling** - Edit Bootstrap classes in views
2. **Add more restaurants** - Use registration form
3. **Setup payments** - Integrate Stripe/PayPal
4. **Add notifications** - Configure Twilio for WhatsApp
5. **Deploy to production** - Follow deployment guide in README

## 📞 Support

- Check README.md for detailed documentation
- Review TEST_RESULTS.md for features
- Check logs in `storage/logs/laravel.log`
- Run `php artisan tinker` to inspect database

## ✅ Verification Checklist

After setup, verify:
- [ ] Server running on http://localhost:8080
- [ ] Landing page loads
- [ ] Login/Register pages work
- [ ] Can login with kasir@bali.local / password
- [ ] Dashboard redirects based on role
- [ ] Customer menu loads at /menu?warung=BALI&meja=1
- [ ] Can place order and get code
- [ ] Kitchen dashboard shows orders
- [ ] Status updates in real-time

## 🎉 You're Ready!

SmartOrder is now running and ready to test. Start with the customer flow, then test each role dashboard. Watch the real-time updates work across all screens!

**Happy Testing! 🚀**

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Status**: ✅ Production Ready
