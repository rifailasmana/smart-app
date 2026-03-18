# Analisis Menyeluruh Sistem Manajemen Restoran / Coffee Shop Digital (SmartOrder)

## 1. Arsitektur Sistem

### 1.1 Pola Desain & Layering

- Berbasis **Laravel MVC klasik**:
  - Controller utama:
    - `OrderController` – alur pemesanan customer, status, struk, pembayaran (kasir).
    - `DashboardController` – semua dashboard staff (kasir, dapur, waiter, owner, admin).
    - `ReportController` – export laporan CSV.
    - `RestaurantController` – manajemen data restoran (warung) untuk admin/owner.
    - `Auth\RegisterController` – pendaftaran restoran + owner.
    - `LandingController` – halaman marketing/landing.
  - Model domain inti:
    - `Warung`, `Order`, `OrderItem`, `MenuItem`, `RestaurantTable`,
      `DailyClosure`, `StaffShift`, `User`.
  - Service layer:
    - `NotificationService` – notifikasi WhatsApp + struk digital.
    - `OrderCodeGenerator` – generator kode order unik per warung.
    - `ReportExporter` – export laporan (daily/weekly/monthly) ke CSV.
    - `GoogleSheetService` – sinkronisasi transaksi ke Google Sheets (saat ini simulasi).

- Tidak ada modular monolith formal, tetapi pemisahan alami:
  - **Customer flow**: menu, order, order status, struk.
  - **Backoffice/dashboard**: kasir, waiter, dapur, owner, admin.
  - **Integrasi eksternal**: WhatsApp, Google Sheets.

### 1.2 Multi-Tenant & Subdomain per Warung

- Multi-tenant dengan **single database**, multi-tenant melalui `warung_id`:
  - `orders`, `order_items`, `menu_items`, `restaurant_tables`, `staff_shifts`, `users`
    semuanya memiliki relasi ke `warung`.

- Subdomain:
  - Customer mengakses subdomain:
    - `{warung_code}.localhost`
    - `{warung_code}.smartorder.local`
    - `{warung_code}.smartorder.com`
  - Didefinisikan di `routes/web.php` dengan group `Route::domain(...)`.
  - Middleware `ResolveWarungFromSubdomain`:
    - Ekstrak `warung_code` dari host.
    - Load `Warung` berdasarkan `code`/`slug`.
    - Set `warung` dan `warung_id` ke atribut request.

- Helper subdomain:
  - `SubdomainHelper`:
    - `getWarungUrl`, `getMenuUrl`, `getTableQRUrl` → membangun URL berbasis
      `code.smartorder.local:8080` (untuk dev).
    - `generateMenuToken` + `validateMenuToken` → token pengaman untuk QR per meja.

### 1.3 Redirect Staff ke Subdomain

- Middleware `RedirectAuthenticatedToSubdomain`:
  - Jika user sudah login di main domain dan bukan admin:
    - Cek `warung_id`.
    - Redirect ke subdomain warung + dashboard sesuai role
      (`owner/kasir/waiter/dapur`).

### 1.4 Role-Based Access Control (RBAC)

- Kolom `users.role` (enum):
  - `admin`, `owner`, `kasir`, `dapur`, `waiter`, `kitchen`.

- Middleware `CheckRole` (alias `role`):
  - Memastikan user login.
  - Hanya mengizinkan request jika `auth()->user()->role` ada di daftar role
    yang diizinkan.

- Pemetaan route → role (di `routes/web.php`):
  - `role:admin,owner`:
    - Owner dashboard (`/dashboard/owner`) + owner orders.
    - Admin dashboard (`/admin/warungs`, `/admin/restaurants/...`).
  - `role:kasir,admin`:
    - `/dashboard/kasir` + alur verify payment, payment final, opening/closing harian,
      edit qty, cancel, toggle stok.
  - `role:waiter,admin`:
    - `/dashboard/waiter` + mark order served.
  - `role:dapur,admin`:
    - `/dashboard/kitchen` + update status order di dapur.
  - `role:admin,owner,kasir`:
    - `/reports/export/{period}` → export laporan CSV.

### 1.5 Struktur Folder

- `app/Http/Controllers`:
  - Terorganisir, tetapi sebagian route masih memakai string controller
    `"App\Http\Controllers\XController@method"` dan sebagian class array.

