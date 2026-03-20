<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Voucher;
use App\Models\RestockRequest;
use App\Models\Ingredient;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManagerController extends Controller
{
    public function index(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $tab = $request->get('tab', 'dashboard');

        // 🏠 1. DASHBOARD DATA (Real-time Feel)
        $today = Carbon::today();
        $todaySales = Order::where('warung_id', $warungId)
            ->where('status', 'paid')
            ->whereDate('created_at', $today)
            ->sum('total');
        
        $activeOrdersCount = Order::where('warung_id', $warungId)
            ->whereIn('status', ['pending', 'preparing', 'ready', 'served'])
            ->count();
        
        $tables = RestaurantTable::where('warung_id', $warungId)->get();
        $occupiedTables = $tables->where('status', 'occupied')->count();
        
        $delayedOrders = Order::where('warung_id', $warungId)
            ->where('status', 'preparing')
            ->where('updated_at', '<', now()->subMinutes(15))
            ->get();

        $lowStockCount = Ingredient::where('warung_id', $warungId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // 📊 2. SALES MONITORING
        $todayTransactions = Order::with(['items.menuItem', 'waiter', 'kasir'])
            ->where('warung_id', $warungId)
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $paymentMethods = Order::where('warung_id', $warungId)
            ->where('status', 'paid')
            ->whereDate('created_at', $today)
            ->select('payment_method', DB::raw('sum(total) as total'))
            ->groupBy('payment_method')
            ->get();

        // 🍽️ 3. MENU MANAGEMENT
        $menuItems = MenuItem::where('warung_id', $warungId)->orderBy('category')->get();

        // ✅ 4. APPROVAL CENTER
        $pendingRestockRequests = RestockRequest::with(['ingredient', 'user'])
            ->where('warung_id', $warungId)
            ->where('status', 'pending')
            ->get();
        
        // Simulating void/refund/discount requests as we don't have a model for them yet
        // In a real app, you'd have an ApprovalRequest model
        $pendingApprovals = []; 

        // 👥 5. STAFF MONITORING
        $staffOnShift = User::where('warung_id', $warungId)
            ->whereIn('role', ['kasir', 'waiter', 'kitchen', 'dapur'])
            ->get(); // In a real app, check shift status

        // 📦 6. INVENTORY CONTROL (LIGHT)
        $ingredients = Ingredient::where('warung_id', $warungId)->get();

        // 🎟️ 7. COUPON / DISCOUNT CONTROL
        $coupons = Voucher::where('warung_id', $warungId)->get();

        // 🔄 8. ORDER MONITORING
        $allActiveOrders = Order::with(['items', 'table'])
            ->where('warung_id', $warungId)
            ->whereIn('status', ['pending', 'preparing', 'ready', 'served'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('dashboard.manager', compact(
            'tab',
            'todaySales',
            'activeOrdersCount',
            'tables',
            'occupiedTables',
            'delayedOrders',
            'lowStockCount',
            'todayTransactions',
            'paymentMethods',
            'menuItems',
            'pendingRestockRequests',
            'staffOnShift',
            'ingredients',
            'coupons',
            'allActiveOrders'
        ));
    }

    public function toggleMenuStatus(MenuItem $menuItem)
    {
        $menuItem->update(['active' => !$menuItem->active]);
        return back()->with('success', 'Status menu berhasil diubah');
    }

    public function approveRestock(RestockRequest $request)
    {
        $request->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);
        return back()->with('success', 'Permintaan restock disetujui');
    }

    public function rejectRestock(RestockRequest $request)
    {
        $request->update(['status' => 'rejected']);
        return back()->with('success', 'Permintaan restock ditolak');
    }

    public function voidOrder(Request $request, Order $order)
    {
        $order->update(['status' => 'cancelled']);
        return back()->with('success', 'Order berhasil di-VOID');
    }

    public function storeTable(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'seats' => 'required|integer|min:1',
        ]);

        RestaurantTable::create([
            'warung_id' => auth()->user()->warung_id,
            'name' => $request->name,
            'seats' => $request->seats,
            'status' => 'available'
        ]);

        return back()->with('success', 'Meja baru berhasil ditambahkan');
    }

    public function deleteTable(RestaurantTable $table)
    {
        if ($table->status !== 'available') {
            return back()->with('error', 'Meja yang sedang digunakan atau reservasi tidak dapat dihapus');
        }
        
        $table->delete();
        return back()->with('success', 'Meja berhasil dihapus');
    }

    public function mergeTables(Request $request)
    {
        $request->validate([
            'main_table_id' => 'required|exists:restaurant_tables,id',
            'merge_table_id' => 'required|exists:restaurant_tables,id|different:main_table_id',
        ]);

        $mainTable = RestaurantTable::findOrFail($request->main_table_id);
        $mergeTable = RestaurantTable::findOrFail($request->merge_table_id);

        if ($mainTable->status !== 'available' || $mergeTable->status !== 'available') {
            return back()->with('error', 'Hanya meja yang tersedia yang dapat digabung');
        }

        // Logic penggabungan meja (simulasi dengan mengubah nama dan kapasitas)
        $mainTable->update([
            'name' => $mainTable->name . ' + ' . $mergeTable->name,
            'seats' => $mainTable->seats + $mergeTable->seats,
        ]);

        $mergeTable->delete();

        return back()->with('success', 'Meja berhasil digabung');
    }

    public function createCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:vouchers,code',
            'value' => 'required|numeric|min:0',
            'category_restriction' => 'required|string',
        ]);

        Voucher::create([
            'warung_id' => auth()->user()->warung_id,
            'code' => $request->code,
            'type' => 'percentage', // default
            'value' => $request->value,
            'category_restriction' => $request->category_restriction,
            'is_used' => false
        ]);

        return back()->with('success', 'Kupon promo berhasil dibuat');
    }
}
