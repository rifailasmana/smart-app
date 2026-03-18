@extends('layouts.dashboard')

@section('title', $warung->name . ' - Admin System')
@section('header_title', $warung->name . ' | Admin System')
@section('header_subtitle', 'Kelola menu, user, laporan, dan pengaturan restoran')

@section('content')
    @php
        $chartData = ($chartItems ?? collect())->map(function ($item) {
            return [
                'label' => $item->menu_name,
                'value' => $item->total_qty,
            ];
        })->values();
    @endphp
    
    <style>
        :root {
            --bg-main: #f8f9fa;
            --bg-card: #ffffff;
            --text-primary: #1a1a1a;
            --text-secondary: #6c757d;
            --accent-blue: #FF8C00;
            --accent-green: #22c55e;
            --accent-red: #ef4444;
            --accent-orange: #FFC107;
            --border-color: #eeeeee;
        }
        
        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
        }

        .dashboard-main, .dashboard-content, .dashboard-shell {
            background-color: var(--bg-main);
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            font-weight: 600;
        }
        .text-muted {
            color: var(--text-secondary) !important;
        }

        /* Cards */
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
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
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .stat-title {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-icon {
            color: var(--accent-blue);
            width: 24px;
            height: 24px;
            opacity: 0.8;
        }
        .stat-value {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.8rem;
            line-height: 1.2;
        }

        /* Quick Actions */
        .quick-actions-container {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            align-items: center;
        }
        .btn-quick-action {
            background-color: #2D3748;
            color: #FFFFFF;
            border-radius: 9999px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-quick-action:hover {
            background-color: #4A5568;
            color: #FFFFFF;
            transform: translateY(-1px);
        }
        .btn-quick-action svg {
            width: 18px;
            height: 18px;
        }

        /* Live Board */
        .live-board-header {
            padding: 0.75rem 1rem;
            border-radius: 8px 8px 0 0;
            color: white;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }
        .lb-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
        }
        .lb-body {
            padding: 0.75rem;
            max-height: 400px;
            overflow-y: auto;
            background-color: rgba(255,255,255,0.01);
        }
        .order-card {
            background-color: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        .order-card:hover {
            background-color: rgba(255,255,255,0.05);
        }
        .order-code {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1rem;
        }
        .order-meta {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .bg-header-pending { background-color: var(--accent-red); }
        .bg-header-paid { background-color: var(--accent-blue); }
        .bg-header-preparing { background-color: var(--accent-orange); }
        .bg-header-ready { background-color: var(--accent-green); }

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

        /* Charts */
        .chart-wrapper {
            position: relative;
            margin: auto;
            height: 350px;
            width: 100%;
            max-width: 450px;
        }
        .chart-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }
        .chart-center-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .chart-center-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .legend-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
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
            background-color: #ffffff;
            color: var(--text-primary);
            border-color: rgba(255, 140, 0, 0.55);
            box-shadow: 0 0 0 0.2rem rgba(255, 140, 0, 0.12);
        }
        .form-control::placeholder {
            color: #4A5568;
        }
        .form-label {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        /* Buttons */
        .btn-primary {
            background: var(--brand-gradient);
            border: none;
            color: #000;
            font-weight: 800;
        }
        .btn-primary:hover {
            filter: brightness(1.05);
            color: #000;
        }
        .btn-success {
            background-color: var(--accent-green);
            border-color: var(--accent-green);
        }
        .btn-secondary {
            background-color: #1a1a1a;
            border-color: #1a1a1a;
        }

        /* Table Overrides */
        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-secondary);
            --bs-table-border-color: var(--border-color);
        }
        .table thead th {
            color: var(--text-primary);
            font-weight: 600;
            border-bottom-width: 1px;
            background-color: rgba(255,255,255,0.02);
        }
        
        /* Modals */
        .modal-content {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Back Button & QRIS -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('admin.warungs') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-left me-2"></i> Kembali
            </a>
            
            <div class="d-flex gap-2">
                <div class="input-group input-group-sm" style="width: 300px;">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-link"></i></span>
                    <input type="text" class="form-control" id="customerUrl" value="{{ $customerUrl ?? 'N/A' }}" readonly>
                    <button class="btn btn-primary" onclick="copyCustomerUrl()" type="button">Copy</button>
                    <a href="{{ $customerUrl ?? '#' }}" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-external-link-alt"></i></a>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="restoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button">
                    Dashboard
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">
                    Users
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="report-tab" data-bs-toggle="tab" data-bs-target="#report" type="button">
                    Laporan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="menu-items-tab" data-bs-toggle="tab" data-bs-target="#menu-items" type="button">
                    Menu
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                    Pengaturan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="subscription-tab" data-bs-toggle="tab" data-bs-target="#subscription" type="button">
                    Langganan
                </button>
            </li>
        </ul>

        <div class="tab-content" id="restoTabContent">
            <!-- Dashboard Tab -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                
                <!-- Stats Grid -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Pendapatan Hari Ini</div>
                                <!-- Icon: Money -->
                                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="stat-value">Rp {{ number_format($dailyReport['revenue'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Total Pesanan</div>
                                <!-- Icon: Box/Receipt -->
                                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </div>
                            <div class="stat-value">{{ $dailyReport['orders'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Best Seller</div>
                                <!-- Icon: Star/Medal -->
                                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                            </div>
                            @php
                                $bestSeller = ($dailyReport['best_seller'] ?? collect())->first();
                            @endphp
                            <div class="stat-value" style="font-size: 1.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $bestSeller ? $bestSeller->menu_name : '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-title">Rata-rata Waktu</div>
                                <!-- Icon: Clock -->
                                <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="stat-value">{{ $avgPrepTime ?? 0 }} <span style="font-size: 1rem; font-weight: 500; color: var(--text-secondary);">menit</span></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions-container">
                    <button class="btn-quick-action" onclick="showAddMenuModal()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Tambah Menu
                    </button>
                    <button class="btn-quick-action" onclick="refreshAllStock()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh Stok
                    </button>
                    <button class="btn-quick-action" onclick="printDailyReport()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak Laporan
                    </button>
                    <button class="btn-quick-action" onclick="showRestaurantSettings()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Edit Info Resto
                    </button>
                </div>

                <!-- Live Board -->
                <div class="mb-4">
                    <h5 class="mb-3 d-flex align-items-center">
                        <span class="me-2">🚦</span> Live Status
                        <span class="badge bg-danger ms-2 rounded-pill px-2 py-1" style="font-size: 0.6rem;">LIVE UPDATE</span>
                    </h5>
                    <div class="row g-3">
                        <!-- Pending -->
                        <div class="col-md-3">
                            <div class="lb-card">
                                <div class="live-board-header bg-header-pending">
                                    <span>Menunggu Verifikasi</span>
                                    <span class="badge bg-white text-dark rounded-pill" id="badge-pending">{{ count($liveBoard['pending'] ?? []) }}</span>
                                </div>
                                <div class="lb-body" id="list-pending">
                                    @forelse($liveBoard['pending'] ?? [] as $order)
                                        <div class="order-card" id="order-card-{{ $order->id }}">
                                            <div class="d-flex justify-content-between">
                                                <div class="order-code">#{{ $order->code }}</div>
                                                <div class="text-white small">{{ $order->table->name ?? 'Takeaway' }}</div>
                                            </div>
                                            <div class="order-meta">
                                                <div>Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted text-center small mt-3">Tidak ada pesanan</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <!-- Paid -->
                        <div class="col-md-3">
                            <div class="lb-card">
                                <div class="live-board-header bg-header-paid">
                                    <span>Dibayar</span>
                                    <span class="badge bg-white text-dark rounded-pill" id="badge-paid">{{ count($liveBoard['paid'] ?? []) }}</span>
                                </div>
                                <div class="lb-body" id="list-paid">
                                    @forelse($liveBoard['paid'] ?? [] as $order)
                                        <div class="order-card" id="order-card-{{ $order->id }}">
                                            <div class="d-flex justify-content-between">
                                                <div class="order-code">#{{ $order->code }}</div>
                                                <div class="text-white small">{{ $order->table->name ?? 'Takeaway' }}</div>
                                            </div>
                                            <div class="order-meta">
                                                <div>Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted text-center small mt-3">Tidak ada pesanan</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <!-- Preparing -->
                        <div class="col-md-3">
                            <div class="lb-card">
                                <div class="live-board-header bg-header-preparing">
                                    <span>Dimasak</span>
                                    <span class="badge bg-white text-dark rounded-pill" id="badge-preparing">{{ count($liveBoard['preparing'] ?? []) }}</span>
                                </div>
                                <div class="lb-body" id="list-preparing">
                                    @forelse($liveBoard['preparing'] ?? [] as $order)
                                        <div class="order-card" id="order-card-{{ $order->id }}">
                                            <div class="d-flex justify-content-between">
                                                <div class="order-code">#{{ $order->code }}</div>
                                                <div class="text-white small">{{ $order->table->name ?? 'Takeaway' }}</div>
                                            </div>
                                            <div class="order-meta">
                                                <div>Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted text-center small mt-3">Tidak ada pesanan</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <!-- Ready -->
                        <div class="col-md-3">
                            <div class="lb-card">
                                <div class="live-board-header bg-header-ready">
                                    <span>Siap Antar</span>
                                    <span class="badge bg-white text-dark rounded-pill" id="badge-ready">{{ count($liveBoard['ready'] ?? []) }}</span>
                                </div>
                                <div class="lb-body" id="list-ready">
                                    @forelse($liveBoard['ready'] ?? [] as $order)
                                        <div class="order-card" id="order-card-{{ $order->id }}">
                                            <div class="d-flex justify-content-between">
                                                <div class="order-code">#{{ $order->code }}</div>
                                                <div class="text-white small">{{ $order->table->name ?? 'Takeaway' }}</div>
                                            </div>
                                            <div class="order-meta">
                                                <div>Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted text-center small mt-3">Tidak ada pesanan</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Distribution Chart -->
                <div class="row">
                    <div class="col-md-8 col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Distribusi Penjualan Menu</span>
                                <span class="badge bg-dark border border-secondary text-secondary">{{ ucfirst($period ?? 'Daily') }}</span>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrapper">
                                    <canvas id="sales-pie-dashboard"></canvas>
                                    <!-- Center Text for Total -->
                                    <div class="chart-center-text">
                                        <div class="chart-center-value">{{ $chartItems->sum('total_qty') }}</div>
                                        <div class="chart-center-label">Terjual</div>
                                    </div>
                                </div>
                                <div id="sales-pie-dashboard-legend" class="legend-container"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Users Tab -->
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Daftar User</span>
                        <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="showAddUserModal()">
                            <i class="fas fa-plus me-1"></i> Tambah User
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Nama</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>WhatsApp</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td class="ps-4 fw-bold">{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td><span class="badge bg-dark border border-secondary text-info rounded-pill">{{ ucfirst($user->role) }}</span></td>
                                            <td>{{ $user->whatsapp ?? '-' }}</td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="editUser({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->role }}', '{{ $user->whatsapp }}', '{{ $user->username }}')">
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="deleteUser({{ $user->id }})">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">Belum ada user</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div class="tab-pane fade" id="report" role="tabpanel">
                <div class="d-flex justify-content-end mb-3">
                    <select class="form-select w-auto" id="reportPeriod" onchange="loadReport()">
                        <option value="daily" {{ $period == 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="weekly" {{ $period == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                        <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        <option value="yearly" {{ $period == 'yearly' ? 'selected' : '' }}>Tahunan</option>
                    </select>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <h5 class="text-secondary mb-3">Total Pesanan</h5>
                                <h2 class="display-4 fw-bold text-white mb-0">
                                    {{ $period == 'daily' ? $dailyReport['orders'] : ($period == 'weekly' ? $weeklyReport['orders'] : ($period == 'monthly' ? $monthlyReport['orders'] : $yearlyReport['orders'])) }}
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <h5 class="text-secondary mb-3">Total Revenue</h5>
                                <h2 class="display-4 fw-bold text-success mb-0">
                                    Rp {{ number_format($period == 'daily' ? $dailyReport['revenue'] : ($period == 'weekly' ? $weeklyReport['revenue'] : ($period == 'monthly' ? $monthlyReport['revenue'] : $yearlyReport['revenue'])), 0, ',', '.') }}
                                </h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">Distribusi Penjualan Menu</div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6 text-center">
                                        <div class="chart-wrapper">
                                            <canvas id="sales-pie-reports"></canvas>
                                            <div class="chart-center-text">
                                                <div class="chart-center-value">{{ $chartItems->sum('total_qty') }}</div>
                                                <div class="chart-center-label">Terjual</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="sales-pie-reports-legend" class="legend-container justify-content-start"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Items Tab -->
            <div class="tab-pane fade" id="menu-items" role="tabpanel">
                 <div class="d-flex justify-content-between mb-4">
                    <h4>Daftar Menu</h4>
                    <button class="btn btn-primary rounded-pill px-4" onclick="showAddMenuModal()">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </button>
                </div>
                <div class="row g-4">
                    @forelse($menuItems as $menu)
                        <div class="col-md-4 col-lg-3">
                            <div class="card h-100 border-0 bg-card overflow-hidden">
                                <div class="position-relative" style="height: 180px;">
                                    @if($menu->image)
                                        <img src="{{ asset('storage/' . $menu->image) }}" class="w-100 h-100 object-fit-cover" alt="{{ $menu->name }}">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark text-secondary">
                                            <i class="fas fa-utensils fa-3x"></i>
                                        </div>
                                    @endif
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <span class="badge {{ $menu->active ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                            {{ $menu->active ? 'Ready' : 'Habis' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="fw-bold text-white mb-1">{{ $menu->name }}</h6>
                                    <p class="small text-secondary mb-2 text-truncate">{{ $menu->description }}</p>
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div class="fw-bold text-info">Rp {{ number_format($menu->price, 0, ',', '.') }}</div>
                                        <div class="dropdown">
                                            <button class="btn btn-link text-secondary p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-dark">
                                                <li><a class="dropdown-item" href="#" onclick="editMenu({{ $menu->id }})">Edit</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="toggleStock({{ $menu->id }}, {{ $menu->active ? 'false' : 'true' }})">{{ $menu->active ? 'Set Habis' : 'Set Ready' }}</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteMenu({{ $menu->id }})">Hapus</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-secondary">
                            <i class="fas fa-box-open fa-3x mb-3"></i>
                            <p>Belum ada menu</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="card">
                    <div class="card-header">Pengaturan Restoran</div>
                    <div class="card-body">
                         <form id="restaurantSettingsForm" action="{{ route('admin.restaurants.update', $warung->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Restoran</label>
                                    <input type="text" class="form-control" name="name" value="{{ $warung->name }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" name="phone" value="{{ $warung->phone }}">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea class="form-control" name="address" rows="2">{{ $warung->address }}</textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Subscription Tab -->
            <div class="tab-pane fade" id="subscription" role="tabpanel">
                 <div class="card">
                    <div class="card-header">Langganan & Fitur</div>
                    <div class="card-body">
                        <form id="subscriptionFeaturesForm" action="{{ route('admin.restaurants.update', $warung->id) }}" method="POST">
                             @csrf
                             @method('PUT')
                             <input type="hidden" name="update_subscription" value="1">
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-info mb-3">Paket Langganan</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Tier</label>
                                        <select class="form-select" name="subscription_tier" id="subscription_tier_select">
                                            <option value="starter" {{ ($warung->subscription_tier ?? 'starter') === 'starter' ? 'selected' : '' }}>Starter</option>
                                            <option value="professional" {{ ($warung->subscription_tier ?? 'starter') === 'professional' ? 'selected' : '' }}>Professional</option>
                                            <option value="enterprise" {{ ($warung->subscription_tier ?? 'starter') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-info mb-3">Fitur Tambahan</h6>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_notification" id="whatsapp_notification" value="1" {{ $warung->whatsapp_notification ? 'checked' : '' }}>
                                        <label class="form-check-label" for="whatsapp_notification">Notifikasi WhatsApp</label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="google_sheets_enabled" id="google_sheets_enabled" value="1" {{ $warung->google_sheets_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="google_sheets_enabled">Integrasi Google Sheets</label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="features[inventory]" id="feat_inventory" value="1" {{ ($warung->features['inventory'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="feat_inventory">Manajemen Stok (Inventory)</label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="features[promo]" id="feat_promo" value="1" {{ ($warung->features['promo'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="feat_promo">Promo & Diskon</label>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="features[reports]" id="feat_reports" value="1" {{ ($warung->features['reports'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="feat_reports">Laporan Lanjutan</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan Konfigurasi</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modals -->
    <!-- Add Menu Modal -->
    <div id="addMenuModal" class="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Menu</h5>
                    <button type="button" class="btn-close" onclick="hideModal('addMenuModal')"></button>
                </div>
                <div class="modal-body">
                    <form id="addMenuForm" enctype="multipart/form-data">
                        <input type="hidden" name="warung_id" value="{{ $warung->id }}">
                        <div class="mb-3">
                            <label class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <input type="number" class="form-control" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category">
                                <option value="makanan">Makanan</option>
                                <option value="minuman">Minuman</option>
                            </select>
                        </div>
                         <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-control" name="image">
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" onclick="hideModal('addMenuModal')">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User</h5>
                    <button type="button" class="btn-close" onclick="hideModal('addUserModal')"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <input type="hidden" name="warung_id" value="{{ $warung->id }}">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="owner">Owner</option>
                                <option value="kasir">Kasir</option>
                                <option value="waiter">Waiter</option>
                                <option value="dapur">Dapur</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" name="whatsapp">
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" onclick="hideModal('addUserModal')">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" onclick="hideModal('editUserModal')"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        @method('PUT')
                        <input type="hidden" id="edit_user_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" id="edit_user_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_user_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_user_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="edit_user_role" name="role" required>
                                <option value="owner">Owner</option>
                                <option value="kasir">Kasir</option>
                                <option value="waiter">Waiter</option>
                                <option value="dapur">Dapur</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="edit_user_whatsapp" name="whatsapp">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru (Opsional)</label>
                            <input type="password" class="form-control" name="password" minlength="6">
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" onclick="hideModal('editUserModal')">Batal</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" onclick="hideModal('addUserModal')">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Modal & Navigation Helpers
        function showModal(id) { 
            const el = document.getElementById(id);
            if(el) {
                el.style.display = 'block'; 
                setTimeout(() => el.classList.add('show'), 10);
                document.body.classList.add('modal-open');
            }
        }
        function hideModal(id) { 
            const el = document.getElementById(id);
            if(el) {
                el.classList.remove('show');
                setTimeout(() => el.style.display = 'none', 300);
                document.body.classList.remove('modal-open');
            }
        }
        function loadReport() { 
            const period = document.getElementById('reportPeriod').value; 
            window.location.href = '{{ route("admin.restaurant.show", $warung->id) }}?period=' + period; 
        }
        function showRestaurantSettings() { 
            document.getElementById('settings-tab').click(); 
        }
        function updateFeaturesByTier() {
            // Placeholder for interactivity
            console.log("Tier changed to: " + document.getElementById('subscription_tier_select').value);
        }
        
        // Show Modals
        function showAddMenuModal() { showModal('addMenuModal'); }
        function showAddUserModal() { showModal('addUserModal'); }

        // Form Handling
        document.getElementById('addMenuForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('/menu-items', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            }).then(r => r.json()).then(data => {
                if(data.success || data.id) { location.reload(); } else { alert(data.message || 'Error'); }
            }).catch(e => alert('Error: ' + e));
        });

        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            // Append warung_id if not in form (it is)
            fetch('/admin/users', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            }).then(r => r.json()).then(data => {
                if(data.success || data.id) { location.reload(); } else { alert(data.message || 'Error'); }
            }).catch(e => alert('Error: ' + e));
        });

        // Actions
        function refreshAllStock() { 
            if(confirm('Refresh stok semua menu?')) {
                 fetch('{{ route("menu.refresh-all-stock") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                }).then(() => location.reload());
            }
        }
        function printDailyReport() { window.print(); }
        
        function copyCustomerUrl() { 
            var copyText = document.getElementById("customerUrl");
            copyText.select();
            document.execCommand("copy");
            alert("Link copied: " + copyText.value);
        }

        // Stub functions for edit/delete (would require specific routes/modals)
        function editUser(id, name, email, role, wa, username) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_user_name').value = name;
            document.getElementById('edit_user_username').value = username || '';
            document.getElementById('edit_user_email').value = email;
            document.getElementById('edit_user_role').value = role;
            document.getElementById('edit_user_whatsapp').value = wa === '-' ? '' : wa;
            showModal('editUserModal');
        }

        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit_user_id').value;
            const formData = new FormData(this);
            
            fetch('/admin/users/' + id, {
                method: 'POST', 
                body: formData,
                headers: { 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) { 
                    location.reload(); 
                } else { 
                    alert(data.message || 'Error updating user'); 
                }
            })
            .catch(e => alert('Error: ' + e));
        });

        // Subscription & Settings Forms
        ['subscriptionFeaturesForm', 'restaurantSettingsForm'].forEach(formId => {
            const form = document.getElementById(formId);
            if(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) { 
                            alert(data.message || 'Updated successfully');
                            location.reload(); 
                        } else { 
                            alert(data.message || 'Error updating'); 
                        }
                    })
                    .catch(e => alert('Error: ' + e));
                });
            }
        });

        function deleteUser(id) {
            if(confirm('Hapus user ini?')) {
                fetch('/admin/users/' + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                }).then(() => location.reload());
            }
        }
        function editMenu(id) {
            alert('Edit menu ' + id + ' (Implementasi Modal Edit)');
        }
        function deleteMenu(id) {
            if(confirm('Hapus menu ini?')) {
                 fetch('/menu-items/' + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                }).then(() => location.reload());
            }
        }
        function toggleStock(id, status) {
             fetch('/menu-items/' + id + '/toggle-stock', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(() => location.reload());
        }

        // Chart Logic
        document.addEventListener('DOMContentLoaded', function() {
            var rawData = @json($chartItems);
            var chartData = rawData.map(item => ({ label: item.menu_name, value: item.total_qty }));
            
            function renderPieChart(canvasId, legendId, data) {
                var canvas = document.getElementById(canvasId);
                if (!canvas) return;
                
                var ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                var total = data.reduce((sum, item) => sum + Number(item.value || 0), 0);
                if (!total) {
                    ctx.beginPath();
                    ctx.arc(canvas.width/2, canvas.height/2, 80, 0, 2 * Math.PI);
                    ctx.strokeStyle = '#2D3748';
                    ctx.lineWidth = 20;
                    ctx.stroke();
                    return;
                }

                var colors = ['#3182CE', '#38A169', '#E53E3E', '#D69E2E', '#805AD5', '#D53F8C', '#319795', '#ED8936'];
                var startAngle = -0.5 * Math.PI; 
                var centerX = canvas.width / 2;
                var centerY = canvas.height / 2;
                var radius = 100;

                var legendHtml = '';

                data.forEach(function(item, index) {
                    var sliceAngle = (2 * Math.PI * item.value) / total;
                    var color = colors[index % colors.length];

                    ctx.beginPath();
                    ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                    ctx.lineWidth = 30;
                    ctx.strokeStyle = color;
                    ctx.stroke();

                    startAngle += sliceAngle;

                    var percentage = Math.round((item.value / total) * 100);
                    legendHtml += `
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: ${color}"></span>
                            <span>${item.label} (${percentage}%)</span>
                        </div>
                    `;
                });

                var legendContainer = document.getElementById(legendId);
                if (legendContainer) legendContainer.innerHTML = legendHtml;
            }

            renderPieChart('sales-pie-dashboard', 'sales-pie-dashboard-legend', chartData);
            renderPieChart('sales-pie-reports', 'sales-pie-reports-legend', chartData);
        });

        // SSE Logic
        document.addEventListener('DOMContentLoaded', function () {
             const source = new EventSource('{{ route('dashboard.stream') }}');
             source.onmessage = function (event) {
                 if (document.body.classList.contains('modal-open')) return;
                 try {
                     const data = JSON.parse(event.data);
                     if (!data || !data.status) return;
                     updateLiveBoard(data);
                 } catch (e) { console.error('SSE error:', e); }
             };
             source.onerror = function () { source.close(); };
        });

        function updateLiveBoard(data) {
             const existingCard = document.getElementById('order-card-' + data.id);
             if (existingCard) existingCard.remove();

             let targetListId = null, badgeId = null;
             switch (data.status) {
                 case 'pending': targetListId = 'list-pending'; badgeId = 'badge-pending'; break;
                 case 'paid': targetListId = 'list-paid'; badgeId = 'badge-paid'; break;
                 case 'preparing': targetListId = 'list-preparing'; badgeId = 'badge-preparing'; break;
                 case 'ready': targetListId = 'list-ready'; badgeId = 'badge-ready'; break;
             }

             if (targetListId) {
                 const list = document.getElementById(targetListId);
                 if (list) {
                     const emptyText = list.querySelector('p.text-muted');
                     if (emptyText) emptyText.remove();

                     const cardHtml = `
                        <div class="order-card" id="order-card-${data.id}">
                            <div class="d-flex justify-content-between">
                                <div class="order-code">#${data.code}</div>
                                <div class="text-white small">${data.table}</div>
                            </div>
                            <div class="order-meta">
                                <div>${data.formatted_total}</div>
                            </div>
                        </div>
                     `;
                     if (data.status === 'paid') list.insertAdjacentHTML('afterbegin', cardHtml);
                     else list.insertAdjacentHTML('beforeend', cardHtml);
                 }
             }
             
             ['pending', 'paid', 'preparing', 'ready'].forEach(status => {
                 const list = document.getElementById('list-' + status);
                 const badge = document.getElementById('badge-' + status);
                 if (list && badge) {
                     const count = list.querySelectorAll('.order-card').length;
                     badge.textContent = count;
                     if (count === 0 && !list.querySelector('p.text-muted')) {
                         list.innerHTML = '<p class="text-muted text-center small mt-3">Tidak ada pesanan</p>';
                     }
                 }
             });
        }
    </script>
@endsection
