@extends('layouts.dashboard')

@section('title', 'Majar Signature | Owner Dashboard')
@section('header_title', 'Majar Signature | Owner Dashboard')
@section('header_subtitle', 'Kontrol penuh operasional restoran, menu, dan tim')

@section('content')
    <style>
        .stat-card {
            border-left: 5px solid var(--accent-orange);
        }
        .stat-card-title {
            color: #6c757d;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .stat-card-value {
            color: #1a1a1a;
            font-weight: 800;
            font-size: 1.75rem;
            line-height: 1.2;
        }
        .stat-card-footer {
            color: #6c757d;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }
        .nav-tabs {
            border-bottom: 2px solid #eee;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
            transition: all 0.3s;
        }
        .nav-tabs .nav-link:hover {
            color: var(--accent-orange);
            background: rgba(255, 140, 0, 0.05);
        }
        .nav-tabs .nav-link.active {
            color: var(--accent-orange);
            background: #fff;
            border-bottom: 3px solid var(--accent-orange);
        }
        .quick-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .live-board-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .live-column {
            background: #f1f3f5;
            border-radius: 15px;
            padding: 1rem;
            min-height: 400px;
        }
        .live-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #dee2e6;
        }
        .live-header-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #495057;
        }
        .live-header-count {
            background: var(--accent-orange);
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .live-card {
            background: #fff;
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 3px solid #dee2e6;
        }
        .live-card-title {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        .live-card-meta {
            font-size: 0.7rem;
            color: #6c757d;
        }
        .qris-card {
            background: linear-gradient(135deg, #fff 0%, #fff9f0 100%);
            border: 1px solid #ffeeba;
        }
        .owner-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }
        .owner-modal-panel {
            width: 95%;
            max-width: 550px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .modal-header {
            padding: 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 1.25rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
    </style>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card p-4">
                <div class="stat-card-title">Penjualan Hari Ini</div>
                <div class="stat-card-value">Rp {{ number_format($dailyReport['revenue'] ?? 0, 0, ',', '.') }}</div>
                <div class="stat-card-footer">{{ $dailyReport['orders'] ?? 0 }} Transaksi Berhasil</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-4" style="border-left-color: #FFC107;">
                <div class="stat-card-title">Profit (HPP)</div>
                <div class="stat-card-value">Rp {{ number_format($dailyReport['profit'] ?? 0, 0, ',', '.') }}</div>
                <div class="stat-card-footer">Estimasi profit bersih</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-4" style="border-left-color: #FF8C00;">
                <div class="stat-card-title">Total Pesanan</div>
                <div class="stat-card-value">{{ $dailyReport['orders'] ?? 0 }}</div>
                <div class="stat-card-footer">Termasuk Dine-in & Takeaway</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-4" style="border-left-color: #FFC107;">
                <div class="stat-card-title">Stok Rendah</div>
                <div class="stat-card-value">{{ $totalLowStock ?? 0 }}</div>
                <div class="stat-card-footer">Menu & Bahan perlu restock</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Aksi Cepat Owner</h5>
                <div class="quick-actions">
                    <button class="btn btn-primary btn-sm" onclick="showOwnerModal('restaurantSettingsModal')">
                        <i class="fas fa-cog me-2"></i>Pengaturan Resto
                    </button>
                    <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Cetak Laporan
                    </button>
                </div>
            </div>
            
            <ul class="nav nav-tabs" id="ownerTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#analytics">Analitik & Laporan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#menu">Manajemen Menu</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#staff">Tim Karyawan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#live">Live Monitoring</button>
                </li>
            </ul>

            <div class="tab-content pt-2">
                <!-- Tab: Analytics -->
                <div class="tab-pane fade show active" id="analytics">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <div class="card border p-4">
                                <h6 class="fw-bold mb-3">Distribusi Penjualan Menu</h6>
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <canvas id="owner-sales-pie" width="250" height="250"></canvas>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="owner-sales-pie-legend"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border p-4 qris-card">
                                <h6 class="fw-bold mb-3">Link Customer & QR</h6>
                                <div id="owner-qr-container" class="text-center mb-3"></div>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" value="{{ $customerUrl }}" readonly id="custLink">
                                    <button class="btn btn-outline-primary" onclick="copyLink()">Salin</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Menu -->
                <div class="tab-pane fade" id="menu">
                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold">Daftar Menu Restoran</h6>
                        <button class="btn btn-primary btn-sm" onclick="showOwnerModal('addMenuModal')">Tambah Menu</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Menu</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menuItems as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td><span class="badge bg-light text-dark">{{ ucfirst($item->category) }}</span></td>
                                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td>
                                        @if($item->active)
                                            <span class="badge bg-success">Tersedia</span>
                                        @else
                                            <span class="badge bg-danger">Habis</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editMenu({{ $item->id }})">Edit</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Staff -->
                <div class="tab-pane fade" id="staff">
                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold">Manajemen Tim</h6>
                        <button class="btn btn-primary btn-sm" onclick="showOwnerModal('addStaffModal')">Tambah Staff</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>WhatsApp</th>
                                    <th>Shift Hari Ini</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staff as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td><span class="badge bg-info text-dark">{{ ucfirst($user->role) }}</span></td>
                                    <td>{{ $user->whatsapp ?: '-' }}</td>
                                    <td>
                                        @php $shift = $staffShiftSummary[$user->id] ?? null; @endphp
                                        @if($shift)
                                            {{ $shift['start']->format('H:i') }} - {{ $shift['end'] ? $shift['end']->format('H:i') : 'Aktif' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteStaff({{ $user->id }})">Hapus</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Live Monitoring -->
                <div class="tab-pane fade" id="live">
                    <div class="live-board-grid">
                        @foreach(['pending' => 'Verifikasi', 'verified' => 'Dibayar', 'preparing' => 'Dimasak', 'ready' => 'Siap', 'paid' => 'Selesai'] as $key => $label)
                        <div class="live-column">
                            <div class="live-header">
                                <span class="live-header-title">{{ $label }}</span>
                                <span class="live-header-count">{{ count($liveBoard[$key] ?? []) }}</span>
                            </div>
                            <div class="live-body">
                                @forelse($liveBoard[$key] ?? [] as $order)
                                <div class="live-card">
                                    <div class="live-card-title">#{{ $order->code }}</div>
                                    <div class="live-card-meta">{{ $order->customer_name }} • {{ $order->table->name ?? 'T.A' }}</div>
                                </div>
                                @empty
                                <div class="text-center py-4 text-muted" style="font-size: 0.7rem;">Kosong</div>
                                @endforelse
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="restaurantSettingsModal" class="owner-modal-backdrop">
        <div class="owner-modal-panel">
            <div class="modal-header">
                <h5 class="fw-bold mb-0">Pengaturan Majar Signature</h5>
                <button class="btn-close" onclick="hideOwnerModal('restaurantSettingsModal')"></button>
            </div>
            <form id="restaurantSettingsForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Restoran</label>
                        <input type="text" class="form-control" name="name" value="{{ $warung->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat</label>
                        <textarea class="form-control" name="address" rows="2">{{ $warung->address }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jam Operasional</label>
                        <input type="text" class="form-control" name="opening_hours" value="{{ $warung->opening_hours }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" onclick="hideOwnerModal('restaurantSettingsModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // QR Code
            new QRCode(document.getElementById("owner-qr-container"), {
                text: "{{ $customerUrl }}",
                width: 150,
                height: 150
            });

            // Pie Chart
            const canvas = document.getElementById('owner-sales-pie');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                const data = @json($dailyReport['best_seller'] ?? []);
                if (data.length > 0) {
                    let total = data.reduce((a, b) => a + b.total_qty, 0);
                    let startAngle = 0;
                    const colors = ['#FF8C00', '#FFC107', '#4ade80', '#3b82f6', '#ef4444'];
                    
                    let legendHtml = '';
                    data.forEach((item, i) => {
                        const sliceAngle = (2 * Math.PI * item.total_qty) / total;
                        ctx.fillStyle = colors[i % colors.length];
                        ctx.beginPath();
                        ctx.moveTo(125, 125);
                        ctx.arc(125, 125, 100, startAngle, startAngle + sliceAngle);
                        ctx.fill();
                        startAngle += sliceAngle;
                        
                        legendHtml += `<div class="d-flex align-items-center mb-2" style="font-size: 0.8rem;">
                            <span style="width:12px;height:12px;background:${colors[i%colors.length]};display:inline-block;margin-right:8px;border-radius:2px;"></span>
                            ${item.menu_name}: <strong>${Math.round(item.total_qty/total*100)}%</strong>
                        </div>`;
                    });
                    document.getElementById('owner-sales-pie-legend').innerHTML = legendHtml;
                }
            </div>
        });

        function showOwnerModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function hideOwnerModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function copyLink() {
            const input = document.getElementById('custLink');
            input.select();
            document.execCommand('copy');
            alert('Link berhasil disalin!');
        }
    </script>
@endsection
