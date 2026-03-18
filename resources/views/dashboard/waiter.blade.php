@extends('layouts.dashboard')

@section('title', 'Majar Signature | Waiter POS')
@section('header_title', 'Waiter Service')
@section('header_subtitle', 'Kelola meja, pesanan, dan pelayanan pelanggan')

@section('content')
<style>
    :root {
        --waiter-primary: #FF8C00;
        --waiter-secondary: #FFC107;
    }
    .table-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 1rem;
    }
    .table-card {
        aspect-ratio: 1;
        background: #fff;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid #eee;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .table-card.available { border-color: #22c55e; background: #f0fdf4; }
    .table-card.occupied { border-color: #ef4444; background: #fef2f2; }
    .table-card.reserved { border-color: #f59e0b; background: #fffbeb; }
    .table-card:hover { transform: scale(1.05); }
    
    .category-pill {
        padding: 0.6rem 1.25rem;
        border-radius: 50px;
        background: #fff;
        color: #666;
        font-weight: 700;
        cursor: pointer;
        border: 2px solid #eee;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .category-pill.active {
        background: var(--waiter-primary);
        color: #fff;
        border-color: var(--waiter-primary);
    }
    .order-item-row {
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }
    .order-item-row:last-child { border-bottom: none; }
    
    .btn-waiter {
        border-radius: 12px;
        font-weight: 700;
        padding: 0.75rem 1.25rem;
    }
</style>

<div class="container-fluid">
    <!-- Category Selector -->
    <div class="d-flex gap-2 mb-4 overflow-auto pb-2">
        <div class="category-pill active" onclick="selectCategory('Regular', this)">Regular</div>
        <div class="category-pill" onclick="selectCategory('Reservation', this)">Reservation</div>
        <div class="category-pill" onclick="selectCategory('Majar Priority', this)">Majar Priority</div>
        <div class="category-pill" onclick="selectCategory('Majar Signature', this)">Majar Signature</div>
    </div>

    <div class="row g-4">
        <!-- Table Grid -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Denah Meja</h5>
                    <div class="small text-muted"><span class="badge bg-success me-1">●</span> Available <span class="badge bg-danger ms-2 me-1">●</span> Occupied</div>
                </div>
                <div class="table-grid">
                    @foreach($tables as $table)
                        <div class="table-card {{ $table->status }}" onclick="handleTableClick({{ json_encode($table) }})">
                            <div class="fw-bold fs-3 text-dark">{{ $table->name }}</div>
                            <div class="small text-muted">{{ $table->seats }} Seats</div>
                            <span class="badge bg-{{ $table->status === 'available' ? 'success' : 'danger' }} rounded-pill mt-2">
                                {{ strtoupper($table->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Active Orders List -->
            <h5 class="fw-bold mb-3 mt-4">Monitoring Pesanan Aktif</h5>
            <div class="row g-3">
                @foreach($activeOrders as $order)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3" style="border-left: 5px solid {{ $order->status === 'ready' ? '#22c55e' : '#FF8C00' }};">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="fw-bold text-dark">#{{ $order->code }}</div>
                            <span class="badge bg-light text-dark border">{{ strtoupper($order->status) }}</span>
                        </div>
                        <div class="small text-muted mb-2"><i class="fas fa-chair me-1"></i> Meja {{ $order->table->name ?? 'T.A' }} | {{ $order->category ?? 'Regular' }}</div>
                        <div class="order-items-mini mb-3">
                            @foreach($order->items->take(3) as $item)
                                <div class="small text-dark">{{ $item->qty }}x {{ $item->menu_name }}</div>
                            @endforeach
                        </div>
                        <div class="d-grid">
                            @if($order->status === 'ready')
                                <button class="btn btn-success btn-sm fw-bold" onclick="serveOrder({{ $order->id }})">SERVE NOW</button>
                            @else
                                <button class="btn btn-outline-dark btn-sm fw-bold" onclick="openOrderOptions({{ json_encode($order) }})">OPTIONS</button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Right Side: Recent Served -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-4">Baru Saja Diantar</h5>
                <div class="list-group list-group-flush">
                    @forelse($servedOrders->take(10) as $order)
                    <div class="list-group-item px-0 py-3 border-0 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark">#{{ $order->code }}</div>
                                <div class="small text-muted">Meja {{ $order->table->name ?? 'T.A' }} • {{ $order->updated_at->format('H:i') }}</div>
                            </div>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3" style="background: #e8f5e9;">SERVED</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">Belum ada pesanan terlayani</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Order Modal -->
<div class="modal fade" id="newOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Pesanan Baru: Meja <span id="modal-table-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-7">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control bg-light border-0" placeholder="Cari Menu..." id="menuSearch">
                        </div>
                        <div id="menuSelection" class="row g-2 overflow-auto" style="max-height: 400px;">
                            <!-- Menu items injected here -->
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="bg-light rounded-4 p-3 h-100">
                            <h6 class="fw-bold mb-3">Rincian Pesanan</h6>
                            <div id="cartItems" class="mb-4">
                                <div class="text-center py-5 text-muted small">Keranjang Kosong</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-waiter" onclick="sendToKitchen()">SEND TO KITCHEN</button>
                                <button class="btn btn-light btn-sm text-muted" data-bs-dismiss="modal">Batal</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table Options Modal -->
<div class="modal fade" id="orderOptionsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Opsi Meja & Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-grid gap-3">
                    <button class="btn btn-outline-dark py-3 rounded-4 text-start d-flex align-items-center" onclick="handleMoveTable()">
                        <i class="fas fa-exchange-alt fa-2x me-3 text-warning"></i>
                        <div>
                            <div class="fw-bold">Pindah Meja</div>
                            <div class="small text-muted">Pindahkan pesanan ke meja lain</div>
                        </div>
                    </button>
                    <button class="btn btn-outline-dark py-3 rounded-4 text-start d-flex align-items-center" onclick="handleMergeTable()">
                        <i class="fas fa-object-group fa-2x me-3 text-info"></i>
                        <div>
                            <div class="fw-bold">Gabung Meja</div>
                            <div class="small text-muted">Gabungkan dengan pesanan meja lain</div>
                        </div>
                    </button>
                    <button class="btn btn-outline-dark py-3 rounded-4 text-start d-flex align-items-center" onclick="handleRequestBill()">
                        <i class="fas fa-receipt fa-2x me-3 text-success"></i>
                        <div>
                            <div class="fw-bold">Request Bill</div>
                            <div class="small text-muted">Minta kasir cetak tagihan</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentCategory = 'Regular';
    let selectedTable = null;
    let cart = [];

    function selectCategory(cat, el) {
        currentCategory = cat;
        document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
    }

    function handleTableClick(table) {
        if(table.status === 'available') {
            selectedTable = table;
            document.getElementById('modal-table-name').innerText = table.name;
            new bootstrap.Modal(document.getElementById('newOrderModal')).show();
        } else {
            // Logic for occupied table options
        }
    }

    async function sendToKitchen() {
        if(cart.length === 0) return alert('Pilih menu terlebih dahulu');
        // Logic to send to kitchen
    }

    async function serveOrder(id) {
        if(!confirm('Tandai pesanan sudah diantar?')) return;
        const res = await fetch(`/order/${id}/serve`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        });
        if(await res.json()) location.reload();
    }
</script>
@endsection
