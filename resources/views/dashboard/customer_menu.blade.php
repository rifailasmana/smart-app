<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Digital - Majar Signature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #FF8C00; --secondary: #FFC107; --success: #22c55e; --warning: #f59e0b; --danger: #ef4444; --dark: #1a1a1a; }
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .menu-header { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: #000; padding: 1rem; }
        .menu-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1.5rem; transition: transform 0.2s; }
        .menu-card:hover { transform: translateY(-5px); }
        .menu-item { padding: 1rem; border-bottom: 1px solid #e9ecef; }
        .menu-item:last-child { border-bottom: none; }
        .menu-name { font-weight: 600; color: var(--dark); margin-bottom: 0.5rem; }
        .menu-description { color: #6c757d; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .menu-price { font-weight: 600; color: var(--primary); font-size: 1.1rem; }
        .menu-actions { display: flex; gap: 0.5rem; align-items: center; }
        .quantity-control { display: flex; align-items: center; gap: 0.5rem; }
        .quantity-btn { background: var(--primary); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; font-weight: bold; }
        .order-summary { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; margin-top: 1rem; }
        .order-btn { background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; }
        .order-btn:hover { background: var(--secondary); }
        .empty-state { text-align: center; padding: 3rem; color: #6c757d; }
        .loading { display: inline-block; width: 20px; height: 20px; border: 3px solid var(--primary); border-radius: 50%; border-top: 3px solid var(--primary); border-bottom: 3px solid var(--primary); animation: spin 1s linear infinite; }
    </style>
</head>
<body>
    <div class="menu-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 d-flex align-items-center gap-2">
                    @if($warung->logo)
                        <div style="width:32px;height:32px;border-radius:8px;overflow:hidden;background:#0f172a;display:flex;align-items:center;justify-content:center;">
                            <img src="{{ asset('storage/' . $warung->logo) }}" alt="{{ $warung->name }}" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                    @endif
                    <h4 class="mb-0"><i class="fas fa-utensils me-2"></i>{{ $warung->name }}</h4>
                </div>
                <div class="col-md-8 text-end">
                    <h5>Meja: {{ $table->name ?? 'Takeaway' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            @if($menuItems->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-utensils fa-3x mb-3"></i>
                    <h5>Menu belum tersedia</h5>
                    <p>Restoran sedang menyiapkan menu</p>
                </div>
            @else
                @foreach($menuItems->groupBy('category') as $category => $items)
                    <div class="col-md-12 mb-4">
                        <h5 class="text-center mb-3">
                            @if($category == 'makanan')
                                <i class="fas fa-utensils"></i> MAKANAN
                            @elseif($category == 'minuman')
                                <i class="fas fa-mug-hot"></i> MINUMAN
                            @elseif($category == 'dessert')
                                <i class="fas fa-ice-cream"></i> DESSERT
                            @else
                                <i class="fas fa-utensils"></i> {{ strtoupper($category) }}
                            @endif
                        </h5>
                        <div class="row">
                            @foreach($items as $item)
                                <div class="col-md-6 col-lg-4">
                                    <div class="menu-card">
                                        <div class="menu-item">
                                            <div class="menu-name">{{ $item->name }}</div>
                                            <div class="menu-description">{{ $item->description }}</div>
                                            <div class="menu-actions">
                                                <div class="menu-price">Rp {{ number_format($item->price, 0) }}</div>
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-btn" onclick="updateQuantity('{{ $item->id }}', -1)">−</button>
                                                    <span class="mx-2" id="qty-{{ $item->id }}">0</span>
                                                    <button type="button" class="quantity-btn" onclick="updateQuantity('{{ $item->id }}', 1)">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Order Summary -->
    <div class="container mt-4">
        <div class="order-summary">
            <h5 class="text-center mb-3">Pesanan Anda</h5>
            <div class="row">
                <div class="col-md-6">
                    <strong>Total Items:</strong> <span id="total-items">0</span>
                </div>
                <div class="col-md-6">
                    <strong>Total:</strong> <span id="total-price">Rp 0</span>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <button class="order-btn" onclick="submitOrder()">
                <i class="fas fa-shopping-cart me-2"></i> Pesan Sekarang
            </button>
        </div>
    </div>

    <script>
        const menuPrices = @json($menuItems->pluck('price', 'id'));
        let orderItems = {};
        let totalItems = 0;
        let totalPrice = 0;

        function updateQuantity(itemId, change) {
            const qtyElement = document.getElementById('qty-' + itemId);
            const currentQty = parseInt(qtyElement.innerText);
            const newQty = Math.max(0, currentQty + change);
            
            qtyElement.innerText = newQty;
            
            if (newQty === 0) {
                delete orderItems[itemId];
            } else {
                orderItems[itemId] = newQty;
            }
            
            updateOrderSummary();
        }

        function updateOrderSummary() {
            totalItems = Object.values(orderItems).reduce((sum, qty) => sum + qty, 0);
            totalPrice = Object.entries(orderItems).reduce((sum, [id, qty]) => {
                const price = Number(menuPrices[id] || 0);
                return sum + price * qty;
            }, 0);
            
            document.getElementById('total-items').innerText = totalItems;
            document.getElementById('total-price').innerText = 'Rp ' + totalPrice.toLocaleString('id-ID');
        }

        function submitOrder() {
            if (totalItems === 0) {
                alert('Silakan pilih menu terlebih dahulu');
                return;
            }
            
            const orderData = {
                items: Object.entries(orderItems).map(([id, qty]) => ({
                    menu_id: id,
                    qty: qty
                })),
                table_id: '{{ $table->id ?? null }}',
                payment_method: 'kasir'
            };
            
            fetch('/order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pesanan berhasil! Kode: ' + data.code);
                    window.location.href = '/order-status?code=' + data.code + '&warung={{ $warung->slug }}';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memesan');
            });
        }
    </script>
</body>
</html>