- `app/Models`:
  - Model per tabel, relasi standar Laravel (hasMany/belongsTo).

- `app/Services`:
  - Kumpulan service stateless yang menangani integrasi/utility.

- `resources/views`:
  - Layout:
    - `layouts/app.blade.php`, `layouts/dashboard.blade.php`.
  - Landing:
    - `landing/index.blade.php` (aktif).
    - `landing.blade.php` (legacy).
  - Customer:
    - `customer/menu.blade.php`, `customer/status.blade.php`.
  - Dashboard:
    - Peran operasional:
      - `dashboard/kasir.blade.php`
      - `dashboard/kitchen.blade.php`
      - `dashboard/waiter.blade.php`
    - Owner:
      - `dashboard/resto/bali/owner.blade.php`
      - `dashboard/resto/bali/owner-orders.blade.php`
    - Admin:
      - `dashboard/admin.blade.php`
      - `dashboard/admin-restaurant.blade.php`
    - Legacy/eksperimen:
      - `dashboard/owner.blade.php`
      - `dashboard/owner_new.blade.php`
      - `dashboard/customer_menu.blade.php`
      - `resources/views/dashboard.blade.php` (landing dashboard generik).


## 2. Alur Utama (End-to-End)

### 2.1 Customer: Akses Menu → Pesan → Lihat Status → Struk & Notifikasi

1. **Akses Menu**
   - Customer buka:
     - `{warung_code}.smartorder.local` atau `.com`.
   - Route:
     - `GET /` (di subdomain) → `OrderController@create`.
   - Middleware `resolve.warung` meng-inject `warung`.
   - Jika ada `?meja=`/`?table_id=`:
     - Cari `RestaurantTable` milik warung tersebut.
   - Token `secure` (opsional):
     - Diverifikasi dengan `SubdomainHelper::validateMenuToken`.
   - View:
     - `customer/menu.blade.php`:
       - Grid menu, kategori, best seller hari ini.
       - Input jumlah, catatan, pilih metode pembayaran (kasir/qris/gateway).

2. **Pemesanan**
   - Frontend mengirim request POST `/order` (subdomain atau main domain).
   - `OrderController@store`:
     - Parsing `items` (JSON → array PHP).
     - Validasi:
       - `table_id`, `items.*.menu_id`, `items.*.qty`,
       - `payment_method`, `payment_channel` (jika gateway),
       - `customer_phone`, `notes`, `secure`.
     - Validasi `secure` token jika ada.
   - Perhitungan harga:
     - Ambil data `MenuItem` per item.
     - Jika `promo_aktif` dan `harga_promo > 0`, gunakan harga promo.
     - `subtotal` = Σ (harga × qty).
     - `admin_fee` = 1% jika `payment_method != kasir`.
     - `total` = `subtotal + admin_fee`.
   - Pembuatan order:
     - Generate kode order via `OrderCodeGenerator::generate($warung)`.
     - Nomor antrian berdasarkan jumlah order hari itu.
     - Buat `Order` status `pending`.
     - Buat `OrderItem` untuk tiap item.
   - Notifikasi:
     - `NotificationService::sendOrderNotification($order, 'new_order')`.
     - Jika `payment_method = qris` → `qris_dummy`.
     - Jika `gateway` → `gateway_dummy`.
   - Session:
     - Simpan kode order di `customer_orders[warung_id]` untuk fitur “Pesanan saya”.
   - Response:
     - JSON berisi:
       - `code`, `queue_number`, `message`, dan `redirect` ke halaman status di subdomain.

3. **Lihat Status Pesanan**
   - Customer dibawa ke:
     - `GET /order-status?code=...` (di subdomain).
   - `OrderController@status`:
     - Cari `warung` via attribute atau query `warung`.
     - Load `Order` (dengan `items`, `warung`, `table`).
     - Cek apakah perlu otomatis expired:
       - Jika `payment_method` qris/gateway dan `status = pending` > 10 menit → `cancelled`.
     - View:
       - `customer/status.blade.php` menampilkan status dan detail pesanan.
       - Menggunakan SSE ke `/order-status/stream` untuk update real-time.
   - `OrderController@streamStatus`:
     - Loop: refresh order, jalankan rules:
       - Jika kadaluarsa → set `cancelled` + kirim WA `cancelled`.
       - Jika qris/gateway dan `pending` > 5 detik (simulasi) → set `verified` + WA `verified`.
     - Kirim event SSE dengan `status` dan `updated_at`.
     - Berhenti jika `status` `paid` atau `cancelled`.

