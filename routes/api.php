<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\TableManagementController;
use App\Http\Controllers\TerminalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ===== AUTH (used by Nuxt SPA) =====

Route::post('/auth/login', function (Request $request) {
    $credentials = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if (!Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
        return response()->json(['message' => 'Username atau password salah'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('nuxt-spa')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => [
            'id'        => $user->id,
            'name'      => $user->name,
            'username'  => $user->username,
            'role'      => $user->role,
            'warung_id' => $user->warung_id,
            'warung'    => $user->warung ? [
                'id'   => $user->warung->id,
                'name' => $user->warung->name,
                'slug' => $user->warung->slug ?? $user->warung->code,
            ] : null,
        ],
    ]);
});

// ===== AUTHENTICATED ROUTES (Sanctum token) =====

Route::middleware('auth:sanctum')->group(function () {

    // Current user
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('warung');
        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'username'  => $user->username,
            'role'      => $user->role,
            'warung_id' => $user->warung_id,
            'warung'    => $user->warung ? [
                'id'   => $user->warung->id,
                'name' => $user->warung->name,
                'slug' => $user->warung->slug ?? $user->warung->code,
            ] : null,
        ]);
    });

    // Logout (revoke current token)
    Route::post('/auth/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    });

    // ===== TERMINAL DATA =====

    // Tables for the user's warung
    Route::get('/terminal/tables', function (Request $request) {
        $user = $request->user();
        $tables = RestaurantTable::where('warung_id', $user->warung_id)
            ->get(['id', 'name', 'seats as capacity', 'status']);
        return response()->json($tables);
    });

    // Active menu items for the user's warung
    Route::get('/terminal/menu-items', function (Request $request) {
        $user = $request->user();
        $items = MenuItem::where('warung_id', $user->warung_id)
            ->where('active', true)
            ->get(['id', 'name', 'category', 'price', 'image as image_url']);

        $categories = $items->pluck('category')->unique()->filter()->values();

        return response()->json([
            'items'      => $items,
            'categories' => $categories,
        ]);
    });

    // Order operations (proxy to TerminalController which already returns JSON)
    Route::get('/terminal/orders',                              [TerminalController::class, 'getOrders']);
    Route::post('/terminal/orders',                             [TerminalController::class, 'storeOrder']);
    Route::get('/terminal/orders/history',                      [TerminalController::class, 'history']);
    Route::get('/terminal/tables/{table}/draft',                [TerminalController::class, 'getTableDraft']);
    Route::post('/terminal/orders/{order}/submit-to-cashier',   [TerminalController::class, 'submitToCashier']);
    Route::post('/terminal/orders/{order}/approve',             [TerminalController::class, 'approveOrder']);
    Route::post('/terminal/orders/{order}/items/{item}/void',   [TerminalController::class, 'voidItem']);
    Route::post('/terminal/orders/{order}/approve-and-pay',     [TerminalController::class, 'approveAndPay']);
    Route::post('/terminal/orders/{order}/finalize-payment',    [TerminalController::class, 'finalizePayment']);
    Route::post('/terminal/orders/{order}/kitchen-status',      [TerminalController::class, 'updateKitchenStatus']);
    Route::post('/terminal/orders/{order}/serve',               [TerminalController::class, 'serveOrder']);
    Route::post('/terminal/orders/{order}/split',               [TerminalController::class, 'splitOrder']);
    Route::post('/terminal/orders/{order}/merge',               [TerminalController::class, 'mergeOrder']);
    Route::post('/terminal/orders/{order}/void',                [TerminalController::class, 'voidOrder']);
    Route::get('/terminal/reports/summary',                     [TerminalController::class, 'reports']);
    Route::post('/terminal/coupons/check',                      [TerminalController::class, 'checkCoupon']);
    Route::post('/terminal/vouchers/check',                     [TerminalController::class, 'checkVoucher']);

    // Table Management Routes
    Route::post('/tables/move',   [TableManagementController::class, 'moveTable']);
    Route::post('/tables/merge',  [TableManagementController::class, 'mergeTables']);
    Route::post('/tables/{table}/reset', [TableManagementController::class, 'resetTable']);
    Route::post('/terminal/orders/{order}/make-takeaway', [TerminalController::class, 'makeTakeaway']);

    // Billing Routes
    Route::post('/billing/split',  [BillingController::class, 'splitBill']);
    Route::post('/billing/coupon', [BillingController::class, 'applyCoupon']);

    // ===== DASHBOARD DATA =====

    Route::get('/dashboard/owner', function (Request $request) {
        $wid   = $request->user()->warung_id;
        $today = \App\Models\Order::where('warung_id', $wid)->whereDate('created_at', today());

        $revenueToday = (clone $today)->where('status', 'PAID')->sum('total');
        $revenueMonth = \App\Models\Order::where('warung_id', $wid)->where('status', 'PAID')
            ->whereMonth('created_at', now()->month)->sum('total');
        $orderCount   = (clone $today)->count();
        $avgOrder     = $orderCount > 0 ? round($revenueToday / $orderCount) : 0;

        $topItems = \App\Models\OrderItem::whereHas('order', fn($q) =>
        $q->where('warung_id', $wid)->where('status', 'PAID'))
            ->selectRaw('menu_name as name, SUM(qty) as qty')
            ->groupBy('menu_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $recentOrders = \App\Models\Order::where('warung_id', $wid)
            ->with(['table', 'kasir'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id'           => $o->id,
                'order_number' => $o->code,
                'table'        => $o->table?->name,
                'cashier'      => $o->kasir?->name ?? '–',
                'total'        => $o->total,
                'status'       => $o->status,
            ]);

        $chart = \App\Models\Order::where('warung_id', $wid)
            ->where('status', 'PAID')
            ->selectRaw('MONTH(created_at) as m, SUM(total) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('m')
            ->pluck('total', 'm');
        $revenueChart = collect(range(1, 12))->map(fn($m) => (float)($chart[$m] ?? 0))->values();

        return response()->json([
            'revenue_today'  => (float)$revenueToday,
            'revenue_month'  => (float)$revenueMonth,
            'order_count'    => $orderCount,
            'avg_order'      => $avgOrder,
            'top_items'      => $topItems,
            'recent_orders'  => $recentOrders,
            'revenue_chart'  => $revenueChart,
        ]);
    });

    Route::get('/dashboard/manager', function (Request $request) {
        $wid   = $request->user()->warung_id;
        $today = \App\Models\Order::where('warung_id', $wid)->whereDate('created_at', today());

        $shifts = \App\Models\StaffShift::where('warung_id', $wid)
            ->whereDate('started_at', today())
            ->with('user')
            ->get()
            ->map(fn($s) => [
                'id'   => $s->id,
                'name' => $s->user?->name ?? '–',
                'time' => optional($s->started_at)->format('H:i') . ' – ' . (optional($s->ended_at)->format('H:i') ?? 'sekarang'),
                'role' => $s->role ?? $s->user?->role ?? '–',
            ]);

        return response()->json([
            'revenue_today'        => (float)(clone $today)->where('status', 'PAID')->sum('total'),
            'order_count'          => (clone $today)->count(),
            'table_turns'          => (clone $today)->where('status', 'PAID')->count(),
            'avg_service_minutes'  => 0,
            'shifts'               => $shifts,
            'order_status'         => [
                'pending' => (clone $today)->whereIn('status', ['DRAFT', 'WAITING_CASHIER'])->count(),
                'serving' => (clone $today)->whereIn('status', ['COOKING', 'READY', 'SERVED'])->count(),
                'done'    => (clone $today)->where('status', 'PAID')->count(),
            ],
        ]);
    });

    Route::get('/dashboard/hrd', function (Request $request) {
        $wid      = $request->user()->warung_id;
        $userIds  = \App\Models\User::where('warung_id', $wid)->pluck('id');
        $employees = $userIds->count();

        $presentToday = \App\Models\Attendance::whereIn('user_id', $userIds)
            ->whereDate('date', today())->where('status', 'hadir')->count();

        $attendance = \App\Models\Attendance::whereIn('user_id', $userIds)
            ->whereDate('date', today())
            ->with('user')
            ->get()
            ->map(fn($a) => [
                'id'        => $a->id,
                'name'      => $a->user?->name ?? '–',
                'check_in'  => $a->clock_in,
                'check_out' => $a->clock_out,
                'status'    => $a->status ?? 'hadir',
            ]);

        $payrollMonth = \App\Models\Payroll::whereIn('user_id', $userIds)
            ->whereMonth('created_at', now()->month)->get();

        return response()->json([
            'total_employees' => $employees,
            'present_today'   => $presentToday,
            'pending_leave'   => 0,
            'paid_count'      => $payrollMonth->where('status', 'paid')->count(),
            'payroll_total'   => (float)$payrollMonth->sum('basic_salary'),
            'bonus_total'     => (float)$payrollMonth->sum('allowances'),
            'attendance'      => $attendance,
        ]);
    });

    Route::get('/dashboard/inventory', function (Request $request) {
        $wid         = $request->user()->warung_id;
        $ingredients = \App\Models\Ingredient::where('warung_id', $wid)->get(['id', 'name', 'unit', 'stock', 'min_stock']);

        return response()->json([
            'total_items'        => $ingredients->count(),
            'low_stock_count'    => $ingredients->filter(fn($i) => $i->stock > 0 && $i->stock <= $i->min_stock)->count(),
            'out_of_stock_count' => $ingredients->filter(fn($i) => $i->stock <= 0)->count(),
            'pending_restock'    => \App\Models\RestockRequest::where('warung_id', $wid)->where('status', 'pending')->count(),
            'ingredients'        => $ingredients,
        ]);
    });

    Route::get('/dashboard/admin', function (Request $request) {
        $users = \App\Models\User::with('warung')->get()->map(fn($u) => [
            'id'       => $u->id,
            'name'     => $u->name,
            'username' => $u->username,
            'role'     => $u->role,
            'warung'   => $u->warung?->name,
        ]);
        $warungs = \App\Models\Warung::withCount(['users', 'tables'])->get()->map(fn($w) => [
            'id'          => $w->id,
            'name'        => $w->name,
            'address'     => $w->address,
            'user_count'  => $w->users_count,
            'table_count' => $w->tables_count,
        ]);

        return response()->json([
            'total_users'      => $users->count(),
            'total_warungs'    => $warungs->count(),
            'total_tables'     => \App\Models\RestaurantTable::count(),
            'total_menu_items' => \App\Models\MenuItem::count(),
            'users'            => $users,
            'warungs'          => $warungs,
        ]);
    });
});
