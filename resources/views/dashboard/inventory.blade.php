@extends('layouts.dashboard')

@section('title', 'Majar Signature | Inventory & Gudang')
@section('header_title', 'Inventory & Gudang')
@section('header_subtitle', 'Manajemen operasional gudang dan bahan baku')

@section('content')
<div class="container-fluid py-4">
    @php $activeTab = request()->get('tab', 'dashboard'); @endphp

    @if($activeTab === 'dashboard')
        <!-- 🏠 1. DASHBOARD INVENTORY -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background: #fff; border-left: 5px solid #FF8C00;">
                    <div class="small text-muted fw-bold uppercase tracking-wider">Total Item</div>
                    <h2 class="fw-bold mb-0 text-dark">{{ $stats['total_items'] }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background: #fff; border-left: 5px solid #FFC107;">
                    <div class="small text-muted fw-bold uppercase tracking-wider">Stok Menipis</div>
                    <h2 class="fw-bold mb-0 text-warning">{{ $stats['low_stock'] }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background: #fff; border-left: 5px solid #ef4444;">
                    <div class="small text-muted fw-bold uppercase tracking-wider">Barang Habis</div>
                    <h2 class="fw-bold mb-0 text-danger">{{ $stats['out_of_stock'] }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background: #fff; border-left: 5px solid #22c55e;">
                    <div class="small text-muted fw-bold uppercase tracking-wider">Masuk Hari Ini</div>
                    <h2 class="fw-bold mb-0 text-success">{{ $stats['incoming_today'] }} <small class="fs-6 text-muted">transaksi</small></h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background: #fff; border-left: 5px solid #3b82f6;">
                    <div class="small text-muted fw-bold uppercase tracking-wider">Pemakaian Hari Ini</div>
                    <h2 class="fw-bold mb-0 text-primary">{{ $stats['usage_today'] }} <small class="fs-6 text-muted">logs</small></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-orange"></i> Aktifitas Terbaru</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Waktu</th>
                                    <th>Item</th>
                                    <th>Tipe</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end pe-4">User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLogs as $log)
                                <tr>
                                    <td class="ps-4 small text-muted">{{ $log->created_at->format('H:i') }}</td>
                                    <td class="fw-bold">{{ $log->ingredient->name }}</td>
                                    <td>
                                        <span class="badge rounded-pill px-3 {{ 
                                            $log->type === 'incoming' ? 'bg-success-soft text-success' : 
                                            ($log->type === 'usage' ? 'bg-primary-soft text-primary' : 'bg-warning-soft text-warning') 
                                        }} uppercase small">
                                            {{ $log->type }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($log->quantity, 2) }} {{ $log->ingredient->unit }}</td>
                                    <td class="text-end pe-4 small">{{ $log->user->name }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="fw-bold mb-4">Quick Actions</h6>
                    <div class="d-grid gap-3">
                        <a href="?tab=incoming" class="btn btn-primary py-3 rounded-3 text-start ps-4">
                            <i class="fas fa-plus-circle me-2"></i> Catat Barang Masuk
                        </a>
                        <a href="?tab=adjustment" class="btn btn-outline-warning py-3 rounded-3 text-start ps-4">
                            <i class="fas fa-sliders-h me-2"></i> Penyesuaian Stok
                        </a>
                        <a href="?tab=items" class="btn btn-light py-3 rounded-3 text-start ps-4 border">
                            <i class="fas fa-boxes me-2"></i> Lihat Semua Barang
                        </a>
                    </div>
                </div>
            </div>
        </div>

    @elseif($activeTab === 'items')
        <!-- 📋 2. DATA BARANG (MAIN PAGE) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Master Data Barang</h5>
                <button class="btn btn-brand px-4 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addIngredientModal">
                    <i class="fas fa-plus me-2"></i> Tambah Barang
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama Barang</th>
                            <th>Kategori</th>
                            <th class="text-center">Stok Saat Ini</th>
                            <th class="text-center">Unit</th>
                            <th class="text-center">HPP (Avg)</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ingredients as $item)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark fs-6">{{ $item->name }}</div>
                                <small class="text-muted">Last Price: Rp {{ number_format($item->last_price, 0, ',', '.') }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $item->category ?? 'General' }}</span></td>
                            <td class="text-center">
                                <h6 class="mb-0 fw-black {{ $item->stock <= $item->min_stock ? 'text-danger' : 'text-dark' }}">
                                    {{ number_format($item->stock, 2) }}
                                </h6>
                            </td>
                            <td class="text-center small uppercase fw-bold text-muted">{{ $item->unit }}</td>
                            <td class="text-center fw-bold">Rp {{ number_format($item->avg_price, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($item->stock <= 0)
                                    <span class="badge bg-danger text-white rounded-pill px-3">HABIS</span>
                                @elseif($item->stock <= $item->min_stock)
                                    <span class="badge bg-warning text-dark rounded-pill px-3">MENIPIS</span>
                                @else
                                    <span class="badge bg-success text-white rounded-pill px-3">AMAN</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light border" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-light border text-primary" title="Incoming" onclick="openIncomingModal({{ $item->id }}, '{{ $item->name }}')"><i class="fas fa-plus"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($activeTab === 'incoming')
        <!-- 📥 4. BARANG MASUK (INCOMING) -->
        <div class="row">
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-4">Input Barang Masuk</h5>
                    <form action="{{ route('inventory.incoming.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Pilih Barang</label>
                            <select name="ingredient_id" class="form-select rounded-3" required>
                                <option value="">-- Pilih Barang --</option>
                                @foreach($ingredients as $i)
                                    <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Supplier</label>
                            <select name="supplier_id" class="form-select rounded-3">
                                <option value="">-- Tanpa Supplier / Cash --</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Jumlah (Qty)</label>
                                <input type="number" step="0.01" name="quantity" class="form-control rounded-3" required placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Harga per Unit</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="number" name="price" class="form-control rounded-3 border-start-0" required placeholder="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Catatan / No. Nota</label>
                            <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Contoh: Nota #123 dari pasar"></textarea>
                        </div>
                        <button type="submit" class="btn btn-brand w-100 py-3 rounded-3 fw-bold">
                            <i class="fas fa-save me-2"></i> Simpan Stok Masuk
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold">Riwayat Masuk Terbaru</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Tanggal</th>
                                    <th>Barang</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end pe-4">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLogs->where('type', 'incoming') as $log)
                                <tr>
                                    <td class="ps-4 small text-muted">{{ $log->created_at->format('d/m H:i') }}</td>
                                    <td class="fw-bold">{{ $log->ingredient->name }}</td>
                                    <td class="text-center fw-bold">{{ number_format($log->quantity, 2) }}</td>
                                    <td class="text-end pe-4">Rp {{ number_format($log->price, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    @elseif($activeTab === 'adjustment')
        <!-- ⚙️ 6. ADJUSTMENT -->
        <div class="card border-0 shadow-sm rounded-4 p-5 max-w-lg mx-auto">
            <h4 class="fw-bold text-center mb-2">Penyesuaian Stok Manual</h4>
            <p class="text-muted text-center mb-5 small">Gunakan ini untuk koreksi stok jika ada barang rusak, hilang, atau selisih opname.</p>
            
            <form action="{{ route('inventory.adjustment.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted">Pilih Barang</label>
                    <select name="ingredient_id" class="form-select form-select-lg rounded-3 border-2" required>
                        <option value="">-- Pilih Barang --</option>
                        @foreach($ingredients as $i)
                            <option value="{{ $i->id }}">{{ $i->name }} (Stok saat ini: {{ number_format($i->stock, 2) }} {{ $i->unit }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted">Jenis Koreksi</label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="type" id="type_plus" value="plus" autocomplete="off" checked>
                            <label class="btn btn-outline-success w-100 py-3 rounded-3" for="type_plus"><i class="fas fa-plus me-1"></i> Tambah</label>

                            <input type="radio" class="btn-check" name="type" id="type_minus" value="minus" autocomplete="off">
                            <label class="btn btn-outline-danger w-100 py-3 rounded-3" for="type_minus"><i class="fas fa-minus me-1"></i> Kurang</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted">Jumlah</label>
                        <input type="number" step="0.01" name="quantity" class="form-control form-control-lg rounded-3 border-2" required placeholder="0.00">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted">Alasan Perubahan</label>
                    <select name="reason" class="form-select rounded-3 border-2" required>
                        <option value="selisih">Selisih Opname (Audit)</option>
                        <option value="rusak">Barang Rusak / Expired</option>
                        <option value="hilang">Barang Hilang</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="form-label fw-bold small text-muted">Keterangan Tambahan</label>
                    <textarea name="notes" class="form-control rounded-3 border-2" rows="3" placeholder="Tulis alasan lebih detail di sini..."></textarea>
                </div>

                <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold fs-5">
                    <i class="fas fa-check-circle me-2 text-warning"></i> Proses Koreksi Stok
                </button>
            </form>
        </div>

    @elseif($activeTab === 'overview')
        <!-- 📊 3. STOCK OVERVIEW -->
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold mb-4">Grafik Pergerakan Stok (7 Hari Terakhir)</h5>
                    <div style="height: 300px; position: relative;">
                        <!-- Mocking a bar chart using simple HTML/CSS for now as vision -->
                        <div class="d-flex align-items-end h-100 gap-2">
                            @foreach($stockMovement as $move)
                                <div class="flex-grow-1 d-flex flex-column align-items-center">
                                    <div class="d-flex flex-column w-100 gap-1" style="height: 200px; justify-content: flex-end;">
                                        @php
                                            $max = $stockMovement->max(fn($m) => max($m->incoming, $m->total_usage)) ?: 1;
                                            $hIncoming = ($move->incoming / $max) * 100;
                                            $hUsage = ($move->total_usage / $max) * 100;
                                        @endphp
                                        <div class="bg-success rounded-top" style="height: {{ $hIncoming }}%; width: 100%; opacity: 0.8;" title="Masuk: {{ $move->incoming }}"></div>
                                        <div class="bg-primary rounded-top" style="height: {{ $hUsage }}%; width: 100%; opacity: 0.8;" title="Keluar: {{ $move->total_usage }}"></div>
                                    </div>
                                    <div class="small text-muted mt-2" style="font-size: 10px;">{{ date('d/m', strtotime($move->date)) }}</div>
                                </div>
                            @endforeach
                            @if($stockMovement->isEmpty())
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                    Belum ada data pergerakan stok 7 hari terakhir
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-3 mt-4 justify-content-center">
                        <div class="small d-flex align-items-center"><span class="bg-success rounded-circle me-1" style="width:10px;height:10px;display:inline-block"></span> Stok Masuk</div>
                        <div class="small d-flex align-items-center"><span class="bg-primary rounded-circle me-1" style="width:10px;height:10px;display:inline-block"></span> Stok Keluar</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold mb-4">Top Stock Usage</h5>
                    <ul class="list-group list-group-flush">
                        @php
                            $topUsage = \App\Models\StockLog::where('type', 'usage')
                                ->whereHas('ingredient', fn($q) => $q->where('warung_id', auth()->user()->warung_id))
                                ->select('ingredient_id', DB::raw('SUM(quantity) as total'))
                                ->groupBy('ingredient_id')
                                ->orderBy('total', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp
                        @foreach($topUsage as $usage)
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                <div>
                                    <div class="fw-bold">{{ $usage->ingredient->name }}</div>
                                    <small class="text-muted">Total: {{ number_format($usage->total, 2) }} {{ $usage->ingredient->unit }}</small>
                                </div>
                                <span class="badge bg-primary-soft text-primary rounded-pill">{{ number_format(($usage->total / ($topUsage->sum('total') ?: 1)) * 100, 0) }}%</span>
                            </li>
                        @endforeach
                        @if($topUsage->isEmpty())
                            <li class="list-group-item border-0 px-0 text-muted">Belum ada data penggunaan</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

    @elseif($activeTab === 'usage')
        <!-- 📤 5. USAGE (PEMAKAIAN) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Tracking Penggunaan Real-time</h5>
                <span class="badge bg-primary text-white rounded-pill px-3">Auto-sync dari POS</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Bahan Baku</th>
                            <th class="text-center">Jumlah</th>
                            <th>Unit</th>
                            <th>User/System</th>
                            <th class="text-end pe-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usageLogs as $log)
                        <tr>
                            <td class="ps-4 small text-muted">{{ $log->created_at->format('d/m H:i') }}</td>
                            <td class="fw-bold">{{ $log->ingredient->name }}</td>
                            <td class="text-center fw-bold text-primary">- {{ number_format($log->quantity, 2) }}</td>
                            <td class="small text-muted">{{ $log->ingredient->unit }}</td>
                            <td><span class="small">{{ $log->user->name ?? 'System' }}</span></td>
                            <td class="text-end pe-4 small text-muted">{{ $log->notes }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 py-3">
                {{ $usageLogs->appends(['tab' => 'usage'])->links() }}
            </div>
        </div>

    @elseif($activeTab === 'alert')
        <!-- 🚨 8. ALERT STOCK -->
        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-danger text-white py-4 px-4 border-0">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Stok Kritis & Habis</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nama Barang</th>
                                    <th class="text-center">Stok Saat Ini</th>
                                    <th class="text-center">Minimal Stok</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($alertIngredients as $item)
                                <tr class="{{ $item->stock <= 0 ? 'bg-danger-soft' : '' }}">
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $item->name }}</div>
                                        <small class="text-muted">{{ $item->category }}</small>
                                    </td>
                                    <td class="text-center fw-black {{ $item->stock <= 0 ? 'text-danger' : 'text-warning' }}">
                                        {{ number_format($item->stock, 2) }} {{ $item->unit }}
                                    </td>
                                    <td class="text-center text-muted small">{{ number_format($item->min_stock, 2) }} {{ $item->unit }}</td>
                                    <td class="text-center">
                                        @if($item->stock <= 0)
                                            <span class="badge bg-danger rounded-pill px-3">HABIS TOTAL</span>
                                        @else
                                            <span class="badge bg-warning text-dark rounded-pill px-3">DIBAWAH LIMIT</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="?tab=incoming&ingredient_id={{ $item->id }}" class="btn btn-sm btn-brand rounded-pill px-3">
                                            <i class="fas fa-plus-circle me-1"></i> Beli Lagi
                                        </a>
                                        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 ms-2" data-bs-toggle="modal" data-bs-target="#requestModal{{ $item->id }}">
                                            <i class="fas fa-paper-plane me-1"></i> Request
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                @if($alertIngredients->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                        Semua stok dalam kondisi aman. Tidak ada alert saat ini.
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @foreach($alertIngredients as $item)
        <!-- Request Modal for each alert item -->
        <div class="modal fade" id="requestModal{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <form action="{{ route('inventory.requests.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="ingredient_id" value="{{ $item->id }}">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bold">Request Restock: {{ $item->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Jumlah yang Dibutuhkan</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="quantity" class="form-control rounded-start-3" required placeholder="0.00">
                                    <span class="input-group-text bg-light rounded-end-3">{{ $item->unit }}</span>
                                </div>
                                <small class="text-muted">Saran: Minimal {{ number_format($item->min_stock * 2, 2) }} {{ $item->unit }}</small>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Catatan Kebutuhan</label>
                                <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Contoh: Stok menipis, butuh segera untuk weekend"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="submit" class="btn btn-dark w-100 py-3 rounded-3 fw-bold">Kirim Permintaan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach

    @elseif($activeTab === 'suppliers')
        <!-- 📦 9. SUPPLIER -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Daftar Supplier / Rekanan</h5>
                <button class="btn btn-brand px-4 rounded-pill fw-bold">
                    <i class="fas fa-plus me-2"></i> Tambah Supplier
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama Supplier</th>
                            <th>Kontak / Telp</th>
                            <th>Email</th>
                            <th>Alamat</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suppliers as $s)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $s->name }}</td>
                            <td>{{ $s->phone }}</td>
                            <td><span class="small text-muted">{{ $s->email }}</span></td>
                            <td class="small">{{ $s->address }}</td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-light border text-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($activeTab === 'recipes')
        <!-- 🍽️ 11. RESEP (HPP) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Kalkulasi HPP Berdasarkan Resep</h5>
                <span class="badge bg-light text-muted border px-3">HPP Update Otomatis saat Barang Masuk</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Menu Item</th>
                            <th>Kategori</th>
                            <th class="text-center">Harga Jual</th>
                            <th class="text-center">Estimasi HPP</th>
                            <th class="text-center">Margin (%)</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menuItems as $item)
                            @php
                                $hpp = $item->hpp;
                                $margin = $item->price > 0 ? (($item->price - $hpp) / $item->price) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $item->name }}</td>
                                <td><span class="small text-muted">{{ $item->category }}</span></td>
                                <td class="text-center fw-bold">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-center text-orange fw-bold">Rp {{ number_format($hpp, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $margin > 30 ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }} rounded-pill px-3">
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-brand rounded-pill px-3" onclick="viewHppDetails({{ $item->id }})">
                                        <i class="fas fa-search-dollar me-1"></i> Rincian Resep
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Modals -->
<!-- Add Ingredient Modal -->
<div class="modal fade" id="addIngredientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('inventory.ingredient.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Tambah Bahan Baku Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Bahan</label>
                        <input type="text" name="name" class="form-control rounded-3" required placeholder="Contoh: Daging Sapi Wagyu">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kategori</label>
                        <input type="text" name="category" class="form-control rounded-3" placeholder="Contoh: Daging / Sayur / Bumbu">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Satuan</label>
                            <input type="text" name="unit" class="form-control rounded-3" required placeholder="kg / gr / pcs">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Minimal Stok (Alert)</label>
                            <input type="number" step="0.01" name="min_stock" class="form-control rounded-3" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Stok Awal</label>
                        <input type="number" step="0.01" name="initial_stock" class="form-control rounded-3" value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-brand w-100 py-3 rounded-3 fw-bold shadow-sm">Simpan Bahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HPP Details Modal -->
<div class="modal fade" id="hppDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="fw-bold mb-1" id="hppMenuName">Rincian Resep</h5>
                    <p class="mb-0 text-muted small" id="hppMenuPrice"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-white border-bottom">
                            <tr>
                                <th class="ps-4 py-3">Bahan Baku</th>
                                <th class="text-center py-3">Kebutuhan (Qty)</th>
                                <th class="text-end py-3">Harga Rata-rata</th>
                                <th class="text-end pe-4 py-3">Subtotal HPP</th>
                            </tr>
                        </thead>
                        <tbody id="hppTableBody">
                            <!-- JS populated -->
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="ps-4 py-3 fw-bold">TOTAL ESTIMASI HPP</td>
                                <td class="text-end pe-4 py-3 fw-black text-orange fs-5" id="hppTotalValue"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('extra_js')
<script>
    function viewHppDetails(menuId) {
        fetch(`/inventory/hpp/${menuId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('hppMenuName').innerText = `Rincian Resep: ${data.menu_name}`;
                document.getElementById('hppMenuPrice').innerText = `Harga Jual: Rp ${new Intl.NumberFormat('id-ID').format(data.price)} | Margin: ${data.margin.toFixed(1)}%`;
                
                let html = '';
                data.details.forEach(item => {
                    html += `
                        <tr>
                            <td class="ps-4 fw-bold text-dark">${item.ingredient}</td>
                            <td class="text-center">${item.qty} ${item.unit}</td>
                            <td class="text-end text-muted">Rp ${new Intl.NumberFormat('id-ID').format(item.avg_price)}</td>
                            <td class="text-end pe-4 fw-bold">Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)}</td>
                        </tr>
                    `;
                });
                document.getElementById('hppTableBody').innerHTML = html;
                document.getElementById('hppTotalValue').innerText = `Rp ${new Intl.NumberFormat('id-ID').format(data.hpp)}`;
                
                new bootstrap.Modal(document.getElementById('hppDetailsModal')).show();
            });
    }

    function openIncomingModal(id, name) {
        // Simple shortcut: set the dropdown and go to incoming tab or show modal
        // For now let's just alert or redirect
        window.location.href = `?tab=incoming&ingredient_id=${id}`;
    }
</script>

<style>
    .bg-success-soft { background-color: rgba(34, 197, 94, 0.1); }
    .bg-danger-soft { background-color: rgba(239, 68, 68, 0.1); }
    .bg-warning-soft { background-color: rgba(251, 191, 36, 0.1); }
    .bg-primary-soft { background-color: rgba(59, 130, 246, 0.1); }
    .text-orange { color: #FF8C00; }
    .fw-black { font-weight: 900; }
    .btn-brand { background: var(--brand-gradient); color: #000; border: none; }
    .btn-brand:hover { opacity: 0.9; color: #000; }
</style>
@endsection