4. **Struk Digital & WA**
   - Setelah kasir menandai `paid`:
     - `OrderController@processPayment`:
       - Update `status` → `paid`, set `kasir_id`.
       - Kirim notifikasi `payment` via WA (melalui `NotificationService`).
       - Panggil `GoogleSheetService::appendOrder($order)` untuk sinkronisasi (simulasi).
   - Struk digital on-demand:
     - Route: `GET /order-receipt?code=...` (public + subdomain & main).
     - `OrderController@receipt`:
       - Hanya mengizinkan jika `order.status === 'paid'`.
       - Menghasilkan teks struk via `NotificationService::buildReceiptMessage`.
       - Return JSON `{ success, code, content }`.
   - `NotificationService::buildReceiptMessage`:
     - Format struk profesional:
       - Nama warung, alamat, telepon.
       - No. resi, kode order, nomor antrian, meja, nama kasir.
       - Tanggal dengan format lokal Indonesia + zona `WITA`.
       - Daftar item (qty × nama) dengan perhitungan harga:
         - Memperhitungkan promo (`harga_promo`, `promo_aktif`).
       - Subtotal, biaya layanan (admin_fee), diskon manual (`diskon_manual`),
         total, metode bayar, status order.
       - Catatan kebijakan (barang tidak bisa dikembalikan) + kontak bantuan.


### 2.2 Staff: Login → Dashboard Sesuai Role → Kelola Order/Menu/Laporan

1. **Login & Shift**
   - Route:
     - `GET /login` → view `auth/login`.
     - `POST /login` → closure di `routes/web.php`.
   - Proses:
     - Validasi email & password.
     - `Auth::attempt`.
     - Jika role salah satu dari `kasir`, `waiter`, `dapur`, `kitchen`:
       - Tutup shift lama user (`StaffShift` dengan `ended_at` null).
       - Buat `StaffShift` baru dengan `started_at=now`.
     - Role `admin`:
       - Redirect ke `/admin/warungs`.
     - Role lain dengan `warung_id`:
       - Redirect ke subdomain warung + dashboard sesuai role.

2. **Kasir**
   - Route:
     - `GET /dashboard/kasir` → `DashboardController@kasir`.
   - Fitur:
     - Validasi `warung_id` dan keberadaan `Warung`.
     - Hitung ringkasan harian:
       - Total order hari ini, order paid, total revenue, average per order.
     - Load:
       - Order `pending`.
       - Order `verified/preparing/ready` (antrian dalam proses).
       - Order `served` (menunggu bayar).
       - Order `paid` (selesai).
       - `menu_items` milik warung.
     - View: `dashboard/kasir.blade.php`.
   - Opening & closing harian (`DailyClosure`):
     - Opening:
       - `POST /dashboard/kasir/opening`:
         - Jika sudah ada record hari ini, update `opened_by`, `opened_at` dan reset nilai total.
         - Jika belum ada, buat record baru.
     - Closing:
       - `POST /dashboard/kasir/closing`:
         - Hitung `transaction_count` dan `total_sales` dari order `paid` hari ini.
         - Hitung `average_transaction`.
         - Simpan ke `daily_closures` sebagai closing diverifikasi kasir.
   - Pembayaran:
     - Verifikasi (approve) pembayaran:
       - `POST /order/{order}/verify-payment` → `OrderController@verifyPayment`.
       - Hanya untuk kasir/admin, hanya ketika `status=pending`.
       - Update ke `verified` + kirim WA `verified`.
     - Final payment:
       - `POST /order/{order}/payment` → `OrderController@processPayment`.
       - Hanya untuk kasir/admin, hanya ketika `status=served`.
       - Update status ke `paid`, set `kasir_id`, kirim WA `payment`, sinkron ke Sheets.
   - Fitur lain:
     - Diskon manual: `PUT /order/{id}/discount`.
     - Edit qty: `POST /order/{order}/edit-qty`.
     - Cancel order: `POST /order/{order}/cancel`.
     - Toggle stok menu: `POST /menu-items/{id}/toggle-stock`.

