<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Ingredient;
use App\Models\User;
use App\Models\Voucher;
use App\Models\RestockRequest;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $tab = $request->get('tab', 'dashboard');
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 🏠 1. DASHBOARD DATA
        $todaySales = Order::where('warung_id', $warungId)->where('status', 'paid')->whereDate('created_at', $today)->sum('total');
        $yesterdaySales = Order::where('warung_id', $warungId)->where('status', 'paid')->whereDate('created_at', $yesterday)->sum('total');
        
        $monthSales = Order::where('warung_id', $warungId)->where('status', 'paid')->whereMonth('created_at', now()->month)->sum('total');
        
        $todayTransactions = Order::where('warung_id', $warungId)->where('status', 'paid')->whereDate('created_at', $today)->count();
        
        // Profit calculation (Revenue - HPP)
        $todayProfit = Order::where('warung_id', $warungId)->where('status', 'paid')->whereDate('created_at', $today)->get()->sum(function($order) {
            return $order->items->sum(function($item) {
                $menuItem = MenuItem::find($item->menu_item_id);
                $hpp = $menuItem ? $menuItem->recipes->sum(fn($r) => ($r->ingredient->avg_price ?? 0) * $r->quantity) : 0;
                return $item->qty * ($item->price - $hpp);
            });
        });

        $bestSelling = OrderItem::whereIn('order_id', Order::where('warung_id', $warungId)->where('status', 'paid')->whereMonth('created_at', now()->month)->pluck('id'))
            ->select('menu_name', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('menu_name')
            ->orderBy('total_qty', 'desc')
            ->limit(5)
            ->get();

        $lowStockCount = Ingredient::where('warung_id', $warungId)->whereColumn('stock', '<=', 'min_stock')->count();

        // 📊 2. ANALYTICS DATA
        $salesAnalytics = Order::where('warung_id', $warungId)
            ->where('status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 📦 3. INVENTORY INSIGHT
        $inventoryValue = Ingredient::where('warung_id', $warungId)->get()->sum(fn($i) => $i->stock * ($i->avg_price ?? 0));
        $mostUsedIngredients = DB::table('stock_logs')
            ->join('ingredients', 'stock_logs.ingredient_id', '=', 'ingredients.id')
            ->where('ingredients.warung_id', $warungId)
            ->where('stock_logs.type', 'usage')
            ->select('ingredients.name', DB::raw('SUM(quantity) as total_usage'), 'ingredients.unit')
            ->groupBy('ingredients.id', 'ingredients.name', 'ingredients.unit')
            ->orderBy('total_usage', 'desc')
            ->limit(5)
            ->get();

        // ✅ 5. APPROVAL PANEL
        $pendingRestock = RestockRequest::where('warung_id', $warungId)->where('status', 'pending')->count();
        $pendingPayroll = Payroll::whereHas('user', fn($q) => $q->where('warung_id', $warungId))->where('status', 'draft')->count();
        // Simulation for voids/discounts
        $pendingVoids = Order::where('warung_id', $warungId)->where('status', 'pending_void')->count(); 

        // 👥 4. EMPLOYEES
        $employees = User::where('warung_id', $warungId)->whereNotIn('role', ['owner', 'admin'])->get();

        // 🎟️ 8. PROMO (Voucher)
        $coupons = Voucher::where('warung_id', $warungId)
            ->orderBy('created_at', 'desc')
            ->get();

        $coupons->each(function ($v) {
            $v->orders_count = $v->is_used ? 1 : 0;
        });

        // 🍽️ 7. MENU CONTROL
        $menuItems = MenuItem::where('warung_id', $warungId)->get();

        return view('dashboard.owner', compact(
            'tab', 'todaySales', 'yesterdaySales', 'monthSales', 'todayTransactions', 'todayProfit', 
            'bestSelling', 'lowStockCount', 'salesAnalytics', 'inventoryValue', 'mostUsedIngredients',
            'pendingRestock', 'pendingPayroll', 'pendingVoids', 'employees', 'coupons', 'menuItems'
        ));
    }

    public function updatePricing(Request $request, MenuItem $menuItem)
    {
        $request->validate(['price' => 'required|numeric|min:0']);
        $menuItem->update(['price' => $request->price]);
        return back()->with('success', 'Harga menu berhasil diperbarui');
    }

    public function toggleMenu(MenuItem $menuItem)
    {
        $menuItem->update(['active' => !$menuItem->active]);
        return back()->with('success', 'Status menu berhasil diubah');
    }
}
