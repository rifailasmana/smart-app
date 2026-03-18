# SUBDOMAIN SETUP GUIDE

## Langkah 1: Update Windows Hosts File

Anda perlu menambahkan subdomain entries ke `C:\Windows\System32\drivers\etc\hosts`

### Metode A: Menggunakan Command Prompt (Admin)
1. Buka Command Prompt sebagai Administrator
2. Copy & paste command berikut:
```
echo 127.0.0.1 smartorder.local >> C:\Windows\System32\drivers\etc\hosts
echo 127.0.0.1 bali.smartorder.local >> C:\Windows\System32\drivers\etc\hosts
echo 127.0.0.1 kitchen.smartorder.local >> C:\Windows\System32\drivers\etc\hosts
```

### Metode B: Edit Manual
1. Buka Notepad sebagai Administrator
2. Buka file: `C:\Windows\System32\drivers\etc\hosts`
3. Tambahkan baris berikut di akhir file:
```
# SmartOrder Subdomains
127.0.0.1 smartorder.local
127.0.0.1 bali.smartorder.local
127.0.0.1 kitchen.smartorder.local
```
4. Simpan file

### Metode C: Gunakan Laragon Menu
Laragon memiliki menu untuk manage hostnames di applications

---

## Langkah 2: Verify Setup

1. Buka Command Prompt
2. Jalankan: `ping smartorder.local`
3. Harusnya respond dari 127.0.0.1

---

## Langkah 3: Test Subdomain Routes

Setelah server running di port 8080:

### Landing Page (Main Domain)
```
http://smartorder.local:8080
```

### Customer Menu via Subdomain (Bali)
```
http://bali.smartorder.local:8080
```

### Customer Menu via Subdomain (Kitchen)
```
http://kitchen.smartorder.local:8080
```

### Fallback dengan Query String (tetap support)
```
http://smartorder.local:8080/menu?warung=BALI&meja=1
```

---

## Routes Structure

```
Domain Pattern: {warung_code}.smartorder.local
Warung Code: Uppercase version dari warung->code

Contoh:
- Warung dengan code "BALI" → bali.smartorder.local
- Warung dengan code "KITCHEN" → kitchen.smartorder.local
```

---

## Database Test Data

Warung yang sudah ada:
- Code: BALI (akses via bali.smartorder.local)

Untuk menambah warung baru:
```php
use App\Models\Warung;

Warung::create([
    'name' => 'Restoran Jakarta',
    'code' => 'JAKBAR', // akan accessible via jakbar.smartorder.local
    'address' => 'Jakarta Barat',
]);
```

---

## Troubleshooting

### Error: "Warung not found"
- Pastikan warung code di database sudah benar
- Subdomain harus match dengan warung->code (case-insensitive)
- Contoh: `bali.smartorder.local` akan match warung dengan code "BALI" atau "bali"

### DNS/Host tidak terdeteksi
- Restart browser setelah edit hosts file
- Atau flush DNS: `ipconfig /flushdns` (Command Prompt as Admin)

### Port issue
- Pastikan port 8080 tidak terpakai oleh aplikasi lain
- Cek dengan: `netstat -ano | findstr :8080`

---

## Fitur Subdomain Middleware

Middleware `ResolveWarungFromSubdomain` otomatis:
1. Extract subdomain dari request host
2. Cari warung berdasarkan code
3. Store warung context di request->attributes
4. OrderController automatically menggunakan warung dari context

Ini memungkinkan:
- Multi-tenant application per subdomain
- Setiap warung punya menu, orders, staff terpisah
- Customer hanya lihat menu dari warung-nya

