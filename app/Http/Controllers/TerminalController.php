<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\MenuItem;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Models\Warung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            // Waiter sees their own drafts and all active orders for status tracking
            $query->where(function($q) use ($user) {
                $q->where('waiter_id', $user->id)
                  ->orWhereIn('stage', ['WAITING_CASHIER', 'CASHIER_APPROVED', 'READY_FOR_KITCHEN', 'COOKING', 'READY']);
            });
        } elseif ($role === 'kasir') {
            // Kasir sees orders waiting for them or recently approved
            $query->whereIn('stage', ['WAITING_CASHIER', 'CASHIER_APPROVED', 'READY_FOR_KITCHEN']);
        } elseif ($role === 'kitchen') {
            // Kitchen sees orders ready for production
            $query->whereIn('stage', ['READY_FOR_KITCHEN', 'COOKING', 'READY']);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    /**
     * Create or update draft order (waiter)
     */
    public function storeOrder(Request $request)
    {
        return DB::transaction(function() use ($request) {
            $user = auth()->user();
            
            $validated = $request->validate([
                'order_id' => 'nullable|exists:orders,id',
                'table_id' => 'required|exists:restaurant_tables,id',
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
                $existingActive = Order::where('table_id', $validated['table_id'])
                    ->where('warung_id', $user->warung_id)
                    ->whereIn('stage', ['WAITING_CASHIER', 'READY_FOR_KITCHEN', 'COOKING', 'READY'])
                    ->first();
                
                if ($existingActive) {
                    return response()->json(['error' => 'Meja sedang digunakan oleh pesanan lain'], 400);
                }

                $order = Order::create([
                    'warung_id' => $user->warung_id,
                    'waiter_id' => $user->id,
                    'customer_name' => $validated['reservation_name'] ?? 'Guest',
                    'code' => 'T' . strtoupper(uniqid()),
                    'table_id' => $validated['table_id'],
                    'stage' => 'DRAFT',
                    'status' => 'pending',
                    'subtotal' => 0,
                    'total' => 0,
                    'guest_category' => $validated['guest_category'] ?? 'REGULER',
                    'order_type' => $validated['order_type'] ?? 'DINE_IN',
                    'reservation_name' => $validated['reservation_name'] ?? null,
                    'reservation_code' => $validated['reservation_code'] ?? null,
                    'merged_table_ids' => $validated['merged_table_ids'] ?? null,
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
                ]);
                $total += $subtotal;
            }

            $order->update([
                'table_id' => $validated['table_id'],
                'customer_name' => $validated['reservation_name'] ?? $order->customer_name ?? 'Guest',
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
            'payment_method' => 'required|in:cash,qris,card,other',
            'amount_paid' => 'required|numeric',
            'customer_name' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'voucher_code' => 'nullable|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'guest_category' => 'nullable|string',
            'order_type' => 'nullable|string',
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        return DB::transaction(function() use ($user, $orderId, $validated) {
            if ($orderId === 'new') {
                $order = Order::create([
                    'warung_id' => $user->warung_id,
                    'table_id' => $validated['table_id'],
                    'kasir_id' => $user->id,
                    'customer_name' => 'Walk-in',
                    'stage' => 'READY_FOR_KITCHEN',
                    'status' => 'paid',
                    'code' => 'POS-' . strtoupper(uniqid()),
                    'subtotal' => 0,
                    'total' => 0,
                    'guest_category' => $validated['guest_category'] ?? 'REGULER',
                    'order_type' => $validated['order_type'] ?? 'TAKE_AWAY',
                ]);
            } else {
                $order = Order::findOrFail($orderId);
                if (!in_array($order->stage, ['WAITING_CASHIER', 'CASHIER_APPROVED', 'DRAFT'])) {
                    return response()->json(['error' => 'Order cannot be paid in current stage'], 400);
                }
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
                ]);
                $subtotal += $itemSubtotal;
            }

            $discountAmount = 0;
            if (isset($validated['discount_percent']) && $validated['discount_percent'] > 0) {
                $discountAmount = $subtotal * ($validated['discount_percent'] / 100);
            }

            $order->update([
                'stage' => 'READY_FOR_KITCHEN',
                'status' => 'paid',
                'customer_name' => $validated['customer_name'] ?? $order->customer_name,
                'paid_at' => now(),
                'ordered_at' => now(),
                'kasir_id' => $user->id,
                'payment_method' => match($validated['payment_method']) {
                    'cash' => 'kasir',
                    'qris' => 'qris',
                    default => 'gateway',
                },
                'sent_to_kitchen_at' => now(),
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'total' => $subtotal - $discountAmount,
                'guest_category' => $validated['guest_category'] ?? $order->guest_category,
                'order_type' => $validated['order_type'] ?? $order->order_type,
                'notes' => ($validated['coupon_code'] ? "Kupon: " . $validated['coupon_code'] : $order->notes),
            ]);

            // Mark coupon as used
            if (isset($validated['coupon_code'])) {
                \App\Models\Coupon::where('code', $validated['coupon_code'])->increment('uses');
            }

            // Mark voucher as used
            if (isset($validated['voucher_code'])) {
                $v = \App\Models\Voucher::where('code', $validated['voucher_code'])->first();
                if ($v) {
                    $v->update(['is_used' => true, 'used_at' => now()]);
                }
            }

            // Sync table status
            $this->syncTableStatuses($order);

            // Deduct stock
            try {
                \App\Http\Controllers\RecipeController::deductStockForOrder($order);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Stock deduction failed for Order #{$order->id}: " . $e->getMessage());
            }

            return response()->json(['success' => true, 'order' => $order->load('items')]);
        });
    }

    /**
     * Update kitchen status (gatekeeper step 3)
     */
    public function updateKitchenStatus(Request $request, Order $order)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'status' => 'required|in:COOKING,READY,DONE',
        ]);

        $updateData = [
            'stage' => $validated['status'],
            'status' => match($validated['status']) {
                'COOKING' => 'preparing',
                'READY' => 'ready',
                'DONE' => 'served',
            }
        ];

        if ($validated['status'] === 'COOKING') {
            $updateData['kitchen_id'] = $user->id;
            $updateData['cooking_at'] = now();
        }

        if ($validated['status'] === 'DONE') {
            $updateData['kitchen_done_at'] = now();
        }

        if ($validated['status'] === 'DONE') {
            $updateData['served_at'] = now();
        }

        $order->update($updateData);

        if ($validated['status'] === 'DONE') {
            // Sync table status
            $this->syncTableStatuses($order);
        }

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

        return DB::transaction(function() use ($order, $validated) {
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

        return DB::transaction(function() use ($order, $validated) {
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

        return DB::transaction(function() use ($order, $validated) {
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

    /**
     * Check and apply voucher
     */
    public function checkVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'cart_categories' => 'required|array'
        ]);
        
        $voucher = \App\Models\Voucher::where('code', $request->code)
            ->where('warung_id', auth()->user()->warung_id)
            ->first();

        if (!$voucher) {
            return response()->json(['error' => 'Voucher tidak ditemukan'], 404);
        }

        if ($voucher->is_used) {
            return response()->json(['error' => 'Voucher Sudah Kadaluwarsa'], 400);
        }

        if ($voucher->category_restriction) {
            $restrictedCats = array_map('trim', explode(',', $voucher->category_restriction));
            $found = false;
            foreach ($request->cart_categories as $cartCat) {
                if (in_array($cartCat, $restrictedCats)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return response()->json(['error' => 'Voucher tidak sesuai kategori item (Butuh: ' . $voucher->category_restriction . ')'], 400);
            }
        }

        return response()->json([
            'code' => $voucher->code,
            'type' => $voucher->type,
            'value' => $voucher->value
        ]);
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
