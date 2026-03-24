<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\MenuItem;
use App\Models\OrderItem;
use App\Models\AccountReceivable;
use App\Models\Warung;
use App\Models\DailyClosure;
use App\Models\StaffShift;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Kasir dashboard - approve pembayaran, edit qty, toggle menu stock
     */
    public function kasir()
    {
        $user = auth()->user();
        
        // Validasi: user harus memiliki warung_id
        if (!$user->warung_id) {
            return redirect()->route('login')
                ->withErrors(['email' => 'User tidak memiliki restaurant. Silakan hubungi admin.']);
        }
        
        // Validasi: warung harus ada di database
        $warung = Warung::find($user->warung_id);
        if (!$warung) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Restaurant tidak ditemukan. Silakan hubungi admin.']);
        }

        $todayOrdersQuery = Order::where('warung_id', $user->warung_id)
            ->whereDate('created_at', today());

        $todayPaidQuery = (clone $todayOrdersQuery)->whereIn('status', ['paid', 'invoiced']);

        $ordersCount = (clone $todayPaidQuery)->count();
        $revenue = (clone $todayPaidQuery)->sum('total');
        $average = $ordersCount > 0 ? $revenue / $ordersCount : 0;

        $kasirDailyReport = [
            'orders' => (clone $todayOrdersQuery)->count(),
            'completed' => $ordersCount,
            'revenue' => $revenue,
            'average' => $average,
        ];

        $todayClosure = DailyClosure::where('warung_id', $user->warung_id)
            ->whereDate('date', today())
            ->first();
        
        // Orders yang perlu approval (pending)
        $pendingOrders = Order::where('warung_id', $user->warung_id)
            ->where('status', 'pending')
            ->with('table', 'items')
            ->orderBy('created_at', 'asc')
            ->get();

        $inProgressOrders = Order::where('warung_id', $user->warung_id)
            ->whereIn('status', ['verified', 'preparing', 'ready'])
            ->with('table', 'items')
            ->orderBy('created_at', 'asc')
            ->get();

        $queueHistory = Order::where('warung_id', $user->warung_id)
            ->whereDate('created_at', today())
            ->orderBy('queue_number')
            ->orderBy('created_at')
            ->with('table')
            ->get();

        // Orders yang sudah verified tapi belum paid (untuk final payment)
        $verifiedOrders = Order::where('warung_id', $user->warung_id)
            ->where('status', 'served')
            ->with('table', 'items')
            ->orderBy('created_at', 'asc')
            ->get();

        // Orders yang sudah selesai dibayar (pesanan selesai)
        $completedOrders = Order::where('warung_id', $user->warung_id)
            ->whereDate('created_at', today())
            ->where('status', 'paid')
            ->with('table', 'items')
            ->orderBy('created_at', 'desc')
            ->get();

        $menuItems = \App\Models\MenuItem::where('warung_id', $user->warung_id)
            ->orderBy('name')
            ->get();

        $outstandingInvoices = AccountReceivable::where('warung_id', $user->warung_id)
            ->where('status', 'outstanding')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.kasir', compact(
            'kasirDailyReport',
            'pendingOrders',
            'inProgressOrders',
            'verifiedOrders',
            'completedOrders',
            'menuItems',
            'todayClosure',
            'queueHistory',
            'outstandingInvoices'
        ));
    }

    /**
     * Kitchen dashboard - hanya lihat pesanan verified, update ke preparing → ready
     */
    public function kitchen()
    {
        $user = auth()->user();
        
        // Validasi: user harus memiliki warung_id
        if (!$user->warung_id) {
            return redirect()->route('login')
                ->withErrors(['email' => 'User tidak memiliki restaurant. Silakan hubungi admin.']);
        }
        
        // Validasi: warung harus ada di database
        $warung = Warung::find($user->warung_id);
        if (!$warung) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Restaurant tidak ditemukan. Silakan hubungi admin.']);
        }
        
        // Hanya lihat pesanan yang sudah verified (approved oleh kasir)
        $verifiedOrders = Order::where('warung_id', $user->warung_id)
            ->where('status', 'verified')
            ->with(['table', 'items' => function ($query) {
                $query->orderBy('id');
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        $preparingOrders = Order::where('warung_id', $user->warung_id)
            ->whereIn('status', ['preparing', 'ready'])
            ->with(['table', 'items' => function ($query) {
                $query->orderBy('id');
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('dashboard.kitchen', compact('verifiedOrders', 'preparingOrders'));
    }

    public function waiter()
    {
        $user = auth()->user();
        
        // Validasi: user harus memiliki warung_id
        if (!$user->warung_id) {
            return redirect()->route('login')
                ->withErrors(['email' => 'User tidak memiliki restaurant. Silakan hubungi admin.']);
        }
        
        $warung = Warung::find($user->warung_id);
        if (!$warung) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Restaurant tidak ditemukan. Silakan hubungi admin.']);
        }
        
        $activeOrders = Order::where('warung_id', $user->warung_id)
            ->whereIn('status', ['verified', 'preparing', 'ready'])
            ->with(['table', 'items' => function ($query) {
                $query->orderBy('id');
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        $servedOrders = Order::where('warung_id', $user->warung_id)
            ->where('status', 'served')
            ->whereDate('created_at', today())
            ->with(['table', 'items' => function ($query) {
                $query->orderBy('id');
            }])
            ->orderBy('updated_at', 'desc')
            ->get();
        
        $tables = \App\Models\RestaurantTable::where('warung_id', $user->warung_id)->get();
        
        return view('dashboard.waiter', compact('activeOrders', 'servedOrders', 'tables'));
    }

    /**
     * Owner dashboard - tambah menu, manage karyawan, report harian/mingguan/bulanan
     */
    public function owner()
    {
        $user = auth()->user();

        try {
            if (!$user->warung_id) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'User tidak memiliki restaurant. Silakan hubungi admin.']);
            }
            
            $warung = Warung::find($user->warung_id);
            
            if (!$warung) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Restaurant tidak ditemukan. Silakan hubungi admin.']);
            }

            $period = request()->get('period', 'daily');

            $dailyReport = [
                'orders' => Order::where('warung_id', $warung->id)
                    ->whereDate('created_at', today())
                    ->where('status', 'paid')
                    ->count(),
                'revenue' => Order::where('warung_id', $warung->id)
                    ->whereDate('created_at', today())
                    ->where('status', 'paid')
                    ->sum('total'),
                'profit' => Order::where('warung_id', $warung->id)
                    ->whereDate('created_at', today())
                    ->where('status', 'paid')
                    ->get()
                    ->sum(function($order) {
                        return $order->items->sum(function($item) {
                            $menuItem = MenuItem::find($item->menu_item_id);
                            return $item->qty * ($item->price - ($menuItem ? $menuItem->hpp : 0));
                        });
                    }),
                'best_seller' => OrderItem::whereIn('order_id', 
                    Order::where('warung_id', $warung->id)
                        ->whereDate('created_at', today())
                        ->where('status', 'paid')
                        ->pluck('id')
                )
                ->selectRaw('menu_name, SUM(qty) as total_qty')
                ->groupBy('menu_name')
                ->orderBy('total_qty', 'desc')
                ->limit(10)
                ->get()
            ];

            $weeklyReport = [
                'orders' => Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->where('status', 'paid')
                    ->count(),
                'revenue' => Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->where('status', 'paid')
                    ->sum('total'),
                'best_seller' => OrderItem::whereIn('order_id', 
                    Order::where('warung_id', $warung->id)
                        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                        ->where('status', 'paid')
                        ->pluck('id')
                )
                ->selectRaw('menu_name, SUM(qty) as total_qty')
                ->groupBy('menu_name')
                ->orderBy('total_qty', 'desc')
                ->limit(10)
                ->get()
            ];

            $monthlyReport = [
                'orders' => Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->where('status', 'paid')
                    ->count(),
                'revenue' => Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->where('status', 'paid')
                    ->sum('total'),
                'best_seller' => OrderItem::whereIn('order_id', 
                    Order::where('warung_id', $warung->id)
                        ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                        ->where('status', 'paid')
                        ->pluck('id')
                )
                ->selectRaw('menu_name, SUM(qty) as total_qty')
                ->groupBy('menu_name')
                ->orderBy('total_qty', 'desc')
                ->limit(10)
                ->get()
            ];

            $yearlyReport = [
                'orders' => Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                    ->where('status', 'paid')
                    ->count(),
                'revenue' => Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                    ->where('status', 'paid')
                    ->sum('total'),
                'best_seller' => OrderItem::whereIn('order_id', 
                    Order::where('warung_id', $warung->id)
                        ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                        ->where('status', 'paid')
                        ->pluck('id')
                )
                ->selectRaw('menu_name, SUM(qty) as total_qty')
                ->groupBy('menu_name')
                ->orderBy('total_qty', 'desc')
                ->limit(10)
                ->get()
            ];

            $chartItems = match ($period) {
                'weekly' => $weeklyReport['best_seller'],
                'monthly' => $monthlyReport['best_seller'],
                'yearly' => $yearlyReport['best_seller'],
                default => $dailyReport['best_seller'],
            };

            $menuItems = MenuItem::where('warung_id', $warung->id)->get();

            $staff = User::where('warung_id', $warung->id)
            ->where('role', '!=', 'owner')
            ->get();

            $ordersToday = Order::where('warung_id', $warung->id)
                ->whereDate('created_at', today())
                ->with('table', 'items')
                ->latest()
                ->get();

            $today = today();

            $shifts = StaffShift::where('warung_id', $warung->id)
                ->whereDate('started_at', $today)
                ->get()
                ->groupBy('user_id');

            $staffShiftSummary = [];
            $staffOrderSummary = [];

            foreach ($staff as $staffUser) {
                $userId = $staffUser->id;
                $userShifts = $shifts->get($userId);

                if ($userShifts) {
                    $sorted = $userShifts->sortBy('started_at');
                    $firstShift = $sorted->first();
                    $lastShift = $sorted->last();

                    $staffShiftSummary[$userId] = [
                        'start' => $firstShift->started_at,
                        'end' => $lastShift->ended_at,
                    ];
                }

                $staffOrderSummary[$userId] = [
                    'kasir_codes' => [],
                    'waiter_codes' => [],
                    'kitchen_codes' => [],
                ];
            }

            foreach ($ordersToday as $order) {
                if ($order->kasir_id && isset($staffOrderSummary[$order->kasir_id])) {
                    $staffOrderSummary[$order->kasir_id]['kasir_codes'][] = $order->code;
                }

                if ($order->waiter_id && isset($staffOrderSummary[$order->waiter_id])) {
                    $staffOrderSummary[$order->waiter_id]['waiter_codes'][] = $order->code;
                }

                if ($order->kitchen_id && isset($staffOrderSummary[$order->kitchen_id])) {
                    $staffOrderSummary[$order->kitchen_id]['kitchen_codes'][] = $order->code;
                }
            }

            $completedOrders = Order::where('warung_id', $warung->id)
                ->whereDate('created_at', today())
                ->where('status', 'paid')
                ->with('table', 'items')
                ->orderBy('created_at', 'desc')
                ->get();

            $soldItemsSummary = OrderItem::whereIn('order_id',
                Order::where('warung_id', $warung->id)
                    ->whereDate('created_at', today())
                    ->where('status', 'paid')
                    ->pluck('id')
            )
                ->selectRaw('menu_name, SUM(qty) as total_qty')
                ->groupBy('menu_name')
                ->orderBy('total_qty', 'desc')
                ->get();

            $liveBoard = [
                'pending' => Order::where('warung_id', $warung->id)
                    ->where('status', 'pending')
                    ->whereDate('created_at', today())
                    ->with('table', 'items')
                    ->orderBy('created_at', 'asc')
                    ->get(),
                'verified' => Order::where('warung_id', $warung->id)
                    ->where('status', 'verified')
                    ->whereDate('created_at', today())
                    ->with('table', 'items')
                    ->orderBy('created_at', 'asc')
                    ->get(),
                'preparing' => Order::where('warung_id', $warung->id)
                    ->where('status', 'preparing')
                    ->whereDate('created_at', today())
                    ->with('table', 'items')
                    ->orderBy('created_at', 'asc')
                    ->get(),
                'ready' => Order::where('warung_id', $warung->id)
                    ->where('status', 'ready')
                    ->whereDate('created_at', today())
                    ->with('table', 'items')
                    ->orderBy('created_at', 'asc')
                    ->get(),
                'paid' => Order::where('warung_id', $warung->id)
                    ->where('status', 'paid')
                    ->whereDate('created_at', today())
                    ->with('table', 'items')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            $paidOrders = Order::where('warung_id', $warung->id)
                ->where('status', 'paid')
                ->whereDate('created_at', today())
                ->get();
            
            $avgPrepTime = $paidOrders->count() > 0 
                ? round($paidOrders->avg(function($order) {
                    return $order->created_at->diffInMinutes($order->updated_at);
                }))
                : 0;

            $notifications = [];
            $outOfStockCount = MenuItem::where('warung_id', $warung->id)->where('active', false)->count();
            if ($outOfStockCount > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'icon' => 'exclamation-triangle',
                    'message' => "{$outOfStockCount} menu habis stok",
                    'action' => 'menu'
                ];
            }
            $unverifiedCount = Order::where('warung_id', $warung->id)
                ->where('status', 'pending')
                ->whereDate('created_at', today())
                ->count();
            if ($unverifiedCount > 0) {
                $notifications[] = [
                    'type' => 'info',
                    'icon' => 'clock',
                    'message' => "{$unverifiedCount} pesanan belum diverifikasi",
                    'action' => 'orders'
                ];
            }

            $protocol = request()->getScheme();
            $port = request()->getPort();
            $portSuffix = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
            $domain = env('SMARTORDER_DOMAIN', 'smartapp.local');
            if (!$warung->slug && $warung->code) {
                $warung->slug = strtolower($warung->code);
                $warung->save();
            }
            $subdomain = strtolower($warung->slug ?? $warung->code ?? 'default');
            $customerUrl = $protocol . '://' . $subdomain . '.' . $domain . $portSuffix;

            // TIER RESTRICTION LOGIC
            $isPro = in_array($warung->subscription_tier, ['professional', 'enterprise']);
            
            if (!$isPro) {
                // Starter Tier: No Best Seller Analytics & Charts
                $dailyReport['best_seller'] = collect([]);
                $weeklyReport['best_seller'] = collect([]);
                $monthlyReport['best_seller'] = collect([]);
                $yearlyReport['best_seller'] = collect([]);
                $chartItems = collect([]); 
            }

            $lowStockCount = MenuItem::where('warung_id', $warung->id)
                ->where('active', false)
                ->count();

            // Also check ingredients for low stock
            $lowStockIngredients = \App\Models\Ingredient::where('warung_id', $warung->id)
                ->whereRaw('stock <= min_stock')
                ->count();
            
            $totalLowStock = $lowStockCount + $lowStockIngredients;

            return view('dashboard.owner', compact(
                'dailyReport',
                'weeklyReport',
                'monthlyReport',
                'yearlyReport',
                'menuItems',
                'staff',
                'ordersToday',
                'staffShiftSummary',
                'staffOrderSummary',
                'liveBoard',
                'avgPrepTime',
                'notifications',
                'period',
                'warung',
                'customerUrl',
                'chartItems',
                'isPro',
                'completedOrders',
                'soldItemsSummary'
            ));
        } catch (\Throwable $e) {
            Log::error('Owner dashboard error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Terjadi error di dashboard owner: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get report data (Admin only)
     */
    public function getReports(Request $request, $type)
    {
        $user = auth()->user();
        
        // Only Admin can access reports
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            switch ($type) {
                case 'revenue':
                    $data = \App\Models\Warung::select('name', 'slug')
                        ->withCount(['orders' => function($query) {
                            return $query->where('status', 'paid');
                        }])
                        ->withSum(['orders' => function($query) {
                            return $query->where('status', 'paid');
                        }, 'total'])
                        ->get()
                        ->map(function ($warung) {
                            return [
                                'name' => $warung->name,
                                'revenue' => $warung->orders_sum_total ?? 0,
                                'paid_orders' => $warung->orders_count ?? 0,
                                'period' => 'All time'
                            ];
                        });
                    break;

                case 'orders':
                    $data = \App\Models\Order::with('warung')
                        ->select('code', 'status', 'total', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(100)
                        ->get()
                        ->map(function ($order) {
                            return [
                                'code' => $order->code,
                                'restaurant' => $order->warung->name,
                                'status' => $order->status,
                                'total' => $order->total,
                                'date' => $order->created_at->format('Y-m-d H:i:s'),
                                'status_color' => $this->getStatusColor($order->status)
                            ];
                        });
                    break;

                case 'users':
                    $data = \App\Models\User::with('warung')
                        ->select('name', 'email', 'role', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($user) {
                            return [
                                'name' => $user->name,
                                'email' => $user->email,
                                'role' => $user->role,
                                'restaurant' => $user->warung->name,
                                'created' => $user->created_at->format('Y-m-d H:i:s'),
                                'role_color' => $this->getRoleColor($user->role)
                            ];
                        });
                    break;

                default:
                    return response()->json(['error' => 'Invalid report type'], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load report data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method to get status color
     */
    private function getStatusColor($status)
    {
        $colors = [
            'pending' => 'warning',
            'verified' => 'info',
            'preparing' => 'primary',
            'ready' => 'success',
            'served' => 'secondary',
            'paid' => 'dark',
            'cancelled' => 'danger'
        ];
        
        return $colors[$status] ?? 'secondary';
    }

    /**
     * Helper method to get role color
     */
    private function getRoleColor($role)
    {
        $colors = [
            'admin' => 'danger',
            'owner' => 'primary',
            'kasir' => 'success',
            'waiter' => 'info',
            'dapur' => 'warning'
        ];
        
        return $colors[$role] ?? 'secondary';
    }

    /**
     * Admin dashboard - hanya tampilkan daftar restoran
     */
    public function admin()
    {
        // Get all restaurants with additional info
        $warungs = \App\Models\Warung::select('id', 'name', 'code', 'slug', 'subscription_tier')
            ->orderBy('name')
            ->get();

        // Generate customer links dan tambahkan info untuk setiap warung
        $protocol = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
        $domain = env('SMARTORDER_DOMAIN', 'smartapp.local');
        
        $warungs = $warungs->map(function ($warung) use ($protocol, $portSuffix, $domain) {
            // Jika slug belum ada, generate dari code
            if (!$warung->slug && $warung->code) {
                $warung->slug = strtolower($warung->code);
                $warung->save();
            }
            $subdomain = strtolower($warung->slug ?? $warung->code ?? 'default');
            $warung->customer_url = $protocol . '://' . $subdomain . '.' . $domain . $portSuffix;
            
            // Penjualan minggu ini
            $warung->weekly_revenue = Order::where('warung_id', $warung->id)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', 'paid')
                ->sum('total');
            
            // Jumlah staff
            $warung->staff_count = \App\Models\User::where('warung_id', $warung->id)
                ->where('role', '!=', 'owner')
                ->count();
            
            // Menu habis stok
            $warung->out_of_stock_count = MenuItem::where('warung_id', $warung->id)
                ->where('active', false)
                ->count();
            
            // Indikator peringatan
            $warung->alerts = [];
            if ($warung->out_of_stock_count > 0) {
                $warung->alerts[] = [
                    'type' => 'warning',
                    'message' => $warung->out_of_stock_count . ' menu habis'
                ];
            }
            if ($warung->staff_count < 2) {
                $warung->alerts[] = [
                    'type' => 'info',
                    'message' => 'Staff kurang (minimal 2)'
                ];
            }
            
            return $warung;
        });

        return view('dashboard.admin', compact('warungs'));
    }

    /**
     * Admin - halaman khusus restaurant
     */
    public function adminRestaurant(Warung $warung)
    {
        $period = request()->get('period', 'daily');

        $dailyReport = [
            'orders' => Order::where('warung_id', $warung->id)
                ->whereDate('created_at', today())
                ->where('status', 'paid')
                ->count(),
            'revenue' => Order::where('warung_id', $warung->id)
                ->whereDate('created_at', today())
                ->where('status', 'paid')
                ->sum('total'),
            'best_seller' => OrderItem::whereIn('order_id',
                Order::where('warung_id', $warung->id)
                    ->whereDate('created_at', today())
                    ->where('status', 'paid')
                    ->pluck('id')
            )
            ->selectRaw('menu_name, SUM(qty) as total_qty')
            ->groupBy('menu_name')
            ->orderBy('total_qty', 'desc')
            ->limit(5)
            ->get(),
        ];

        $weeklyReport = [
            'orders' => Order::where('warung_id', $warung->id)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', 'paid')
                ->count(),
            'revenue' => Order::where('warung_id', $warung->id)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', 'paid')
                ->sum('total'),
            'best_seller' => OrderItem::whereIn('order_id',
                Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->where('status', 'paid')
                    ->pluck('id')
            )
            ->selectRaw('menu_name, SUM(qty) as total_qty')
            ->groupBy('menu_name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->get(),
        ];

        $monthlyReport = [
            'orders' => Order::where('warung_id', $warung->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->where('status', 'paid')
                ->count(),
            'revenue' => Order::where('warung_id', $warung->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->where('status', 'paid')
                ->sum('total'),
            'best_seller' => OrderItem::whereIn('order_id',
                Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->where('status', 'paid')
                    ->pluck('id')
            )
            ->selectRaw('menu_name, SUM(qty) as total_qty')
            ->groupBy('menu_name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->get(),
        ];

        $yearlyReport = [
            'orders' => Order::where('warung_id', $warung->id)
                ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                ->where('status', 'paid')
                ->count(),
            'revenue' => Order::where('warung_id', $warung->id)
                ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                ->where('status', 'paid')
                ->sum('total'),
            'best_seller' => OrderItem::whereIn('order_id',
                Order::where('warung_id', $warung->id)
                    ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
                    ->where('status', 'paid')
                    ->pluck('id')
            )
            ->selectRaw('menu_name, SUM(qty) as total_qty')
            ->groupBy('menu_name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->get(),
        ];

        $menuItems = MenuItem::where('warung_id', $warung->id)->get();

        $users = \App\Models\User::where('warung_id', $warung->id)->get();

        $liveBoard = [
            'pending' => Order::where('warung_id', $warung->id)
                ->where('status', 'pending')
                ->whereDate('created_at', today())
                ->with('table', 'items')
                ->orderBy('created_at', 'asc')
                ->get(),
            'preparing' => Order::where('warung_id', $warung->id)
                ->where('status', 'preparing')
                ->whereDate('created_at', today())
                ->with('table', 'items')
                ->orderBy('created_at', 'asc')
                ->get(),
            'ready' => Order::where('warung_id', $warung->id)
                ->where('status', 'ready')
                ->whereDate('created_at', today())
                ->with('table', 'items')
                ->orderBy('created_at', 'asc')
                ->get(),
            'paid' => Order::where('warung_id', $warung->id)
                ->where('status', 'paid')
                ->whereDate('created_at', today())
                ->with('table', 'items')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        $chartItems = match ($period) {
            'weekly' => $weeklyReport['best_seller'],
            'monthly' => $monthlyReport['best_seller'],
            'yearly' => $yearlyReport['best_seller'],
            default => $dailyReport['best_seller'],
        };

        $paidOrders = Order::where('warung_id', $warung->id)
            ->where('status', 'paid')
            ->whereDate('created_at', today())
            ->get();

        $avgPrepTime = $paidOrders->count() > 0
            ? round($paidOrders->avg(function ($order) {
                return $order->created_at->diffInMinutes($order->updated_at);
            }))
            : 0;

        $protocol = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
        $domain = env('SMARTORDER_DOMAIN', 'smartapp.local');
        if (!$warung->slug && $warung->code) {
            $warung->slug = strtolower($warung->code);
            $warung->save();
        }
        $subdomain = strtolower($warung->slug ?? $warung->code ?? 'default');
        $customerUrl = $protocol . '://' . $subdomain . '.' . $domain . $portSuffix;

        return view('dashboard.admin-restaurant', compact(
            'warung',
            'dailyReport',
            'weeklyReport',
            'monthlyReport',
            'yearlyReport',
            'menuItems',
            'users',
            'period',
            'customerUrl',
            'liveBoard',
            'avgPrepTime',
            'chartItems'
        ));
    }

    public function adminDiagnostics(Request $request)
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(function ($route) {
                $methods = array_values(array_diff($route->methods(), ['HEAD']));
                $name = $route->getName();
                $uri = $route->uri();
                $action = $route->getActionName();
                $middleware = $route->gatherMiddleware();

                return [
                    'methods' => implode('|', $methods),
                    'uri' => $uri,
                    'name' => $name,
                    'action' => $action,
                    'middleware' => $middleware,
                ];
            })
            ->sortBy('uri')
            ->values();

        $routesByRole = $routes
            ->map(function ($r) {
                $role = null;
                foreach ($r['middleware'] as $m) {
                    if (is_string($m) && str_starts_with($m, 'role:')) {
                        $role = $m;
                        break;
                    }
                }
                $r['role_middleware'] = $role;
                return $r;
            })
            ->groupBy(function ($r) {
                return $r['role_middleware'] ?? 'no-role-middleware';
            })
            ->sortKeys();

        $viewFunctions = [];
        $viewFiles = collect(File::allFiles(resource_path('views')))
            ->filter(function ($f) {
                return str_ends_with($f->getFilename(), '.blade.php');
            })
            ->values();

        foreach ($viewFiles as $file) {
            $path = $file->getPathname();
            $content = File::get($path);
            preg_match_all('/function\\s+([a-zA-Z0-9_]+)\\s*\\(/', $content, $matches);
            $fnNames = collect($matches[1] ?? [])->unique()->values()->all();
            if (!empty($fnNames)) {
                $viewFunctions[] = [
                    'file' => str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path),
                    'functions' => $fnNames,
                ];
            }
        }

        $viewFunctions = collect($viewFunctions)->sortBy('file')->values();

        return view('dashboard.admin-diagnostics', [
            'routes' => $routes,
            'routesByRole' => $routesByRole,
            'viewFunctions' => $viewFunctions,
        ]);
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $user = auth()->user();

        // Verify user owns this warung
        if ($user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only Kitchen (dapur) can update status
        if (!in_array($user->role, ['dapur', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:preparing,ready',
        ]);

        // Validate status transition
        if ($validated['status'] === 'preparing' && $order->status !== 'verified') {
            return response()->json([
                'error' => 'Can only start preparing verified orders',
                'current_status' => $order->status
            ], 400);
        }

        if ($validated['status'] === 'ready' && $order->status !== 'preparing') {
            return response()->json([
                'error' => 'Can only mark ready when status is preparing',
                'current_status' => $order->status
            ], 400);
        }

        $order->update([
            'status' => $validated['status'],
            'kitchen_id' => $user->id,
        ]);

        // Send notification
        $notificationType = match ($validated['status']) {
            'ready' => 'ready',
            default => 'update',
        };
        NotificationService::sendOrderNotification($order, $notificationType);

        return response()->json([
            'success' => true,
            'message' => "Status updated to {$validated['status']}",
        ]);
    }

    public function updateOrderItemStatus(Request $request, OrderItem $item)
    {
        $user = auth()->user();
        $order = $item->order;

        if (!$order || $user->warung_id !== $order->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!in_array($user->role, ['dapur', 'waiter', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,served',
        ]);

        if ($user->role === 'dapur' && !in_array($validated['status'], ['preparing', 'ready', 'served'])) {
            return response()->json(['error' => 'Status tidak diperbolehkan untuk dapur'], 403);
        }

        if ($user->role === 'waiter' && $validated['status'] !== 'served') {
            return response()->json(['error' => 'Status tidak diperbolehkan untuk waiter'], 403);
        }

        $item->update(['status' => $validated['status']]);

        // Logic: Check all items status to update Order Status
        $allItems = $order->items()->get();

        // 1. If ALL items are SERVED => Order Status = SERVED
        if ($allItems->every(fn($i) => $i->status === 'served')) {
            if ($order->status !== 'served') {
                $order->update([
                    'status' => 'served',
                    // Jika waiter yg update, catat waiter_id. Jika dapur, ya dapur.
                    // Kita anggap user yg melakukan action terakhir adalah penanggung jawabnya
                    'waiter_id' => ($user->role === 'waiter') ? $user->id : $order->waiter_id, 
                ]);
                NotificationService::sendOrderNotification($order, 'served');
            }
        }
        // 2. If ALL items are READY (or SERVED) => Order Status = READY
        // (Only if order is not already served/paid)
        elseif ($allItems->every(fn($i) => in_array($i->status, ['ready', 'served']))) {
            if (!in_array($order->status, ['ready', 'served', 'paid'])) {
                $order->update([
                    'status' => 'ready',
                    'kitchen_id' => ($user->role === 'dapur') ? $user->id : $order->kitchen_id,
                ]);
                NotificationService::sendOrderNotification($order, 'ready');
            }
        }
        // 3. If ANY item is PREPARING => Order Status = PREPARING
        // (Only if order is verified/ready, downgrade status if needed)
        elseif ($allItems->contains(fn($i) => $i->status === 'preparing')) {
             if (!in_array($order->status, ['preparing', 'served', 'paid'])) {
                $order->update([
                    'status' => 'preparing',
                    'kitchen_id' => ($user->role === 'dapur') ? $user->id : $order->kitchen_id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status item berhasil diperbarui',
        ]);
    }

    /**
     * SSE untuk real-time order updates di dashboard
     */
    public function streamOrders(Request $request)
    {
        $user = auth()->user();
        $lastUpdated = now();

        return response()->stream(function () use ($user, &$lastUpdated) {
            while (true) {
                $orders = Order::where('warung_id', $user->warung_id)
                    ->where('updated_at', '>', $lastUpdated)
                    ->with('table', 'items')
                    ->orderBy('updated_at')
                    ->get();

                foreach ($orders as $order) {
                    echo "data: " . json_encode([
                        'id' => $order->id,
                        'code' => $order->code,
                        'customer_name' => $order->customer_name,
                        'table' => $order->table ? $order->table->name : 'Takeaway',
                        'status' => $order->status,
                        'total' => $order->total,
                        'formatted_total' => 'Rp ' . number_format($order->total, 0, ',', '.'),
                        'items_count' => $order->items->count(),
                        'payment_method' => $order->payment_method,
                        'payment_channel' => $order->payment_channel,
                        'notes' => $order->notes,
                        'subtotal' => $order->subtotal,
                        'admin_fee' => $order->admin_fee,
                        'diskon_manual' => $order->diskon_manual,
                        'alasan_diskon' => $order->alasan_diskon,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'menu_name' => $item->menu_name,
                                'qty' => $item->qty,
                                'price' => $item->price,
                                'status' => $item->status,
                            ];
                        }),
                    ]) . "\n\n";

                    $lastUpdated = $order->updated_at ?? now();
                }

                ob_flush();
                flush();
                sleep(1);
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