3. **Dapur (Kitchen)**

   - Route:
     - `GET /dashboard/kitchen` → `DashboardController@kitchen`.
   - Data:
     - `verifiedOrders` – order `status=verified` (menunggu dimasak).
     - `preparingOrders` – order `status=preparing` atau `ready`.
     - Relasi `table` dan `items` sudah di-`with` untuk menghindari N+1.
   - View:
     - `dashboard/kitchen.blade.php`:
       - Mengelompokkan pesanan berdasarkan status.
       - Tombol untuk mengubah status order dan status item per menu (Pending/On Progress/Ready).
   - Logic status:
     - Di `DashboardController` (order level) dan `updateOrderItemStatus` (item level).
     - Menjaga sinkronisasi status order dan status item agar waiter bisa melihat per menu.

4. **Waiter**

   - Route:
     - `GET /dashboard/waiter` → `DashboardController@waiter`.
   - Data:
     - `activeOrders`: order dengan status `verified`, `preparing`, `ready`.
     - `servedOrders`: order `served` pada hari berjalan.
     - Semua order di-load dengan relasi `table` dan `items`.
   - View:
     - `dashboard/waiter.blade.php`:
       - Menampilkan setiap order dengan daftar item dan badge status tiap item:
         - Pending / On Progress / Ready / Served.
       - Tombol `Served` per item dan tombol "Tandai Semua Sudah Diterima (SERVED)" per order.
   - Logic:
     - `OrderController@markServed`:
       - Verifikasi `warung_id` user.
       - Update status order ke `served`.
       - Kirim WA `served`.

5. **Owner**

   - Route:
     - `GET /dashboard/owner` → `DashboardController@owner`.
     - `GET /dashboard/owner/orders` → `DashboardController@ownerOrders`.
   - Data:
     - Laporan:
       - Harian, mingguan, bulanan, tahunan:
         - Menghitung jumlah order `paid`, total revenue, dan best seller (top 10).
     - `menuItems` milik warung.
     - `staff` (user non owner) per warung.
     - `ordersToday` (dengan `table` dan `items`).
     - `staffShifts` dan `staffOrderSummary` untuk aktivitas staff.
     - `liveBoard` (antrian) dan `avgPrepTime`.
   - View:
     - `dashboard/resto/bali/owner.blade.php`:
       - Ringkasan hari ini (stat card).
       - Tab periode (daily/weekly/monthly/yearly) untuk grafik best seller.
       - Quick actions:
         - Tambah menu, refresh stok semua menu.
         - Edit info restoran & profil.
       - Seksi khusus “Antrian & Laporan Pesanan” → link ke halaman owner-orders.
       - Pengaturan:
         - Info restoran (logo, alamat, jam buka, kontak).
         - Diskon (batas persentase, otorisasi owner).
         - Google Sheets (enable, spreadsheet id, sheet name, last synced).
       - Preview tampilan customer & link QR customer.
     - `dashboard/resto/bali/owner-orders.blade.php`:
       - Live board status pesanan (menunggu verifikasi, diproses, siap, selesai).
       - Tabel ringkasan penjualan hari ini.

6. **Admin**

   - Route:
     - `GET /admin/warungs` → `DashboardController@admin`.
     - `GET /admin/restaurants/{warung}` → `DashboardController@adminRestaurant`.
   - Fitur:
     - Daftar semua warung:
       - `customer_url` otomatis (subdomain per warung).
       - `weekly_revenue` per warung.
       - `staff_count` per warung.
     - Dashboard per warung:
       - Laporan harian/mingguan/bulanan/tahunan mirip owner.
       - Best seller per periode.
     - Manajemen:
       - `RestaurantController`: create/update/destroy restoran.
       - `UserController`: create/update/destroy user staff.
       - `OrderController@restaurantOrders`: daftar order per restoran.
     - Endpoint laporan JSON:
       - `/admin/reports/{type}` → `DashboardController@getReports`.
     - Pengaturan global:
       - `/admin/settings` → `SettingsController@update` (perlu dicek penggunaannya di UI).


### 2.3 Integrasi Eksternal

