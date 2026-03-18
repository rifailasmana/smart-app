# SmartOrder - Sistem Pemesan Makanan Digital

Sistem restoran self-ordering berbasis Laravel 12 dengan Kitchen Display System (KDS) real-time, multi-role dashboard, dan payment processing terintegrasi.

## 🎯 Fitur Utama

✅ **Landing Page dengan Pricing Tiers** - 3 paket berlangganan (Starter, Professional, Enterprise)
✅ **Customer Self-Ordering** - Menu digital dengan order code generation
✅ **Kitchen Display System (KDS)** - Real-time split-screen untuk kitchen staff
✅ **Multi-Role Dashboard** - Owner, Kasir, Waiter, Kitchen, Admin
✅ **Real-time Updates** - Server-Sent Events (SSE) untuk live status
✅ **Payment Processing** - Support 3 metode pembayaran (Tunai, QRIS, Gateway)
✅ **Order Tracking** - Customer dapat tracking pesanan via order code
✅ **WhatsApp Notifications** - Logging untuk notifikasi (siap untuk Twilio integration)

## 🛠️ Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.3.28
- **Database**: MySQL 8.4
- **Frontend**: Bootstrap 5, Vanilla JavaScript
- **Real-time**: Server-Sent Events (SSE)
- **Authentication**: Laravel Session Auth

## 📋 Prerequisites

- PHP 8.3+
- MySQL 8.0+
- Composer
- Git
- Laragon (recommended for Windows development)

## 🚀 Installation

### 1. Clone Repository
```bash
cd c:\laragon\www
git clone <repository-url> smart-app
cd smart-app
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env`:
```env
APP_NAME=SmartOrder
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartorder
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Setup
```bash
# Run migrations and seeding
php artisan migrate:fresh --seed --seeder=WarungSeeder

# Verify seeding
php artisan tinker
DB::table('users')->count()  # Should return 4
```

### 5. Start Server
```bash
php artisan serve --host=0.0.0.0 --port=8080
```

Access: `http://localhost:8080`

## 📊 Database Schema

### Core Tables

**users**
- id, name, email, password, role (enum: admin/owner/kasir/dapur/waiter)
- warung_id (FK), timestamps

**warungs**
- id, name, code (unique), description
- subscription_tier (enum: starter/professional/enterprise)
- monthly_price, subscription_expires
- timestamps

**restaurant_tables**
- id, warung_id (FK), name, seats, timestamps

**orders**
- id, warung_id (FK), table_id (FK), code (unique)
- status (enum: pending/preparing/ready/served/paid)
- subtotal, admin_fee, total, payment_method
- timestamps

**order_items**
- id, order_id (FK), menu_name, qty, price, timestamps

**menu_items**
- id, warung_id (FK), name, description, price
- category (enum: makanan/minuman/dessert)
- image, active (boolean), timestamps

## 👥 Demo Accounts

All accounts use password: `password`

| Email | Role | Warung | Access |
|-------|------|--------|--------|
| owner@bali.local | Owner | Bali | `/dashboard/owner` |
| kasir@bali.local | Kasir | Bali | `/dashboard/kasir` |
| dapur@bali.local | Kitchen | Bali | `/dashboard/kitchen` |
| waiter@bali.local | Waiter | Bali | `/dashboard/waiter` |

## 🗺️ Routes Overview

### Public Routes
```
GET  /                          - Landing page
GET  /menu?warung=CODE&meja=ID  - Customer menu
POST /order                     - Place order
GET  /order-status?code=CODE    - Track order
GET  /order-status/stream       - SSE status stream
```

### Authentication
```
GET  /login                     - Login form
POST /login                     - Process login
GET  /register                  - Register form
POST /register                  - Process registration
POST /logout                    - Logout
```

### Dashboard Routes (Authenticated)
```
GET  /dashboard                 - Role-based redirect
GET  /dashboard/owner           - Owner dashboard (role: owner, admin)
GET  /dashboard/kasir           - Kasir dashboard (role: kasir, admin)
GET  /dashboard/kitchen         - Kitchen dashboard (role: dapur, admin)
GET  /dashboard/waiter          - Waiter dashboard (role: waiter, admin)
GET  /dashboard/admin           - Admin panel (role: admin)
```

### API Endpoints (AJAX/JSON)
```
POST /order/{id}/payment        - Process payment (Kasir)
POST /order/{id}/status         - Update order status
POST /order/{id}/serve          - Mark as served (Waiter)
GET  /dashboard/stream-orders   - SSE order stream
```

