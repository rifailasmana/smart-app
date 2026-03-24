<?php

namespace App\Http\Controllers;

use App\Helpers\SubdomainHelper;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AccountReceivable;
use App\Models\RestaurantTable;
use App\Models\Warung;
use App\Models\MenuItem;
use App\Services\NotificationService;
use App\Services\OrderCodeGenerator;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Show menu for customer ordering
     */
    public function create(Request $request)
    {
        $warung = $request->attributes->get('warung');
        
        if (!$warung) {
            $warungCode = $request->get('warung');
            $warung = Warung::where('code', $warungCode)->firstOrFail();
        }
        
        $tableId = $request->get('meja') ?: $request->get('table_id');

        if ($tableId) {
            $table = RestaurantTable::where('warung_id', $warung->id)
                ->where('id', $tableId)
                ->firstOrFail();
        } else {
            $table = null;
        }

        $secureParam = $request->get('secure');
        if ($secureParam) {
            $tableKey = $table ? $table->id : null;
            if (!SubdomainHelper::validateMenuToken($warung, $tableKey, $secureParam)) {
                abort(403);
            }
        }

        $customerOrders = session()->get('customer_orders', []);
        $warungOrderCodes = $customerOrders[$warung->id] ?? [];

        $myOrders = collect();

        if (!empty($warungOrderCodes)) {
            $myOrders = Order::where('warung_id', $warung->id)
                ->whereIn('code', $warungOrderCodes)
                ->whereIn('status', ['pending', 'verified', 'preparing', 'ready', 'served'])
                ->with('table')
                ->orderBy('created_at', 'desc')
                ->get();

            $expiredCodes = [];

            foreach ($myOrders as $order) {
                if ($this->shouldExpireOrder($order)) {
                    $order->update(['status' => 'cancelled']);
                    NotificationService::sendOrderNotification($order, 'cancelled');
                    $expiredCodes[] = $order->code;
                }
            }

            if (!empty($expiredCodes)) {
                $myOrders = $myOrders->filter(function ($order) use ($expiredCodes) {
                    return !in_array($order->code, $expiredCodes, true);
                });
            }

            $customerOrders[$warung->id] = $myOrders->pluck('code')->values()->all();
            session()->put('customer_orders', $customerOrders);
        }

        $menuItemsQuery = MenuItem::where('warung_id', $warung->id)
            ->orderBy('category')
            ->orderBy('name');

        $bestToday = OrderItem::whereIn('order_id',
                Order::where('warung_id', $warung->id)
                    ->whereDate('created_at', today())
                    ->whereIn('status', ['pending', 'verified', 'preparing', 'ready', 'served', 'paid'])
                    ->pluck('id')
            )
            ->selectRaw('menu_name, SUM(qty) as total_qty')
            ->groupBy('menu_name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->pluck('menu_name')
            ->toArray();

        $menuItems = $menuItemsQuery->get()->map(function ($item) use ($bestToday) {
            $item->is_best_today = in_array($item->name, $bestToday, true);
            return $item;
        });

        $tables = RestaurantTable::where('warung_id', $warung->id)
            ->orderBy('name')
            ->get();

        return view('customer.menu', compact('warung', 'table', 'menuItems', 'myOrders', 'tables'));
    }

    /**
     * Store order to database
     */
    public function store(Request $request)
    {
        $warung = $request->attributes->get('warung');
        
        if (!$warung) {
            $warung = Warung::findOrFail($request->warung_id);
        }

        $itemsInput = $request->input('items');
        if (is_string($itemsInput)) {
            $decodedItems = json_decode($itemsInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['items' => $decodedItems]);
            }
        }

        $validated = $request->validate([
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'customer_name' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.menu_id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:kasir,qris,gateway',
            'payment_channel' => 'nullable|string|max:50|required_if:payment_method,gateway',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'secure' => 'nullable|string',
            'category' => 'nullable|in:Regular,Reservation,Majar Priority,Majar Signature',
        ]);

        if (!empty($validated['customer_phone'])) {
            $validated['customer_phone'] = $this->normalizeCustomerPhone($validated['customer_phone']);
        }

        if (isset($validated['secure'])) {
            $tableKey = $validated['table_id'] ?? null;
            if (!SubdomainHelper::validateMenuToken($warung, $tableKey, $validated['secure'])) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Link pemesanan tidak valid',
                    ],
                    403
                );
            }
        }

        try {
            // Calculate subtotal from items
            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_id']);
                
                // Use promo price if active
                $price = ($menuItem->promo_aktif && $menuItem->harga_promo > 0) ? $menuItem->harga_promo : $menuItem->price;
                
                $itemTotal = $price * $item['qty'];
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'menu_name' => $menuItem->name,
                    'qty' => $item['qty'],
                    'price' => $price,
                    'total' => $itemTotal,
                ];
            }

            // Calculate admin fee (1% for every transaction)
            $adminFee = round($subtotal * 0.01, 2);
            $total = $subtotal + $adminFee;

            // Generate unique order code
            $code = OrderCodeGenerator::generate($warung);

            // Generate queue number (urutan order hari ini)
            $todayOrders = Order::where('warung_id', $warung->id)
                ->whereDate('created_at', today())
                ->count();
            $queueNumber = str_pad($todayOrders + 1, 3, '0', STR_PAD_LEFT);

            $order = Order::create([
                'warung_id' => $warung->id,
                'table_id' => $validated['table_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'code' => $code,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'admin_fee' => $adminFee,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_channel' => $validated['payment_channel'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'queue_number' => $queueNumber,
                'notes' => $validated['notes'] ?? null,
                'category' => $validated['category'] ?? 'Regular',
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_name' => $item['menu_name'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);
            }

            NotificationService::sendOrderNotification($order, 'new_order');

            $customerOrders = session()->get('customer_orders', []);
            $warungOrderCodes = $customerOrders[$warung->id] ?? [];
            $warungOrderCodes[] = $order->code;
            $warungOrderCodes = array_values(array_unique($warungOrderCodes));
            $customerOrders[$warung->id] = $warungOrderCodes;
            session()->put('customer_orders', $customerOrders);

            if (app()->environment('local')) {
                if ($order->payment_method === 'qris') {
                    NotificationService::sendOrderNotification($order, 'qris_dummy');
                } elseif ($order->payment_method === 'gateway') {
                    NotificationService::sendOrderNotification($order, 'gateway_dummy');
                }
            }

            // Build redirect URL ke subdomain
            $subdomain = strtolower($warung->code ?? $warung->slug);
            $protocol = request()->getScheme();
            $port = request()->getPort();
            $portSuffix = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
            $host = request()->getHost();
            $baseDomain = env('SMARTORDER_DOMAIN', 'smartapp.local');
            $domain = str_contains($host, 'localhost') ? 'localhost' : $baseDomain;
            $statusUrl = $protocol . '://' . $subdomain . '.' . $domain . $portSuffix . '/order-status?code=' . $order->code;

            return response()->json([
                'success' => true,
                'code' => $order->code,
                'queue_number' => $order->queue_number,
                'message' => "Pesanan diterima! Kode: {$order->code}, Antrian: {$order->queue_number}",
                'redirect' => $statusUrl,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply manual discount to an order
     */
    public function updateDiscount(Request $request, $id)
    {
        $request->validate([
            'diskon_manual' => 'required|numeric|min:0',
            'alasan_diskon' => 'nullable|string|max:255',
            'owner_password' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);
        $warung = $order->warung;

        if ($warung->require_owner_auth_for_discount) {
            $user = auth()->user();
            
            if (!in_array($user->role, ['owner', 'admin', 'kasir'], true)) {
                if ($request->filled('owner_password')) {
                    $owner = \App\Models\User::where('warung_id', $warung->id)->where('role', 'owner')->first();
                    if (!$owner || !\Illuminate\Support\Facades\Hash::check($request->owner_password, $owner->password)) {
                        return response()->json(['success' => false, 'message' => 'Password owner salah.'], 403);
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'Membutuhkan otorisasi owner.'], 403);
                }
            }
        }

        if ($warung->max_discount_percent > 0) {
            $maxDiscount = $order->subtotal * ($warung->max_discount_percent / 100);
            if ($request->diskon_manual > $maxDiscount) {
                return response()->json(['success' => false, 'message' => 'Diskon melebihi batas maksimal (' . $warung->max_discount_percent . '%).'], 422);
            }
        }
        
        // Ensure discount doesn't exceed total
        if ($request->diskon_manual > $order->subtotal) {
             return response()->json(['success' => false, 'message' => 'Diskon tidak boleh melebihi subtotal.'], 422);
        }

        $order->diskon_manual = $request->diskon_manual;
        $order->alasan_diskon = $request->alasan_diskon;
        
        // Recalculate total
        $order->total = max(0, $order->subtotal + $order->admin_fee - $order->diskon_manual);
        $order->save();

        return response()->json(['success' => true, 'message' => 'Diskon berhasil diterapkan.', 'data' => $order]);
    }

    /**
     * Show order status
     */
    public function status(Request $request)
    {
        $warung = $request->attributes->get('warung');
        
        if (!$warung) {
            $warungCode = $request->get('warung');
            $warung = Warung::where('code', $warungCode)->firstOrFail();
        }
        
        $order = Order::where('code', $request->code)
            ->where('warung_id', $warung->id)
            ->with('items', 'warung', 'table')
            ->firstOrFail();

        if ($this->shouldExpireOrder($order)) {
            $order->update(['status' => 'cancelled']);
            NotificationService::sendOrderNotification($order, 'cancelled');
            $order->refresh();
        }

        return view('customer.status', compact('order'));
    }

    /**
     * Generate digital receipt content for customer download
     */
    public function receipt(Request $request)
    {
        $warung = $request->attributes->get('warung');

        if (!$warung) {
            $warungCode = $request->get('warung');
            $warung = Warung::where('code', $warungCode)->firstOrFail();
        }

        $order = Order::where('code', $request->code)
            ->where('warung_id', $warung->id)
            ->with('items', 'warung', 'table')
            ->firstOrFail();

        if ($order->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Struk hanya tersedia setelah pembayaran selesai.',
            ], 403);
        }

        $content = NotificationService::buildReceiptMessage($order);

        return response()->json([
            'success' => true,
            'code' => $order->code,
            'content' => $content,
        ]);
    }

    public function printReceipt(Request $request)
    {
        $warung = $request->attributes->get('warung');

        if (!$warung) {
            $warungCode = $request->get('warung');
            $warung = Warung::where('code', $warungCode)->firstOrFail();
        }

        $order = Order::where('code', $request->code)
            ->where('warung_id', $warung->id)
            ->with('items', 'warung', 'table')
            ->firstOrFail();

        // Optional: Check if paid, but maybe they want to print before paying?
        // Usually receipts are for paid orders.
        // if ($order->status !== 'paid') { abort(403, 'Order not paid'); }

        return view('dashboard.receipt_print', compact('order', 'warung'));
    }

    protected function shouldExpireOrder(Order $order): bool
    {
        if (!in_array($order->payment_method, ['qris', 'gateway'], true)) {
            return false;
        }

        if ($order->status !== 'pending') {
            return false;
        }

        return $order->created_at->diffInMinutes(now()) >= 10;
    }

    /**
     * Server-Sent Events untuk real-time update status
     */
    public function streamStatus(Request $request)
    {
        $warung = Warung::where('code', $request->warung)->firstOrFail();
        $order = Order::where('code', $request->code)
            ->where('warung_id', $warung->id)
            ->firstOrFail();

        $controller = $this;

        return response()->stream(function () use ($order, $controller) {
            while (true) {
                $order->refresh();

                if ($controller->shouldExpireOrder($order)) {
                    $order->update(['status' => 'cancelled']);
                    NotificationService::sendOrderNotification($order, 'cancelled');
                    $order->refresh();
                } elseif (in_array($order->payment_method, ['qris', 'gateway'], true) && $order->status === 'pending') {
                    if ($order->created_at->diffInSeconds(Carbon::now()) >= 5) {
                        $order->update(['status' => 'verified']);
                        NotificationService::sendOrderNotification($order, 'verified');
                    }
                }

                echo "data: " . json_encode([
                    'status' => $order->status,
                    'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                ]) . "\n\n";

                ob_flush();
                flush();

                // Update setiap 2 detik
                sleep(2);

                if (in_array($order->status, ['paid', 'cancelled'], true)) {
                    break;
                }
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Verify payment untuk kasir (approve pembayaran)
     * Flow: pending → verified (kasir approve) → preparing → ready → served → paid
     */
    public function verifyPayment(Request $request, Order $order)
    {
        $user = auth()->user();
        
        // Verify order belongs to user's warung
        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only Kasir can verify payment
        if (!in_array($user->role, ['kasir', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only verify if status is 'pending'
        if ($order->status !== 'pending') {
            return response()->json([
                'error' => 'Order can only be verified when status is pending',
                'current_status' => $order->status
            ], 400);
        }

        // Update status to verified (payment approved)
        $order->update(['status' => 'verified']);

        // Send notification
        NotificationService::sendOrderNotification($order, 'verified');

        return response()->json([
            'success' => true, 
            'message' => 'Pembayaran diverifikasi! Pesanan siap diproses dapur.',
            'order_status' => 'verified'
        ]);
    }

    /**
     * Process payment untuk kasir (mark as paid - final step)
     */
    public function processPayment(Request $request, Order $order)
    {
        $user = auth()->user();
        
        // Verify order belongs to user's warung
        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only Kasir can mark as paid
        if (!in_array($user->role, ['kasir', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Can only mark as paid if status is 'served', 'ready', or 'invoiced'
        if (!in_array($order->status, ['served', 'ready', 'invoiced'])) {
            return response()->json([
                'error' => 'Order can only be marked as paid when status is served, ready, or invoiced',
                'current_status' => $order->status
            ], 400);
        }

        $isInvoicePayment = ($order->status === 'invoiced');

        $order->update([
            'status' => 'paid',
            'kasir_id' => $user->id,
            'paid_at' => now(),
        ]);

        // If it's an invoice payment, update the AccountReceivable record
        if ($isInvoicePayment) {
            AccountReceivable::where('order_id', $order->id)
                ->where('status', 'outstanding')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
        }

        // Deduct stock based on recipe (only if not already deducted)
        // For invoiced orders, stock was already deducted during checkoutToInvoice
        if (!$isInvoicePayment) {
            try {
                \App\Http\Controllers\RecipeController::deductStockForOrder($order);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Stock deduction failed for Order #{$order->id}: " . $e->getMessage());
            }
        }

        // Send notification
        NotificationService::sendOrderNotification($order, 'payment');

        // Sync to Google Sheets (Transaksi sheet) if enabled
        GoogleSheetService::syncOrdersToTransaksi($order->warung, collect([$order]));

        // Sync reports (Harian, Bulanan, Tahunan)
        try {
            $allPaidOrders = Order::where('warung_id', $order->warung_id)
                ->where('status', 'paid')
                ->orderBy('created_at')
                ->get();

            GoogleSheetService::syncReports($order->warung, $allPaidOrders);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to sync reports: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Pembayaran berhasil']);
    }

    /**
     * Settle to Account / Invoice (Majar Signature)
     * Memindahkan pesanan aktif ke piutang tanpa mengunci meja.
     */
    public function checkoutToInvoice(Request $request, Order $order)
    {
        $user = auth()->user();

        // Verify order belongs to user's warung
        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only Kasir/Admin can settle to invoice
        if (!in_array($user->role, ['kasir', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Can only settle if status is 'served' (or maybe 'verified' if they want to settle earlier)
        if (!in_array($order->status, ['verified', 'preparing', 'ready', 'served'])) {
            return response()->json([
                'error' => 'Pesanan tidak dapat dipindahkan ke invoice pada status saat ini',
                'current_status' => $order->status
            ], 400);
        }

        return DB::transaction(function () use ($user, $order) {
            // 1. Create AccountReceivable record (Persistent Audit Trail)
            $receivable = AccountReceivable::create([
                'warung_id' => $order->warung_id,
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'table_number' => $order->table ? $order->table->name : 'N/A',
                'customer_name' => $order->customer_name ?? 'Guest',
                'order_code' => $order->code,
                'subtotal' => $order->subtotal,
                'admin_fee' => $order->admin_fee,
                'discount' => $order->diskon_manual,
                'total' => $order->total,
                'status' => 'outstanding',
                'revenue_recognized_at' => now(), // Accrual Accounting: recognize revenue today
                'cashier_id' => $user->id,
                'cashier_name' => $user->name,
                'items_snapshot' => $order->items->map(function($item) {
                    return [
                        'menu_name' => $item->menu_name,
                        'qty' => $item->qty,
                        'price' => $item->price,
                        'subtotal' => $item->price * $item->qty
                    ];
                })->toArray(),
                'meta' => [
                    'checkout_at' => now()->toDateTimeString(),
                    'original_status' => $order->status,
                    'table_id' => $order->table_id,
                ]
            ]);

            // 2. Update Order status
            $order->update([
                'status' => 'invoiced', // New status for invoiced orders
                'kasir_id' => $user->id,
            ]);

            // 3. Update Table status to AVAILABLE (Separation of Logic)
            if ($order->table) {
                $order->table->update(['status' => 'available']);
            }

            // 4. Inventory Decrement (Accrual Accounting)
            try {
                \App\Http\Controllers\RecipeController::deductStockForOrder($order);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Stock deduction failed for Invoiced Order #{$order->id}: " . $e->getMessage());
            }

            // 5. Send Notification
            NotificationService::sendOrderNotification($order, 'payment');

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dipindahkan ke Piutang (Invoice). Meja sekarang tersedia.',
                'receivable_id' => $receivable->id
            ]);
        });
    }

    /**
     * Edit order quantity (untuk kasir/waiter)
     */
    public function editQuantity(Request $request, Order $order)
    {
        if (auth()->user()->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'item_id' => 'required|exists:order_items,id',
            'qty' => 'required|integer|min:1',
        ]);

        $item = OrderItem::findOrFail($validated['item_id']);
        $oldPrice = $item->price * $item->qty;
        $newPrice = $item->price * $validated['qty'];
        
        $item->update(['qty' => $validated['qty']]);

        // Recalculate order total
        $subtotal = $order->items()->sum(\DB::raw('price * qty'));
        $adminFee = round($subtotal * 0.01, 2);
        $total = $subtotal + $adminFee;

        $order->update(['subtotal' => $subtotal, 'admin_fee' => $adminFee, 'total' => $total]);

        return response()->json(['success' => true, 'new_total' => $total]);
    }

    /**
     * Mark order as served (untuk waiter)
     */
    public function markServed(Request $request, Order $order)
    {
        $user = auth()->user();

        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!in_array($user->role, ['waiter', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validasi: Pesanan harus status 'ready' sebelum bisa di-serve
        if ($order->status !== 'ready') {
            return response()->json([
                'error' => 'Pesanan belum siap (Status: ' . ucfirst($order->status) . '). Tunggu dapur menyelesaikan pesanan.'
            ], 400);
        }

        $order->items()->update(['status' => 'served']);

        $order->update([
            'status' => 'served',
            'waiter_id' => $user->id,
        ]);
        NotificationService::sendOrderNotification($order, 'served');

        return response()->json(['success' => true, 'message' => 'Order marked as served']);
    }


    /**
     * Cancel order (Kasir only, before verification)
     */
    public function cancelOrder(Request $request, Order $order)
    {
        $user = auth()->user();
        
        // Only Kasir can cancel orders
        if (!in_array($user->role, ['kasir', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only cancel if status is 'pending' or 'verified'
        if (!in_array($order->status, ['pending', 'verified'])) {
            return response()->json([
                'error' => 'Order can only be cancelled when status is pending or verified',
                'current_status' => $order->status
            ], 400);
        }

        $order->update(['status' => 'cancelled']);
        NotificationService::sendOrderNotification($order, 'cancelled');

        return response()->json([
            'success' => true, 
            'message' => 'Order cancelled successfully!',
            'order_status' => 'cancelled'
        ]);
    }

    /**
     * Get orders for specific restaurant (Admin view)
     */
    public function restaurantOrders(Request $request, Warung $warung)
    {
        $user = auth()->user();
        
        // Only Admin can view orders from any restaurant
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $orders = Order::where('warung_id', $warung->id)
            ->with(['table', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'code' => $order->code,
                    'table' => $order->table ? $order->table->name : 'Takeaway',
                    'status' => $order->status,
                    'subtotal' => 'Rp ' . number_format($order->subtotal, 0),
                    'admin_fee' => 'Rp ' . number_format($order->admin_fee, 0),
                    'total' => 'Rp ' . number_format($order->total, 0),
                    'payment_method' => $order->payment_method,
                    'payment_channel' => $order->payment_channel,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            })
        ]);
    }

    private function normalizeCustomerPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            throw ValidationException::withMessages([
                'customer_phone' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        if (strpos($digits, '0') === 0) {
            $digits = '62' . substr($digits, 1);
        } elseif (strpos($digits, '62') === 0) {
        } elseif (strpos($digits, '8') === 0) {
            $digits = '62' . $digits;
        }

        $length = strlen($digits);

        if ($length < 10 || $length > 15) {
            throw ValidationException::withMessages([
                'customer_phone' => 'Nomor WhatsApp harus 10–15 digit setelah normalisasi.',
            ]);
        }

        return $digits;
    }

    /**
     * Manual sync to Google Sheets (for owner/admin)
     */
    public function syncToGoogleSheet(Request $request)
    {
        $user = auth()->user();
        
        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $warung = Warung::find($user->warung_id);
        if (!$warung) {
            return response()->json(['success' => false, 'message' => 'Warung not found'], 404);
        }

        if (!$warung->google_sheets_enabled || !$warung->google_sheets_spreadsheet_id) {
            return response()->json(['success' => false, 'message' => 'Google Sheets belum dikonfigurasi.'], 400);
        }

        try {
            // Get existing order codes from Sheet
            $existingCodes = GoogleSheetService::getExistingOrderCodes($warung);
            
            // Get all PAID orders that are NOT in the sheet
            $ordersToSync = Order::where('warung_id', $warung->id)
                ->where('status', 'paid') // Only sync paid orders
                ->whereNotIn('code', $existingCodes)
                ->orderBy('created_at', 'asc') // Sync oldest first
                ->get();

            if ($ordersToSync->isEmpty()) {
                return response()->json(['success' => true, 'message' => 'Semua pesanan sudah tersinkronisasi.']);
            }

            GoogleSheetService::syncOrdersToTransaksi($warung, $ordersToSync);

            $syncedCount = $ordersToSync->count();
            $message = "Berhasil menyinkronkan {$syncedCount} pesanan ke sheet Transaksi.";

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            \Log::error("Manual Sync Fatal Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
