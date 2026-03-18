<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Log WhatsApp notification (simulasi)
     * Dalam produksi, ini akan mengirim ke API WhatsApp gateway
     */
    public static function sendOrderNotification(Order $order, string $type = 'new_order'): void
    {
        $message = self::generateMessage($order, $type);

        Log::channel('orders')->info("WA Notification", [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'table' => $order->table ? $order->table->name : 'Takeaway',
            'warung' => $order->warung->name,
            'type' => $type,
            'message' => $message,
            'timestamp' => now(),
        ]);

        $warung = $order->warung;

        if (!$warung || !$warung->whatsapp_notification || !$order->customer_phone) {
            return;
        }

        if ($type === 'payment') {
            $resiMessage = self::buildReceiptMessage($order);
            self::sendWhatsApp($resiMessage, $order->customer_phone);
        } elseif (in_array($type, ['new_order', 'verified', 'preparing', 'ready', 'served', 'cancelled', 'update'], true)) {
            self::sendWhatsApp($message, $order->customer_phone);
        }
    }

    /**
     * Generate notification message
     */
    private static function generateMessage(Order $order, string $type): string
    {
        $tableName = $order->table ? $order->table->name : 'Takeaway';
        $queueNumber = $order->queue_number ?? '001';
        $paymentLabel = match ($order->payment_method) {
            'kasir' => 'Bayar di Kasir',
            'qris' => 'QRIS',
            'gateway' => 'Dompet Digital',
            default => strtoupper((string) $order->payment_method),
        };
        if ($order->payment_method === 'gateway' && $order->payment_channel) {
            $channelName = match ($order->payment_channel) {
                'dana' => 'DANA',
                'shopeepay' => 'ShopeePay',
                'gopay' => 'GoPay',
                'ovo' => 'OVO',
                'linkaja' => 'LinkAja',
                default => strtoupper($order->payment_channel),
            };
            $paymentLabel .= ' - ' . $channelName;
        }
        $customerInfo = $order->customer_name ? " '{$order->customer_name}'" : " 'Pelanggan'";
        return match ($type) {
            'new_order' => "🔔 Order Baru!\nKode: {$order->code}{$customerInfo}\nNomor Antrian: {$queueNumber}\nMeja: {$tableName}\nMetode: {$paymentLabel}\nTotal: Rp " . number_format($order->total),
            'verified' => "✅ Pembayaran Diverifikasi Kasir\nKode: {$order->code}{$customerInfo}\nNomor Antrian: {$queueNumber}\nMeja: {$tableName}\nMetode: {$paymentLabel}\nTotal: Rp " . number_format($order->total),
            'preparing' => "👨‍🍳 Pesanan Sedang Dimasak\nKode: {$order->code}{$customerInfo}\nNomor Antrian: {$queueNumber}\nMeja: {$tableName}",
            'ready' => "✅ Pesanan Siap Diantar\nKode: {$order->code}{$customerInfo}\nNomor Antrian: {$queueNumber}\nMeja: {$tableName}",
            'served' => "🍽️ Pesanan Sudah Diantar ke Meja\nKode: {$order->code}{$customerInfo}\nNomor Antrian: {$queueNumber}\nMeja: {$tableName}",
            'payment' => "💳 Pembayaran Diterima\nKode: {$order->code}{$customerInfo}\nNomor Antrian: {$queueNumber}\nMetode: {$paymentLabel}\nTotal: Rp " . number_format($order->total),
            'cancelled' => "⚠️ Pesanan Dibatalkan\nKode: {$order->code}{$customerInfo}\nNomor Antrian: {$queueNumber}\nMeja: {$tableName}",
            'qris_dummy' => "📱 QRIS Dummy Payment\nKode: {$order->code}{$customerInfo}\nMeja: {$tableName}\nMetode: QRIS\nTotal: Rp " . number_format($order->total) . "\nStatus: BERHASIL (simulasi)\nCatatan: Kasir silakan verifikasi di dashboard.",
            'gateway_dummy' => "💻 Dompet Digital Dummy Payment\nKode: {$order->code}{$customerInfo}\nMeja: {$tableName}\nMetode: Dompet Digital\nTotal: Rp " . number_format($order->total) . "\nStatus: BERHASIL (simulasi)\nCatatan: Kasir silakan verifikasi di dashboard.",
            default => "📋 Update Order: {$order->code}{$customerInfo}",
        };
    }

    public static function buildReceiptMessage(Order $order): string
    {
        $warung = $order->warung;
        $warungName = $warung->name ?? 'Restoran';
        $warungAddress = $warung->address ?: 'Alamat belum tersedia';
        $warungPhone = $warung->phone ?: 'Telepon belum tersedia';
        $warungLogo = $warung->logo ?? null;
        $logoUrl = $warungLogo ? asset('storage/' . $warungLogo) : null;

        $tableName = $order->table ? $order->table->name : 'Takeaway';
        $queueNumber = $order->queue_number ?: '-';

        $createdAt = $order->created_at
            ? $order->created_at->copy()->setTimezone('Asia/Makassar')
            : now()->setTimezone('Asia/Makassar');

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $monthIndex = (int) $createdAt->format('n');
        $monthName = $monthNames[$monthIndex] ?? $createdAt->format('F');
        $dateString = $createdAt->format('d') . ' ' . $monthName . ' ' . $createdAt->format('Y') . ', ' . $createdAt->format('H.i') . ' WITA';

        $cashierName = 'Kasir';

        if (!empty($order->kasir_id)) {
            $kasirUser = method_exists($order, 'kasir') ? $order->kasir : null;
            if (!$kasirUser && class_exists(User::class)) {
                $kasirUser = User::find($order->kasir_id);
            }
            if ($kasirUser) {
                $cashierName = $kasirUser->name;
            } else {
                $cashierName = 'Sistem';
            }
        } elseif (!empty($order->paid_by_user_id)) {
            $paidBy = method_exists($order, 'paidByUser') ? $order->paidByUser : null;
            if (!$paidBy && class_exists(User::class)) {
                $paidBy = User::find($order->paid_by_user_id);
            }
            if ($paidBy) {
                $cashierName = $paidBy->name;
            }
        } elseif (!empty($order->user_id)) {
            $creator = method_exists($order, 'creatorUser') ? $order->creatorUser : null;
            if (!$creator && class_exists(User::class)) {
                $creator = User::find($order->user_id);
            }
            if ($creator) {
                $cashierName = $creator->name;
            } else {
                $cashierName = 'Sistem';
            }
        } else {
            $cashierName = 'Sistem';
        }

        $paymentLabel = match ($order->payment_method) {
            'kasir' => 'Bayar di Kasir',
            'qris' => 'QRIS',
            'gateway' => 'Dompet Digital',
            default => strtoupper((string) $order->payment_method),
        };

        if ($order->payment_method === 'gateway') {
            if ($order->payment_channel) {
                $channel = strtolower((string) $order->payment_channel);
                $channelLabel = match ($channel) {
                    'dana' => 'DANA',
                    'shopeepay' => 'ShopeePay',
                    'gopay' => 'GoPay',
                    'ovo' => 'OVO',
                    'linkaja' => 'LinkAja',
                    default => ucfirst($channel),
                };
                $paymentLabel = $channelLabel;
            } else {
                $paymentLabel = 'Dompet Digital';
            }
        }

        $statusLabel = match ($order->status) {
            'pending' => 'Menunggu Pembayaran',
            'verified' => 'Pembayaran Diverifikasi',
            'preparing' => 'Sedang Diproses',
            'ready' => 'Siap Diantar',
            'served' => 'Sudah Disajikan',
            'paid' => 'Pembayaran Berhasil',
            'cancelled' => 'Dibatalkan',
            default => ucfirst((string) $order->status),
        };

        $maxNameLength = 36;
        $itemLines = [];

        foreach ($order->items as $item) {
            $namePart = $item->menu_name . ' x' . $item->qty;
            $nameLength = mb_strlen($namePart);

            if ($nameLength > $maxNameLength) {
                $namePart = mb_substr($namePart, 0, $maxNameLength - 1) . '…';
                $nameLength = mb_strlen($namePart);
            }

            $spaces = max(1, $maxNameLength - $nameLength);
            $unitPrice = $item->price;
            if (property_exists($item, 'promo_aktif') && property_exists($item, 'harga_promo')) {
                if ($item->promo_aktif && (float) $item->harga_promo > 0) {
                    $unitPrice = (float) $item->harga_promo;
                }
            }
            $priceValue = ($item->total && (float) $item->total > 0)
                ? (float) $item->total
                : (float) $unitPrice * (int) $item->qty;
            $priceText = 'Rp ' . number_format((float) $priceValue, 0, ',', '.');
            $itemLines[] = '• ' . $namePart . str_repeat(' ', $spaces) . $priceText;
        }

        if (empty($itemLines)) {
            $itemLines[] = '-';
        }

        $itemsText = implode("\n", $itemLines);

        $subtotalText = 'Rp ' . number_format((float) $order->subtotal, 0, ',', '.');
        $serviceFeeText = 'Rp ' . number_format((float) $order->admin_fee, 0, ',', '.');
        $discountValue = (float) ($order->diskon_manual ?? 0);
        $discountText = $discountValue > 0
            ? 'Rp ' . number_format($discountValue, 0, ',', '.')
            : 'Rp 0';
        $totalText = 'Rp ' . number_format((float) $order->total, 0, ',', '.');

        $lines = [];
        $lines[] = $warungName;
        if ($logoUrl) {
            $lines[] = $logoUrl;
        }
        $lines[] = $warungAddress;
        $lines[] = 'Telp: ' . $warungPhone;
        $lines[] = '';
        $lines[] = 'No. Resi       : ' . $order->code . " '" . ($order->customer_name ?? 'Pelanggan') . "'";
        $lines[] = 'Kode Order     : ' . $order->code . " '" . ($order->customer_name ?? 'Pelanggan') . "'";
        $lines[] = 'Pelanggan      : ' . ($order->customer_name ?? 'Pelanggan');
        $lines[] = 'Nomor Antrian  : ' . $queueNumber;
        $lines[] = 'Meja           : ' . $tableName;
        $lines[] = 'Kasir          : ' . $cashierName;
        $lines[] = 'Tanggal        : ' . $dateString;
        $lines[] = '';
        $lines[] = 'Detail Pesanan:';
        $lines[] = $itemsText;
        $lines[] = '';
        $lines[] = 'Subtotal       : ' . $subtotalText;
        $lines[] = 'Biaya Layanan  : ' . $serviceFeeText;
        $lines[] = 'Diskon         : -' . $discountText;
        $lines[] = 'Total          : ' . $totalText;
        $lines[] = '';
        $lines[] = 'Metode Bayar   : ' . $paymentLabel;
        $lines[] = 'Status         : ' . $statusLabel;
        $lines[] = '';
        $lines[] = 'Barang makanan/minuman bersifat akhir dan tidak dapat dikembalikan.';
        $lines[] = 'Untuk bantuan, hubungi: ' . $warungPhone;
        $lines[] = 'Terima kasih atas kunjungan Anda.';

        return trim(implode("\n", $lines));
    }

    /**
     * Actually send WhatsApp (implement with Twilio, MessageBird, etc.)
     */
    public static function sendWhatsApp(string $message, string $phoneNumber): void
    {
        $token = env('FONNTE_TOKEN');

        if (!$token) {
            Log::warning('Fonnte token is not configured');
            return;
        }

        $target = preg_replace('/[^0-9]/', '', $phoneNumber);

        if ($target === '') {
            Log::warning('Fonnte target number is empty after normalization', [
                'original' => $phoneNumber,
            ]);
            return;
        }

        $payload = [
            'target' => $target,
            'message' => $message,
            'countryCode' => env('FONNTE_COUNTRY_CODE', '62'),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->asForm()->post('https://api.fonnte.com/send', $payload);

            Log::info('Fonnte send response', [
                'target' => $target,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Fonnte send exception', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
