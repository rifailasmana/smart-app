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
        $user = auth()->user();
        
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'table_id' => 'required|exists:restaurant_tables,id',
            'guest_category' => 'nullable|string',
            'order_type' => 'nullable|string',
            'reservation_name' => 'nullable|string',
            'reservation_code' => 'nullable|string',
            'merged_table_ids' => 'nullable|string', // Expecting JSON array
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        return DB::transaction(function() use ($user, $validated) {
            if (isset($validated['order_id'])) {
                $order = Order::findOrFail($validated['order_id']);
                if ($order->stage !== 'DRAFT') {
                    return response()->json(['error' => 'Can only update DRAFT orders'], 400);
                }
                // Clear existing items to rewrite them
                $order->items()->delete();
            } else {
                $order = Order::create([
                    'warung_id' => $user->warung_id,
                    'table_id' => $validated['table_id'],
                    'waiter_id' => $user->id,
                    'stage' => 'DRAFT',
                    'status' => 'pending',
                    'code' => 'T' . strtoupper(uniqid()),
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
                'subtotal' => $total,
                'total' => $total,
                'guest_category' => $validated['guest_category'] ?? $order->guest_category,
                'order_type' => $validated['order_type'] ?? $order->order_type,
                'reservation_name' => $validated['reservation_name'] ?? $order->reservation_name,
                'reservation_code' => $validated['reservation_code'] ?? $order->reservation_code,
                'merged_table_ids' => $validated['merged_table_ids'] ?? $order->merged_table_ids,
            ]);

            // Update tables status to occupied
            $tableIds = [$validated['table_id']];
            if ($order->merged_table_ids) {
                $mergedIds = json_decode($order->merged_table_ids, true);
                if (is_array($mergedIds)) {
                    $tableIds = array_merge($tableIds, $mergedIds);
                }
            }
            RestaurantTable::whereIn('id', $tableIds)->update(['status' => 'occupied']);

            return response()->json($order->load('items'));
        });
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
            return response()->json(['error' => 'Order is not in DRAFT stage'], 400);
        }

        $order->update([
            'stage' => 'WAITING_CASHIER',
            'submitted_to_cashier_at' => now(),
        ]);

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Approve and pay (gatekeeper step 2)
     */
    public function approveAndPay(Request $request, Order $order)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,qris,card,other',
            'amount_paid' => 'required|numeric',
            'coupon_code' => 'nullable|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'items' => 'nullable|array', // Optional: edit items during approval
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        if (!in_array($order->stage, ['WAITING_CASHIER', 'CASHIER_APPROVED'])) {
            return response()->json(['error' => 'Order cannot be paid in current stage'], 400);
        }

        return DB::transaction(function() use ($user, $order, $validated) {
            // If items were edited
            if (isset($validated['items'])) {
                $order->items()->delete();
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
                    'subtotal' => $total,
                    'total' => $total,
                ]);
            }

            $discountAmount = 0;
            if (isset($validated['discount_percent']) && $validated['discount_percent'] > 0) {
                $discountAmount = $order->total * ($validated['discount_percent'] / 100);
            }

            $order->update([
                'stage' => 'READY_FOR_KITCHEN',
                'status' => 'paid',
                'paid_at' => now(),
                'kasir_id' => $user->id,
                'payment_method' => $validated['payment_method'] === 'cash' ? 'kasir' : 'gateway',
                'sent_to_kitchen_at' => now(),
                'discount' => $discountAmount,
                'total' => $order->total - $discountAmount,
                'notes' => $validated['coupon_code'] ? "Kupon: " . $validated['coupon_code'] : $order->notes,
            ]);

            // Mark coupon as used
            if (isset($validated['coupon_code'])) {
                \App\Models\Coupon::where('code', $validated['coupon_code'])->increment('uses');
            }

            // Deduct stock based on recipe
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
        $validated = $request->validate([
            'status' => 'required|in:COOKING,READY,DONE',
        ]);

        $order->update([
            'stage' => $validated['status'],
            'status' => match($validated['status']) {
                'COOKING' => 'preparing',
                'READY' => 'ready',
                'DONE' => 'served',
            }
        ]);

        if ($validated['status'] === 'DONE') {
            $order->update(['kitchen_done_at' => now()]);
            
            // Free the tables ONLY IF no other active orders exist for these tables
            $tableIds = [$order->table_id];
            if ($order->merged_table_ids) {
                $mergedIds = json_decode($order->merged_table_ids, true);
                if (is_array($mergedIds)) {
                    $tableIds = array_merge($tableIds, $mergedIds);
                }
            }

            foreach ($tableIds as $tId) {
                $otherActiveOrders = Order::where('warung_id', $order->warung_id)
                    ->where('id', '!=', $order->id)
                    ->where('stage', '!=', 'DONE')
                    ->where(function($q) use ($tId) {
                        $q->where('table_id', $tId)
                          ->orWhereJsonContains('merged_table_ids', (int)$tId)
                          ->orWhereJsonContains('merged_table_ids', (string)$tId);
                    })
                    ->exists();

                if (!$otherActiveOrders) {
                    RestaurantTable::where('id', $tId)->update(['status' => 'available']);
                }
            }
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
                'waiter_id' => $order->waiter_id,
                'stage' => $order->stage,
                'status' => $order->status,
                'code' => $order->code . '-S' . rand(10, 99), // Add random suffix to avoid duplicates if multiple splits
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
