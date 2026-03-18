# Spesifikasi Desain Halaman — Mode PWA Full-screen Terminal

## Global Styles (berlaku untuk semua halaman terminal)
- Desktop-first: layout optimal untuk 1366×768 dan 1920×1080; tetap usable di tablet 10–12".
- Layout system: CSS Grid untuk pembagian panel (kiri daftar, kanan detail), Flexbox untuk bar tombol dan kartu.
- Design tokens:
  - Background: #0B1220 (dark) dengan panel #111A2E
  - Accent utama: #22C55E (success/ready), Accent kedua: #F59E0B (warning/cooking), Danger: #EF4444
  - Typography: 16px base; heading 20–28px; angka qty/total 24–32px
  - Button: tinggi 48–56px, radius 12px; hover + focus ring jelas; area sentuh min 44px
  - Status chip: bentuk pill dengan warna per stage
- Interaksi & aksesibilitas:
  - Navigasi keyboard tetap berfungsi (tab order rapi), namun UI mengutamakan klik/touch.
  - Konfirmasi aksi “mengunci alur” (submit to cashier, approve+pay, send to kitchen, mark done).
- Full-screen behavior:
  - Tombol “Masuk Full-screen” + fallback bila browser menolak.
  - Tombol “Keluar Terminal” tersembunyi di menu (mencegah salah klik), tapi tetap ada.

---

## 1) Halaman: Pilih Terminal (Full-screen Mode)
### Meta Information
- Title: "Terminal — Pilih Peran"
- Description: "Masuk mode full-screen untuk waiter, kasir, atau kitchen."
- Open Graph: og:title sama dengan title; og:type="website"

### Page Structure
- Single column (max-width 920px) di tengah, dengan kartu besar untuk 3 peran.

### Sections & Components
1. Header
   - Brand/nama warung (existing) + indikator user aktif.
2. Kartu Pilih Peran (3 kartu)
   - Waiter / Kasir / Kitchen
   - Tiap kartu menampilkan ringkas “tugas & langkah gatekeeper”.
3. Tombol Aksi
   - Primary: “Masuk Full-screen” (munculkan dialog browser)
   - Secondary: “Ganti User” (logout cepat / kembali ke login existing)
4. Panel Info Gatekeeper
   - Diagram teks 3 langkah: Waiter → Kasir → Kitchen
   - Catatan: perubahan order mengikuti urutan dan status.

Responsive
- Di bawah 1024px: kartu peran menjadi stacked.

---

## 2) Halaman: Terminal Waiter
### Meta Information
- Title: "Terminal Waiter"
- Description: "Buat pesanan meja dan kirim ke kasir."

### Page Structure
- Grid 2 kolom:
  - Kiri (35%): daftar meja + pencarian
  - Kanan (65%): detail order aktif

### Sections & Components
1. Top Bar
   - Nama peran “WAITer”, user, jam, tombol “Kembali ke Pilih Terminal”.
2. Panel Meja (kiri)
   - Search box
   - List/grid meja dengan status (kosong/terisi/menunggu kasir)
   - Klik meja membuka/membuat order draft.
3. Panel Order (kanan)
   - Ringkasan meja + nomor order (jika ada)
   - Daftar item (row: nama, qty stepper +/- besar, note)
   - Tombol “Tambah Item” membuka modal/panel daftar menu (existing menu items)
4. Footer Actions (sticky)
   - “Simpan Draft”
   - Primary: “Kirim ke Kasir” (konfirmasi + setelah sukses UI terkunci sesuai stage)
5. Status Timeline
   - Chip stage saat ini + riwayat timestamp ringkas.

Responsive
- 1024px ke bawah: panel meja menjadi drawer; fokus ke order.

---

## 3) Halaman: Terminal Kasir
### Meta Information
- Title: "Terminal Kasir"
- Description: "Verifikasi pesanan, proses pembayaran, kirim ke kitchen."

### Page Structure
- Grid 2 kolom:
  - Kiri (40%): antrian order
  - Kanan (60%): detail + pembayaran

### Sections & Components
1. Top Bar
   - Role “KASIR”, user, indikator online/offline PWA.
2. Panel Antrian (kiri)
   - Tabs/filters: “Menunggu Kasir”, “Disetujui”, “Dikirim ke Kitchen”
   - Kartu order: meja, waktu, waiter, total sementara.
3. Panel Detail Order (kanan)
   - Daftar item read-only by default
   - (Opsional sesuai izin existing) tombol “Buka Koreksi” untuk edit qty/note
4. Panel Pembayaran
   - Pilih metode (button group)
   - Input nominal bayar (besar, mudah disentuh)
   - Kalkulasi kembalian (jika cash)
   - Primary: “Setujui & Tandai Lunas”
   - Setelah lunas: tombol “Kirim ke Kitchen” (atau otomatis setelah lunas, sesuai kebijakan yang dipilih)

State & Errors
- Jika order belum memenuhi syarat (mis. kosong): tampilkan banner error jelas.

---

## 4) Halaman: Terminal Kitchen
### Meta Information
- Title: "Terminal Kitchen"
- Description: "Terima tiket produksi dari kasir dan kelola status masak."

### Page Structure
- Dashboard 3 kolom (desktop):
  - Kolom 1: “Baru”
  - Kolom 2: “Dimasak”
  - Kolom 3: “Siap/Diambil”

### Sections & Components
1. Top Bar
   - Role “KITCHEN”, user, toggle suara notifikasi (opsional), refresh.
2. Kanban Tickets
   - Ticket card: meja, waktu masuk, daftar item ringkas, catatan penting (highlight)
   - Klik ticket membuka drawer detail (item lengkap)
3. Aksi Status Cepat
   - Di kartu: tombol besar “Mulai Masak”, “Tandai Siap”, “Selesai”
   - Setelah “Selesai”, tiket pindah ke arsip/hilang dari papan.
4. Filter
   - Toggle “Tampilkan hanya prioritas/baru” (berbasis status saja).

Responsive
- <1280px: menjadi 1–2 kolom; status dipilih via tabs.
