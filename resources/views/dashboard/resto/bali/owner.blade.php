@extends('layouts.dashboard')

@section('title', 'Majar Signature | Owner Dashboard')
@section('header_title', 'Majar Signature | Owner Dashboard')
@section('header_subtitle', 'Kontrol penuh operasional restoran, menu, dan tim')

@section('content')
    <style>
        :root {
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --accent-orange: #FF8C00;
            --accent-yellow: #FFC107;
            --border-color: #e2e8f0;
        }
        
        body {
            background-color: var(--dashboard-bg);
            color: #333;
            font-family: 'Inter', sans-serif;
        }

        .dashboard-main, .dashboard-content, .dashboard-shell {
            background-color: var(--dashboard-bg);
        }

        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1.25rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-left: 5px solid var(--accent-orange);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-orange), var(--accent-yellow));
            border: none;
            color: #000;
            font-weight: 700;
        }
        .card-header {
            background-color: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-body {
            padding: 1.5rem;
            color: var(--text-secondary);
        }

        /* Stat Cards */
        .stat-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent-blue);
        }
        .stat-card-title {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .stat-card-value {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.8rem;
            line-height: 1.2;
        }
        .stat-card-footer {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            align-items: center;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            padding: 1rem 1.5rem;
            background: transparent;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-tabs .nav-link:hover {
            color: var(--text-primary);
            border: none;
        }
        .nav-tabs .nav-link.active {
            color: var(--accent-blue);
            background: transparent;
            border-bottom: 2px solid var(--accent-blue);
            font-weight: 600;
        }

        /* Forms & Inputs */
        .form-control, .form-select, .input-group-text {
            background-color: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.6rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(255,255,255,0.05);
            color: var(--text-primary);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 2px rgba(49, 130, 206, 0.2);
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
        }
        .btn-primary:hover {
            background-color: #2b6cb0;
            border-color: #2b6cb0;
        }
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        .btn-outline:hover {
            background-color: var(--border-color);
            color: var(--text-primary);
        }

        /* Tables */
        .table {
            color: var(--text-secondary);
            width: 100%;
            margin-bottom: 1rem;
        }
        .table th, .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        .table th {
            color: var(--text-primary);
            font-weight: 600;
            border-top: none;
        }
        
        /* Legacy Owner Support */
        .owner-card-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        /* Tier Locking Styles */
        .locked-feature {
            position: relative;
            overflow: hidden;
        }
        .locked-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: inherit;
            color: #fff;
            text-align: center;
            padding: 1rem;
        }
        .locked-overlay i { margin-bottom: 10px; color: #fb7185; }
        .locked-overlay h3 { margin: 0 0 0.5rem 0; font-weight: bold; font-size: 1.2rem; }
        .locked-overlay p { font-size: 0.9rem; opacity: 0.9; }

        /* QRIS Card */
        .qris-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            gap: 2rem;
            align-items: center;
            justify-content: space-between;
        }
        .qris-main { flex: 1; }
        .qris-label { font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem; }
        .qris-input-row { display: flex; gap: 0.5rem; }
        .qris-input { flex: 1; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); color: var(--text-primary); padding: 0.5rem 1rem; border-radius: 6px; }
        .qris-qr { background: white; padding: 0.5rem; border-radius: 8px; }

        /* Owner Modal Styles (Custom to avoid Bootstrap conflicts) */
        .owner-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }
        .owner-modal-panel {
            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            color: var(--text-secondary);
            max-height: 90vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .owner-modal-panel .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-primary);
        }
        .owner-modal-panel .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }
        .owner-modal-panel .btn-close {
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23A0AEC0'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            opacity: 0.8;
            filter: none;
        }
        .owner-modal-panel .btn-close:hover {
            opacity: 1;
        }
        .owner-modal-panel .modal-body {
            padding: 1.5rem;
            flex: 1;
            overflow-y: auto;
        }
        .owner-modal-panel .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
    </style>

    <div class="page-heading">
        <div class="page-title-block">
            <div class="page-title">Ringkasan Hari Ini</div>
            <div class="page-subtitle">
                Penjualan, antrian, dan performa menu untuk {{ $warung->name }}
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="ownerTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab" aria-controls="summary" aria-selected="true">Ringkasan & Laporan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu" type="button" role="tab" aria-controls="menu" aria-selected="false">Menu Restoran</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff" type="button" role="tab" aria-controls="staff" aria-selected="false">Tim Karyawan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">Antrian & Pesanan</button>
        </li>
    </ul>

    <div class="tab-content" id="ownerTabsContent">
        <!-- Tab 1: Ringkasan & Laporan -->
        <div class="tab-pane fade show active" id="summary" role="tabpanel" aria-labelledby="summary-tab">
            
            <div class="d-flex justify-content-end mb-3">
                <div class="tab-nav">
                    <button type="button"
                            class="tab-button {{ $period === 'daily' ? 'tab-button-active' : '' }}"
                            onclick="document.getElementById('owner-period-select').value='daily'; document.getElementById('owner-period-select').dispatchEvent(new Event('change'));">
                        Hari Ini
                    </button>
                    <button type="button"
                            class="tab-button {{ $period === 'weekly' ? 'tab-button-active' : '' }}"
                            onclick="document.getElementById('owner-period-select').value='weekly'; document.getElementById('owner-period-select').dispatchEvent(new Event('change'));">
                        Mingguan
                    </button>
                    <button type="button"
                            class="tab-button {{ $period === 'monthly' ? 'tab-button-active' : '' }}"
                            onclick="document.getElementById('owner-period-select').value='monthly'; document.getElementById('owner-period-select').dispatchEvent(new Event('change'));">
                        Bulanan
                    </button>
                    <button type="button"
                            class="tab-button {{ $period === 'yearly' ? 'tab-button-active' : '' }}"
                            onclick="document.getElementById('owner-period-select').value='yearly'; document.getElementById('owner-period-select').dispatchEvent(new Event('change'));">
                        Tahunan
                    </button>
                </div>
            </div>



            <div class="grid grid-4" style="margin-top: 1rem;">
                <div class="stat-card">
                    <div class="stat-card-title">Penjualan Hari Ini</div>
                    <div class="stat-card-value">
                        Rp {{ number_format($dailyReport['revenue'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="stat-card-footer">
                        {{ $dailyReport['orders'] ?? 0 }} pesanan berhasil dibayar
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Total Pesanan Hari Ini</div>
                    <div class="stat-card-value">
                        {{ $dailyReport['orders'] ?? 0 }}
                    </div>
                    <div class="stat-card-footer">
                        Termasuk dine-in dan takeaway
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Menu Terlaris</div>
                    <div class="stat-card-value">
                        @if(($dailyReport['best_seller'] ?? collect())->first())
                            {{ ($dailyReport['best_seller']->first())->menu_name }}
                        @else
                            Belum ada
                        @endif
                    </div>
                    <div class="stat-card-footer">
                        Top 3 menu ditampilkan di bawah
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Rata-rata Waktu Selesai</div>
                    <div class="stat-card-value">
                        {{ $avgPrepTime }} menit
                    </div>
                    <div class="stat-card-footer">
                        Dari pesanan masuk sampai dibayar
                    </div>
                </div>
            </div>

            <section class="surface section" style="margin-top: 1rem;">
                <div class="section-header">
                    <div>
                        <div class="section-title">Aksi Cepat</div>
                        <div class="section-subtitle">
                            Kelola menu, stok, laporan, dan pengaturan restoran dari satu tempat.
                        </div>
                    </div>
                </div>
                <div class="quick-actions">
                    <button type="button" class="btn btn-outline btn-sm" onclick="refreshAllStock()">
                        Refresh Stok Semua Menu
                    </button>
                    <a href="{{ route('reports.export', ['period' => 'daily-detail']) }}" class="btn btn-outline btn-sm">
                        Export CSV Pesanan Hari Ini
                    </a>
                    <button type="button" class="btn btn-outline btn-sm" onclick="syncToGoogleSheet(this)">
                        Sync Google Sheet
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="showOwnerModal('restaurantSettingsModal')">
                        Edit Info Restoran
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="showOwnerModal('profileSettingsModal')">
                        Edit Profil Anda
                    </button>
                </div>
            </section>

            <section style="margin-top: 1rem;" class="surface section">
                <div class="section-header">
                    <div>
                        <div class="section-title">Laporan Penjualan</div>
                        <div class="section-subtitle">
                            Ringkasan performa berdasarkan periode yang dipilih.
                        </div>
                    </div>
                    <div>
                        <select id="owner-period-select"
                                class="form-select"
                                data-base-url="{{ route('dashboard.owner') }}">
                            <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Harian</option>
                            <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                            <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-3">
                    <div class="stat-card">
                        <div class="stat-card-title">Total Pesanan</div>
                        <div class="stat-card-value">
                            @php
                                $reportData = $period === 'daily'
                                    ? $dailyReport
                                    : ($period === 'weekly'
                                        ? $weeklyReport
                                        : ($period === 'monthly'
                                            ? $monthlyReport
                                            : $yearlyReport));
                            @endphp
                            {{ $reportData['orders'] ?? 0 }}
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-title">Total Revenue</div>
                        <div class="stat-card-value">
                            Rp {{ number_format($reportData['revenue'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="stat-card {{ !$isPro ? 'locked-feature' : '' }}">
                        @if(!$isPro)
                        <div class="locked-overlay">
                            <i class="fas fa-lock"></i>
                            <h3>Pro Feature</h3>
                            <p>Upgrade untuk melihat analitik detail</p>
                        </div>
                        @endif
                        <div class="stat-card-title">Menu Terlaris Periode Ini</div>
                        <div class="stat-card-value">
                            @php
                                $bestSeller = $period === 'daily'
                                    ? $dailyReport['best_seller']
                                    : ($period === 'weekly'
                                        ? $weeklyReport['best_seller']
                                        : ($period === 'monthly'
                                            ? $monthlyReport['best_seller']
                                            : $yearlyReport['best_seller']));
                            @endphp
                            @if(($bestSeller ?? collect())->first())
                                {{ $bestSeller->first()->menu_name }}
                            @else
                                Belum ada data
                            @endif
                        </div>
                    </div>
                </div>
            </section>
            @php
                $chartData = ($chartItems ?? collect())->map(function ($item) {
                    return [
                        'label' => $item->menu_name,
                        'value' => $item->total_qty,
                    ];
                })->values();
            @endphp
            @if(($chartItems ?? collect())->count())
                <section style="margin-top: 1rem;" class="surface section">
                    <div class="section-header">
                        <div>
                            <div class="section-title">Distribusi Penjualan Menu</div>
                            <div class="section-subtitle">
                                Diagram lingkaran berdasarkan jumlah unit terjual pada periode terpilih.
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-2" style="align-items: center;">
                        <div style="padding: 0.5rem;">
                            <canvas id="owner-sales-pie" width="260" height="260"></canvas>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div>Legenda Menu</div>
                            </div>
                            <div class="card-body">
                                <div id="owner-sales-pie-legend"></div>
                            </div>
                        </div>
                    </div>
                </section>
            @elseif(!$isPro)
                <section style="margin-top: 1rem;" class="surface section locked-feature">
                    <div class="locked-overlay">
                        <i class="fas fa-lock fa-2x"></i>
                        <h3>Analitik Premium</h3>
                        <p>Upgrade ke paket Professional untuk melihat Distribusi Penjualan Menu.</p>
                    </div>
                    <div class="section-header" style="opacity: 0.3; filter: blur(2px);">
                        <div>
                            <div class="section-title">Distribusi Penjualan Menu</div>
                            <div class="section-subtitle">
                                Diagram lingkaran berdasarkan jumlah unit terjual pada periode terpilih.
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-2" style="align-items: center; opacity: 0.3; filter: blur(2px);">
                        <div style="padding: 0.5rem; height: 260px; background: #2d3748; border-radius: 50%; width: 260px; margin: auto;"></div>
                        <div class="card">
                            <div class="card-header"><div>Legenda Menu</div></div>
                            <div class="card-body"><div style="height: 100px;"></div></div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="surface section" style="margin-top: 1rem;">
                <div class="section-header">
                    <div>
                        <div class="section-title">Link Pemesanan & QRIS</div>
                        <div class="section-subtitle">
                            Bagikan link ini ke pelanggan, atau cetak QR untuk ditempel di meja.
                        </div>
                    </div>
                </div>
                <div class="qris-card">
                    <div class="qris-main">
                        <div class="qris-label">Link Customer</div>
                        <div class="qris-input-row">
                            <input id="customer-link-input"
                                   type="text"
                                   class="qris-input"
                                   value="{{ $customerUrl }}"
                                   readonly>
                            <button type="button"
                                    class="btn btn-outline btn-sm"
                                    data-copy-target="#customer-link-input">
                                Salin Link
                            </button>
                            <a href="{{ $customerUrl }}" target="_blank" class="btn btn-primary btn-sm">
                                Buka Halaman Customer
                            </a>
                        </div>
                    </div>
                    <div class="qris-qr">
                        <div id="owner-qr-fallback" class="skeleton" style="width:72px;height:72px;border-radius:0.75rem;"></div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Tab 2: Menu Restoran -->
        <div class="tab-pane fade" id="menu" role="tabpanel" aria-labelledby="menu-tab">
            <div class="card owner-card-dark">
                <div class="card-header">
                    <div>Menu Restoran</div>
                    <div class="pill pill-muted">
                        {{ $menuItems->count() }} item
                    </div>
                    <div class="owner-card-actions">
                        <button type="button" class="btn btn-primary btn-sm" onclick="showOwnerModal('addMenuModal')">
                            Tambah Menu
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="owner-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Promo</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($menuItems as $menu)
                                    <tr>
                                        <td class="owner-table-text">{{ $menu->name }}</td>
                                        <td class="owner-table-text">{{ ucfirst($menu->category) }}</td>
                                        <td class="owner-table-text">Rp {{ number_format($menu->price, 0, ',', '.') }}</td>
                                        <td>
                                            @if($menu->promo_aktif && $menu->harga_promo > 0)
                                                <span class="badge badge-success">Rp {{ number_format($menu->harga_promo, 0, ',', '.') }}</span>
                                            @else
                                                <span class="owner-table-text">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $menu->active ? 'badge-success' : 'badge-danger' }} owner-table-text">
                                                {{ $menu->active ? 'Active' : 'Out of Stock' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="owner-action-group">
                                                <button type="button" class="btn btn-sm btn-outline-light owner-action-btn" onclick="editMenu({{ $menu->id }})">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger owner-action-btn" onclick="deleteMenu({{ $menu->id }})">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center owner-table-text">Belum ada menu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Tim Karyawan -->
        <div class="tab-pane fade" id="staff" role="tabpanel" aria-labelledby="staff-tab">
            <div class="card owner-card-dark">
                <div class="card-header">
                    <div>Tim Karyawan</div>
                    <div class="pill pill-muted">
                        {{ $staff->count() }} orang
                    </div>
                    <div class="owner-card-actions">
                        <button type="button" class="btn btn-primary btn-sm" onclick="showOwnerModal('addStaffModal')">
                            Tambah Karyawan
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="owner-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>WhatsApp</th>
                                    <th>Shift Hari Ini</th>
                                    <th>Pesanan Dikerjakan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staff as $user)
                                    <tr>
                                        <td class="owner-table-text">{{ $user->name }}</td>
                                        <td class="owner-table-text">{{ ucfirst($user->role) }}</td>
                                        <td class="owner-table-text">{{ $user->whatsapp ?: '-' }}</td>
                                        <td class="owner-table-text">
                                            @php
                                                $shift = $staffShiftSummary[$user->id] ?? null;
                                            @endphp
                                            @if($shift && $shift['start'])
                                                {{ $shift['start']->format('H:i') }} -
                                                {{ $shift['end'] ? $shift['end']->format('H:i') : 'Sekarang' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="owner-table-text">
                                            @php
                                                $summary = $staffOrderSummary[$user->id] ?? null;
                                            @endphp
                                            @if($summary && (count($summary['kasir_codes']) || count($summary['waiter_codes']) || count($summary['kitchen_codes'])))
                                                @if(count($summary['kasir_codes']))
                                                    Kasir: {{ implode(', ', array_slice($summary['kasir_codes'], 0, 5)) }}@if(count($summary['kasir_codes']) > 5) ... @endif
                                                @endif
                                                @if(count($summary['waiter_codes']))
                                                    @if(count($summary['kasir_codes']))<br>@endif
                                                    Waiter: {{ implode(', ', array_slice($summary['waiter_codes'], 0, 5)) }}@if(count($summary['waiter_codes']) > 5) ... @endif
                                                @endif
                                                @if(count($summary['kitchen_codes']))
                                                    @if(count($summary['kasir_codes']) || count($summary['waiter_codes']))<br>@endif
                                                    Dapur: {{ implode(', ', array_slice($summary['kitchen_codes'], 0, 5)) }}@if(count($summary['kitchen_codes']) > 5) ... @endif
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <div class="owner-action-group">
                                                <button type="button" class="btn btn-sm btn-outline-light owner-action-btn" onclick="editStaff({{ $user->id }})">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger owner-action-btn" onclick="deleteStaff({{ $user->id }})">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">Belum ada karyawan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: Antrian & Pesanan -->
        <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
            <section class="surface section">
                <div class="section-header">
                    <div>
                        <div class="section-title">Status Pesanan Real-time</div>
                        <div class="section-subtitle">
                            Pantau pergerakan pesanan dari verifikasi, dimasak, diantar, hingga selesai.
                        </div>
                    </div>
                </div>
                <div class="live-board-grid">
                    <div class="live-column">
                        <div class="live-header">
                            <div class="live-header-title"><span>Menunggu Verifikasi</span></div>
                            <div class="live-header-count">{{ count($liveBoard['pending'] ?? []) }}</div>
                        </div>
                        <div class="live-body">
                            @forelse($liveBoard['pending'] ?? [] as $order)
                                <div class="live-card">
                                    <div class="live-card-title">#{{ $order->code }} '{{ $order->customer_name ?? 'Pelanggan' }}'</div>
                                    <div class="live-card-meta">Meja {{ $order->table->name ?? 'Takeaway' }} • Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                </div>
                            @empty
                                <div class="live-card skeleton"></div>
                            @endforelse
                        </div>
                    </div>
                    <div class="live-column">
                        <div class="live-header">
                            <div class="live-header-title"><span>Dibayar</span></div>
                            <div class="live-header-count">{{ count($liveBoard['verified'] ?? []) }}</div>
                        </div>
                        <div class="live-body">
                            @forelse($liveBoard['verified'] ?? [] as $order)
                                <div class="live-card">
                                    <div class="live-card-title">#{{ $order->code }} '{{ $order->customer_name ?? 'Pelanggan' }}'</div>
                                    <div class="live-card-meta">Meja {{ $order->table->name ?? 'Takeaway' }} • Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                </div>
                            @empty
                                <div class="live-card skeleton"></div>
                            @endforelse
                        </div>
                    </div>
                    <div class="live-column">
                        <div class="live-header">
                            <div class="live-header-title"><span>Dimasak</span></div>
                            <div class="live-header-count">{{ count($liveBoard['preparing'] ?? []) }}</div>
                        </div>
                        <div class="live-body">
                            @forelse($liveBoard['preparing'] ?? [] as $order)
                                <div class="live-card">
                                    <div class="live-card-title">#{{ $order->code }} '{{ $order->customer_name ?? 'Pelanggan' }}'</div>
                                    <div class="live-card-meta">Meja {{ $order->table->name ?? 'Takeaway' }} • Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                </div>
                            @empty
                                <div class="live-card skeleton"></div>
                            @endforelse
                        </div>
                    </div>
                    <div class="live-column">
                        <div class="live-header">
                            <div class="live-header-title"><span>Diantar</span></div>
                            <div class="live-header-count">{{ count($liveBoard['ready'] ?? []) }}</div>
                        </div>
                        <div class="live-body">
                            @forelse($liveBoard['ready'] ?? [] as $order)
                                <div class="live-card">
                                    <div class="live-card-title">#{{ $order->code }} '{{ $order->customer_name ?? 'Pelanggan' }}'</div>
                                    <div class="live-card-meta">Meja {{ $order->table->name ?? 'Takeaway' }} • Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                </div>
                            @empty
                                <div class="live-card skeleton"></div>
                            @endforelse
                        </div>
                    </div>
                    <div class="live-column">
                        <div class="live-header">
                            <div class="live-header-title"><span>Pesanan Selesai</span></div>
                            <div class="live-header-count">{{ count($liveBoard['paid'] ?? []) }}</div>
                        </div>
                        <div class="live-body">
                            @forelse($liveBoard['paid'] ?? [] as $order)
                                <div class="live-card">
                                    <div class="live-card-title">#{{ $order->code }} '{{ $order->customer_name ?? 'Pelanggan' }}'</div>
                                    <div class="live-card-meta">Meja {{ $order->table->name ?? 'Takeaway' }} • Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                </div>
                            @empty
                                <div class="live-card skeleton"></div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section style="margin-top: 1rem;" class="surface section">
                <div class="section-header">
                    <div>
                        <div class="section-title">Laporan Pesanan Selesai</div>
                        <div class="section-subtitle">Detail pesanan selesai hari ini dan ringkasan unit menu terjual.</div>
                    </div>
                </div>
                <div class="grid grid-2">
                    <div class="card">
                        <div class="card-header">
                            <div>Pesanan Selesai Hari Ini</div>
                            <div class="pill pill-muted">{{ $completedOrders->count() }} pesanan</div>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <table class="owner-table">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Meja</th>
                                            <th>Pelanggan</th>
                                            <th>Metode Bayar</th>
                                            <th>Total</th>
                                            <th>Item</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($completedOrders as $order)
                                            <tr>
                                                <td class="owner-table-text">#{{ $order->code }}</td>
                                                <td class="owner-table-text">{{ $order->table->name ?? 'Takeaway' }}</td>
                                                <td class="owner-table-text">{{ $order->customer_name ?? '-' }}</td>
                                                <td>
                                                    <span class="badge bg-info text-dark">{{ $order->payment_method }}</span>
                                                </td>
                                                <td class="owner-table-text">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                                <td class="owner-table-text">
                                                    @foreach($order->items as $item)
                                                        {{ $item->qty }}x {{ $item->menu_name }}<br>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6">Belum ada pesanan selesai hari ini.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div>Unit Menu Terjual</div>
                            <div class="pill pill-muted">{{ $soldItemsSummary->sum('total_qty') }} porsi</div>
                        </div>
                        <div class="card-body">
                            <div class="table-wrapper">
                                <table class="owner-table">
                                    <thead><tr><th>Menu</th><th>Terjual</th></tr></thead>
                                    <tbody>
                                        @forelse($soldItemsSummary as $item)
                                            <tr><td class="owner-table-text">{{ $item->menu_name }}</td><td class="owner-table-text">{{ $item->total_qty }}</td></tr>
                                        @empty
                                            <tr><td colspan="2">Belum ada penjualan hari ini.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

    </div>

    <div id="addMenuModal" class="owner-modal-backdrop">
        <div class="owner-modal-panel">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Menu Baru</h5>
                <button type="button" class="btn-close" onclick="hideOwnerModal('addMenuModal')"></button>
            </div>
            <form id="addMenuForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category" required>
                            <option value="makanan">Makanan</option>
                            <option value="minuman">Minuman</option>
                            <option value="dessert">Dessert</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" class="form-control" name="price" min="1000" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Harga Promo</label>
                                <input type="number" class="form-control" name="harga_promo" min="0" placeholder="Opsional">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status Promo</label>
                                <select class="form-select" name="promo_aktif">
                                    <option value="0">Tidak Aktif</option>
                                    <option value="1">Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Menu</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <input type="hidden" name="warung_id" value="{{ $warung->id }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideOwnerModal('addMenuModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="restaurantSettingsModal" class="owner-modal-backdrop" aria-hidden="true">
        <div class="owner-modal-panel">
            <div class="modal-header">
                <h5 class="modal-title">Edit Info Restoran</h5>
                <button type="button" class="btn-close" onclick="hideOwnerModal('restaurantSettingsModal')"></button>
            </div>
            <form id="restaurantSettingsForm" enctype="multipart/form-data">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Logo Restoran</label>
                        @if($warung->logo)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $warung->logo) }}" alt="Logo" style="max-height: 80px; border-radius: 8px;">
                            </div>
                        @endif
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <div class="form-text">Format: JPG, PNG. Maks 2MB.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Restoran</label>
                        <input type="text" class="form-control" name="name" value="{{ $warung->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2">{{ $warung->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="address" rows="2">{{ $warung->address }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jam Operasional</label>
                        <input type="text" class="form-control" name="opening_hours" value="{{ $warung->opening_hours }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Kontak</label>
                        <input type="email" class="form-control" name="contact_email" value="{{ $warung->contact_email }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-control" name="phone" value="{{ $warung->phone }}">
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Integrasi Google Sheets</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="google_sheets_enabled" id="googleSheetsEnabled" value="1" {{ $warung->google_sheets_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="googleSheetsEnabled">
                                Aktifkan sinkronisasi otomatis ke Google Sheets
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Spreadsheet ID</label>
                        <input type="text" class="form-control" name="google_sheets_spreadsheet_id" value="{{ $warung->google_sheets_spreadsheet_id }}" placeholder="1AbCdEfG...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Sheet</label>
                        <input type="text" class="form-control" name="google_sheets_sheet_name" value="{{ $warung->google_sheets_sheet_name ?? 'Transaksi' }}" placeholder="Transaksi">
                    </div>
                    @if($warung->google_sheets_last_synced_at)
                        <div class="mb-3">
                            <small class="text-success">
                                Terakhir sinkronisasi: {{ $warung->google_sheets_last_synced_at->format('d M Y H:i') }}
                            </small>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideOwnerModal('restaurantSettingsModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editMenuModal" class="owner-modal-backdrop" aria-hidden="true">
        <div class="owner-modal-panel">
            <div class="modal-header">
                <h5 class="modal-title">Edit Menu</h5>
                <button type="button" class="btn-close" onclick="hideOwnerModal('editMenuModal')"></button>
            </div>
            <form id="editMenuForm" enctype="multipart/form-data">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="menu_id" id="edit_menu_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" name="name" id="edit_menu_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" id="edit_menu_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category" id="edit_menu_category" required>
                            <option value="makanan">Makanan</option>
                            <option value="minuman">Minuman</option>
                            <option value="dessert">Dessert</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" class="form-control" name="price" id="edit_menu_price" min="1000" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Harga Promo</label>
                                <input type="number" class="form-control" name="harga_promo" id="edit_menu_harga_promo" min="0" placeholder="Opsional">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status Promo</label>
                                <select class="form-select" name="promo_aktif" id="edit_menu_promo_aktif">
                                    <option value="0">Tidak Aktif</option>
                                    <option value="1">Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gambar Menu</label>
                        <div id="edit_menu_image_preview" class="mb-2"></div>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="active" id="edit_menu_active">
                            <option value="1">Active</option>
                            <option value="0">Out of Stock</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideOwnerModal('editMenuModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="profileSettingsModal" class="owner-modal-backdrop" aria-hidden="true">
        <div class="owner-modal-panel">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profil</h5>
                <button type="button" class="btn-close" onclick="hideOwnerModal('profileSettingsModal')"></button>
            </div>
            <form id="profileSettingsForm">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" name="name" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ auth()->user()->email }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideOwnerModal('profileSettingsModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addStaffModal" class="owner-modal-backdrop" aria-hidden="true">
        <div class="owner-modal-panel">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Karyawan</h5>
                <button type="button" class="btn-close" onclick="hideOwnerModal('addStaffModal')"></button>
            </div>
            <form id="addStaffForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Karyawan</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Posisi</label>
                        <select class="form-select" name="role" required>
                            <option value="kasir">Kasir</option>
                            <option value="waiter">Waiter</option>
                            <option value="dapur">Kitchen</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor WhatsApp</label>
                        <input type="text" class="form-control" name="whatsapp" placeholder="6281234567890">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="hideOwnerModal('addStaffModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var qrFallback = document.getElementById('owner-qr-fallback');
            if (qrFallback && typeof QRCode !== 'undefined') {
                var qrContainer = document.createElement('div');
                qrFallback.parentNode.replaceChild(qrContainer, qrFallback);
                new QRCode(qrContainer, {
                    text: '{{ $customerUrl }}',
                    width: 72,
                    height: 72
                });
            }

            var addMenuForm = document.getElementById('addMenuForm');
            if (addMenuForm) {
                addMenuForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(addMenuForm);
                    fetch('/menu-items', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                alert('Menu berhasil ditambahkan');
                                hideOwnerModal('addMenuModal');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal menambahkan menu'));
                            }
                        })
                        .catch(function () {
                            alert('Gagal menambahkan menu');
                        });
                });
            }

            var editMenuForm = document.getElementById('editMenuForm');
            if (editMenuForm) {
                editMenuForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(editMenuForm);
                    var menuId = document.getElementById('edit_menu_id').value;
                    fetch('/menu-items/' + menuId, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                alert('Menu berhasil diperbarui');
                                hideOwnerModal('editMenuModal');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal memperbarui menu'));
                            }
                        })
                        .catch(function () {
                            alert('Gagal memperbarui menu');
                        });
                });
            }

            var settingsForm = document.getElementById('restaurantSettingsForm');
            if (settingsForm) {
                settingsForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(settingsForm);
                    var submitBtn = settingsForm.querySelector('button[type="submit"]');
                    var originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Menyimpan...';
                    fetch('/warung/{{ $warung->id }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                alert('Pengaturan restoran berhasil disimpan');
                                hideOwnerModal('restaurantSettingsModal');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal menyimpan'));
                            }
                        })
                        .catch(function () {
                            alert('Gagal menyimpan pengaturan');
                        })
                        .finally(function () {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                });
            }

            var addStaffForm = document.getElementById('addStaffForm');
            if (addStaffForm) {
                addStaffForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(addStaffForm);
                    fetch('/admin/users', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                alert('Karyawan berhasil ditambahkan');
                                hideOwnerModal('addStaffModal');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal menambahkan karyawan'));
                            }
                        })
                        .catch(function () {
                            alert('Gagal menambahkan karyawan');
                        });
                });
            }

            window.editStaff = function (id) {
                alert('Edit karyawan #' + id);
            };

            window.deleteStaff = function (id) {
                if (confirm('Hapus karyawan ini?')) {
                    fetch('/admin/users/' + id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                alert('Karyawan berhasil dihapus');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal menghapus karyawan'));
                            }
                        })
                        .catch(function () {
                            alert('Gagal menghapus karyawan');
                        });
                }
            };

            window.deleteMenu = function (id) {
                if (confirm('Hapus menu ini?')) {
                    fetch('/menu-items/' + id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                alert('Menu berhasil dihapus!');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal menghapus menu'));
                            }
                        })
                        .catch(function () {
                            alert('Gagal menghapus menu');
                        });
                }
            };

            var profileForm = document.getElementById('profileSettingsForm');
            if (profileForm) {
                profileForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var formData = new FormData(profileForm);
                    var submitBtn = profileForm.querySelector('button[type="submit"]');
                    var originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Menyimpan...';
                    fetch('/profile', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                alert('Profil berhasil diperbarui');
                                hideOwnerModal('profileSettingsModal');
                                window.location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Gagal memperbarui profil'));
                            }
                        })
                        .catch(function () {
                            alert('Gagal memperbarui profil');
                        })
                        .finally(function () {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                });
            }
        });

        function showOwnerModal(id) {
            var modalEl = document.getElementById(id);
            if (!modalEl) {
                return;
            }
            modalEl.style.display = 'flex';
        }

        function hideOwnerModal(id) {
            var modalEl = document.getElementById(id);
            if (!modalEl) {
                return;
            }
            modalEl.style.display = 'none';
        }

        function editMenu(id) {
            fetch('/menu-items/' + id, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.success) {
                        var menu = data.data;
                        
                        // Set values safely
                        var setVal = function(id, val) {
                            var el = document.getElementById(id);
                            if (el) el.value = val;
                        };

                        setVal('edit_menu_id', menu.id);
                        setVal('edit_menu_name', menu.name);
                        setVal('edit_menu_description', menu.description || '');
                        setVal('edit_menu_category', menu.category);
                        setVal('edit_menu_price', menu.price);
                        setVal('edit_menu_harga_promo', menu.harga_promo || '');
                        setVal('edit_menu_promo_aktif', menu.promo_aktif ? '1' : '0');
                        setVal('edit_menu_active', menu.active ? '1' : '0');

                        var previewDiv = document.getElementById('edit_menu_image_preview');
                        if (previewDiv) {
                            if (menu.image) {
                                previewDiv.innerHTML = '<img src="' + menu.image + '" style="max-height:150px;border-radius:8px;">';
                            } else {
                                previewDiv.innerHTML = '<span class="text-muted">Tidak ada gambar</span>';
                            }
                        }
                        
                        showOwnerModal('editMenuModal');
                    } else {
                        alert('Error: ' + (data.message || 'Gagal memuat data menu'));
                    }
                })
                .catch(function (error) {
                    console.error('Error:', error);
                    alert('Gagal memuat data menu');
                });
        }

        function refreshAllStock() {
            if (!confirm('Aktifkan semua menu yang out of stock?')) {
                return;
            }
            fetch('/menu-items/refresh-all-stock', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.success) {
                        alert('Stok semua menu berhasil di-refresh');
                        window.location.reload();
                    }
                });
        }

        function printDailyReport() {
            window.print();
        }

        function showRestaurantSettings() {
            showOwnerModal('restaurantSettingsModal');
        }

        function showProfileSettings() {
            showOwnerModal('profileSettingsModal');
        }

        (function () {
            var canvas = document.getElementById('owner-sales-pie');
            if (!canvas || !canvas.getContext) {
                return;
            }
            var data = @json($chartData);
            if (!data.length) {
                return;
            }
            var ctx = canvas.getContext('2d');
            var total = data.reduce(function (sum, item) {
                return sum + Number(item.value || 0);
            }, 0);
            if (!total) {
                return;
            }
            var colors = [
                '#60a5fa',
                '#f97316',
                '#22c55e',
                '#a855f7',
                '#facc15',
                '#f43f5e',
                '#2dd4bf',
                '#4ade80'
            ];
            var startAngle = 0;
            var centerX = canvas.width / 2;
            var centerY = canvas.height / 2;
            var radius = Math.min(centerX, centerY) - 10;
            var legendHtml = '';
            data.forEach(function (item, index) {
                var sliceAngle = (2 * Math.PI * item.value) / total;
                var color = colors[index % colors.length];
                ctx.fillStyle = color;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                ctx.closePath();
                ctx.fill();
                startAngle += sliceAngle;
                var percentage = Math.round((item.value / total) * 100);
                legendHtml += '<div style="display:flex;align-items:center;margin-bottom:0.5rem;font-size:0.875rem;">' +
                    '<span style="display:inline-block;width:12px;height:12px;border-radius:2px;background-color:' + color + ';margin-right:0.5rem;"></span>' +
                    '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:0.5rem;">' + item.label + '</span>' +
                    '<span style="font-weight:bold;">' + percentage + '%</span>' +
                    '</div>';
            });
            var legendContainer = document.getElementById('owner-sales-pie-legend');
            if (legendContainer) {
                legendContainer.innerHTML = legendHtml;
            }
        })();
    </script>
@endsection
