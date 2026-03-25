<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $order->code }}</title>
    <style>
        @media print {
            @page { margin: 0; size: auto; }
            body { margin: 0; padding: 10px; }
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            width: 58mm; /* Standard thermal paper width */
            margin: 0 auto;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .logo { 
            max-width: 80%; 
            max-height: 60px; 
            object-fit: contain;
            margin-bottom: 5px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .qty { width: 15%; }
        .item { width: 55%; }
        .price { width: 30%; text-align: right; }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center mb-2">
        @if($warung->logo)
            <img src="{{ asset('storage/' . $warung->logo) }}" alt="Logo" class="logo">
        @endif
        <div class="bold" style="font-size: 14px;">{{ $warung->name }}</div>
        <div>{{ $warung->address ?? '' }}</div>
        @if($warung->phone)
            <div>Telp: {{ $warung->phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <table class="mb-2">
        <tr>
            <td>Tgl</td>
            <td class="text-right">{{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : ($order->created_at ? $order->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i')) }}</td>
        </tr>
        <tr>
            <td>Order</td>
            <td class="text-right">#{{ $order->code }}</td>
        </tr>
        <tr>
            <td>Pelanggan</td>
            <td class="text-right">{{ $order->customer_name ?? 'Guest' }}</td>
        </tr>
        <tr>
            <td>Meja</td>
            <td class="text-right">{{ $order->table->name ?? 'Takeaway' }}</td>
        </tr>
        @if($order->kasir)
        <tr>
            <td>Kasir</td>
            <td class="text-right">{{ $order->kasir->name }}</td>
        </tr>
        @endif
        @if($order->payment_method)
        <tr>
            <td>Bayar</td>
            <td class="text-right">{{ strtoupper($order->payment_method) }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <table>
        @foreach($order->items as $item)
            <tr>
                <td class="qty">{{ $item->qty }}x</td>
                <td class="item">{{ $item->menu_name }}</td>
                <td class="price">{{ number_format($item->price * $item->qty, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ number_format($order->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($order->admin_fee > 0)
        <tr>
            <td>Biaya Layanan</td>
            <td class="text-right">{{ number_format($order->admin_fee, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="bold" style="font-size: 14px;">
            <td>Total</td>
            <td class="text-right">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center">
        <div class="mb-1">Terima Kasih</div>
        <div>Simpan struk ini sebagai bukti pembayaran yang sah.</div>
    </div>
</body>
</html>