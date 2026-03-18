<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Warung;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GoogleSheetService
{
    public static function appendOrder(Order $order): bool
    {
        try {
            $warung = $order->warung;

            if (!$warung instanceof Warung) {
                Log::warning('Google Sheets sync skipped: order has no valid warung', [
                    'order_id' => $order->id ?? null,
                ]);
                return false;
            }

            if (!$warung->google_sheets_enabled || !$warung->google_sheets_spreadsheet_id) {
                Log::info('Google Sheets sync skipped: warung not enabled or missing spreadsheet id', [
                    'warung_id' => $warung->id,
                    'enabled' => $warung->google_sheets_enabled,
                    'spreadsheet_id_present' => (bool) $warung->google_sheets_spreadsheet_id,
                ]);
                return false;
            }

            if (!env('GOOGLE_SHEETS_ENABLED', false)) {
                Log::info('Google Sheets sync skipped: GOOGLE_SHEETS_ENABLED is false in env');
                return false;
            }

            $accessToken = self::getAccessToken();

            if (!$accessToken) {
                Log::warning('Google Sheets sync skipped: unable to obtain access token');
                return false;
            }

            $createdAt = $order->created_at ? $order->created_at->copy()->setTimezone('Asia/Makassar') : Carbon::now('Asia/Makassar');
            $tableName = $order->table ? $order->table->name : 'Takeaway';
            $itemsSummary = $order->items->map(function ($item) {
                return $item->menu_name . ' x' . $item->qty;
            })->implode(', ');

            $data = [
                $createdAt->format('Y-m-d H:i:s'),
                $order->code,
                $tableName,
                $order->customer_phone ?? '',
                (float) $order->subtotal,
                (float) $order->admin_fee,
                (float) $order->total,
                $order->payment_method,
                $order->payment_channel ?? '',
                $order->status,
                $itemsSummary,
            ];

            $sheetName = $warung->google_sheets_sheet_name ?: 'Sheet1';
            $range = urlencode($sheetName . '!A1');
            $spreadsheetId = $warung->google_sheets_spreadsheet_id;
            $query = http_build_query([
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS',
            ]);

            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?{$query}";

            $response = Http::withToken($accessToken)->post($url, [
                'values' => [$data],
                'majorDimension' => 'ROWS',
            ]);

            if (!$response->ok()) {
                Log::warning('Google Sheets append failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $warung->update(['google_sheets_last_synced_at' => now()]);

            return true;

        } catch (\Exception $e) {
            Log::error("Google Sheets Sync Error: " . $e->getMessage());
            return false;
        }
    }

    protected static function getAccessToken(): ?string
    {
        $clientEmail = env('GOOGLE_SHEETS_SERVICE_ACCOUNT_EMAIL');
        $privateKey = env('GOOGLE_SHEETS_PRIVATE_KEY');

        if (!$clientEmail || !$privateKey) {
            Log::warning('Google Sheets service account env not configured');
            return null;
        }

        $privateKey = str_replace(['\\n', "\r\n", "\r"], "\n", $privateKey);

        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64Header = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64Claims = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');
        $data = $base64Header . '.' . $base64Claims;

        $signature = null;
        $signed = openssl_sign($data, $signature, $privateKey, 'sha256WithRSAEncryption');

        if (!$signed || !$signature) {
            Log::warning('Google Sheets JWT signing failed');
            return null;
        }

        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $jwt = $data . '.' . $base64Signature;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->ok()) {
            Log::warning('Google Sheets token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $accessToken = $response->json('access_token');

        if (!$accessToken) {
            Log::warning('Google Sheets token missing from response');
            return null;
        }

        return $accessToken;
    }

    public static function syncReports(Warung $warung, Collection $orders): void
    {
        try {
            if (!$warung->google_sheets_enabled || !$warung->google_sheets_spreadsheet_id) {
                return;
            }

            if (!env('GOOGLE_SHEETS_ENABLED', false)) {
                return;
            }

            if ($orders->isEmpty()) {
                return;
            }

            $accessToken = self::getAccessToken();
            if (!$accessToken) {
                return;
            }

            $spreadsheetId = $warung->google_sheets_spreadsheet_id;

            // 1. Process Harian
            $dailyRows = [];
            $orders->groupBy(fn($o) => $o->created_at->format('Y-m-d'))
                ->sortKeys()
                ->each(function ($group, $date) use (&$dailyRows) {
                    $count = $group->count();
                    $total = (int) $group->sum('total');
                    $admin = (int) $group->sum('admin_fee');
                    $avg = $count > 0 ? (int) round($total / $count) : 0;
                    $dailyRows[] = [$date, $count, $total, $admin, $avg];
                });

            if (!empty($dailyRows)) {
                self::ensureSheet($spreadsheetId, $accessToken, 'Harian');
                self::ensureHeader($spreadsheetId, $accessToken, 'Harian', [
                    'Tanggal', 'Jumlah Order', 'Total Penjualan', 'Total Admin Fee', 'Rata-rata/Order'
                ]);
                self::clearSheetContent($spreadsheetId, $accessToken, 'Harian');
                self::appendRows($spreadsheetId, $accessToken, 'Harian', $dailyRows);
                Log::info("Google Sheets Report: Sent " . count($dailyRows) . " rows to Harian");
            }

            // 2. Process Bulanan
            $monthlyRows = [];
            $orders->groupBy(fn($o) => $o->created_at->format('Y-m'))
                ->sortKeys()
                ->each(function ($group, $monthKey) use (&$monthlyRows) {
                    $monthName = Carbon::createFromFormat('Y-m', $monthKey)->locale('id')->translatedFormat('F Y');
                    $count = $group->count();
                    $total = (int) $group->sum('total');
                    $admin = (int) $group->sum('admin_fee');
                    $monthlyRows[] = [$monthName, $count, $total, $admin];
                });

            if (!empty($monthlyRows)) {
                self::ensureSheet($spreadsheetId, $accessToken, 'Bulanan');
                self::ensureHeader($spreadsheetId, $accessToken, 'Bulanan', [
                    'Bulan', 'Jumlah Order', 'Total Penjualan', 'Total Admin Fee'
                ]);
                self::clearSheetContent($spreadsheetId, $accessToken, 'Bulanan');
                self::appendRows($spreadsheetId, $accessToken, 'Bulanan', $monthlyRows);
                Log::info("Google Sheets Report: Sent " . count($monthlyRows) . " rows to Bulanan");
            }

            // 3. Process Tahunan
            $yearlyRows = [];
            $orders->groupBy(fn($o) => $o->created_at->format('Y'))
                ->sortKeys()
                ->each(function ($group, $year) use (&$yearlyRows) {
                    $count = $group->count();
                    $total = (int) $group->sum('total');
                    $admin = (int) $group->sum('admin_fee');
                    $yearlyRows[] = [$year, $count, $total, $admin];
                });

            if (!empty($yearlyRows)) {
                self::ensureSheet($spreadsheetId, $accessToken, 'Tahunan');
                self::ensureHeader($spreadsheetId, $accessToken, 'Tahunan', [
                    'Tahun', 'Jumlah Order', 'Total Penjualan', 'Total Admin Fee'
                ]);
                self::clearSheetContent($spreadsheetId, $accessToken, 'Tahunan');
                self::appendRows($spreadsheetId, $accessToken, 'Tahunan', $yearlyRows);
                Log::info("Google Sheets Report: Sent " . count($yearlyRows) . " rows to Tahunan");
            }

        } catch (\Throwable $e) {
            Log::error('Google Sheets syncReports fatal error', [
                'warung_id' => $warung->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private static function clearSheetContent(string $spreadsheetId, string $accessToken, string $sheetName): void
    {
        try {
            $range = urlencode($sheetName . '!A2:Z');
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:clear";
            Http::withToken($accessToken)->post($url);
        } catch (\Throwable $e) {
             Log::error("Google Sheets clearSheetContent ($sheetName) error: " . $e->getMessage());
        }
    }

    private static function ensureSheet(string $spreadsheetId, string $accessToken, string $title): void
    {
        try {
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}?fields=sheets.properties";
            $response = Http::withToken($accessToken)->get($url);

            if ($response->ok()) {
                $data = $response->json();
                $sheets = $data['sheets'] ?? [];
                foreach ($sheets as $sheet) {
                    if (($sheet['properties']['title'] ?? null) === $title) {
                        return;
                    }
                }
            }

            $batchUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate";
            Http::withToken($accessToken)->post($batchUrl, [
                'requests' => [
                    ['addSheet' => ['properties' => ['title' => $title]]],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error("Google Sheets ensureSheet ($title) error: " . $e->getMessage());
        }
    }

    private static function ensureHeader(string $spreadsheetId, string $accessToken, string $sheetName, array $headerRow): void
    {
        try {
            $range = urlencode($sheetName . '!A1:Z1');
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}";
            $response = Http::withToken($accessToken)->get($url);

            $needsHeader = true;
            if ($response->ok()) {
                $values = $response->json('values');
                if (!empty($values) && !empty($values[0])) {
                    foreach ($values[0] as $cell) {
                        if ($cell !== null && $cell !== '') {
                            $needsHeader = false;
                            break;
                        }
                    }
                }
            }

            if ($needsHeader) {
                $updateUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}?valueInputOption=RAW";
                Http::withToken($accessToken)->put($updateUrl, [
                    'values' => [$headerRow],
                    'majorDimension' => 'ROWS',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Google Sheets ensureHeader ($sheetName) error: " . $e->getMessage());
        }
    }

    private static function appendRows(string $spreadsheetId, string $accessToken, string $sheetName, array $rows): void
    {
        try {
            $range = urlencode($sheetName . '!A1');
            $query = http_build_query([
                'valueInputOption' => 'RAW',
                'insertDataOption' => 'INSERT_ROWS',
            ]);
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?{$query}";

            Http::withToken($accessToken)->post($url, [
                'values' => $rows,
                'majorDimension' => 'ROWS',
            ]);
        } catch (\Throwable $e) {
            Log::error("Google Sheets appendRows ($sheetName) error: " . $e->getMessage());
        }
    }

    public static function syncOrdersToTransaksi(Warung $warung, Collection $orders): void
    {
        try {
            if (!$warung->google_sheets_enabled || !$warung->google_sheets_spreadsheet_id) {
                Log::info('Google Sheets batch sync skipped: warung not enabled or missing spreadsheet id', [
                    'warung_id' => $warung->id,
                    'enabled' => $warung->google_sheets_enabled,
                    'spreadsheet_id_present' => (bool) $warung->google_sheets_spreadsheet_id,
                ]);
                return;
            }

            if (!env('GOOGLE_SHEETS_ENABLED', false)) {
                Log::info('Google Sheets batch sync skipped: GOOGLE_SHEETS_ENABLED is false in env');
                return;
            }

            if ($orders->isEmpty()) {
                Log::info('Google Sheets batch sync skipped: no orders to sync', [
                    'warung_id' => $warung->id,
                ]);
                return;
            }

            $accessToken = self::getAccessToken();

            if (!$accessToken) {
                Log::warning('Google Sheets batch sync skipped: unable to obtain access token', [
                    'warung_id' => $warung->id,
                ]);
                return;
            }

            $spreadsheetId = $warung->google_sheets_spreadsheet_id;

            self::ensureTransaksiSheetExists($spreadsheetId, $accessToken);
            self::ensureTransaksiHeaderExists($spreadsheetId, $accessToken);

            $rows = [];

            foreach ($orders as $order) {
                if (!$order instanceof Order) {
                    continue;
                }

                try {
                    $order->loadMissing('items', 'table');

                    $createdAt = $order->created_at
                        ? $order->created_at->copy()->setTimezone('Asia/Makassar')
                        : Carbon::now('Asia/Makassar');

                    $timestamp = $createdAt->format('Y-m-d H:i:s');
                    $tableName = $order->table ? $order->table->name : 'Takeaway';
                    $customerName = $order->customer_name ?: '-';
                    $customerPhone = $order->customer_phone ?: '-';

                    $paymentLabel = match ($order->payment_method) {
                        'kasir' => 'Bayar di Kasir',
                        'qris' => 'QRIS',
                        'gateway' => $order->payment_channel
                            ? ucfirst($order->payment_channel)
                            : 'Dompet Digital',
                        default => strtoupper((string) $order->payment_method),
                    };

                    foreach ($order->items as $item) {
                        $menuName = (string) ($item->menu_name ?? '');
                        $menuName = preg_replace("/[\r\n\t]+/", ' ', $menuName);

                        $qty = (int) ($item->qty ?? 0);
                        $unitPrice = (float) ($item->price ?? 0);
                        $itemSubtotal = property_exists($item, 'total') && $item->total !== null
                            ? (float) $item->total
                            : $unitPrice * $qty;

                        $rows[] = [
                            $timestamp,
                            $order->code,
                            $tableName,
                            $customerName,
                            $customerPhone,
                            $menuName,
                            $qty,
                            $unitPrice,
                            $itemSubtotal,
                            (float) $order->subtotal,
                            (float) $order->admin_fee,
                            (float) $order->total,
                            $paymentLabel,
                            (string) $order->status,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error('Google Sheets batch sync row build failed', [
                        'warung_id' => $warung->id,
                        'order_id' => $order->id ?? null,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    continue;
                }
            }

            if (empty($rows)) {
                Log::warning('Google Sheets batch sync: no rows built from orders', [
                    'warung_id' => $warung->id,
                    'orders_count' => $orders->count(),
                ]);
                return;
            }

            $sheetName = 'Transaksi';
            $range = urlencode($sheetName . '!A1:N1');
            $query = http_build_query([
                'valueInputOption' => 'RAW',
                'insertDataOption' => 'INSERT_ROWS',
            ]);

            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?{$query}";

            $response = Http::withToken($accessToken)->post($url, [
                'values' => $rows,
                'majorDimension' => 'ROWS',
            ]);

            if (!$response->ok()) {
                Log::warning('Google Sheets batch append failed', [
                    'warung_id' => $warung->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $warung->update(['google_sheets_last_synced_at' => now()]);

            Log::info('Google Sheets batch sync success', [
                'warung_id' => $warung->id,
                'orders_count' => $orders->count(),
                'rows_sent' => count($rows),
                'sheet' => $sheetName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Google Sheets batch sync fatal error', [
                'warung_id' => $warung->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected static function ensureTransaksiSheetExists(string $spreadsheetId, string $accessToken): void
    {
        try {
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}?fields=sheets.properties";
            $response = Http::withToken($accessToken)->get($url);

            if ($response->ok()) {
                $data = $response->json();
                $sheets = $data['sheets'] ?? [];

                foreach ($sheets as $sheet) {
                    $title = $sheet['properties']['title'] ?? null;
                    if ($title === 'Transaksi') {
                        return;
                    }
                }
            }

            $batchUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate";

            $batchResponse = Http::withToken($accessToken)->post($batchUrl, [
                'requests' => [
                    [
                        'addSheet' => [
                            'properties' => [
                                'title' => 'Transaksi',
                            ],
                        ],
                    ],
                ],
            ]);

            if (!$batchResponse->ok()) {
                Log::warning('Google Sheets ensure sheet failed', [
                    'status' => $batchResponse->status(),
                    'body' => $batchResponse->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Google Sheets ensure sheet fatal error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected static function ensureTransaksiHeaderExists(string $spreadsheetId, string $accessToken): void
    {
        try {
            $sheetName = 'Transaksi';
            $range = urlencode($sheetName . '!A1:N1');
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}";

            $response = Http::withToken($accessToken)->get($url);

            $needsHeader = true;

            if ($response->ok()) {
                $values = $response->json('values');
                if (!empty($values) && !empty($values[0])) {
                    $hasNonEmpty = false;
                    foreach ($values[0] as $cell) {
                        if ($cell !== null && $cell !== '') {
                            $hasNonEmpty = true;
                            break;
                        }
                    }
                    if ($hasNonEmpty) {
                        $needsHeader = false;
                    }
                }
            }

            if (!$needsHeader) {
                return;
            }

            $headerRow = [
                'Timestamp',
                'Order Code',
                'Meja',
                'Nama Pelanggan',
                'Nomor WA',
                'Nama Menu',
                'Qty',
                'Harga Satuan',
                'Subtotal Item',
                'Subtotal Order',
                'Admin Fee',
                'Total Order',
                'Metode Bayar',
                'Status',
            ];

            $updateUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}?valueInputOption=RAW";

            $updateResponse = Http::withToken($accessToken)->put($updateUrl, [
                'values' => [$headerRow],
                'majorDimension' => 'ROWS',
            ]);

            if (!$updateResponse->ok()) {
                Log::warning('Google Sheets header update failed', [
                    'status' => $updateResponse->status(),
                    'body' => $updateResponse->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Google Sheets ensure header fatal error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public static function getExistingOrderCodes(Warung $warung): array
    {
        try {
            if (!$warung->google_sheets_enabled || !$warung->google_sheets_spreadsheet_id) {
                return [];
            }

            $accessToken = self::getAccessToken();
            if (!$accessToken) return [];

            $spreadsheetId = $warung->google_sheets_spreadsheet_id;
            $sheetName = 'Transaksi';
            // Read Column B (Kode Pesanan)
            $range = urlencode($sheetName . '!B:B');

            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}";

            $response = Http::withToken($accessToken)->get($url);

            if (!$response->ok()) {
                Log::warning('Google Sheets read failed', ['body' => $response->body()]);
                return [];
            }

            $values = $response->json('values');
            if (empty($values)) return [];

            $codes = [];
            foreach ($values as $row) {
                if (isset($row[0])) {
                    $codes[] = $row[0];
                }
            }

            return $codes;
        } catch (\Exception $e) {
            Log::error("Google Sheets Read Error: " . $e->getMessage());
            return [];
        }
    }
}