## 💻 User Workflows

### 1. Customer Ordering Flow
```
1. Visit http://localhost:8080/menu?warung=BALI&meja=1
2. Browse menu items (Makanan, Minuman, Dessert)
3. Add items to order with quantity
4. Select payment method (Tunai/QRIS/Gateway)
5. Submit order
6. Get unique order code (e.g., BALI-MON0915-001)
7. View status page with real-time updates
```

### 2. Kitchen Processing Flow
```
1. Login as dapur@bali.local → /dashboard/kitchen
2. See PESANAN BARU (Pending) orders on left
3. Click "MULAI MASAK" → status changes to preparing
4. Order moves to SEDANG DIMASAK (Preparing) on right
5. Click "SIAP ANTAR" → status changes to ready
6. Order disappears from KDS (waiter takes over)
```

### 3. Kasir Payment Flow
```
1. Login as kasir@bali.local → /dashboard/kasir
2. See all pending orders (status != paid)
3. Review order details (items, subtotal, fees, total)
4. Click "BAYAR" button
5. Confirm payment amount
6. Status changes to paid
7. Order removed from list
```

### 4. Waiter Service Flow
```
1. Login as waiter@bali.local → /dashboard/waiter
2. See only READY orders
3. Deliver order to table
4. Click "SERAH ke CUSTOMER"
5. Status changes to served
```

### 5. Owner Management Flow
```
1. Login as owner@bali.local → /dashboard/owner
2. View overview stats:
   - Total orders today
   - Total revenue
   - Menu items count
   - Best-selling item
3. Manage menu items
4. View all orders with status
5. Access reports (optional)
```

## 🔐 Role-Based Access Control

| Role | Can Access | Actions |
|------|-----------|---------|
| Admin | All areas | Full control, manage all warungs |
| Owner | Own warung | View stats, manage menu, view orders |
| Kasir | Own warung | Process payments, view pending orders |
| Dapur (Kitchen) | Own warung | Update order status, view KDS |
| Waiter | Own warung | Mark orders as served, view ready orders |

## 🔗 Order Code Format

Format: `{WARUNG_CODE}-{DAY}{MMDD}-{SEQUENCE}`

Example: `BALI-MON0915-001`
- BALI = Warung code
- MON = Day of week
- 0915 = Month-Day (09/15)
- 001 = Sequence number (resets daily)

## 💳 Payment Methods & Admin Fee

| Method | Admin Fee | Usage |
|--------|-----------|-------|
| Tunai (Cash) | 0% | Direct cash payment |
| QRIS | 1% | QR code payment |
| Gateway | 1% | Online payment gateway |

Fee calculated: `admin_fee = subtotal * 0.01` (only for non-cash)

## 🔄 Order Status Flow

```
pending → preparing → ready → served → paid
   ↓
 (payment can be made at any stage after pending)
```

## ⚡ Real-time Features

### Server-Sent Events (SSE)
- **Customer Status Page**: Updates every 2 seconds
- **Kitchen Dashboard**: Updates every 5 seconds
- **Kasir Dashboard**: Updates every 3 seconds (auto-reload)

### Implementation
```javascript
const eventSource = new EventSource('/order-status/stream?warung=CODE&code=CODE');
eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log(data.status);
};
```

## 📞 Notification Service

Located in `app/Services/NotificationService.php`

**Current**: Logs to 'orders' channel
**Production Hook**: Twilio WhatsApp integration ready

Notification types:
- `new_order` - Order created
- `ready` - Order ready for delivery
- `served` - Order delivered
- `payment` - Payment received

## 🧪 Testing

### Test Complete Workflow
```bash
# 1. Start server
php artisan serve --host=0.0.0.0 --port=8080

# 2. Open in browsers
# Browser 1: Customer at http://localhost:8080/menu?warung=BALI&meja=1
# Browser 2: Kitchen at http://localhost:8080/dashboard/kitchen
# Browser 3: Kasir at http://localhost:8080/dashboard/kasir
# Browser 4: Waiter at http://localhost:8080/dashboard/waiter

# 3. Create order in customer browser
# 4. Watch kitchen dashboard update in real-time
# 5. Process payment in kasir browser
# 6. Verify status changes in all browsers
```

### Database Testing
```bash
php artisan tinker

# Check users
DB::table('users')->count()  # 4 demo users

# Check seeded data
DB::table('warungs')->first()  # Restoran Bali

# Check menu items
DB::table('menu_items')->count()  # 12 items

# Create test order
$order = Order::factory()->create();
```

