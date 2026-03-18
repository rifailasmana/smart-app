@extends('layouts.dashboard')

@section('title', 'Majar Signature | Gudang & Stok')
@section('header_title', 'Gudang & Stok')
@section('header_subtitle', 'Manajemen bahan baku, stok, dan supplier Majar Signature')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #FF8C00;">
                <div class="small text-muted fw-bold">Total Item Bahan</div>
                <h3 class="fw-bold mb-0 text-dark">{{ $ingredients->count() }}</h3>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #ef4444;">
                <div class="small text-muted fw-bold">Perlu Restock</div>
                <h3 class="fw-bold mb-0 text-danger">{{ $ingredients->where('stock', '<=', 'min_stock')->count() }}</h3>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #FFC107;">
                <div class="small text-muted fw-bold">Supplier Aktif</div>
                <h3 class="fw-bold mb-0 text-brand">{{ $suppliers->count() }}</h3>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4" style="background: #fff; border-left: 5px solid #22c55e;">
                <div class="small text-muted fw-bold">Barang Masuk (Hari Ini)</div>
                <h3 class="fw-bold mb-0 text-success">5</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Panel: Tabs for Inventory & Recipes -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3">
                    <ul class="nav nav-pills card-header-pills" id="inventoryTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill px-4 fw-bold" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock-content" type="button" role="tab">
                                <i class="bi bi-box-seam me-2"></i> Stok Bahan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-4 fw-bold" id="recipes-tab" data-bs-toggle="tab" data-bs-target="#recipes-content" type="button" role="tab">
                                <i class="bi bi-journal-text me-2"></i> Resep Menu
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="inventoryTabsContent">
                    <!-- Tab 1: Stock Bahan -->
                    <div class="tab-pane fade show active" id="stock-content" role="tabpanel">
                        <div class="p-3 d-flex justify-content-between align-items-center bg-light border-bottom">
                            <h6 class="mb-0 fw-bold text-muted">Daftar Bahan Baku</h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-brand btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#adjustmentModal">
                                    <i class="fas fa-sliders-h me-1"></i> Adjustment
                                </button>
                                <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addIngredientModal">
                                    <i class="fas fa-plus me-1"></i> Tambah Bahan
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Nama Bahan</th>
                                        <th class="text-center">Stok</th>
                                        <th class="text-center">Satuan</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ingredients as $item)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">{{ $item->name }}</div>
                                            <small class="text-muted">HPP: Rp {{ number_format($item->avg_price, 0, ',', '.') }}</small>
                                        </td>
                                        <td class="text-center fw-bold text-dark">{{ number_format($item->stock, 2) }}</td>
                                        <td class="text-center"><span class="badge bg-light text-dark border rounded-pill px-3">{{ $item->unit }}</span></td>
                                        <td class="text-center">
                                            @if($item->stock <= $item->min_stock)
                                                <span class="badge bg-danger text-white rounded-pill px-3">LOW STOCK</span>
                                            @else
                                                <span class="badge bg-success text-white rounded-pill px-3">AMAN</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-light border rounded-circle" onclick="openUpdateStockModal({{ $item->id }}, '{{ $item->name }}')">
                                                <i class="fas fa-plus text-success"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Resep Menu -->
                    <div class="tab-pane fade" id="recipes-content" role="tabpanel">
                        <div class="p-3 bg-light border-bottom">
                            <h6 class="mb-0 fw-bold text-muted">Pengaturan Resep & Kalkulasi HPP</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Menu Item</th>
                                        <th class="text-center">Kategori</th>
                                        <th class="text-center">Harga Jual</th>
                                        <th class="text-center">Estimasi HPP</th>
                                        <th class="text-center">Margin (%)</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($menuItems as $item)
                                        @php
                                            $hpp = $item->recipes->sum(fn($r) => $r->ingredient->last_price * $r->quantity);
                                            $margin = $item->price > 0 ? (($item->price - $hpp) / $item->price) * 100 : 0;
                                        @endphp
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark">{{ $item->name }}</td>
                                            <td class="text-center small">{{ $item->category }}</td>
                                            <td class="text-center fw-bold">{{ number_format($item->price, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($hpp, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $margin > 30 ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }}">
                                                    {{ number_format($margin, 1) }}%
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="{{ route('inventory.recipes.index', $item->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    <i class="bi bi-journal-plus"></i> Atur Resep
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Suppliers & Quick Actions -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-truck me-2 text-orange" style="color: #FF8C00;"></i>Supplier Utama</h6>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($suppliers as $supplier)
                    <div class="list-group-item px-4 py-3 border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark">{{ $supplier->name }}</div>
                                <small class="text-muted"><i class="fas fa-phone me-1"></i> {{ $supplier->phone }}</small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill">Hubungi</button>
                        </div>
                    </div>
                    @endforeach
                    <div class="list-group-item px-4 py-3 border-0 bg-light text-center">
                        <button class="btn btn-sm btn-link text-decoration-none fw-bold" data-bs-toggle="modal" data-bs-target="#addSupplierModal">+ Tambah Supplier</button>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>Log Penggunaan Terakhir</h6>
                </div>
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-4 py-3 border-0">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-dark">Beras Putih</span>
                            <span class="text-danger">-5.0 kg</span>
                        </div>
                        <div class="text-muted small">Dipakai oleh: Kitchen (Pesanan #A123)</div>
                    </div>
                    <div class="list-group-item px-4 py-3 border-0">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-dark">Daging Ayam</span>
                            <span class="text-danger">-2.5 kg</span>
                        </div>
                        <div class="text-muted small">Dipakai oleh: Kitchen (Pesanan #A124)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Ingredient -->
<div class="modal fade" id="addIngredientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('inventory.ingredient.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Tambah Bahan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Bahan</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Beras Premium" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Satuan</label>
                            <input type="text" name="unit" class="form-control rounded-3" placeholder="kg, gr, pcs" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Stok Minimum</label>
                            <input type="number" name="min_stock" class="form-control rounded-3" value="10" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold">SIMPAN BAHAN</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
