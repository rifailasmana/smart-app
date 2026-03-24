@extends('layouts.dashboard')

@section('title', 'Majar Signature | Manager Dashboard')
@section('header_title', 'Manager Operating System')
@section('header_subtitle', 'Monitoring operasional, approval, dan kontrol tim')

@section('content')
    <div class="container-fluid py-4">
        @if ($tab === 'dashboard')
            <!-- 🏠 1. DASHBOARD (REAL-TIME BANGET) -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-brand">
                        <div class="small fw-bold text-dark opacity-75 uppercase">Penjualan Hari Ini</div>
                        <h2 class="fw-black mb-0">Rp {{ number_format($todaySales, 0, ',', '.') }}</h2>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                        <div class="small text-muted fw-bold uppercase">Order Aktif</div>
                        <h2 class="fw-black mb-0 text-orange">{{ $activeOrdersCount }} <small
                                class="fs-6 text-muted">Pesanan</small></h2>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="small text-muted fw-bold uppercase">Meja Terisi</div>
                            <button class="btn btn-sm btn-brand rounded-circle" data-bs-toggle="modal"
                                data-bs-target="#addTableModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <h2 class="fw-black mb-0 text-dark">{{ $occupiedTables }} / {{ $tables->count() }} <small
                                class="fs-6 text-muted">Meja</small></h2>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div
                        class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-danger-soft border-start border-danger border-4">
                        <div class="small text-danger fw-bold uppercase">Kitchen Delay</div>
                        <h2 class="fw-black mb-0 text-danger">{{ $delayedOrders->count() }} <small class="fs-6">Order >
                                15m</small></h2>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Live Order Status</h6>
                            <span class="badge bg-success-soft text-success rounded-pill px-3">Live Updates</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Order</th>
                                        <th>Meja</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($allActiveOrders->take(10) as $order)
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark">#{{ $order->code }}</td>
                                            <td>{{ $order->table ? $order->table->name : 'Takeaway' }}</td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill px-3 {{ $order->status === 'pending'
                                                        ? 'bg-warning-soft text-warning'
                                                        : ($order->status === 'preparing'
                                                            ? 'bg-primary-soft text-primary'
                                                            : 'bg-success-soft text-success') }}">
                                                    {{ strtoupper($order->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 small text-muted">
                                                {{ $order->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h6 class="fw-bold mb-4">Quick Alerts</h6>
                        @if ($lowStockCount > 0)
                            <div class="alert bg-warning-soft border-0 rounded-4 d-flex align-items-center mb-3">
                                <i class="fas fa-exclamation-triangle text-warning fs-4 me-3"></i>
                                <div>
                                    <div class="fw-bold text-dark">Stok Menipis</div>
                                    <div class="small text-muted">{{ $lowStockCount }} item perlu restock</div>
                                </div>
                            </div>
                        @endif
                        @if ($delayedOrders->count() > 0)
                            <div class="alert bg-danger-soft border-0 rounded-4 d-flex align-items-center mb-0">
                                <i class="fas fa-clock text-danger fs-4 me-3"></i>
                                <div>
                                    <div class="fw-bold text-dark">Pesanan Terlambat</div>
                                    <div class="small text-muted">{{ $delayedOrders->count() }} order di kitchen macet
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($tab === 'sales')
            <!-- 📊 2. SALES MONITORING -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Transaksi Hari Ini</h5>
                    <div class="d-flex gap-2">
                        @foreach ($paymentMethods as $pm)
                            <span class="badge bg-light text-dark border px-3 rounded-pill">
                                {{ strtoupper($pm->payment_method) }}: Rp {{ number_format($pm->total, 0, ',', '.') }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Waktu</th>
                                <th>Kode</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th class="text-end pe-4">Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($todayTransactions as $tx)
                                <tr>
                                    <td class="ps-4 small text-muted">{{ $tx->created_at->format('H:i') }}</td>
                                    <td class="fw-bold">#{{ $tx->code }}</td>
                                    <td class="small">{{ $tx->items->count() }} items</td>
                                    <td class="fw-bold">Rp {{ number_format($tx->total, 0, ',', '.') }}</td>
                                    <td><span
                                            class="badge bg-light text-dark border">{{ strtoupper($tx->payment_method) }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="small fw-bold">{{ $tx->kasir ? $tx->kasir->name : 'System' }}</div>
                                        <div class="text-muted" style="font-size: 10px;">Waiter:
                                            {{ $tx->waiter ? $tx->waiter->name : '-' }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($tab === 'menu')
            <!-- 🍽️ 3. MENU MANAGEMENT -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Management Ketersediaan Menu</h5>
                    <input class="form-control w-25 rounded-pill" type="text" placeholder="Cari menu...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nama Menu</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menuItems as $item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    </td>
                                    <td><span class="badge bg-light text-muted border">{{ $item->category }}</span></td>
                                    <td class="fw-bold text-dark">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge rounded-pill px-3 {{ $item->active ? 'bg-success text-white' : 'bg-danger text-white' }}">
                                            {{ $item->active ? 'TERSEDIA' : 'HABIS' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form action="{{ route('manager.menu.toggle', $item->id) }}" method="POST">
                                            @csrf
                                            <button
                                                class="btn btn-sm {{ $item->active ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill px-3"
                                                type="submit">
                                                {{ $item->active ? 'Set Habis' : 'Set Tersedia' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($tab === 'approval')
            <!-- ✅ 4. APPROVAL CENTER -->
            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-dark text-white py-4 px-4 border-0">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-warning"></i> Permintaan Restock
                                Gudang</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($pendingRestockRequests as $req)
                                <div class="list-group-item p-4 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">{{ $req->ingredient->name }}</h6>
                                            <div class="small text-muted">Dari: {{ $req->user->name }} •
                                                {{ $req->created_at->diffForHumans() }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-black text-orange fs-5">{{ number_format($req->quantity, 2) }}
                                                {{ $req->ingredient->unit }}</div>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-light rounded-3 mb-3 small italic">
                                        "{{ $req->notes ?: 'Tidak ada catatan' }}"
                                    </div>
                                    <div class="d-flex gap-2">
                                        <form class="flex-grow-1"
                                            action="{{ route('manager.restock.approve', $req->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-success w-100 rounded-3 fw-bold">APPROVE</button>
                                        </form>
                                        <form class="flex-grow-1"
                                            action="{{ route('manager.restock.reject', $req->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-outline-danger w-100 rounded-3 fw-bold">REJECT</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                            @if ($pendingRestockRequests->isEmpty())
                                <div class="p-5 text-center text-muted">
                                    <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                    Tidak ada permintaan restock tertunda
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-danger text-white py-4 px-4 border-0">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-shield-alt me-2"></i> Approval Void / Refund /
                                Discount</h5>
                        </div>
                        <div class="p-3">
                            <form method="GET" action="{{ route('dashboard.manager') }}">
                                <input name="tab" type="hidden" value="approval">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input class="form-control" name="q" value="{{ request('q') }}"
                                            placeholder="Search order, item, reason">
                                    </div>
                                    <div class="col-md-3">
                                        <input class="form-control" name="from" type="date"
                                            value="{{ request('from') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <input class="form-control" name="to" type="date"
                                            value="{{ request('to') }}">
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-primary w-100">Go</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($voids as $v)
                                <div class="list-group-item p-4 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-danger rounded-pill px-2 small mb-2">VOID</span>
                                        <span class="small text-muted">{{ $v->created_at->diffForHumans() }}</span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Order
                                        #{{ $v->order ? $v->order->code : $v->order_id }} @if ($v->order && $v->order->table)
                                            (Meja {{ $v->order->table->name }})
                                        @endif
                                    </h6>
                                    <p class="small text-muted mb-2">Item:
                                        {{ $v->menuItem ? $v->menuItem->name : ($v->orderItem ? $v->orderItem->name ?? '-' : '-') }}
                                        • Qty voided: {{ $v->qty }} (was {{ $v->prev_qty }})</p>
                                    @if ($v->reason)
                                        <div class="p-3 bg-light rounded-3 mb-3 small italic">"{{ $v->reason }}"</div>
                                    @endif
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-secondary flex-grow-1 rounded-3 fw-bold">Mark
                                            Reviewed</button>
                                        <button
                                            class="btn btn-sm btn-outline-danger flex-grow-1 rounded-3 fw-bold">Export</button>
                                    </div>
                                    <div class="small text-muted mt-2">Voided by:
                                        {{ $v->user ? $v->user->name : 'System' }}</div>
                                </div>
                            @empty
                                <div class="p-5 text-center text-muted">
                                    <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                    Tidak ada void request / history
                                </div>
                            @endforelse
                        </div>
                        <div class="p-3">
                            {{ $voids->appends(request()->except('page'))->links() }}
                        </div>
                        <div class="p-4 text-center">
                            <small class="text-muted italic">Riwayat void terakhir ditampilkan di sini.</small>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($tab === 'staff')
            <!-- 👥 5. STAFF MONITORING -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-0">
                    <h5 class="mb-0 fw-bold">Tim Bertugas Saat Ini</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Staff</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Performance Today</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($staffOnShift as $s)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3 bg-brand" style="width:40px;height:40px;">
                                                {{ strtoupper(substr($s->name, 0, 1)) }}</div>
                                            <div class="fw-bold text-dark">{{ $s->name }}</div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ strtoupper($s->role) }}</span>
                                    </td>
                                    <td><span class="badge bg-success rounded-pill px-3">ACTIVE</span></td>
                                    <td class="text-end pe-4 fw-bold">
                                        {{ $s->role === 'waiter' ? '12 Orders Served' : ($s->role === 'kasir' ? 'Rp 2.4M Collected' : '8 Items Cooked') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($tab === 'inventory')
            <!-- 📦 6. INVENTORY CONTROL (LIGHT) -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Monitoring Stok Gudang</h5>
                    <span class="badge bg-warning-soft text-warning rounded-pill px-3">View Only</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Item</th>
                                <th>Stok Saat Ini</th>
                                <th>Limit Alert</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ingredients as $ing)
                                <tr>
                                    <td class="ps-4 fw-bold text-dark">{{ $ing->name }}</td>
                                    <td class="fw-black">{{ number_format($ing->stock, 2) }} {{ $ing->unit }}</td>
                                    <td class="text-muted small">{{ number_format($ing->min_stock, 2) }}
                                        {{ $ing->unit }}</td>
                                    <td class="text-end pe-4">
                                        @if ($ing->stock <= 0)
                                            <span class="badge bg-danger text-white rounded-pill px-3">HABIS</span>
                                        @elseif($ing->stock <= $ing->min_stock)
                                            <span class="badge bg-warning text-dark rounded-pill px-3">MENIPIS</span>
                                        @else
                                            <span class="badge bg-success text-white rounded-pill px-3">AMAN</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($tab === 'coupon')
            <!-- 🎟️ 7. COUPON / DISCOUNT CONTROL -->
            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold mb-4">Buat Kupon Promo</h5>
                        <form action="{{ route('manager.coupon.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Kode Kupon</label>
                                <input class="form-control rounded-3" name="code" type="text"
                                    placeholder="Contoh: MAJAR20" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Besaran Diskon (%)</label>
                                <input class="form-control rounded-3" name="value" type="number"
                                    placeholder="Contoh: 10" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">Kategori Member</label>
                                <select class="form-select rounded-3" name="category_restriction">
                                    <option value="Regular">Regular</option>
                                    <option value="Reservation">Reservation</option>
                                    <option value="Majar Priority">Majar Priority</option>
                                    <option value="Majar Signature">Majar Signature</option>
                                </select>
                            </div>
                            <button class="btn btn-dark w-100 py-3 rounded-3 fw-bold" type="submit">BUAT KUPON
                                SEKARANG</button>
                        </form>
                    </div>
                </div>
                <div class="col-12 col-xl-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="mb-0 fw-bold">Daftar Kupon Aktif</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Kode</th>
                                        <th>Diskon</th>
                                        <th>Kategori</th>
                                        <th class="text-end pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($coupons as $c)
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark">{{ $c->code }}</td>
                                            <td class="fw-bold text-orange">{{ number_format($c->value, 0) }}%</td>
                                            <td><span
                                                    class="badge bg-light text-dark border">{{ $c->category_restriction }}</span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <span
                                                    class="badge {{ $c->is_used ? 'bg-danger-soft text-danger' : 'bg-success-soft text-success' }} rounded-pill px-3">
                                                    {{ $c->is_used ? 'EXPIRED/USED' : 'ACTIVE' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($tab === 'orders')
            <!-- 🔄 8. ORDER MONITORING -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Monitoring Bottleneck Kitchen & Service</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Order #</th>
                                <th>Meja</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Durasi Tunggu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allActiveOrders as $o)
                                <tr>
                                    <td class="ps-4 fw-bold">#{{ $o->code }}</td>
                                    <td>{{ $o->table ? $o->table->name : 'Takeaway' }}</td>
                                    <td>
                                        <div class="small text-muted">
                                            @foreach ($o->items->take(2) as $item)
                                                {{ $item->quantity }}x {{ $item->menuItem->name }},
                                            @endforeach
                                            {{ $o->items->count() > 2 ? '...' : '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge rounded-pill px-3 {{ $o->status === 'pending'
                                                ? 'bg-warning text-dark'
                                                : ($o->status === 'preparing'
                                                    ? 'bg-primary text-white'
                                                    : 'bg-success text-white') }}">
                                            {{ strtoupper($o->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span
                                            class="fw-bold {{ $o->created_at->diffInMinutes() > 20 ? 'text-danger' : 'text-dark' }}">
                                            {{ $o->created_at->diffInMinutes() }} Menit
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($tab === 'tables')
            <!-- 🪑 TABLE MANAGEMENT -->
            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-brand">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="fw-black mb-1">Layout & Manajemen Meja</h4>
                                <p class="mb-0 text-dark opacity-75">Klik meja untuk menghapus, atau gunakan tombol di
                                    bawah untuk tambah/gabung.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-dark px-4 rounded-pill fw-bold" data-bs-toggle="modal"
                                    data-bs-target="#addTableModal">
                                    <i class="fas fa-plus me-2"></i> Tambah Meja
                                </button>
                                <button class="btn btn-outline-dark px-4 rounded-pill fw-bold" data-bs-toggle="modal"
                                    data-bs-target="#mergeTableModal">
                                    <i class="fas fa-object-group me-2"></i> Gabung Meja
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="row g-3">
                        @foreach ($tables as $table)
                            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                <div
                                    class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 transition-all hover-shadow {{ $table->status === 'available'
                                        ? 'bg-white border-2 border-success-soft'
                                        : ($table->status === 'occupied'
                                            ? 'bg-light border-2 border-gray-200'
                                            : 'bg-orange-soft border-2 border-orange-200') }}">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div
                                            class="badge {{ $table->status === 'available'
                                                ? 'bg-success'
                                                : ($table->status === 'occupied'
                                                    ? 'bg-secondary'
                                                    : 'bg-warning') }} rounded-pill px-2 py-1 small">
                                            {{ strtoupper($table->status) }}
                                        </div>
                                        @if ($table->status === 'available')
                                            <form action="{{ route('manager.table.delete', $table->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus meja ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-link p-0 text-danger" type="submit">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="mb-3">
                                        <i
                                            class="fas fa-th-large fs-1 {{ $table->status === 'available' ? 'text-success' : 'text-muted' }}"></i>
                                    </div>
                                    <h5 class="fw-black mb-1 text-dark">{{ $table->name }}</h5>
                                    <p class="small text-muted mb-0">Kapasitas: {{ $table->seats }} Orang</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Modal Merge Meja -->
            <div class="modal fade" id="mergeTableModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4 shadow">
                        <form action="{{ route('manager.table.merge') }}" method="POST">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <h5 class="fw-bold">Gabungkan Dua Meja</h5>
                                <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="small text-muted mb-4">Meja yang digabung akan dihapus dan kapasitasnya
                                    ditambahkan ke meja utama.</p>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Meja Utama (Tetap Ada)</label>
                                    <select class="form-select rounded-3" name="main_table_id" required>
                                        @foreach ($tables->where('status', 'available') as $t)
                                            <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->seats }}
                                                kursi)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small fw-bold">Meja yang Digabung (Akan Dihapus)</label>
                                    <select class="form-select rounded-3" name="merge_table_id" required>
                                        @foreach ($tables->where('status', 'available') as $t)
                                            <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->seats }}
                                                kursi)</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button class="btn btn-brand w-100 py-3 rounded-3 fw-bold shadow-sm" type="submit">Proses
                                    Gabung Meja</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Tambah Meja -->
    <div class="modal fade" id="addTableModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form action="{{ route('manager.table.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold">Tambah Meja Baru</h5>
                        <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama/Nomor Meja</label>
                            <input class="form-control rounded-3" name="name" type="text"
                                placeholder="Contoh: Meja 15 atau VIP 1" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Kapasitas Kursi</label>
                            <input class="form-control rounded-3" name="seats" type="number" placeholder="Contoh: 4"
                                min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button class="btn btn-brand w-100 py-3 rounded-3 fw-bold shadow-sm" type="submit">Simpan
                            Meja</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .bg-brand {
            background: var(--brand-gradient);
            color: #000;
        }

        .bg-success-soft {
            background-color: rgba(34, 197, 94, 0.1);
        }

        .bg-danger-soft {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .bg-warning-soft {
            background-color: rgba(251, 191, 36, 0.1);
        }

        .bg-primary-soft {
            background-color: rgba(59, 130, 246, 0.1);
        }

        .bg-orange-soft {
            background-color: rgba(255, 140, 0, 0.1);
        }

        .bg-light-soft {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .text-orange {
            color: #FF8C00;
        }

        .fw-black {
            font-weight: 900;
        }

        .fw-bold {
            font-weight: 700;
        }

        .uppercase {
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .italic {
            font-style: italic;
        }
    </style>
@endsection