## 📝 Log Files

**Laravel Logs**: `storage/logs/laravel.log`
**Order Logs**: `storage/logs/orders.log` (SSE and notifications)

View logs:
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/orders.log
```

## 🚨 Troubleshooting

### Server won't start
```bash
# Check PHP version
php -v  # Should be 8.3+

# Use Laragon's PHP 8.3
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe artisan serve
```

### Database errors
```bash
# Verify connection
php artisan tinker
DB::connection()->getPdo()  # Should return connection

# Re-seed database
php artisan migrate:fresh --seed --seeder=WarungSeeder
```

### CSRF token errors
- Ensure `@csrf` directive in all POST forms
- Check meta tag: `<meta name="csrf-token" content="{{ csrf_token() }}">`

### SSE not updating
- Check browser console for errors
- Verify server is still running (no timeout)
- Ensure routes are correct: `/order-status/stream`

## 📦 File Structure

```
smart-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── LandingController.php
│   │   │   ├── OrderController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── Auth/RegisterController.php
│   │   │   └── MenuItemController.php
│   │   ├── Middleware/
│   │   │   └── CheckRole.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Warung.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── MenuItem.php
│   │   └── RestaurantTable.php
│   ├── Services/
│   │   ├── OrderCodeGenerator.php
│   │   └── NotificationService.php
│
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_warungs_table.php
│   │   ├── create_menu_items_table.php
│   │   ├── create_orders_table.php
│   │   └── ... (9 total)
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   └── WarungSeeder.php
│
├── resources/
│   ├── views/
│   │   ├── landing/
│   │   │   └── index.blade.php
│   │   ├── auth/
│   │   │   ├── login.blade.php
│   │   │   └── register.blade.php
│   │   ├── customer/
│   │   │   ├── menu.blade.php
│   │   │   └── status.blade.php
│   │   ├── dashboard/
│   │   │   ├── owner.blade.php
│   │   │   ├── kasir.blade.php
│   │   │   ├── kitchen.blade.php
│   │   │   ├── waiter.blade.php
│   │   │   └── admin.blade.php
│   │   └── layouts/
│   │       └── app.blade.php
│
├── routes/
│   └── web.php
│
├── .env
├── composer.json
└── README.md
```

## 🎨 Pricing Tiers

### Starter - Rp 150.000/bulan → 250.000/bulan
- Menu Digital
- Kitchen Display System
- 5 staff maksimal
- Basic reporting

### Professional ⭐ - Rp 250.000/bulan → 350.000/bulan
- Semua fitur Starter +
- Best-Seller Analytics
- Advanced Dashboard
- 6 bulan maintenance gratis
- Priority support

### Enterprise - Custom
- Semua fitur Professional +
- Unlimited staff
- Hardware included (Kasir PC, KDS, Customer TV)
- On-site training
- 24/7 dedicated support
- Custom integrations

## 📞 Support & Contact

**Email**: support@smartorder.local
**WhatsApp**: +62 xxx xxxx xxxx
**Documentation**: Full API docs available in `/docs`

## 📄 License

Proprietary - SmartOrder 2024

## 🤝 Contributing

For development, please:
1. Create feature branch: `git checkout -b feature/your-feature`
2. Commit changes: `git commit -am 'Add feature'`
3. Push to branch: `git push origin feature/your-feature`
4. Submit pull request

## ✅ Checklist - Before Production

- [ ] Update `.env` with production database
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate new `APP_KEY`
- [ ] Run migrations on production database
- [ ] Setup Twilio for WhatsApp notifications
- [ ] Configure Stripe/PayPal for payment gateway
- [ ] Setup email service (SendGrid/Mailtrap)
- [ ] Configure CDN for image uploads
- [ ] Setup backup automation
- [ ] Configure monitoring & logging
- [ ] Setup SSL certificate
- [ ] Create production deployment guide

## 🎯 Future Enhancements

- [ ] Mobile app (React Native)
- [ ] Stripe/PayPal integration
- [ ] Email notifications
- [ ] Inventory management
- [ ] Customer loyalty program
- [ ] Analytics dashboard
- [ ] Multi-location management
- [ ] Delivery tracking
- [ ] Recipe management
- [ ] Staff shift scheduling

---

**Last Updated**: December 2024
**Version**: 1.0.0
**Status**: Production Ready ✅
