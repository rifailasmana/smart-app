@extends('layouts.dashboard')

@section('title', 'Majar Signature | Kitchen Display System')
@section('header_title', 'Kitchen Display System')
@section('header_subtitle', 'Pantau dan update status pesanan di dapur')

@section('content')
    <style>
        .kitchen-main {
            padding: 1rem 0;
        }
        .kitchen-order-card {
            background: #fff;
            border-top: 6px solid #FF8C00;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .kitchen-order-card.preparing {
            border-top-color: #FFC107;
        }
        .kitchen-order-code {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .priority-badge {
            font-size: 0.75rem;
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .kitchen-item-list {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
        }
        .kitchen-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 1rem;
            color: #333;
        }
        .kitchen-item:last-child {
            border-bottom: none;
        }
        .kitchen-btn-start {
            background: #FF8C00;
            color: #fff;
            border: none;
            font-weight: 700;
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .kitchen-btn-start:hover {
            background: #e67e00;
            transform: scale(1.02);
        }
        .kitchen-btn-ready {
            background: #22c55e;
            color: #fff;
            border: none;
            font-weight: 700;
            width: 100%;
            padding: 12px;
            border-radius: 12px;
        }
        .kitchen-btn-ready:hover {
            background: #16a34a;
        }
    </style>

    <div class="kitchen-main container-fluid">
        <div id="kitchenSseNotice" class="alert alert-warning d-none shadow-sm rounded-4 border-0 mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div><i class="fas fa-bell me-2"></i> Ada pesanan baru masuk!</div>
                <button type="button" class="btn btn-sm btn-dark rounded-pill px-3" onclick="window.location.reload()">Refresh Halaman</button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Verified Orders -->
            <div class="col-lg-6">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-orange p-2 rounded-3 me-3" style="background: #FF8C00;"><i class="fas fa-clock text-white"></i></div>
                    <h4 class="fw-bold mb-0">Antrian Pesanan Baru</h4>
                </div>
                <div id="verifiedList">
                    @forelse($verifiedOrders as $order)
                        <div class="kitchen-order-card" id="order-{{ $order->id }}">
                            <div class="kitchen-order-code">
                                <span>#{{ $order->code }}</span>
                                @php
                                    $priority = $order->category ?? 'Regular';
                                    $pColor = match($priority) {
                                        'Majar Signature' => 'bg-danger text-white',
                                        'Majar Priority' => 'bg-warning text-dark',
                                        'Reservation' => 'bg-primary text-white',
                                        default => 'bg-secondary text-white',
                                    };
                                @endphp
                                <span class="priority-badge {{ $pColor }}">{{ $priority }}</span>
                            </div>
                            <div class="text-muted small mb-2"><i class="fas fa-chair me-1"></i> Meja: {{ $order->table->name ?? 'Takeaway' }} | <i class="fas fa-user me-1"></i> {{ $order->customer_name ?? 'Pelanggan' }}</div>
                            
                            @if($order->notes)
                                <div class="alert alert-warning py-2 px-3 small mb-2 border-0"><i class="fas fa-sticky-note me-2"></i>{{ $order->notes }}</div>
                            @endif

                            <div class="kitchen-item-list">
                                @foreach($order->items as $item)
                                    <div class="kitchen-item d-flex justify-content-between align-items-center">
                                        <div><span class="fw-bold text-orange" style="color: #FF8C00;">{{ $item->qty }}x</span> {{ $item->menu_name }}</div>
                                        <span class="badge bg-light text-dark border">Pending</span>
                                    </div>
                                @endforeach
                            </div>
                            <button class="kitchen-btn-start" onclick="updateOrderStatus({{ $order->id }}, 'preparing')">
                                <i class="fas fa-fire me-2"></i> MULAI MASAK
                            </button>
                        </div>
                    @empty
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3 text-light"></i>
                            <p>Belum ada pesanan baru</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Preparing Orders -->
            <div class="col-lg-6">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-yellow p-2 rounded-3 me-3" style="background: #FFC107;"><i class="fas fa-fire text-dark"></i></div>
                    <h4 class="fw-bold mb-0">Sedang Dimasak</h4>
                </div>
                <div id="preparingList">
                    @forelse($preparingOrders as $order)
                        <div class="kitchen-order-card preparing" id="order-{{ $order->id }}">
                            <div class="kitchen-order-code">
                                <span>#{{ $order->code }}</span>
                                <span class="priority-badge {{ match($order->category ?? 'Regular') {
                                    'Majar Signature' => 'bg-danger text-white',
                                    'Majar Priority' => 'bg-warning text-dark',
                                    'Reservation' => 'bg-primary text-white',
                                    default => 'bg-secondary text-white',
                                } }}">{{ $order->category ?? 'Regular' }}</span>
                            </div>
                            <div class="text-muted small mb-2"><i class="fas fa-chair me-1"></i> Meja: {{ $order->table->name ?? 'Takeaway' }}</div>

                            <div class="kitchen-item-list">
                                @foreach($order->items as $item)
                                    <div class="kitchen-item d-flex justify-content-between align-items-center">
                                        <div><span class="fw-bold text-orange" style="color: #FF8C00;">{{ $item->qty }}x</span> {{ $item->menu_name }}</div>
                                        <div class="d-flex gap-2">
                                            @if(($item->status ?? 'pending') != 'ready')
                                            <button class="btn btn-sm btn-success rounded-pill px-3" onclick="updateItemStatus({{ $item->id }}, 'ready', this)">Ready</button>
                                            @else
                                            <span class="badge bg-success">Selesai</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button class="kitchen-btn-ready" onclick="updateOrderStatus({{ $order->id }}, 'ready')">
                                <i class="fas fa-check-double me-2"></i> SEMUA SIAP (READY)
                            </button>
                        </div>
                    @empty
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                            <i class="fas fa-utensils fa-3x mb-3 text-light"></i>
                            <p>Tidak ada masakan aktif</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        async function updateOrderStatus(orderId, newStatus) {
            try {
                const response = await fetch(`/order/${orderId}/status`, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Gagal update: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Terjadi kesalahan koneksi');
            }
        }

        async function updateItemStatus(itemId, newStatus, btn) {
            try {
                const response = await fetch(`/order-items/${itemId}/status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await response.json();
                if (data.success) {
                    btn.parentNode.innerHTML = '<span class="badge bg-success">Selesai</span>';
                }
            } catch (e) {}
        }
    </script>
@endsection
