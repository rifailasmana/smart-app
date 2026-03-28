<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\OrderItemVoid;
use App\Models\AccountReceivable;
use App\Models\RestaurantTable;
use App\Models\Warung;
use App\Models\Ingredient;
use App\Models\Supplier;
use App\Models\StockLog;
use App\Models\RestockRequest;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TerminalController extends Controller
{
    /**
     * Halaman Pilih Terminal (Full-screen Mode)
     */
    public function index()
    {
        $user = auth()->user();
        $warung = Warung::find($user->warung_id);

        // Get all staff for quick switch
        $staff = User::where('warung_id', $user->warung_id)
            ->whereIn('role', ['waiter', 'kasir', 'dapur', 'kitchen', 'owner'])
            ->get();

        return view('terminal.index', compact('user', 'warung', 'staff'));
    }

    /**
     * Terminal Waiter
     */
    public function waiter()
    {
        $user = auth()->user();
        $warung = Warung::find($user->warung_id);
        $tables = RestaurantTable::where('warung_id', $user->warung_id)->get();
        $menuItems = MenuItem::where('warung_id', $user->warung_id)->where('active', true)->get();
        $categories = MenuItem::where('warung_id', $user->warung_id)
            ->where('active', true)
            ->distinct()
            ->pluck('category')
            ->filter();

        return view('terminal.waiter', compact('user', 'warung', 'tables', 'menuItems', 'categories'));
    }

    /**
     * Terminal Kasir
     */
    public function kasir()
    {
        $user = auth()->user();
        $warung = Warung::find($user->warung_id);
        $menuItems = MenuItem::where('warung_id', $user->warung_id)->where('active', true)->get();
        $categories = MenuItem::where('warung_id', $user->warung_id)
            ->where('active', true)
            ->distinct()
            ->pluck('category')
            ->filter();
        $tables = RestaurantTable::where('warung_id', $user->warung_id)->get();

        return view('terminal.kasir', compact('user', 'warung', 'menuItems', 'categories', 'tables'));
    }

    /**
     * Terminal Kitchen
     */
    public function kitchen()
    {
        $user = auth()->user();
        $warung = Warung::find($user->warung_id);
        $tables = RestaurantTable::where('warung_id', $user->warung_id)->get();

        return view('terminal.kitchen', compact('user', 'warung', 'tables'));
    }

    // --- API ENDPOINTS ---

    /**
     * Get orders for specific terminal role
     */
    public function getOrders(Request $request)
    {
        $user = auth()->user();
        $role = $request->query('role'); // waiter, kasir, kitchen

        $query = Order::where('warung_id', $user->warung_id)
            ->with(['table', 'items']);

        if ($role === 'waiter') {
            // Waiter sees active orders to track status
            $query->whereIn('stage', ['WAITING_CASHIER', 'READY_FOR_KITCHEN', 'COOKING', 'READY', 'SERVED']);
        } elseif ($role === 'kasir') {
            // Kasir sees orders waiting for approval OR orders served waiting for payment
            $query->whereIn('stage', ['WAITING_CASHIER', 'READY_FOR_KITCHEN', 'COOKING', 'READY', 'SERVED']);
        } elseif ($role === 'kitchen') {
            // Kitchen sees orders ready for production
            $query->whereIn('stage', ['COOKING', 'READY']);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    /**
     * Check voucher validity
     */
    public function checkVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth()->user();
        $code = strtoupper($request->code);

        $voucher = Voucher::where('warung_id', $user->warung_id)
            ->where('code', $code)
            ->where('is_used', 0)
            ->where(function($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->first();

        if (!$voucher) {
            return response()->json(['error' => 'Voucher tidak valid atau sudah digunakan/kadaluarsa'], 404);
        }

        return response()->json($voucher);
    }

    /**
     * Get tables for terminal
     */
    public function getTables()
    {
        $user = auth()->user();
        $tables = RestaurantTable::where('warung_id', $user->warung_id)->get();

        if ($tables->isEmpty()) {
            Log::warning("No tables found for warung_id: {$user->warung_id}");
        }

        return response()->json($tables);
    }

    /**
     * Create or update draft order (waiter)
     */
    public function storeOrder(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $user = auth()->user();

            $validated = $request->validate([
                'order_id' => 'nullable|exists:orders,id',
                'table_id' => 'required|exists:restaurant_tables,id',
                'customer_name' => 'nullable|string',
                'guest_category' => 'nullable|string',
                'order_type' => 'nullable|string',
                'reservation_name' => 'nullable|required_if:guest_category,RESERVED|string',
                'reservation_code' => 'nullable|required_if:guest_category,RESERVED|string',
                'merged_table_ids' => 'nullable|string',
                'items' => 'required|array',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.qty' => 'required|integer|min:1',
                'items.*.note' => 'nullable|string',
            ], [
                'reservation_name.required_if' => 'Nama reservasi wajib diisi untuk kategori Reserved.',
                'reservation_code.required_if' => 'Kode reservasi wajib diisi untuk kategori Reserved.',
            ]);

            if (isset($validated['order_id'])) {
                $order = Order::findOrFail($validated['order_id']);
                if ($order->stage !== 'DRAFT') {
                    return response()->json(['error' => 'Hanya draft yang dapat diperbarui'], 400);
                }
                // Clear old items
                $order->items()->delete();
            } else {
                // Check if table is already occupied by non-draft order
                $tableIdsToCheck = [$validated['table_id']];
                if ($validated['merged_table_ids']) {
                    $mergedIds = json_decode($validated['merged_table_ids'], true);
                    if (is_array($mergedIds)) $tableIdsToCheck = array_merge($tableIdsToCheck, $mergedIds);
                }

                $existingActive = Order::whereIn('table_id', $tableIdsToCheck)
                    ->where('warung_id', $user->warung_id)
                    ->whereIn('stage', ['WAITING_CASHIER', 'READY_FOR_KITCHEN', 'COOKING', 'READY', 'SERVED'])
                    ->first();

                if ($existingActive) {
                    return response()->json(['error' => 'Salah satu meja sedang digunakan oleh pesanan lain'], 400);
                }

                $order = Order::create([
                    'warung_id' => $user->warung_id,
                    'waiter_id' => $user->role === 'waiter' ? $user->id : null,
                    'kasir_id' => $user->role === 'kasir' ? $user->id : null,
                    'customer_name' => $validated['reservation_name'] ?? $validated['customer_name'] ?? 'Guest',
                    'code' => 'T' . strtoupper(uniqid()),
                    'table_id' => $validated['table_id'],
                    'stage' => $user->role === 'kasir' ? 'COOKING' : 'DRAFT',
                    'status' => 'pending',
                    'subtotal' => 0,
                    'total' => 0,
                    'guest_category' => $validated['guest_category'] ?? 'REGULER',
                    'order_type' => $validated['order_type'] ?? 'DINE_IN',
                    'reservation_name' => $validated['reservation_name'] ?? null,
                    'reservation_code' => $validated['reservation_code'] ?? null,
                    'merged_table_ids' => $validated['merged_table_ids'] ?? null,
                    'sent_to_kitchen_at' => $user->role === 'kasir' ? now() : null,
                ]);
            }

            $total = 0;
            foreach ($validated['items'] as $itemData) {
                $menuItem = MenuItem::find($itemData['menu_item_id']);
                $subtotal = $menuItem->price * $itemData['qty'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'menu_name' => $menuItem->name,
                    'qty' => $itemData['qty'],
                    'price' => $menuItem->price,
                    'note' => $itemData['note'] ?? null,
                    'status' => $user->role === 'kasir' ? 'cooking' : 'pending',
                    'cooking_at' => $user->role === 'kasir' ? now() : null,
                ]);
                $total += $subtotal;
            }

            $order->update([
                'table_id' => $validated['table_id'],
                'customer_name' => $validated['reservation_name'] ?? $validated['customer_name'] ?? $order->customer_name ?? 'Guest',
                'subtotal' => $total,
                'total' => $total,
                'guest_category' => $validated['guest_category'] ?? $order->guest_category,
                'order_type' => $validated['order_type'] ?? $order->order_type,
                'reservation_name' => $validated['reservation_name'] ?? $order->reservation_name,
                'reservation_code' => $validated['reservation_code'] ?? $order->reservation_code,
                'merged_table_ids' => $validated['merged_table_ids'] ?? $order->merged_table_ids,
            ]);

            // Sync table status
            $this->syncTableStatuses($order);

            return response()->json($order->load('items'));
        });
    }

    private function syncTableStatuses($order)
    {
        $tableIds = [$order->table_id];
        if ($order->merged_table_ids) {
            $mergedIds = json_decode($order->merged_table_ids, true);
            if (is_array($mergedIds)) {
                $tableIds = array_merge($tableIds, $mergedIds);
            }
        }

        if ($order->stage === 'DONE' || $order->stage === 'VOID') {
            // Check if tables have other active orders before freeing
            foreach ($tableIds as $tid) {
                $hasOther = Order::where('table_id', $tid)
                    ->where('warung_id', $order->warung_id)
                    ->whereNotIn('stage', ['DONE', 'VOID'])
                    ->where('id', '!=', $order->id)
                    ->exists();

                if (!$hasOther) {
                    RestaurantTable::where('id', $tid)->update(['status' => 'available']);
                }
            }
        } else {
            RestaurantTable::whereIn('id', $tableIds)->update(['status' => 'occupied']);
        }
    }

    /**
     * Void an item from an active order
     */
    public function voidItem(Request $request, Order $order, OrderItem $item)
    {
        $user = auth()->user();
        if ($user->warung_id !== $order->warung_id || $order->id !== $item->order_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (in_array($order->status, ['paid', 'cancelled', 'invoiced'])) {
            return response()->json(['error' => 'Pesanan sudah selesai, tidak bisa melakukan VOID'], 400);
        }

        $validated = $request->validate([
            'qty' => 'nullable|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'pin' => 'nullable|string'
        ]);

        $voidQty = $validated['qty'] ?? $item->qty;
        $reason = $validated['reason'] ?? null;

        return DB::transaction(function () use ($order, $item, $voidQty, $reason) {
            $user = auth()->user();

            // Cap voidQty to item's qty
            $voidQty = min($voidQty, $item->qty);

            Log::info("Item VOID: Order #{$order->code}, Item: {$item->menu_name}, VoidQty: {$voidQty}, OriginalQty: {$item->qty}, By: " . ($user->name ?? 'system') . ($reason ? ", Reason: {$reason}" : ''));

            // Record void audit
            OrderItemVoid::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'menu_item_id' => $item->menu_item_id,
                'qty' => $voidQty,
                'prev_qty' => $item->qty,
                'reason' => $reason,
                'voided_by' => $user->id ?? null,
                'voided_by_role' => $user->role ?? null,
                // 'manager_pin_used' => $validated['pin'] ?? null, // Re-enable if pin is passed
            ]);

            if ($voidQty >= $item->qty) {
                // Remove item entirely
                $item->delete();
            } else {
                // Update quantity directly instead of decrement
                $item->update(['qty' => $item->qty - $voidQty]);
            }

            // Recalculate order total
            $order->refresh();
            $newSubtotal = $order->items->sum(function ($i) {
                return $i->price * $i->qty;
            });

            $order->update([
                'subtotal' => $newSubtotal,
                'total' => $newSubtotal + ($order->admin_fee ?? 0) - ($order->diskon_manual ?? 0),
                'notes' => ($order->notes ? $order->notes . ' | ' : '') . ($reason ? "VOID: {$reason}" : 'VOID by user')
            ]);

            // If order has no items left, delete the order and free tables when appropriate
            if ($order->items()->count() === 0) {
                $tableIds = [$order->table_id];
                if ($order->merged_table_ids) {
                    $mergedIds = json_decode($order->merged_table_ids, true);
                    if (is_array($mergedIds)) $tableIds = array_merge($tableIds, $mergedIds);
                }

                foreach ($tableIds as $tid) {
                    $hasOther = Order::where('table_id', $tid)
                        ->where('warung_id', $order->warung_id)
                        ->whereNotIn('stage', ['DONE', 'VOID'])
                        ->where('id', '!=', $order->id)
                        ->exists();

                    if (!$hasOther) {
                        RestaurantTable::where('id', $tid)->update(['status' => 'available']);
                    }
                }

                $order->delete();

                return response()->json(['success' => true, 'message' => 'Item berhasil di-VOID, order kosong dihapus', 'order_deleted' => true]);
            }

            return response()->json(['success' => true, 'message' => 'Item berhasil di-VOID']);
        });
    }

    /**
     * Finalize payment for an active order
     */
    public function finalizePayment(Request $request, Order $order)
    {
        $user = auth()->user();
        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            // Accept frontend values but normalize below to DB enum (kasir,qris,gateway)
            'payment_method' => 'required|in:cash,qris,card,other,tunai,edc,invoice,gateway,kasir',
            'amount_paid' => 'required|numeric',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_id' => 'nullable'
        ]);

        // Ensure relationships are loaded
        $order->load(['items', 'table']);

        try {
            return DB::transaction(function () use ($user, $order, $validated) {
                $isInvoice = (strtolower($validated['payment_method']) === 'invoice');
                $discount = $validated['discount_amount'] ?? 0;
                $discountId = $validated['discount_id'] ?? null;
                $subtotal = (float) $order->subtotal;
                $adminFee = (float) ($order->admin_fee ?? 0);
                $finalTotal = max(0, $subtotal + $adminFee - $discount);

                // 1. Update Order status
                $order->update([
                    'status' => $isInvoice ? 'invoiced' : 'paid',
                    'stage' => 'DONE',
                    'kasir_id' => $user->id,
                    'paid_at' => now(),
                    // Normalize to DB enum: kasir, qris, gateway
                    'payment_method' => (function ($pm) {
                        $pm = strtolower((string)$pm);
                        $map = [
                            'tunai' => 'kasir',
                            'cash' => 'kasir',
                            'invoice' => 'kasir',
                            'kasir' => 'kasir',
                            'qris' => 'qris',
                            'edc' => 'gateway',
                            'card' => 'gateway',
                            'gateway' => 'gateway',
                            'other' => 'kasir',
                        ];
                        return $map[$pm] ?? 'kasir';
                    })($validated['payment_method']),
                    'amount_paid' => $validated['amount_paid'],
                    'diskon_manual' => $discount,
                    'total' => $finalTotal,
                    'revenue_recognized_at' => $order->created_at ?? now(), // Accrual
                ]);

                // 2. Burn voucher if used
                if ($discountId) {
                    $voucher = \App\Models\Voucher::find($discountId);
                    if ($voucher) {
                        $voucher->update(['is_used' => 1]);
                    }
                }

                // 3. If Invoice, create AccountReceivable record
                if ($isInvoice) {
                    AccountReceivable::create([
                        'warung_id' => $order->warung_id,
                        'order_id' => $order->id,
                        'table_id' => $order->table_id,
                        'table_number' => $order->table ? $order->table->name : 'N/A',
                        'customer_name' => $order->customer_name ?? 'Guest',
                        'order_code' => $order->code,
                        'subtotal' => $subtotal,
                        'admin_fee' => $adminFee,
                        'discount' => $discount,
                        'total' => $finalTotal,
                        'status' => 'outstanding',
                        'revenue_recognized_at' => $order->created_at ?? now(),
                        'cashier_id' => $user->id,
                        'cashier_name' => $user->name,
                        'items_snapshot' => $order->items->map(fn($i) => [
                            'menu_name' => $i->menu_name,
                            'qty' => $i->qty,
                            'price' => $i->price,
                        ])->toArray(),
                    ]);
                }

                // 3. Free Table
                $this->syncTableStatuses($order);

                // 4. Stock deduction
                try {
                    StockService::reduceStockForOrder($order);
                } catch (\Exception $e) {
                    Log::error("Stock reduction failed during payment: " . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => $isInvoice ? 'Pesanan berhasil dipindahkan ke Invoice' : 'Pembayaran berhasil diselesaikan',
                    // Include related warung/table so frontend can open receipt URL with proper context
                    'order' => $order->load('items', 'warung', 'table')
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Finalize Payment Failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Gagal memproses pembayaran: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString() // For debugging
            ], 500);
        }
    }

    /**
     * Get draft for a specific table
     */
    public function getTableDraft($tableId)
    {
        $user = auth()->user();
        $draft = Order::where('warung_id', $user->warung_id)
            ->where('table_id', $tableId)
            ->where('stage', 'DRAFT')
            ->with('items')
            ->first();

        return response()->json($draft);
    }

    /**
     * Submit to cashier (gatekeeper step 1)
     */
    public function submitToCashier(Order $order)
    {
        if ($order->stage !== 'DRAFT') {
            return response()->json(['error' => 'Pesanan bukan dalam status DRAFT'], 400);
        }

        $order->update([
            'stage' => 'WAITING_CASHIER',
            'waiter_id' => $order->waiter_id ?? auth()->id(),
            'submitted_to_cashier_at' => now(),
        ]);

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Approve and pay (gatekeeper step 2)
     */
    public function approveAndPay(Request $request, $orderId)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'table_id' => 'required|exists:restaurant_tables,id',
            'payment_method' => 'required|in:cash,qris,card,other,tunai,edc,invoice',
            'amount_paid' => 'required|numeric',
            'customer_name' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'voucher_code' => 'nullable|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'guest_category' => 'nullable|string',
            'order_type' => 'nullable|string',
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($user, $orderId, $validated) {
            if ($orderId === 'new') {
                $order = Order::create([
                    'warung_id' => $user->warung_id,
                    'table_id' => $validated['table_id'],
                    'kasir_id' => $user->id,
                    'customer_name' => $validated['customer_name'] ?? 'Walk-in',
                    'stage' => 'DONE',
                    'status' => (strtolower($validated['payment_method']) === 'invoice') ? 'invoiced' : 'paid',
                    'code' => 'POS-' . strtoupper(uniqid()),
                    'subtotal' => 0,
                    'total' => 0,
                    'guest_category' => $validated['guest_category'] ?? 'REGULER',
                    'order_type' => $validated['order_type'] ?? 'TAKE_AWAY',
                    'revenue_recognized_at' => now(),
                ]);
            } else {
                $order = Order::findOrFail($orderId);
            }

            // Sync items
            $order->items()->delete();
            $subtotal = 0;
            foreach ($validated['items'] as $itemData) {
                $menuItem = MenuItem::find($itemData['menu_item_id']);
                $itemSubtotal = $menuItem->price * $itemData['qty'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'menu_name' => $menuItem->name,
                    'qty' => $itemData['qty'],
                    'price' => $menuItem->price,
                    'note' => $itemData['note'] ?? null,
                    'status' => 'served',
                    'served_at' => now(),
                ]);
                $subtotal += $itemSubtotal;
            }

            $discountAmount = $validated['discount_amount'] ?? 0;
            if (isset($validated['discount_percent']) && $validated['discount_percent'] > 0) {
                $discountAmount = $subtotal * ($validated['discount_percent'] / 100);
            }

            $isInvoice = (strtolower($validated['payment_method']) === 'invoice');

            $order->update([
                'stage' => 'DONE',
                'status' => $isInvoice ? 'invoiced' : 'paid',
                'customer_name' => $validated['customer_name'] ?? $order->customer_name,
                'paid_at' => now(),
                'ordered_at' => $order->ordered_at ?? now(),
                'kasir_id' => $user->id,
                // Normalize to DB enum: kasir, qris, gateway
                'payment_method' => (function ($pm) {
                    $pm = strtolower((string)$pm);
                    $map = [
                        'tunai' => 'kasir',
                        'cash' => 'kasir',
                        'invoice' => 'kasir',
                        'kasir' => 'kasir',
                        'qris' => 'qris',
                        'edc' => 'gateway',
                        'card' => 'gateway',
                        'gateway' => 'gateway',
                        'other' => 'kasir',
                    ];
                    return $map[$pm] ?? 'kasir';
                })($validated['payment_method']),
                'amount_paid' => $validated['amount_paid'],
                'sent_to_kitchen_at' => $order->sent_to_kitchen_at ?? now(),
                'subtotal' => $subtotal,
                'diskon_manual' => $discountAmount,
                'total' => max(0, $subtotal - $discountAmount),
                'guest_category' => $validated['guest_category'] ?? $order->guest_category,
                'order_type' => $validated['order_type'] ?? $order->order_type,
                'notes' => ($validated['coupon_code'] ? "Kupon: " . $validated['coupon_code'] : $order->notes),
                'revenue_recognized_at' => $order->revenue_recognized_at ?? now(),
            ]);

            if ($isInvoice) {
                AccountReceivable::create([
                    'warung_id' => $order->warung_id,
                    'order_id' => $order->id,
                    'table_id' => $order->table_id,
                    'table_number' => $order->table ? $order->table->name : 'N/A',
                    'customer_name' => $order->customer_name ?? 'Guest',
                    'order_code' => $order->code,
                    'subtotal' => $subtotal,
                    'admin_fee' => $order->admin_fee ?? 0,
                    'discount' => $discountAmount,
                    'total' => $order->total,
                    'status' => 'outstanding',
                    'revenue_recognized_at' => $order->revenue_recognized_at,
                    'cashier_id' => $user->id,
                    'cashier_name' => $user->name,
                    'items_snapshot' => $order->items->map(fn($i) => [
                        'menu_name' => $i->menu_name,
                        'qty' => $i->qty,
                        'price' => $i->price,
                    ])->toArray(),
                ]);
            }

            // Sync table status
            $this->syncTableStatuses($order);

            StockService::reduceStockForOrder($order);

            return response()->json(['success' => true, 'order' => $order->load('items')]);
        });
    }

    /**
     * Approve order (Kasir step)
     */
    public function approveOrder(Order $order)
    {
        if ($order->stage !== 'WAITING_CASHIER') {
            return response()->json(['error' => 'Hanya pesanan menunggu approval yang dapat disetujui'], 400);
        }

        return DB::transaction(function () use ($order) {
            $order->update([
                'stage' => 'COOKING',
                'kasir_id' => auth()->id(),
                'approved_at' => now(),
                'sent_to_kitchen_at' => now(),
                'revenue_recognized_at' => now(), // Accrual basis: recognized when approved/started
            ]);

            // Update all items to cooking status
            $order->items()->update([
                'status' => 'cooking',
                'cooking_at' => now()
            ]);

            StockService::reduceStockForOrder($order);

            return response()->json(['success' => true, 'order' => $order->load('items')]);
        });
    }

    /**
     * Update status for a specific item (Partial Confirmation)
     */
    public function updateItemStatus(Request $request, Order $order, OrderItem $item)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'status' => 'required|in:ready,served',
        ]);

        if ($order->id !== $item->order_id) {
            return response()->json(['error' => 'Item tidak sesuai dengan pesanan'], 400);
        }

        $newStatus = $validated['status'];

        return DB::transaction(function () use ($order, $item, $newStatus) {
            if ($newStatus === 'ready') {
                // Kitchen marks item as ready
                $item->update([
                    'status' => 'ready',
                    'ready_at' => now()
                ]);
            } elseif ($newStatus === 'served') {
                // Waiter marks item as served
                if ($item->status !== 'ready') {
                    return response()->json(['error' => 'Hanya item berstatus READY yang bisa di-serve'], 400);
                }
                $item->update([
                    'status' => 'served',
                    'served_at' => now()
                ]);
            }

            // Check if all items are served to auto-update order stage
            $order->refresh();
            $totalItems = $order->items()->where('status', '!=', 'void')->count();
            $servedItems = $order->items()->where('status', 'served')->count();
            $readyItems = $order->items()->where('status', 'ready')->count();

            if ($servedItems === $totalItems && $totalItems > 0) {
                $order->update(['stage' => 'SERVED', 'status' => 'served', 'served_at' => now()]);
            } elseif ($readyItems + $servedItems === $totalItems && $totalItems > 0) {
                $order->update(['stage' => 'READY']);
            }

            return response()->json(['success' => true, 'item' => $item, 'order_stage' => $order->stage]);
        });
    }

    /**
     * Mark as served (Waiter step)
     */
    public function serveOrder(Order $order)
    {
        if ($order->stage !== 'READY') {
            return response()->json(['error' => 'Hanya pesanan yang sudah siap (READY) yang dapat di-serve'], 400);
        }

        $order->update([
            'stage' => 'SERVED',
            'status' => 'served',
            'served_at' => now(),
        ]);

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Update kitchen status
     */
    public function updateKitchenStatus(Request $request, Order $order)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'status' => 'required|in:COOKING,READY',
        ]);

        $updateData = ['stage' => $validated['status']];

        if ($validated['status'] === 'COOKING') {
            $updateData['kitchen_id'] = $user->id;
            $updateData['cooking_at'] = now();
        }

        if ($validated['status'] === 'READY') {
            $updateData['kitchen_done_at'] = now();
        }

        $order->update($updateData);

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Split order into two (for split bill)
     */
    public function splitOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($order, $validated) {
            // 1. Create new order (copy details)
            $newOrder = Order::create([
                'warung_id' => $order->warung_id,
                'table_id' => $order->table_id,
                'customer_name' => $order->customer_name ?? 'Split Bill',
                'waiter_id' => $order->waiter_id,
                'stage' => $order->stage,
                'status' => $order->status,
                'code' => $order->code . '-S' . rand(10, 99),
                'subtotal' => 0,
                'total' => 0,
                'guest_category' => $order->guest_category,
                'order_type' => $order->order_type,
                'reservation_name' => $order->reservation_name,
                'reservation_code' => $order->reservation_code,
                'merged_table_ids' => $order->merged_table_ids,
                'is_split_bill' => true,
            ]);

            $newSubtotal = 0;
            foreach ($validated['items'] as $itemData) {
                $item = OrderItem::find($itemData['order_item_id']);

                // If moving full qty
                if ($item->qty <= $itemData['qty']) {
                    $item->update(['order_id' => $newOrder->id]);
                    $newSubtotal += ($item->price * $item->qty);
                } else {
                    // Split qty
                    $newSubtotal += ($item->price * $itemData['qty']);

                    // Create new item for new order
                    OrderItem::create([
                        'order_id' => $newOrder->id,
                        'menu_item_id' => $item->menu_item_id,
                        'menu_name' => $item->menu_name,
                        'qty' => $itemData['qty'],
                        'price' => $item->price,
                        'note' => $item->note,
                    ]);

                    // Update old item qty
                    $item->decrement('qty', $itemData['qty']);
                }
            }

            $newOrder->update([
                'subtotal' => $newSubtotal,
                'total' => $newSubtotal,
            ]);

            // Recalculate old order
            $oldSubtotal = $order->items()->sum(DB::raw('price * qty'));
            $order->update([
                'subtotal' => $oldSubtotal,
                'total' => $oldSubtotal,
                'is_split_bill' => true,
            ]);

            return response()->json([
                'success' => true,
                'original_order' => $order->load('items'),
                'new_order' => $newOrder->load('items')
            ]);
        });
    }

    /**
     * Merge items from another table's order into this one
     */
    public function mergeOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'source_table_id' => 'required|exists:restaurant_tables,id',
        ]);

        return DB::transaction(function () use ($order, $validated) {
            $sourceOrder = Order::where('table_id', $validated['source_table_id'])
                ->whereIn('stage', ['DRAFT', 'WAITING_CASHIER'])
                ->first();

            if (!$sourceOrder) {
                return response()->json(['error' => 'Tidak ada pesanan aktif di meja tersebut'], 404);
            }

            // Move items
            foreach ($sourceOrder->items as $item) {
                // Check if item already exists in target order to combine qty
                $existing = OrderItem::where('order_id', $order->id)
                    ->where('menu_item_id', $item->menu_item_id)
                    ->where('note', $item->note)
                    ->first();

                if ($existing) {
                    $existing->increment('qty', $item->qty);
                    $item->delete();
                } else {
                    $item->update(['order_id' => $order->id]);
                }
            }

            // Record merged table ID
            $merged = json_decode($order->merged_table_ids, true) ?: [];
            if (!in_array($validated['source_table_id'], $merged)) {
                $merged[] = (int)$validated['source_table_id'];
                $order->update(['merged_table_ids' => json_encode($merged)]);
            }

            // Recalculate subtotal/total
            $newSubtotal = $order->items()->sum(DB::raw('price * qty'));
            $order->update(['subtotal' => $newSubtotal, 'total' => $newSubtotal]);

            // Delete empty source order
            $sourceOrder->delete();

            return response()->json(['success' => true, 'order' => $order->load('items')]);
        });
    }

    /**
     * Void an order (manager authorization required)
     */
    public function voidOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'pin' => 'required|string', // Manager PIN
        ]);

        // Simple PIN check for now, in production this should check against a manager user
        if ($validated['pin'] !== '1234') {
            return response()->json(['error' => 'PIN Manager tidak valid'], 403);
        }

        return DB::transaction(function () use ($order, $validated) {
            $order->update([
                'stage' => 'VOID',
                'status' => 'void',
                'notes' => ($order->notes ? $order->notes . " | " : "") . "VOID: " . $validated['reason'],
            ]);

            // If it was already paid, we might need to handle refund logic here
            // For now, just mark as void.

            // Restore stock if it was already deducted
            if ($order->sent_to_kitchen_at) {
                // Logic to restore stock could go here
            }

            // Sync table status
            $this->syncTableStatuses($order);

            return response()->json(['success' => true]);
        });
    }

    /**
     * Settle to Account / Invoice (Majar Signature)
     */
    public function checkoutToInvoice(Request $request, Order $order)
    {
        $user = auth()->user();

        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($user, $order) {
            // 1. Create AccountReceivable
            AccountReceivable::create([
                'warung_id' => $order->warung_id,
                'order_id' => $order->id,
                'table_id' => $order->table_id,
                'table_number' => $order->table ? $order->table->name : 'N/A',
                'customer_name' => $order->customer_name ?? 'Guest',
                'order_code' => $order->code,
                'subtotal' => $order->subtotal,
                'admin_fee' => $order->admin_fee,
                'total' => $order->total,
                'status' => 'outstanding',
                'revenue_recognized_at' => now(),
                'cashier_id' => $user->id,
                'cashier_name' => $user->name,
                'items_snapshot' => $order->items->map(fn($i) => [
                    'menu_name' => $i->menu_name,
                    'qty' => $i->qty,
                    'price' => $i->price,
                ])->toArray(),
            ]);

            // 2. Update Order
            $order->update([
                'status' => 'invoiced',
                'stage' => 'DONE',
                'kasir_id' => $user->id,
            ]);

            // 3. Free Table
            $this->syncTableStatuses($order);

            // 4. Stock deduction
            \App\Services\StockService::reduceStockForOrder($order);

            return response()->json(['success' => true, 'message' => 'Settle to Invoice berhasil. Meja tersedia.']);
        });
    }

    /**
     * Change order to take away and free the table
     */
    public function makeTakeaway(Order $order)
    {
        return DB::transaction(function () use ($order) {
            $order->update([
                'order_type' => 'TAKE_AWAY',
                'table_id' => null, 
            ]);

            $this->syncTableStatuses($order);

            return response()->json(['success' => true]);
        });
    }

    /**
     * Get transaction history for today
     */
    public function history()
    {
        $user = auth()->user();
        $orders = Order::with(['table', 'items', 'waiter', 'kasir', 'kitchen'])
            ->where('warung_id', $user->warung_id)
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Get summary report for today
     */
    public function reports()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $totalSales = Order::where('warung_id', $user->warung_id)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'served'])
            ->sum('total');

        $orderCount = Order::where('warung_id', $user->warung_id)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'served'])
            ->count();

        $voidCount = Order::where('warung_id', $user->warung_id)
            ->whereDate('created_at', $today)
            ->where('stage', 'VOID')
            ->count();

        $categorySales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.warung_id', $user->warung_id)
            ->whereDate('orders.created_at', $today)
            ->whereIn('orders.status', ['paid', 'served'])
            ->select('menu_items.category', DB::raw('SUM(order_items.price * order_items.qty) as total'))
            ->groupBy('menu_items.category')
            ->get();

        return response()->json([
            'date' => $today,
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'void_count' => $voidCount,
            'category_sales' => $categorySales,
        ]);
    }



    public function getActiveDiscounts(Request $request)
    {
        $user = auth()->user();
        
        // Fetch all coupons for this warung (if applicable) that are not used/expired
        $query = \App\Models\Coupon::query();
        
        if (\Schema::hasColumn('coupons', 'warung_id')) {
            $query->where('warung_id', $user->warung_id);
        }

        $coupons = $query->where('is_used', false)->get();

        return response()->json($coupons->map(function($c) {
            return [
                'id' => $c->id,
                'kode_diskon' => $c->code,
                'tipe' => 'Persen', // Based on manager UI showing %
                'nilai' => $c->value ?? $c->discount_percent ?? 0,
                'is_used' => (bool)$c->is_used,
                'category_restriction' => $c->category_restriction ?? 'Semua'
            ];
        }));
    }

    /**
     * Check and apply coupon
     */
    public function checkCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $coupon = \App\Models\Coupon::where('code', $request->code)
            ->where('expires_at', '>', now())
            ->whereRaw('uses < max_uses')
            ->first();

        if (!$coupon) {
            return response()->json(['error' => 'Kupon tidak valid atau sudah kadaluarsa'], 404);
        }

        return response()->json([
            'code' => $coupon->code,
            'discount_percent' => $coupon->discount_percent
        ]);
    }
}