1. **WhatsApp via Fonnte**
   - `NotificationService::sendWhatsApp`:
     - Mengambil `FONNTE_TOKEN` dari env.
     - Menormalkan nomor telepon dengan menghapus non-digit.
     - Mengirim request `POST` ke `https://api.fonnte.com/send` dengan:
       - `target`, `message`, `countryCode` (default `62`).
     - Logging status response atau exception.
   - Entry point:
     - `NotificationService::sendOrderNotification` dipanggil di banyak titik
       (order baru, status berubah, pembayaran, cancel).
     - `customer_phone` diambil dari input customer.

2. **Google Sheets**
   - `GoogleSheetService::appendOrder(Order $order)`:
     - Mengecek `warung->google_sheets_enabled` dan `google_sheets_spreadsheet_id`.
     - Menyusun data:
       - Waktu transaksi, kode order, nomor HP, nama meja, total, metode bayar,
         status, ringkasan item dalam satu string.
     - Saat ini:
       - Belum memanggil Google API sebenarnya (hanya logging dengan channel `daily`).
       - Meng-update `google_sheets_last_synced_at`.
   - Dipanggil pada:
     - `OrderController@processPayment` setelah order ditandai `paid`.


## 3. Fitur Aktif & Terpakai

Ringkasan fitur inti yang benar-benar dipakai di alur produksi:

1. **Menu Digital & Pemesanan Tanpa Login**
   - Routes public & subdomain untuk `/`, `/menu`, `/order`, `/order-status`,
     `/order-status/stream`, `/order-receipt`.
   - Controller `OrderController@create/store/status/streamStatus/receipt`.
   - Views `customer/menu.blade.php`, `customer/status.blade.php`.

2. **Antrian & Status Pesanan Bertahap**
   - Status order:
     - `pending → verified → preparing → ready → served → paid (+ cancelled)`.
   - Status item:
     - `pending`, `preparing`, `ready`, `served`.
   - Dioperasikan oleh:
     - Dapur (update preparing/ready).
     - Waiter (served).
     - Kasir (verified/paid).
   - Ditampilkan di:
     - `dashboard/kasir`, `dashboard/kitchen`, `dashboard/waiter`,
       `dashboard/resto/bali/owner-orders`, `dashboard/admin-restaurant`.

3. **Dashboard Per Peran**
   - Kasir:
     - Dashboard, antrian, pembayaran, discount, opening/closing harian.
   - Dapur:
     - Dashboard pesanan yang perlu dimasak dan sedang dimasak, status per item.
   - Waiter:
     - Dashboard pesanan yang siap/akan diantar, kontrol mark served.
   - Owner:
     - Dashboard ringkasan penjualan + best seller (harian, mingguan, bulanan, tahunan).
     - Halaman khusus antrian & laporan pesanan.
     - Manajemen menu, staff, pengaturan warung.
   - Admin:
     - Daftar warung, ringkasan revenue/staff, dashboard per warung, laporan global.

4. **Closing Harian & Verifikasi Pendapatan Kasir**
   - `DailyClosure` digunakan untuk mencatat:
     - Opening (kasir on duty).
     - Closing (total penjualan, jumlah transaksi, average).

5. **Laporan Harian/Mingguan/Bulanan**
   - Owner:
     - Dashboard owner menampilkan ringkasan revenue + best seller.
   - Export CSV:
     - `ReportController + ReportExporter` untuk periode `daily`, `weekly`, `monthly`.

6. **Promo Harga & Diskon Manual**
   - Promo:
     - Field `harga_promo`, `promo_aktif` pada `MenuItem`.
     - Dipakai pada perhitungan order dan struk.
   - Diskon manual:
     - `OrderController@updateDiscount`:
       - Memperhitungkan batas persentase diskon dan otorisasi owner (secara logika).
       - Mengurangi total order (`total = subtotal + admin_fee - diskon_manual`).

7. **Notifikasi WhatsApp**
   - Event utama:
     - `new_order`, `qris_dummy`, `gateway_dummy`, `verified`, `preparing`,
       `ready`, `served`, `payment`, `cancelled`.
   - Menggunakan Fonnte dengan token dari env.

8. **Integrasi Google Sheets (Simulasi)**
   - Setting di owner dashboard.
   - Service `GoogleSheetService` mencatat log sinkronisasi dan timestamp terakhir.


## 4. Fitur Tidak Terpakai / Legacy

