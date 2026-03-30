<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $order->code }}</title>
    <style>
        @media print {
            @page { margin: 0; size: 58mm auto; }
            body { margin: 0; }
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px; /* Smaller base font size for thermal printers */
            color: #000;
            width: 58mm; /* Standard thermal paper width */
            padding: 5px;
            box-sizing: border-box;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .mb-1 { margin-bottom: 2px; }
        .mb-2 { margin-bottom: 4px; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .logo { 
            max-width: 60%; 
            max-height: 40px; 
            object-fit: contain;
            margin-bottom: 5px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        table { width: 100%; border-collapse: collapse; line-height: 1.2; }
        td { vertical-align: top; padding: 1px 0; }
        .item-col {
            white-space: normal; /* Allow item names to wrap */
            word-break: break-word;
        }
        .price-col { white-space: nowrap; }
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
            @php
                $receiptDateRaw = $order->paid_at ?? $order->created_at ?? now();
                // Ensure we always end up with a Carbon instance (not a plain string/empty value)
                try {
                    $receiptDate = $receiptDateRaw instanceof \Carbon\CarbonInterface
                        ? $receiptDateRaw
                        : \Illuminate\Support\Carbon::parse($receiptDateRaw);

                    if (!is_object($receiptDate) || !method_exists($receiptDate, 'format')) {
                        $receiptDate = now();
                    }
                } catch (\Throwable $e) {
                    $receiptDate = now();
                }
            @endphp
            <td class="text-right">{{ $receiptDate->format('d/m/Y H:i') }}</td>
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
                <td colspan="3">{{ $item->menu_name }}</td>
            </tr>
            <tr>
                <td>{{ $item->qty }}x</td>
                <td class="text-right">@ {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="price-col text-right">{{ number_format($item->price * $item->qty, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="price-col text-right">{{ number_format($order->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($order->admin_fee > 0)
        <tr>
            <td>Biaya Layanan</td>
            <td class="price-col text-right">{{ number_format($order->admin_fee, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($order->diskon_manual > 0)
        <tr>
            <td>Diskon</td>
            <td class="price-col text-right">-{{ number_format($order->diskon_manual, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="bold" style="font-size: 12px;">
            <td>Total</td>
            <td class="price-col text-right">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        @if($order->amount_paid && $order->amount_paid >= $order->total)
        <tr>
            <td>Bayar</td>
            <td class="price-col text-right">{{ number_format($order->amount_paid, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="price-col text-right">{{ number_format($order->amount_paid - $order->total, 0, ',', '.') }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <div class="text-center">
        <div class="mb-1">Terima Kasih</div>
        <div>Simpan struk ini sebagai bukti pembayaran yang sah.</div>
    </div>
</body>
</html>