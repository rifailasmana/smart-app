@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Status Pesanan</h3>
                </div>
                <div class="card-body p-5">
                    <!-- Restaurant Logo -->
                    <div class="text-center mb-4">
                        @if($order->warung->logo)
                            <img src="{{ asset('storage/' . $order->warung->logo) }}" alt="{{ $order->warung->name }}" style="max-height: 80px; max-width: 100%;">
                        @else
                            <h3 class="fw-bold text-dark">{{ $order->warung->name }}</h3>
                        @endif
                    </div>

                    <!-- Order Code & Queue Number -->
                    <div class="text-center mb-5">
                        <h4 class="text-muted mb-2">Kode Pesanan</h4>
                        <h1 class="display-4 fw-bold text-primary" id="orderCode">{{ $order->code }}</h1>
                        @if($order->queue_number)
                            <div class="mt-3">
                                <h4 class="text-muted mb-2">Nomor Antrian</h4>
                                <h2 class="display-5 fw-bold text-success">{{ $order->queue_number }}</h2>
                            </div>
                        @endif
                        <p class="text-muted mt-3">Tunjukkan kode ini kepada pelayan</p>
                        <a href="#" onclick="downloadReceipt()" class="btn btn-outline-primary mt-2" id="receiptButton" @if($order->status !== 'paid') style="display: none;" @endif>
                            <i class="fas fa-print"></i> Cetak Struk
                        </a>
                    </div>

                    <div class="mb-5">
                        <h5 class="mb-3">Detail Pesanan</h5>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>Meja:</strong></td>
                                    <td>{{ $order->table->name ?? 'Takeaway' }}</td>
                                </tr>
                                @if($order->notes)
                                    <tr>
                                        <td><strong>Catatan:</strong></td>
                                        <td>{{ $order->notes }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Status Pesanan:</strong></td>
                                    <td>
                                        @php
                                            $statusText = match ($order->status) {
                                                'pending' => 'Pesanan Diterima',
                                                'verified' => 'Pembayaran Terverifikasi',
                                                'preparing' => 'Sedang Dimasak',
                                                'ready' => 'Siap Antar',
                                                'served' => 'Sudah Diantar',
                                                'paid' => 'Selesai',
                                                'cancelled' => 'Pesanan Dibatalkan',
                                                default => $order->status,
                                            };
                                        @endphp
                                        <span class="badge" id="statusBadge" data-status="{{ $order->status }}">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Metode Pembayaran:</strong></td>
                                    <td>
                                        @php
                                            $paymentText = match ($order->payment_method) {
                                                'kasir' => 'Bayar di Kasir',
                                                'qris' => 'QRIS',
                                                'gateway' => 'Dompet Digital',
                                                default => strtoupper($order->payment_method),
                                            };
                                            $channelText = null;
                                            if ($order->payment_method === 'gateway' && $order->payment_channel) {
                                                $channelText = match ($order->payment_channel) {
                                                    'dana' => 'DANA',
                                                    'shopeepay' => 'ShopeePay',
                                                    'gopay' => 'GoPay',
                                                    'ovo' => 'OVO',
                                                    'linkaja' => 'LinkAja',
                                                    default => strtoupper($order->payment_channel),
                                                };
                                            }
                                        @endphp
                                        {{ $paymentText }}
                                    </td>
                                </tr>
                                @if($order->payment_method === 'gateway' && $order->payment_channel)
                                    <tr>
                                        <td><strong>Dompet Digital:</strong></td>
                                        <td>{{ $channelText }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Waktu Pesan:</strong></td>
                                    <td>{{ $order->created_at->format('H:i:s') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($order->payment_method === 'qris')
                        <div class="mb-5 text-center">
                            <h5 class="mb-3">Pembayaran QRIS</h5>
                            <p class="text-muted">Silakan scan QRIS berikut menggunakan aplikasi pembayaran Anda.</p>
                            <div class="qris-box mx-auto mb-3">
                                <div class="qris-inner">
                                    <span>QRIS DUMMY</span>
                                </div>
                            </div>
                            <p id="paymentStatusText" class="text-muted">Menunggu pembayaran Anda terkonfirmasi secara otomatis.</p>
                        </div>
                    @elseif($order->payment_method === 'gateway')
                        <div class="mb-5 text-center">
                            <h5 class="mb-3">Pembayaran Dompet Digital</h5>
                            <p class="text-muted">
                                Selesaikan pembayaran melalui aplikasi
                                @if($order->payment_channel)
                                    {{ $channelText ?? '' }}
                                @else
                                    dompet digital pilihan Anda
                                @endif
                                pada ponsel Anda.
                            </p>
                            <p id="paymentStatusText" class="text-muted">Menunggu pembayaran Anda terkonfirmasi secara otomatis.</p>
                        </div>
                    @endif

                    <!-- Items -->
                    <div class="mb-5">
                        <h5 class="mb-3">Item Pesanan</h5>
                        <ul class="list-group">
                            @foreach($order->items as $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $item->menu_name }}</strong><br>
                                        <small class="text-muted">× {{ $item->qty }} | Rp {{ number_format($item->price) }}</small>
                                    </div>
                                    <span class="badge bg-secondary">Rp {{ number_format($item->qty * $item->price) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Total -->
                    <div class="mb-5 border-top pt-3">
                        <div class="row">
                            <div class="col-md-6 text-start">
                                <p>Subtotal:</p>
                                <p>Biaya Admin (1%):</p>
                                <h5><strong>Total:</strong></h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <p>Rp {{ number_format($order->subtotal) }}</p>
                                <p>Rp {{ number_format($order->admin_fee) }}</p>
                                <h5><strong>Rp {{ number_format($order->total) }}</strong></h5>
                            </div>
                        </div>
                    </div>

                    <!-- Status Timeline -->
                    <div class="mb-5">
                        <h5 class="mb-3">Tahapan Pesanan</h5>
                        <div class="timeline">
                            <div class="timeline-item {{ in_array($order->status, ['pending', 'verified', 'preparing', 'ready', 'served', 'paid']) ? 'completed' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <p><strong>Pesanan Diterima</strong><br><small>{{ $order->created_at->format('H:i:s') }}</small></p>
                                </div>
                            </div>
                            <div class="timeline-item {{ in_array($order->status, ['verified', 'preparing', 'ready', 'served', 'paid']) ? 'completed' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <p><strong>Sedang Dimasak</strong></p>
                                </div>
                            </div>
                            <div class="timeline-item {{ in_array($order->status, ['ready', 'served', 'paid']) ? 'completed' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <p><strong>Siap Antar</strong></p>
                                </div>
                            </div>
                            <div class="timeline-item {{ in_array($order->status, ['served', 'paid']) ? 'completed' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <p><strong>Sudah Diantar</strong></p>
                                </div>
                            </div>
                            <div class="timeline-item {{ $order->status === 'paid' ? 'completed' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <p><strong>Selesai</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center">
                        <button class="btn btn-primary btn-lg" id="refreshBtn" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        @php
                            $tableId = $order->table->id ?? null;
                            $orderMenuUrl = $tableId
                                ? route('order.menu', ['warung' => $order->warung->code, 'meja' => $tableId])
                                : route('order.menu', ['warung' => $order->warung->code]);
                        @endphp
                        <a href="{{ $orderMenuUrl }}" class="btn btn-secondary btn-lg">
                            Pesan Lagi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        display: flex;
        margin-bottom: 30px;
        opacity: 0.5;
        transition: opacity 0.3s ease;
    }

    .timeline-item.completed {
        opacity: 1;
    }

    .timeline-marker {
        width: 30px;
        height: 30px;
        background-color: #dee2e6;
        border-radius: 50%;
        margin-right: 20px;
        flex-shrink: 0;
        margin-top: 5px;
        transition: background-color 0.3s ease;
    }

    .timeline-item.completed .timeline-marker {
        background-color: #28a745;
    }

    .timeline-content {
        flex: 1;
    }

    .timeline-content p {
        margin-bottom: 0;
    }

    #statusBadge {
        font-size: 1rem;
        padding: 10px 15px;
    }

    #statusBadge[data-status="pending"] {
        background-color: #FF8C00;
        color: #000;
    }

    #statusBadge[data-status="preparing"] {
        background-color: #facc15;
        color: #111827;
    }

    #statusBadge[data-status="ready"] {
        background-color: #22c55e;
    }

    #statusBadge[data-status="served"] {
        background-color: #6c757d;
    }

    #statusBadge[data-status="paid"] {
        background-color: #28a745;
    }

    #statusBadge[data-status="cancelled"] {
        background-color: #dc3545;
    }

    .qris-box {
        width: 220px;
        height: 220px;
        border-radius: 16px;
        background: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #bfdbfe;
    }

    .qris-inner {
        width: 160px;
        height: 160px;
        background: repeating-linear-gradient(
            45deg,
            #343a40,
            #343a40 4px,
            #f8f9fa 4px,
            #f8f9fa 8px
        );
        display: flex;
        align-items: center;
        justify-content: center;
        color: #f8f9fa;
        font-weight: 700;
        font-size: 0.9rem;
        text-align: center;
        padding: 10px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const warung = '{{ $order->warung->code }}';
        const code = '{{ $order->code }}';
        const eventSource = new EventSource(`/order-status/stream?warung=${warung}&code=${code}`);

        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            const statusBadge = document.getElementById('statusBadge');
            const receiptButton = document.getElementById('receiptButton');

            const statusText = data.status === 'pending' ? 'Pesanan Diterima' : 
                             data.status === 'verified' ? 'Pembayaran Terverifikasi' :
                             data.status === 'preparing' ? 'Sedang Dimasak' : 
                             data.status === 'ready' ? 'Siap Antar' : 
                             data.status === 'served' ? 'Sudah Diantar' : 
                             data.status === 'paid' ? 'Selesai' :
                             data.status === 'cancelled' ? 'Pesanan Dibatalkan' : data.status;
            
            statusBadge.textContent = statusText;
            statusBadge.setAttribute('data-status', data.status);

            if (receiptButton) {
                if (data.status === 'paid') {
                    receiptButton.style.display = 'inline-block';
                } else {
                    receiptButton.style.display = 'none';
                }
            }

            const paymentStatusText = document.getElementById('paymentStatusText');
            if (paymentStatusText) {
                if (data.status === 'pending') {
                    paymentStatusText.textContent = 'Menunggu pembayaran Anda terkonfirmasi secara otomatis.';
                } else if (data.status === 'verified') {
                    paymentStatusText.textContent = 'Pembayaran terverifikasi. Pesanan akan segera diproses oleh dapur.';
                } else if (data.status === 'preparing') {
                    paymentStatusText.textContent = 'Pembayaran sudah terverifikasi. Pesanan sedang dimasak.';
                } else if (data.status === 'ready') {
                    paymentStatusText.textContent = 'Pembayaran sudah terverifikasi. Pesanan siap untuk diantar.';
                } else if (data.status === 'served') {
                    paymentStatusText.textContent = 'Pembayaran sudah terverifikasi. Pesanan sudah diantar ke meja.';
                } else if (data.status === 'paid') {
                    paymentStatusText.textContent = 'Pesanan selesai dan pembayaran sudah lunas. Terima kasih.';
                } else if (data.status === 'cancelled') {
                    paymentStatusText.textContent = 'Pesanan Anda dibatalkan karena melewati batas waktu pembayaran.';
                }
            }

            if (data.status === 'paid' || data.status === 'cancelled') {
                eventSource.close();
                setTimeout(() => location.reload(), 2000);
            }
        };

        eventSource.onerror = function() {
            eventSource.close();
        };
    });

    function downloadReceipt() {
        const warung = '{{ $order->warung->code }}';
        const code = '{{ $order->code }}';
        const url = `/order-receipt-print?warung=${encodeURIComponent(warung)}&code=${encodeURIComponent(code)}`;
        window.open(url, '_blank');
    }
</script>
@endsection
