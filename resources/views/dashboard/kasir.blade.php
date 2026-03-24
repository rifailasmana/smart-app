@extends('layouts.dashboard')

@section('title', 'Majar Signature | Cashier POS')
@section('header_title', 'Point of Sale')
@section('header_subtitle', 'Kelola pembayaran, antrian, dan bill pelanggan')

@section('content')
<style>
    .pos-container {
        --pos-primary: var(--brand-orange);
        --pos-secondary: var(--brand-yellow);
        display: flex;
        gap: 1.5rem;
        min-height: 70vh;
    }
    .pos-left {
        flex: 1;
        overflow-y: auto;
        padding-right: 0.5rem;
    }
    .pos-right {
        width: 400px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
    }
    .menu-card {
        background: #fff;
        border-radius: 15px;
        padding: 1rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    }
    .menu-card:hover {
        transform: translateY(-5px);
        border-color: var(--pos-secondary);
    }
    .menu-card img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 12px;
        margin-bottom: 0.75rem;
    }
    .order-card {
        background: #fff;
        border-radius: 15px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        border-left: 5px solid var(--pos-primary);
        cursor: pointer;
        transition: all 0.2s;
    }
    .order-card:hover {
        background: #fff9f0;
    }
    .order-card.active {
        background: #fff3e0;
        border-color: var(--pos-secondary);
    }
    .cart-header {
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        background: #fff;
    }
    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
    }
    .cart-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    .cart-footer {
        padding: 1.5rem;
        background: #f8f9fa;
        border-top: 1px solid #eee;
    }
    .btn-pos {
        padding: 0.75rem;
        font-weight: 700;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .nav-pills-pos .nav-link {
        color: #6c757d;
        font-weight: 600;
        border-radius: 10px;
        padding: 0.6rem 1.25rem;
    }
    .nav-pills-pos .nav-link.active {
        background-color: var(--pos-primary);
        color: #fff;
    }

    @media (max-width: 992px) {
        .pos-container {
            flex-direction: column;
        }
        .pos-right {
            width: 100%;
        }
    }
</style>

<div class="container-fluid">
    <!-- Top Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center">
                <div class="bg-orange-subtle p-3 rounded-3 me-3" style="background: #fff3e0;"><i class="fas fa-wallet text-orange" style="color: #FF8C00;"></i></div>
                <div>
                    <div class="small text-muted">Revenue</div>
                    <div class="fw-bold">Rp {{ number_format($kasirDailyReport['revenue'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center">
                <div class="bg-yellow-subtle p-3 rounded-3 me-3" style="background: #fff9e6;"><i class="fas fa-shopping-bag text-yellow" style="color: #FFC107;"></i></div>
                <div>
                    <div class="small text-muted">Orders</div>
                    <div class="fw-bold">{{ $kasirDailyReport['orders'] ?? 0 }} Transaksi</div>
                </div>
            </div>
        </div>
    </div>

    <div class="pos-container">
        <!-- Left Side: Order Tabs & Menu -->
        <div class="pos-left">
            <ul class="nav nav-pills nav-pills-pos mb-4 gap-2" id="posTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#active-orders">Approval & Antrian <span class="badge bg-white text-dark ms-1">{{ $pendingOrders->count() + $inProgressOrders->count() }}</span></button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#payment-queue">Siap Bayar <span class="badge bg-white text-dark ms-1">{{ $verifiedOrders->count() }}</span></button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#outstanding-invoices">Piutang (Invoice) <span class="badge bg-white text-dark ms-1">{{ $outstandingInvoices->count() }}</span></button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#menu-grid">Buka Menu POS</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Tab: Active Orders -->
                <div class="tab-pane fade show active" id="active-orders">
                    <h6 class="fw-bold mb-3 text-muted uppercase small">Menunggu Approval</h6>
                    <div class="row g-3">
                        @foreach($pendingOrders as $order)
                        <div class="col-md-6">
                            <div class="order-card shadow-sm" onclick="selectOrder({{ json_encode($order) }})">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold text-dark">#{{ $order->code }}</div>
                                        <div class="small text-muted">{{ $order->customer_name ?? 'Guest' }} • Meja {{ $order->table->name ?? 'T.A' }}</div>
                                    </div>
                                    <span class="badge bg-warning text-dark">PENDING</span>
                                </div>
                                <div class="mt-2 small">Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <h6 class="fw-bold mt-4 mb-3 text-muted uppercase small">Dalam Proses Antrian</h6>
                    <div class="row g-3">
                        @foreach($inProgressOrders as $order)
                        <div class="col-md-6">
                            <div class="order-card shadow-sm" style="border-left-color: #FFC107;" onclick="selectOrder({{ json_encode($order) }})">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold text-dark">#{{ $order->code }}</div>
                                        <div class="small text-muted">Meja {{ $order->table->name ?? 'T.A' }}</div>
                                    </div>
                                    <span class="badge bg-info text-white">{{ strtoupper($order->status) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Tab: Siap Bayar -->
                <div class="tab-pane fade" id="payment-queue">
                    <div class="row g-3">
                        @foreach($verifiedOrders as $order)
                        <div class="col-md-6">
                            <div class="order-card shadow-sm" style="border-left-color: #FF8C00;" onclick="selectOrder({{ json_encode($order) }})">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold text-dark">#{{ $order->code }}</div>
                                        <div class="small text-muted">{{ $order->customer_name }} • Meja {{ $order->table->name ?? 'T.A' }}</div>
                                    </div>
                                    <span class="badge bg-success">READY TO PAY</span>
                                </div>
                                <div class="mt-2 fw-bold text-dark">Total: Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Tab: Outstanding Invoices -->
                <div class="tab-pane fade" id="outstanding-invoices">
                    <div class="row g-3">
                        @foreach($outstandingInvoices as $invoice)
                        <div class="col-md-6">
                            <div class="order-card shadow-sm" style="border-left-color: #f59e0b;" onclick="selectInvoice({{ json_encode($invoice) }})">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold text-dark">#{{ $invoice->order_code }}</div>
                                        <div class="small text-muted">{{ $invoice->customer_name }} • Meja {{ $invoice->table_number }}</div>
                                    </div>
                                    <span class="badge bg-warning text-dark">PIUTANG (UNPAID)</span>
                                </div>
                                <div class="mt-2 fw-bold text-dark">Tagihan: Rp {{ number_format($invoice->total, 0, ',', '.') }}</div>
                                <div class="small text-muted mt-1">Dibuat: {{ $invoice->created_at->format('d M, H:i') }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Tab: Menu Grid -->
                <div class="tab-pane fade" id="menu-grid">
                    <div class="menu-grid">
                        @foreach($menuItems as $menu)
                        <div class="menu-card shadow-sm" onclick="addToCart({{ json_encode($menu) }})">
                            @if($menu->image)
                                <img src="{{ $menu->image }}" alt="{{ $menu->name }}">
                            @else
                                <div class="bg-light rounded-3 mb-2 d-flex align-items-center justify-content-center" style="width:80px;height:80px;margin:0 auto;"><i class="fas fa-utensils text-muted"></i></div>
                            @endif
                            <div class="small fw-bold text-dark text-truncate">{{ $menu->name }}</div>
                            <div class="small fw-bold text-brand">Rp {{ number_format($menu->price, 0, ',', '.') }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Cart & Bill -->
        <div class="pos-right shadow">
            <div class="cart-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Current Bill</h5>
                    <span id="cart-order-code" class="badge bg-light text-dark border">New Order</span>
                </div>
            </div>
            
            <div class="cart-items" id="cart-items-list">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-25"></i>
                    <p>Pilih pesanan atau menu</p>
                </div>
            </div>

            <div class="cart-footer">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-bold" id="bill-subtotal">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tax / Service</span>
                    <span class="fw-bold" id="bill-tax">Rp 0</span>
                </div>
                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control" placeholder="Coupon Code" id="coupon-input">
                    <button class="btn btn-outline-dark" onclick="applyCouponPos()">Apply</button>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <h4 class="fw-bold">Total</h4>
                    <h4 class="fw-bold text-brand" id="bill-total">Rp 0</h4>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-pos" id="btn-pay" disabled onclick="handlePayment()">BAYAR SEKARANG</button>
                    <button class="btn btn-outline-warning btn-pos fw-bold" id="btn-invoice" disabled onclick="settleToInvoice()">SETTLE TO INVOICE</button>
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-outline-dark btn-sm w-100 fw-bold" onclick="handleSplit()">SPLIT BILL</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-danger btn-sm w-100 fw-bold" onclick="handleVoid()">VOID</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let selectedOrder = null;

    function selectInvoice(invoice) {
        selectedOrder = {
            id: invoice.order_id,
            code: invoice.order_code,
            total: invoice.total,
            subtotal: invoice.subtotal,
            admin_fee: invoice.admin_fee,
            status: 'invoice',
            items: invoice.items || []
        };
        
        document.getElementById('cart-order-code').innerText = 'INV#' + invoice.order_code;
        
        let itemsHtml = '';
        (invoice.items || []).forEach(item => {
            itemsHtml += `
                <div class="cart-item">
                    <div>
                        <div class="fw-bold text-dark">${item.menu_name}</div>
                        <small class="text-muted">${item.qty} x Rp ${item.price.toLocaleString('id-ID')}</small>
                    </div>
                    <div class="fw-bold">Rp ${(item.qty * item.price).toLocaleString('id-ID')}</div>
                </div>
            `;
        });
        
        if(itemsHtml === '') {
            itemsHtml = '<div class="text-center py-3 text-muted small">Detail item tidak tersedia</div>';
        }
        
        document.getElementById('cart-items-list').innerHTML = itemsHtml;
        document.getElementById('bill-subtotal').innerText = 'Rp ' + invoice.subtotal.toLocaleString('id-ID');
        document.getElementById('bill-tax').innerText = 'Rp ' + (invoice.admin_fee || 0).toLocaleString('id-ID');
        document.getElementById('bill-total').innerText = 'Rp ' + invoice.total.toLocaleString('id-ID');
        
        const btnPay = document.getElementById('btn-pay');
        btnPay.disabled = false;
        btnPay.innerText = 'BAYAR PELUNASAN';
        btnPay.onclick = () => processPayment(invoice.order_id, invoice.total);

        const btnInvoice = document.getElementById('btn-invoice');
        if(btnInvoice) btnInvoice.disabled = true;
    }

    function selectOrder(order) {
        selectedOrder = order;
        document.getElementById('cart-order-code').innerText = '#' + order.code;
        
        let itemsHtml = '';
        order.items.forEach(item => {
            itemsHtml += `
                <div class="cart-item">
                    <div>
                        <div class="fw-bold text-dark">${item.menu_name}</div>
                        <small class="text-muted">${item.qty} x Rp ${item.price.toLocaleString('id-ID')}</small>
                    </div>
                    <div class="fw-bold">Rp ${(item.qty * item.price).toLocaleString('id-ID')}</div>
                </div>
            `;
        });
        document.getElementById('cart-items-list').innerHTML = itemsHtml;
        
        document.getElementById('bill-subtotal').innerText = 'Rp ' + order.subtotal.toLocaleString('id-ID');
        document.getElementById('bill-tax').innerText = 'Rp ' + (order.admin_fee || 0).toLocaleString('id-ID');
        document.getElementById('bill-total').innerText = 'Rp ' + order.total.toLocaleString('id-ID');
        
        const btnPay = document.getElementById('btn-pay');
        btnPay.disabled = false;

        const btnInvoice = document.getElementById('btn-invoice');
        if(btnInvoice) btnInvoice.disabled = false;
        
        if(order.status === 'pending') {
            btnPay.innerText = 'APPROVE PAYMENT';
            btnPay.onclick = () => verifyPayment(order.id);
        } else if(order.status === 'served' || order.status === 'ready') {
            btnPay.innerText = 'MARK AS PAID';
            btnPay.onclick = () => processPayment(order.id, order.total);
        } else {
            btnPay.disabled = true;
            btnPay.innerText = 'IN PROGRESS';
        }
    }

    async function settleToInvoice() {
        if(!selectedOrder) return alert('Pilih pesanan terlebih dahulu');
        if(!confirm(`Pindahkan pesanan #${selectedOrder.code} ke Piutang (Invoice)? Meja akan langsung tersedia.`)) return;
        
        const res = await fetch(`/order/${selectedOrder.id}/invoice`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        });
        const data = await res.json();
        if(data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.error || 'Terjadi kesalahan');
        }
    }

    async function verifyPayment(id) {
        if(!confirm('Approve pembayaran ini?')) return;
        const res = await fetch(`/order/${id}/verify-payment`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        });
        if(await res.json()) location.reload();
    }

    async function processPayment(id, total) {
        if(!confirm(`Konfirmasi pembayaran Rp ${total.toLocaleString('id-ID')}?`)) return;
        const res = await fetch(`/order/${id}/payment`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        });
        if(await res.json()) location.reload();
    }

    function handleVoid() {
        if(!selectedOrder) return alert('Pilih pesanan terlebih dahulu');
        if(confirm('Void pesanan #' + selectedOrder.code + '?')) {
            // Add void logic
        }
    }

    function handleSplit() {
        if(!selectedOrder) return alert('Pilih pesanan terlebih dahulu');
        // Trigger split modal
    }
</script>
@endsection