### 4.1 View Tidak Direferensikan di Route/Controller

- `resources/views/dashboard/customer_menu.blade.php`
  - Tidak ada `view('dashboard.customer_menu')` dalam project.
  - Duplikat konsep menu customer yang sekarang ada di `customer/menu.blade.php`.

- `resources/views/dashboard/owner_new.blade.php`
  - Tidak ada referensi `view('dashboard.owner_new')`.
  - Eksperimen desain dashboard owner lama.

- `resources/views/dashboard/owner.blade.php`
  - Owner dashboard versi lama.
  - Saat ini owner menggunakan `dashboard/resto/bali/owner.blade.php`.

- `resources/views/dashboard.blade.php`
  - Dashboard generik yang tidak lagi dipakai.
  - Route `/dashboard` fallback menggunakan `view('dashboard.default')`
    yang bahkan belum ada view-nya.

- `resources/views/landing.blade.php`
  - Route landing menggunakan `view('landing.index')`.
  - File ini adalah sisa lama.

- `resources/views/welcome.blade.php`
  - Template default Laravel, tidak dipakai.

### 4.2 Route Legacy / Redirect Saja

- `/home`:
  - Hanya redirect ke `/dashboard`.

- `/dashboard/admin`:
  - Redirect ke `/admin/warungs` (backward compatibility).

- Subdomain `/menu`:
  - Di semua domain group (`localhost/smartorder.local/smartorder.com`):
    - Hanya redirect ke `/`.

### 4.3 Migration Kosong / Redundan

- `2026_01_17_091306_add_require_owner_auth_for_discount_to_warungs_table.php`:
  - `up()` dan `down()` kosong (`//`).
  - Tetapi:
    - Model `Warung` memiliki field `max_discount_percent`, `max_discount_amount`,
      `require_owner_auth_for_discount` dalam `$fillable`.
    - `OrderController@updateDiscount` mengakses `require_owner_auth_for_discount`
      dan `max_discount_percent`.
  - Artinya, migrasi lain (yang tidak tampil) atau perubahan manual DB dipakai
    untuk field-field ini, dan migrasi ini menjadi **dangling**/membingungkan.


## 5. Potensi Error & Anti-Pattern

### 5.1 Auto-Refresh dengan Full Page Reload

- Banyak view menggunakan:
  - `location.reload();`
  - `setInterval(() => location.reload(), X);`
- Contoh:
  - `dashboard/waiter.blade.php`
  - `dashboard/kitchen.blade.php`
  - `dashboard/kasir.blade.php`
  - `dashboard/resto/bali/owner.blade.php`
  - `dashboard/admin-restaurant.blade.php`
  - `customer/status.blade.php`
- Masalah:
  - Beban server besar (render Blade berkali-kali).
  - UX tidak halus (kedipan halaman).
  - Tidak memanfaatkan SSE yang sudah tersedia (`streamOrders`, `streamStatus`).

### 5.2 Validasi Input Nomor WhatsApp Minim

- `customer_phone`:
  - Validasi: `nullable|string|max:20`.
- `NotificationService::sendWhatsApp`:
  - Hanya menghapus karakter non-digit.
  - Tidak ada validasi panjang minimal/maksimal atau prefix (0/62).
- Dampak:
  - Banyak nomor tidak valid bisa lewat ke API.
  - Kegagalan pengiriman hanya terlihat di log, tidak di UI.
  - Setting `warung->whatsapp_notification` belum terintegrasi ke logika pengiriman.

### 5.3 Relasi Model & Potensi N+1 / Beban Query

- Sebagian besar query sudah memakai `with('table','items')`:
  - Kasir, Waiter, Dapur, Owner, Admin.
- Owner dashboard:
  - Menghitung banyak laporan (daily/weekly/monthly/yearly) dengan
    query mirip berulang ke `Order` dan `OrderItem`.
  - Bukan N+1, tetapi beban query besar per load dashboard.

- SSE `DashboardController::streamOrders`:
  - Loop tanpa batas waktu:
    - Tiap 1 detik query order baru (`id > lastId`) dan kirim event.
  - Potensi:
    - Long-running response tanpa mekanisme break/timeout.
    - Beban server dan DB jika banyak client membuka dashboard real-time.

### 5.4 Penggunaan Static Method Berlebihan

