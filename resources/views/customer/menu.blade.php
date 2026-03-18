<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesan Sekarang - Majar Signature</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#FF8C00">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/1046/1046747.png">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Majar Signature">
    <style>

        :root {
            --primary: #FF8C00;
            --primary-dark: #E67E00;
            --secondary: #FFC107;
            --accent: #FFC107;
            --dark: #1a1a1a;
            --light: #f3f4f6;
        }

        body {
            background: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--dark);
        }

        .menu-header {
            background: transparent;
            color: var(--dark);
            padding: 16px 0 10px;
            margin-bottom: 0;
        }

        .menu-header-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 12px 16px;
            color: #1a1a1a;
        }

        .menu-header h1 {
            font-weight: 800;
            margin-bottom: 0;
        }

        .table-info {
            padding: 6px 14px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            border: 1px solid #e5e7eb;
            background: #f1f5f9;
            color: #4b5563;
        }

        .category-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 12px 16px 8px;
            margin-bottom: 18px;
        }

        .category-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .category-btn {
            padding: 6px 12px;
            border-radius: 999px;
            border: none;
            background: #e5e7eb;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            color: #374151;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .category-btn.active {
            background: var(--primary);
            color: #f9fafb;
            box-shadow: 0 10px 30px rgba(255, 140, 0, 0.25);
        }

        .category-btn:hover {
            background: #e5e7eb;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 40px;
        }

        .category-separator {
            grid-column: 1 / -1;
            border-bottom: 2px solid #eee;
            padding-bottom: 6px;
            margin-top: 20px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            color: var(--dark);
        }

        .category-separator-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .category-separator-label i {
            color: var(--primary);
        }

        .menu-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.25s ease;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
            border-color: rgba(59,130,246,0.45);
        }

        .menu-image {
            width: 100%;
            height: 180px;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 2.5rem;
        }

        .menu-content {
            padding: 15px;
        }

        .menu-name {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .menu-desc {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 10px;
            line-height: 1.3;
            min-height: 40px;
        }

        .menu-price {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .quantity-selector {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            background: #e5f0ff;
            border-radius: 999px;
            padding: 4px;
        }

        .qty-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: white;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 700;
            color: #1f2937;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .qty-input {
            width: 28px;
            border: none;
            background: transparent;
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .add-to-cart-btn {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            background: white;
            color: #2563eb;
            border: 1px solid rgba(148,163,184,0.6);
            cursor: pointer;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .add-to-cart-btn:hover {
            background: #2563eb;
            color: #f9fafb;
            border-color: #2563eb;
            box-shadow: 0 10px 25px rgba(37,99,235,0.35);
        }

        /* Order Summary Sidebar */
        .order-summary {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .order-summary.collapsed {
            max-width: 60px;
            padding: 15px 10px;
        }

        .menu-layout {
            max-width: 1120px;
            margin: 0 auto 40px;
            padding: 0 16px;
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.05fr);
            gap: 24px;
        }

        .menu-left {
            min-width: 0;
        }

        .menu-right {
            min-width: 0;
        }

        .order-items {
            max-height: 260px;
            overflow-y: auto;
            padding: 10px 16px 0;
        }

        .order-item {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-item-info {
            flex: 1;
        }

        .order-item-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .order-item-price {
            color: var(--primary);
            font-weight: 700;
        }

        .order-summary-footer {
            padding: 16px 16px 20px;
            border-top: 1px solid #e5e7eb;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .order-total .price {
            color: var(--primary);
        }

        .checkout-btn {
            width: 100%;
            padding: 11px 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            background: var(--primary-dark);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
        }

        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 1200px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .menu-layout {
                grid-template-columns: 1fr;
            }

            .menu-header {
                padding: 20px 0;
            }

            .menu-header h1 {
                font-size: 1.5rem;
            }

            .menu-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .menu-card {
                border-radius: 10px;
            }

            .menu-image {
                height: 110px;
                font-size: 2rem;
            }

            .menu-content {
                padding: 10px;
            }

            .menu-name {
                font-size: 0.9rem;
            }

            .menu-price {
                font-size: 1rem;
            }

            .quantity-selector {
                gap: 4px;
            }

            .order-summary-header h5 {
                font-size: 0.95rem;
            }
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'ui-sans-serif', 'sans-serif'],
                    },
                    colors: {
                        primary: '#FF8C00',
                        primarySoft: '#fff3e0',
                        accent: '#FFC107',
                    },
                    boxShadow: {
                        soft: '0 10px 30px rgba(15, 23, 42, 0.08)',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans min-h-screen">
    <div class="menu-header">
        <div class="menu-layout" style="padding:0 16px;">
            <div class="menu-header-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        @if($warung->logo)
                            <div style="width: 40px; height: 40px; border-radius: 12px; overflow: hidden; background: #FF8C00; display: flex; align-items: center; justify-content: center;">
                                <img src="{{ asset('storage/' . $warung->logo) }}" alt="{{ $warung->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @else
                            <div style="width: 40px; height: 40px; border-radius: 12px; background: #fff3e0; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; color: #FF8C00;">
                                {{ strtoupper(mb_substr($warung->name,0,1)) }}
                            </div>
                        @endif
                        <div>
                            <div style="font-weight: 600; font-size: 0.95rem; color:#1a1a1a;">{{ $warung->name }}</div>
                            <div style="font-size: 0.8rem; color: #6b7280;">
                                Menu digital • Bayar di kasir atau via QR
                            </div>
                        </div>
                    </div>
                    <div class="d-none d-sm-flex align-items-center gap-2" style="font-size: 0.8rem;">
                        <div class="table-info" style="background:#fff7e6; border-color:#ffe5c2; color:#7a3c00;">
                            <span>Buka • 10.00–23.00</span>
                        </div>
                        <div class="table-info" style="background:#f3f4f6; border-color:#e5e7eb; color:#4b5563;">
                            <span>Meja: {{ $table->name ?? 'Belum dipilih' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="menu-layout">
        <div class="menu-left">
        @if(isset($myOrders) && $myOrders->count() > 0)
            <div class="mb-4">
                <h4 style="font-weight: 700; margin-bottom: 10px;">
                    <i class="fas fa-receipt"></i> Pesanan Saya (On Progress)
                </h4>
                <div class="list-group">
                    @foreach($myOrders as $order)
                        @php
                            $statusLabel = match ($order->status) {
                                'pending' => 'Pesanan Diterima',
                                'verified' => 'Pembayaran Terverifikasi',
                                'preparing' => 'Sedang Dimasak',
                                'ready' => 'Siap Antar',
                                'served' => 'Sudah Diantar',
                                default => strtoupper($order->status),
                            };
                            $statusClass = match ($order->status) {
                                'pending' => 'badge bg-danger',
                                'verified' => 'badge bg-primary',
                                'preparing' => 'badge bg-warning text-dark',
                                'ready' => 'badge bg-success',
                                'served' => 'badge bg-secondary',
                                default => 'badge bg-dark',
                            };
                            $statusUrl = url('/order-status') . '?code=' . $order->code . '&warung=' . $warung->code;
                        @endphp
                        <a href="{{ $statusUrl }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <div style="font-weight: 700;">{{ $order->code }}</div>
                                <div style="font-size: 0.85rem; color: #6c757d;">
                                    Antrian {{ $order->queue_number ?? '-' }} • {{ $order->table->name ?? 'Takeaway' }}
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                <div style="font-size: 0.9rem; font-weight: 700; margin-top: 4px;">
                                    Rp {{ number_format($order->total, 0, ',', '.') }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="category-card">
            <div style="font-size:0.9rem; font-weight:600; color:#111827; margin-bottom:6px;">Kategori</div>
            <div class="category-tabs">
                <button class="category-btn active" onclick="filterCategory('all')">
                    Makanan
                </button>
                <button class="category-btn" onclick="filterCategory('minuman')">
                    Minuman
                </button>
                <button class="category-btn" onclick="filterCategory('dessert')">
                    Dessert
                </button>
                <button class="category-btn" onclick="filterCategory('promo')">
                    Promo
                </button>
            </div>
        </div>

        @php
            $bestSellerItems = $menuItems->filter(function ($item) {
                return property_exists($item, 'is_best_today') && $item->is_best_today;
            });
            $nonBestItems = $menuItems->reject(function ($item) {
                return property_exists($item, 'is_best_today') && $item->is_best_today;
            });
            $categoryLabels = [
                'makanan' => 'Makanan',
                'minuman' => 'Minuman',
                'dessert' => 'Dessert',
                'promo' => 'Promo',
            ];
            $placeholderImages = [
                'makanan' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
                'minuman' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&w=900&q=80',
                'dessert' => 'https://images.unsplash.com/photo-1541781774459-bb2af2f05b55?auto=format&fit=crop&w=900&q=80',
                'promo' => 'https://images.unsplash.com/photo-1536964549204-655d2a431434?auto=format&fit=crop&w=900&q=80',
                'default' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
            ];
        @endphp

        <div style="margin-bottom:10px;">
            <div style="font-weight:700; font-size:1rem; margin-bottom:4px;">Menu Favorit Keluarga</div>
            <div style="font-size:0.85rem; color:#6b7280;">Pilih menu, lalu cek pesanan di sisi kanan.</div>
        </div>

        <div class="menu-grid" id="menuGrid">
            @if($bestSellerItems->count() > 0)
                <div class="category-separator" data-category="best">
                    <div class="category-separator-label">
                        <i class="fas fa-fire"></i>
                        <span>Best Seller Hari Ini</span>
                    </div>
                    <span class="badge bg-warning text-dark">{{ $bestSellerItems->count() }} menu</span>
                </div>
                @foreach($bestSellerItems as $item)
                    <div class="menu-card" data-category="{{ $item->category }}" data-active="{{ $item->active ? '1' : '0' }}" data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-price="{{ $item->price }}">
                        <div class="menu-image" style="position: relative;">
                            @if($item->image)
                                <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                @php
                                    $placeholderCategory = $item->category ?? 'default';
                                    $placeholderUrl = $placeholderImages[$placeholderCategory] ?? $placeholderImages['default'];
                                @endphp
                                <img src="{{ $placeholderUrl }}" alt="{{ $item->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @endif
                            <div style="position: absolute; top: 10px; left: 10px; background: rgba(255, 193, 7, 0.95); color: #212529; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                                <i class="fas fa-fire"></i> Best Seller Hari Ini
                            </div>
                            @if(!$item->active)
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(220, 53, 69, 0.9); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                                    <i class="fas fa-times-circle"></i> HABIS
                                </div>
                            @endif
                        </div>
                        <div class="menu-content">
                            <div class="menu-name">{{ $item->name }}</div>
                            <div class="menu-desc">{{ $item->description ?? '-' }}</div>
                            @if(isset($item->promo_aktif) && $item->promo_aktif && isset($item->harga_promo) && $item->harga_promo > 0)
                                <div class="menu-price">
                                    <span class="text-decoration-line-through text-muted" style="font-size: 0.9rem; font-weight: normal;">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                    <span class="text-danger ms-1">Rp {{ number_format($item->harga_promo, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="menu-price">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                            @endif
                            @if($item->active)
                                <div class="quantity-selector">
                                    <button class="qty-btn" onclick="decreaseQty(this)">-</button>
                                    <input type="number" class="qty-input" value="0" min="0" readonly>
                                    <button class="qty-btn" onclick="increaseQty(this)">+</button>
                                </div>
                                <button class="add-to-cart-btn" onclick="addToCart(this, {{ $item->id }}, '{{ $item->name }}', {{ $item->price }})">
                                    +
                                </button>
                            @else
                                <button class="add-to-cart-btn" disabled style="background: #ccc; cursor: not-allowed;">
                                    <i class="fas fa-ban"></i> Stok Habis
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

            @php $currentCategory = null; @endphp
            @forelse($nonBestItems as $item)
                @if($currentCategory !== $item->category)
                    @php $currentCategory = $item->category; @endphp
                    <div class="category-separator" data-category="{{ $item->category }}">
                        <div class="category-separator-label">
                            <i class="fas fa-utensils"></i>
                            <span>
                                {{ $categoryLabels[$item->category] ?? ucfirst($item->category ?? 'Lainnya') }}
                            </span>
                        </div>
                    </div>
                @endif
                <div class="menu-card" data-category="{{ $item->category }}" data-active="{{ $item->active ? '1' : '0' }}" data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-price="{{ $item->price }}">
                    <div class="menu-image" style="position: relative;">
                        @if($item->image)
                            <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            @php
                                $placeholderCategory = $item->category ?? 'default';
                                $placeholderUrl = $placeholderImages[$placeholderCategory] ?? $placeholderImages['default'];
                            @endphp
                            <img src="{{ $placeholderUrl }}" alt="{{ $item->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @endif
                        @if(!$item->active)
                            <div style="position: absolute; top: 10px; right: 10px; background: rgba(220, 53, 69, 0.9); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                                <i class="fas fa-times-circle"></i> HABIS
                            </div>
                        @endif
                    </div>
                    <div class="menu-content">
                        <div class="menu-name">{{ $item->name }}</div>
                        <div class="menu-desc">{{ $item->description ?? '-' }}</div>
                        @if(isset($item->promo_aktif) && $item->promo_aktif && isset($item->harga_promo) && $item->harga_promo > 0)
                            <div class="menu-price">
                                <span class="text-decoration-line-through text-muted" style="font-size: 0.9rem; font-weight: normal;">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                <span class="text-danger ms-1">Rp {{ number_format($item->harga_promo, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div class="menu-price">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                        @endif
                        @if($item->active)
                            <div class="quantity-selector">
                                <button class="qty-btn" onclick="decreaseQty(this)">-</button>
                                <input type="number" class="qty-input" value="0" min="0" readonly>
                                <button class="qty-btn" onclick="increaseQty(this)">+</button>
                            </div>
                            <button class="add-to-cart-btn" onclick="addToCart(this, {{ $item->id }}, '{{ $item->name }}', {{ $item->price }})">
                                +
                            </button>
                        @else
                            <button class="add-to-cart-btn" disabled style="background: #ccc; cursor: not-allowed;">
                                <i class="fas fa-ban"></i> Stok Habis
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                    <p><i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i></p>
                    <p>Menu belum tersedia. Silakan hubungi restoran.</p>
                </div>
            @endforelse
        </div>
        </div>

        <div class="menu-right">
            <div class="order-summary" id="orderSummary">
                <div style="padding: 16px 16px 8px;">
                    <h5 style="margin:0; font-weight:600; font-size:0.95rem; color:#1a1a1a;">
                        <i class="fas fa-shopping-cart"></i> Pesanan Anda
                    </h5>
                    <p style="margin:4px 0 0; font-size:0.8rem; color:#6b7280;">
                        Pilih meja terlebih dahulu sebelum konfirmasi.
                    </p>
                </div>
                <div class="order-items" id="orderItems" style="display: block;">
                    <div style="text-align: center; color: #9ca3af; padding: 20px 0; font-size:0.8rem;">
                        <p><i class="fas fa-inbox"></i></p>
                        <p>Belum ada pesanan. Tambahkan menu dengan tombol "+".</p>
                    </div>
                </div>
                <div class="order-summary-footer" id="summaryFooter" style="display: none;">
                    <div class="d-flex justify-content-between mb-2" style="font-size: 0.9rem; color: #6b7280;">
                        <span>Subtotal:</span>
                        <span id="subtotalPrice">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3" style="font-size: 0.9rem; color: #6b7280;">
                        <span>Biaya Admin (1%):</span>
                        <span id="adminFeePrice">Rp 0</span>
                    </div>
                    <div class="order-total">
                        <span>Total:</span>
                        <span class="price" id="totalPrice">Rp 0</span>
                    </div>
                    <form id="orderForm" method="POST" action="{{ route('order.store') }}">
                        @csrf
                        @if(request()->attributes->get('warung'))
                        @else
                            <input type="hidden" name="warung_id" value="{{ $warung->id }}">
                        @endif
                        @if($table)
                            <input type="hidden" name="table_id" value="{{ $table->id }}">
                        @else
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 600;">Nomor Meja / Takeaway</label>
                                <select name="table_id" class="form-select">
                                    <option value="">Takeaway (tanpa meja)</option>
                                    @foreach($tables as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <input type="hidden" name="secure" value="{{ request()->get('secure') }}">
                        <input type="hidden" id="orderItemsInput" name="items" value="[]">
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">Nama Customer</label>
                            <input type="text" class="form-control" name="customer_name" placeholder="Contoh: Budi / Keluarga Andi" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">Nomor WhatsApp (Opsional)</label>
                            <input type="text" class="form-control" name="customer_phone" placeholder="Contoh: 08xx-xxxx-xxxx" style="margin-bottom: 10px;">
                            <small class="text-muted">Nomor WhatsApp opsional — hanya untuk terima notifikasi &amp; struk otomatis.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">Catatan Pesanan (Opsional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Misal: pedas dikit, jangan bawang"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">Kategori Pesanan</label>
                            <select name="category" class="form-select rounded-3" required>
                                <option value="Regular" selected>Regular</option>
                                <option value="Reservation">Reservation</option>
                                <option value="Majar Priority">Majar Priority</option>
                                <option value="Majar Signature">Majar Signature</option>
                            </select>
                            <small class="text-muted">Pilih kategori layanan Anda.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">Metode Pembayaran</label>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_method" value="kasir" checked style="margin-right: 5px;">
                                    <i class="fas fa-cash-register"></i> Bayar di Kasir
                                </label>
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_method" value="qris" style="margin-right: 5px;">
                                    <i class="fas fa-qrcode"></i> QRIS
                                </label>
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_method" value="gateway" style="margin-right: 5px;">
                                    <i class="fas fa-credit-card"></i> Dompet Digital
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="walletProviderSection" style="display: none;">
                            <label class="form-label" style="font-weight: 600;">Pilih Dompet Digital</label>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_channel" value="dana" style="margin-right: 5px;">
                                    DANA
                                </label>
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_channel" value="shopeepay" style="margin-right: 5px;">
                                    ShopeePay
                                </label>
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_channel" value="gopay" style="margin-right: 5px;">
                                    GoPay
                                </label>
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_channel" value="ovo" style="margin-right: 5px;">
                                    OVO
                                </label>
                                <label style="flex: 1; min-width: 100px;">
                                    <input type="radio" name="payment_channel" value="linkaja" style="margin-right: 5px;">
                                    LinkAja
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info" id="feeAlert" style="margin-bottom: 15px; font-size: 0.85rem;">
                            <i class="fas fa-info-circle"></i> Biaya admin 1% akan ditambahkan pada total tagihan
                        </div>

                        <button type="submit" class="checkout-btn" id="checkoutBtn" disabled>
                            <i class="fas fa-check-circle"></i> Konfirmasi Pesanan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const cart = {};
        const priceMap = {};
        const nameMap = {};
        const OFFLINE_KEY = 'offline_orders';

        function loadOfflineOrders() {
            try {
                const raw = localStorage.getItem(OFFLINE_KEY);
                if (!raw) return [];
                const parsed = JSON.parse(raw);
                if (!Array.isArray(parsed)) return [];
                return parsed;
            } catch (e) {
                return [];
            }
        }

        function saveOfflineOrders(list) {
            try {
                localStorage.setItem(OFFLINE_KEY, JSON.stringify(list));
            } catch (e) {
            }
        }

        function enqueueOfflineOrder(payload, orderRoute) {
            const list = loadOfflineOrders();
            list.push({
                warung_code: '{{ $warung->code }}',
                orderRoute: orderRoute,
                payload: payload,
                created_at: new Date().toISOString()
            });
            saveOfflineOrders(list);
        }

        async function syncOfflineOrders() {
            if (!navigator.onLine) return;
            const list = loadOfflineOrders();
            if (!list.length) return;
            const remaining = [];
            for (const item of list) {
                if (item.warung_code !== '{{ $warung->code }}') {
                    remaining.push(item);
                    continue;
                }
                const payload = item.payload || {};
                const formData = new FormData();
                Object.keys(payload).forEach(key => {
                    formData.append(key, payload[key]);
                });
                try {
                    const response = await fetch(item.orderRoute || '{{ route("order.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (!data || !data.success) {
                        remaining.push(item);
                    }
                } catch (e) {
                    remaining.push(item);
                }
            }
            saveOfflineOrders(remaining);
        }

        window.addEventListener('online', function() {
            syncOfflineOrders();
        });

        window.addEventListener('load', function() {
            syncOfflineOrders();
        });

        function filterCategory(category) {
            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.category-btn').classList.add('active');

            document.querySelectorAll('.menu-card').forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            document.querySelectorAll('.category-separator').forEach(sep => {
                const sepCategory = sep.dataset.category;
                if (category === 'all') {
                    sep.style.display = '';
                } else if (sepCategory === category) {
                    sep.style.display = '';
                } else if (sepCategory === 'best' && category === 'all') {
                    sep.style.display = '';
                } else {
                    sep.style.display = 'none';
                }
            });
        }

        function increaseQty(btn) {
            const menuCard = btn.closest('.menu-card');
            if (menuCard.dataset.active === '0') {
                alert('Menu ini sedang habis stok!');
                return;
            }

            const input = btn.previousElementSibling;
            let qty = parseInt(input.value) || 0;
            qty += 1;
            input.value = qty;
        }

        function decreaseQty(btn) {
            const input = btn.nextElementSibling;
            let qty = parseInt(input.value) || 0;
            if (qty === 0) {
                return;
            }

            qty -= 1;
            input.value = qty;
        }

        function addToCart(btn, itemId, name, price) {
            const menuCard = btn.closest('.menu-card');
            if (menuCard.dataset.active === '0') {
                alert('Menu ini sedang habis stok!');
                return;
            }

            const qtyInput = menuCard.querySelector('.qty-input');
            const qty = parseInt(qtyInput.value) || 0;

            if (qty === 0) {
                alert('Pilih jumlah pesanan terlebih dahulu!');
                return;
            }

            cart[itemId] = qty;
            priceMap[itemId] = price;
            nameMap[itemId] = name;

            updateOrderSummary();
            
            btn.textContent = '✓ Ditambahkan';
            btn.style.background = '#4ecdc4';
            setTimeout(() => {
                btn.textContent = '+';
                btn.style.background = 'var(--primary)';
            }, 1500);
        }

        function updateOrderSummary() {
            const orderItemsDiv = document.getElementById('orderItems');
            const itemIds = Object.keys(cart);

            if (itemIds.length === 0) {
                orderItemsDiv.innerHTML = `
                    <div style="text-align: center; color: #999; padding: 20px 0;">
                        <p><i class="fas fa-inbox"></i></p>
                        <p style="font-size: 0.9rem;">Belum ada pesanan</p>
                    </div>
                `;
                document.getElementById('summaryFooter').style.display = 'none';
                document.getElementById('checkoutBtn').disabled = true;
                return;
            }

            let html = '';
            let total = 0;

            itemIds.forEach(id => {
                const qty = cart[id];
                const price = priceMap[id];
                const itemTotal = qty * price;
                total += itemTotal;

                html += `
                    <div class="order-item">
                        <div class="order-item-info">
                            <div class="order-item-name">${nameMap[id]}</div>
                            <div style="font-size: 0.85rem; color: #999;">
                                ${qty}x Rp ${number_format(price, 0, ',', '.')}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="order-item-price">Rp ${number_format(itemTotal, 0, ',', '.')}</div>
                            <button style="background: none; border: none; color: #ccc; cursor: pointer; font-size: 0.8rem;" onclick="removeFromCart(${id})">
                                Hapus
                            </button>
                        </div>
                    </div>
                `;
            });

            const adminFee = Math.round(total * 0.01);
            const grandTotal = total + adminFee;

            orderItemsDiv.innerHTML = html;
            document.getElementById('summaryFooter').style.display = 'block';
            document.getElementById('subtotalPrice').textContent = 'Rp ' + number_format(total, 0, ',', '.');
            document.getElementById('adminFeePrice').textContent = 'Rp ' + number_format(adminFee, 0, ',', '.');
            document.getElementById('totalPrice').textContent = 'Rp ' + number_format(grandTotal, 0, ',', '.');
            document.getElementById('checkoutBtn').disabled = false;
            document.getElementById('orderItemsInput').value = JSON.stringify(Object.keys(cart).map(id => ({
                menu_id: id,
                qty: cart[id],
                price: priceMap[id]
            })));
        }

        function removeFromCart(itemId) {
            const menuCard = document.querySelector('.menu-card[data-id="' + itemId + '"]');
            if (menuCard) {
                const input = menuCard.querySelector('.qty-input');
                if (input) {
                    input.value = 0;
                }
            }

            delete cart[itemId];
            delete priceMap[itemId];
            delete nameMap[itemId];

            updateOrderSummary();
        }

        function toggleSummary() {
            const summary = document.getElementById('orderSummary');
            const toggle = document.getElementById('toggleIcon');
            const items = document.getElementById('orderItems');
            const footer = document.getElementById('summaryFooter');

            if (items.style.display === 'none') {
                items.style.display = 'block';
                footer.style.display = 'block';
                toggle.textContent = '▼';
            } else {
                items.style.display = 'none';
                footer.style.display = 'none';
                toggle.textContent = '▲';
            }
        }

        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // feeAlert is always visible
                // const feeAlert = document.getElementById('feeAlert');
                const walletSection = document.getElementById('walletProviderSection');
                if (this.value === 'kasir') {
                    walletSection.style.display = 'none';
                } else if (this.value === 'qris') {
                    walletSection.style.display = 'none';
                } else {
                    walletSection.style.display = 'block';
                }
            });
        });

        function number_format(number, decimals, dec_point, thousands_sep) {
            return number.toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&' + thousands_sep).replace(dec_point, dec_point);
        }

        document.getElementById('orderForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const paymentMethodInput = document.querySelector('input[name="payment_method"]:checked');
            const paymentMethod = paymentMethodInput ? paymentMethodInput.value : 'kasir';
            if (paymentMethod === 'gateway') {
                const selectedWallet = document.querySelector('input[name="payment_channel"]:checked');
                if (!selectedWallet) {
                    alert('Pilih dompet digital yang akan digunakan.');
                    return;
                }
            }

            const formData = new FormData(this);
            const payload = {};
            formData.forEach(function(value, key) {
                payload[key] = value;
            });
            const submitBtn = document.getElementById('checkoutBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            submitBtn.disabled = true;
            
            const isSubdomain = window.location.hostname.includes('{{ $warung->code }}');
            const orderRoute = isSubdomain ? '/order' : '{{ route("order.store") }}';

            if (!navigator.onLine) {
                enqueueOfflineOrder(payload, orderRoute);
                alert('Koneksi internet terputus. Pesanan disimpan dan akan dikirim otomatis saat koneksi kembali.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                return;
            }
            
            fetch(orderRoute, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Pesanan berhasil!');
                    window.location.href = data.redirect;
                } else {
                    alert('Error: ' + (data.message || 'Gagal membuat pesanan'));
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                enqueueOfflineOrder(payload, orderRoute);
                alert('Koneksi bermasalah. Pesanan disimpan dan akan dikirim saat koneksi membaik.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>

    <!-- Footer -->
    <footer style="background: #2c3e50; color: white; padding: 40px 0; margin-top: 60px;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-store"></i> {{ $warung->name }}</h5>
                    @if($warung->description)
                        <p style="color: #bdc3c7; font-size: 0.9rem;">{{ $warung->description }}</p>
                    @endif
                </div>
                <div class="col-md-4 mb-3">
                    <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-map-marker-alt"></i> Alamat</h5>
                    @if($warung->address)
                        <p style="color: #bdc3c7; font-size: 0.9rem;">{{ $warung->address }}</p>
                    @else
                        <p style="color: #bdc3c7; font-size: 0.9rem;">-</p>
                    @endif
                </div>
                <div class="col-md-4 mb-3">
                    <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-clock"></i> Jam Buka</h5>
                    @if($warung->opening_hours)
                        <p style="color: #bdc3c7; font-size: 0.9rem;">{{ $warung->opening_hours }}</p>
                    @else
                        <p style="color: #bdc3c7; font-size: 0.9rem;">-</p>
                    @endif
                    @if($warung->phone)
                        <p style="color: #bdc3c7; font-size: 0.9rem; margin-top: 10px;">
                            <i class="fas fa-phone"></i> {{ $warung->phone }}
                        </p>
                    @endif
                    @if($warung->contact_email)
                        <p style="color: #bdc3c7; font-size: 0.9rem;">
                            <i class="fas fa-envelope"></i> {{ $warung->contact_email }}
                        </p>
                    @endif
                </div>
            </div>
            <hr style="border-color: #ffe5c2; margin: 30px 0;">
            <div class="text-center" style="color: #6c757d; font-size: 0.85rem;">
                <p>&copy; {{ date('Y') }} {{ $warung->name }}. Powered by Majar Signature OS.</p>
            </div>
        </div>
    </footer>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(registration => console.log('Service Worker registered:', registration.scope))
                    .catch(error => console.log('Service Worker registration failed:', error));
            });
        }
    </script>
</body>
</html>

