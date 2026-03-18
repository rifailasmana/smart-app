<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Warung;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExporter
{
    public function export(string $period, Warung $warung): StreamedResponse
    {
        return match ($period) {
            'daily' => $this->exportDaily($warung),
            'weekly' => $this->exportWeekly($warung),
            'monthly' => $this->exportMonthly($warung),
            'daily-detail' => $this->exportDailyDetail($warung),
            default => $this->exportDaily($warung),
        };
    }

    protected function exportDaily(Warung $warung): StreamedResponse
    {
        $date = today();

        $paidQuery = Order::where('warung_id', $warung->id)
            ->whereDate('created_at', $date)
            ->where('status', 'paid');

        $totalSales = $paidQuery->sum('total');
        $transactionCount = $paidQuery->count();

        $bestSeller = OrderItem::whereIn(
            'order_id',
            $paidQuery->pluck('id')
        )
            ->selectRaw('menu_name, SUM(qty) as total_qty')
            ->groupBy('menu_name')
            ->orderByDesc('total_qty')
            ->first();

        $fileName = 'daily_report_' . $date->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function () use ($date, $totalSales, $transactionCount, $bestSeller) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Tanggal', 'Total Penjualan', 'Jumlah Transaksi', 'Best Seller', 'Qty Best Seller']);

            $row = [
                $date->format('Y-m-d'),
                $this->formatRupiah($totalSales),
                $transactionCount,
                $bestSeller?->menu_name ?? '-',
                $bestSeller?->total_qty ?? 0,
            ];

            fputcsv($handle, $row);

            fclose($handle);
        }, $fileName, $headers);
    }

    protected function exportWeekly(Warung $warung): StreamedResponse
    {
        $start = Carbon::now()->startOfWeek();
        $end = Carbon::now()->endOfWeek();

        $paidQuery = Order::where('warung_id', $warung->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'paid');

        $totalSales = $paidQuery->sum('total');
        $transactionCount = $paidQuery->count();

        $busiestDay = Order::where('warung_id', $warung->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'paid')
            ->selectRaw('DATE(created_at) as day, SUM(total) as day_total')
            ->groupBy('day')
            ->orderByDesc('day_total')
            ->first();

        $fileName = 'weekly_report_' . $start->format('Y-m-d') . '_to_' . $end->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function () use ($start, $end, $totalSales, $transactionCount, $busiestDay) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Minggu', 'Total Penjualan', 'Jumlah Transaksi', 'Hari Terlaris']);

            $weekLabel = $start->format('Y-m-d') . ' s/d ' . $end->format('Y-m-d');

            $row = [
                $weekLabel,
                $this->formatRupiah($totalSales),
                $transactionCount,
                $busiestDay ? Carbon::parse($busiestDay->day)->isoFormat('dddd') : '-',
            ];

            fputcsv($handle, $row);

            fclose($handle);
        }, $fileName, $headers);
    }

    protected function exportMonthly(Warung $warung): StreamedResponse
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $paidQuery = Order::where('warung_id', $warung->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'paid');

        $totalSales = $paidQuery->sum('total');

        $daysInMonth = $start->daysInMonth;
        $averagePerDay = $daysInMonth > 0 ? $totalSales / $daysInMonth : 0;

        $bestSeller = OrderItem::whereIn(
            'order_id',
            $paidQuery->pluck('id')
        )
            ->selectRaw('menu_name, SUM(qty) as total_qty')
            ->groupBy('menu_name')
            ->orderByDesc('total_qty')
            ->first();

        $fileName = 'monthly_report_' . $start->format('Y-m') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function () use ($start, $totalSales, $bestSeller, $averagePerDay) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Bulan', 'Total Penjualan', 'Menu Terlaris', 'Rata-rata/Hari']);

            $row = [
                $start->format('Y-m'),
                $this->formatRupiah($totalSales),
                $bestSeller?->menu_name ?? '-',
                $this->formatRupiah($averagePerDay),
            ];

            fputcsv($handle, $row);

            fclose($handle);
        }, $fileName, $headers);
    }

    protected function exportDailyDetail(Warung $warung): StreamedResponse
    {
        $date = today();

        $orders = Order::where('warung_id', $warung->id)
            ->whereDate('created_at', $date)
            ->where('status', 'paid')
            ->with(['items', 'table'])
            ->orderBy('created_at')
            ->get();

        $fileName = 'daily_orders_detail_' . $date->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(function () use ($orders, $date) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tanggal Waktu',
                'Kode Pesanan',
                'Meja',
                'No HP',
                'Subtotal',
                'Biaya Admin',
                'Total',
                'Metode Pembayaran',
                'Channel Pembayaran',
                'Status',
                'Rincian Item',
            ]);

            foreach ($orders as $order) {
                $createdAt = $order->created_at
                    ? $order->created_at->copy()->setTimezone('Asia/Makassar')
                    : $date->copy()->setTimezone('Asia/Makassar');

                $tableName = $order->table ? $order->table->name : 'Takeaway';

                $itemsSummary = $order->items->map(function ($item) {
                    return $item->menu_name . ' x' . $item->qty;
                })->implode(', ');

                $row = [
                    $createdAt->format('Y-m-d H:i:s'),
                    $order->code,
                    $tableName,
                    $order->customer_phone ?? '',
                    $order->customer_name ?? '',
                    (float) $order->subtotal,
                    (float) $order->admin_fee,
                    (float) $order->total,
                    $order->payment_method,
                    $order->payment_channel ?? '',
                    $order->status,
                    $itemsSummary,
                ];

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, $headers);
    }

    protected function formatRupiah(float|int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