- `NotificationService`, `GoogleSheetService`, `OrderCodeGenerator`
  semuanya memakai static methods.
- Implikasi:
  - Sulit untuk di-mock atau diganti implementasinya di testing.
  - Tidak fleksibel untuk dependency injection (mis: provider WA berbeda).

### 5.5 Hardcoded Value vs Konfigurasi

- Domain/subdomain:
  - `SubdomainHelper` menggunakan `*.smartorder.local:8080`.
  - `RedirectAuthenticatedToSubdomain` men-set domain produksi ke `smartorder.dev`.
  - `DashboardController` memakai `env('SMARTORDER_DOMAIN', 'smartorder.local')`.
  - Ada tiga sumber kebenaran domain → rentan inkonsistensi.

- Timezone:
  - Struk digital mengunci timezone ke `Asia/Makassar` dan label `WITA`.
  - Tidak configurable per warung.

- Admin fee:
  - `admin_fee` fix 1% untuk semua non-kasir.
  - Harusnya configurable di level warung atau global config.

- System clock settings:
  - Field `enable_system_clock`, `system_clock_format` ada di DB, tetapi:
    - Belum dibaca/digunakan di view atau logic.

### 5.6 Route ke View yang Tidak Ada

- Fallback di `/dashboard`:
  - Role default mengembalikan `view('dashboard.default')`.
  - Tidak ada file `resources/views/dashboard/default.blade.php`.
  - Potensi 500 error jika ada role baru / role tidak dikenali.

### 5.7 Perbedaan Definisi Role

- Enum role di migration berisi `kitchen`.
- Routing & middleware di seluruh aplikasi menggunakan `dapur`.
- Jika ada user dengan role `kitchen`:
  - Mereka tidak akan masuk ke grup route `role:dapur,admin`.


## 6. Fitur Bernilai Rendah (Low ROI)

### 6.1 Dashboard Owner Legacy

- `dashboard/owner.blade.php` dan `dashboard/owner_new.blade.php`:
  - Besar dan kompleks, tapi tidak terhubung route.
  - Menambah kebingungan bagi developer baru dan beban pemeliharaan.

### 6.2 View Customer Menu di Folder Dashboard

- `dashboard/customer_menu.blade.php`:
  - Konsepnya duplikat dengan `customer/menu.blade.php`.
  - Tidak digunakan, sebaiknya dihapus atau di-archive.

### 6.3 SettingsController & /admin/settings

- Route:
  - `PUT /admin/settings` → `SettingsController@update`.
- Dari pencarian:
  - Tidak terlihat view yang aktif menggunakan route ini (perlu verifikasi manual).
  - Jika benar tidak digunakan, integrasi ini termasuk low ROI sampai ada UI admin settings.

### 6.4 Notifikasi Dummy qris_dummy & gateway_dummy

- Saat ini:
  - Digunakan tiap kali order dengan `payment_method=qris` atau `gateway`.
  - Mengirim pesan dummy “BERHASIL (simulasi)” ke customer.
- Untuk produksi nyata:
  - Berpotensi menyesatkan jika nanti benar-benar terhubung ke payment gateway.
  - Lebih baik dikontrol:
    - Hanya aktif jika `APP_ENV != production` atau ada flag `PAYMENT_SIMULATION=true`.

### 6.5 System Clock & Beberapa Field Subscription

- `enable_system_clock`, `system_clock_format`:
  - Ada di DB & model Warung.
  - Belum digunakan di UI atau logic.

- `subscription_tier`, `monthly_price`, `subscription_expires_at`:
  - Dipakai di `RegisterController` untuk menentukan tier & harga.
  - Belum ada enforcement:
    - Warung expired tetap dapat beroperasi penuh.


## 7. Rekomendasi Teknis

### 7.1 Yang Sebaiknya Dihapus / Di-Archive

1. **View Tidak Terpakai**
   - Pindahkan ke folder `legacy/` atau hapus jika tidak dibutuhkan:
     - `dashboard/owner.blade.php`
     - `dashboard/owner_new.blade.php`
     - `dashboard/customer_menu.blade.php`
     - `resources/views/dashboard.blade.php`
     - `resources/views/landing.blade.php` (jika tidak digunakan).

2. **Migration Kosong**
   - Selesaikan atau hapus `2026_01_17_091306_add_require_owner_auth_for_discount_to_warungs_table.php`:
     - Isi kolom yang benar-benar digunakan (`max_discount_percent`, `max_discount_amount`, `require_owner_auth_for_discount`).
     - Atau buat migrasi baru yang lengkap dan buang migrasi kosong.

3. **Role kitchen (Jika Tidak Dipakai)**
   - Jika seluruh sistem memakai istilah `dapur`:
     - Hapus role `kitchen` dari enum dan logika terkait.

### 7.2 Refactor yang Disarankan

1. **Sentralisasi Konfigurasi Domain/Subdomain**
   - Buat helper / service tunggal untuk membangun:
     - URL customer menu.
     - URL status order.
     - URL dashboard per role.
   - Gunakan `env('SMARTORDER_DOMAIN')` + `config('app.url')` sebagai sumber utama.

2. **Kurangi Full Page Reload, Manfaatkan SSE/AJAX**
   - Customer status:
     - Hapus `location.reload()`; gunakan SSE `streamStatus` sepenuhnya.
   - Dashboard kasir/dapur/waiter:
     - Manfaatkan `DashboardController::streamOrders` untuk update real time.
     - Alternatif minimal:
       - Ganti full reload dengan fetch JSON + update DOM.

3. **Perkuat Validasi & Normalisasi Nomor WhatsApp**

   - Tambah rule:
     - Hanya digit, panjang 10–15 karakter.
     - Normalisasi ke format konsisten (mis: `628xx...`).
   - Integrasikan flag `warung->whatsapp_notification`:
     - Skip pengiriman WA jika warung menonaktifkan notifikasi.

4. **Lengkapi Schema Diskon Warung**

   - Pastikan kolom:
     - `max_discount_percent`
     - `max_discount_amount`
     - `require_owner_auth_for_discount`
     benar-benar ada di database dan dikonfigurasikan via owner/admin UI.

5. **Optimasi Query Laporan Owner**

   - Abstraksikan query per periode ke service tersendiri (`OrderReportService`):
     - Mengurangi duplikasi kode.
     - Memudahkan tuning index DB.
   - Pertimbangkan caching ringkasan (misal: cache 1–5 menit) untuk owner dashboard.

6. **Refactor Service Static ke Instance-Based**

   - `NotificationService`, `GoogleSheetService`, `OrderCodeGenerator`:
     - Daftar sebagai service di container.
     - Controller menerima melalui dependency injection.
   - Manfaat:
     - Lebih mudah di-test.
     - Memungkinkan konfigurasi dinamis (multi provider).

### 7.3 Penambahan untuk Stabilitas & Profesionalisme

1. **Fallback View untuk /dashboard**
   - Tambah `resources/views/dashboard/default.blade.php`:
     - Menjelaskan bahwa role tidak dikenali / belum punya dashboard khusus.
     - Menyertakan opsi logout dan kontak admin.

2. **Health Check & Logging Konsisten**
   - Endpoint `/health`:
     - Cek koneksi database.
     - Cek konfigurasi wajib (FONNTE_TOKEN, SMARTORDER_DOMAIN).
   - Konfirmasi konfigurasi channel log:
     - Log khusus notifikasi WA dan sinkronisasi Sheets.

3. **Penegakan Subscription**
   - Jika `subscription_expires_at` lewat:
     - Beri peringatan jelas di owner dashboard.
     - Pilihan kebijakan:
       - Batasi akses staff atau nonaktifkan order baru.

4. **Guard untuk SSE Long-Running**
   - Tambah:
     - Batas waktu (mis: maksimal 60 detik per koneksi).
     - Cek `connection_aborted()` untuk keluar dari loop.

5. **Penguatan Flow Closing Harian Kasir**
   - Tambah:
     - Modal ringkasan sebelum closing.
     - Tombol export "Laporan Closing Harian" dari layar kasir.

6. **Konsistensi Penamaan Role & Status**

   - Dokumentasikan dengan jelas:
     - Role resmi: `admin`, `owner`, `kasir`, `waiter`, `dapur`.
     - Flow status order:
       - `pending → verified → preparing → ready → served → paid`, plus `cancelled`.
   - Pastikan semua dashboard dan notifikasi mengikuti terminologi yang sama.

