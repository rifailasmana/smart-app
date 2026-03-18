<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\HRDController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Models\DailyClosure;
use App\Models\StaffShift;
use App\Http\Controllers\Auth\RegisterController;
use App\Models\Warung;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ===== SYSTEM UPGRADE ROUTES =====
Route::get('/system-upgrade-username', function () {
    // 1. Schema Migration
    if (!Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
        Illuminate\Support\Facades\Schema::table('users', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
        });
    }

    // 2. Data Migration (Update Users)
    $users = App\Models\User::all();
    foreach ($users as $user) {
        // Specific Users
        if ($user->email === 'admin@smartorder.local' || $user->role === 'admin') {
            $user->username = 'admin';
            $user->password = Illuminate\Support\Facades\Hash::make('admin');
        } elseif ($user->email === 'owner@bali.local' || ($user->role === 'owner' && stripos($user->name, 'bambang') !== false)) {
            $user->username = 'bambangbali';
            $user->password = Illuminate\Support\Facades\Hash::make('bali');
        } else {
            // Default for others
            if (empty($user->username)) {
                // Generate from email or name
                $base = explode('@', $user->email)[0];
                // Sanitize
                $base = preg_replace('/[^a-z0-9]/', '', strtolower($base));
                if (empty($base)) $base = 'user' . $user->id;
                
                $username = $base;
                $counter = 1;
                while (App\Models\User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $base . $counter++;
                }
                $user->username = $username;
                // Keep existing password if possible, but user asked to see passwords.
                // We'll set them to 'password' for demo purposes as implied by the login page note
                // "password : password" (from previous login page text)
                // But for safety let's only change if it's a demo account or if we really need to.
                // The user said: "username lain bisa mengikuti dan kamu taroh di login page untuk semua usernamenya dan password biar aku liat"
                // So I should set a standard password for them.
                $user->password = Illuminate\Support\Facades\Hash::make('password'); 
            }
        }
        $user->save();
    }
    
    return "System Upgrade Complete: Username column added and Users updated.";
});

// ===== PUBLIC ROUTES (Landing & Authentication) =====

Route::domain('smartapp.local')->group(function () {
    Route::get('/', [LandingController::class, 'index'])
        ->middleware('auth.subdomain.redirect')
        ->name('landing');
});

// Backward compatibility: /home redirect ke /dashboard
Route::get('/home', function () {
    return redirect()->route('dashboard');
});

// ===== SUBDOMAIN ROUTES (Customer Order per Warung) =====
// Support localhost untuk development
Route::domain('{warung_code}.localhost')->group(function () {
    Route::middleware(['web', 'resolve.warung'])->group(function () {
        Route::get('/', [OrderController::class, 'create'])->name('order.menu.subdomain.localhost');
        Route::get('/menu', function () {
            return redirect('/');
        })->name('order.menu.subdomain.localhost.menu');
        Route::post('/order', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('order.store.subdomain.localhost');
        Route::get('/order-status', [OrderController::class, 'status'])->name('order.status.subdomain.localhost');
        Route::get('/order-status/stream', [OrderController::class, 'streamStatus'])->name('order.stream.subdomain.localhost');
    });
});

Route::domain('{warung_code}.smartapp.local')->group(function () {
    Route::middleware(['web', 'resolve.warung'])->group(function () {
        Route::get('/', [OrderController::class, 'create'])->name('order.menu.subdomain');
        Route::get('/menu', function () {
            return redirect('/');
        })->name('order.menu.subdomain.menu');
        Route::post('/order', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('order.store.subdomain');
        Route::get('/order-status', [OrderController::class, 'status'])->name('order.status.subdomain');
        Route::get('/order-status/stream', [OrderController::class, 'streamStatus'])->name('order.stream.subdomain');
    });
});

Route::domain('{warung_code}.smartorder.com')->group(function () {
    Route::middleware(['web', 'resolve.warung'])->group(function () {
        Route::get('/', [OrderController::class, 'create'])->name('order.menu.subdomain.prod');
        Route::get('/menu', function () {
            return redirect('/');
        })->name('order.menu.subdomain.prod');
        Route::post('/order', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('order.store.subdomain.prod');
        Route::get('/order-status', [OrderController::class, 'status'])->name('order.status.subdomain.prod');
        Route::get('/order-status/stream', [OrderController::class, 'streamStatus'])->name('order.stream.subdomain.prod');
        Route::get('/order-receipt', [OrderController::class, 'receipt'])->name('order.receipt.subdomain.prod');
    });
});

// ===== PUBLIC ROUTES (Landing & Customer Ordering) =====

Route::get('/menu', [OrderController::class, 'create'])->name('order.menu');
Route::post('/order', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('order.store');
Route::get('/order-status', [OrderController::class, 'status'])->name('order.status');
Route::get('/order-status/stream', [OrderController::class, 'streamStatus'])->name('order.stream');
Route::get('/order-receipt', [OrderController::class, 'receipt'])->name('order.receipt');
Route::get('/order-receipt-print', [OrderController::class, 'printReceipt'])->name('order.receipt.print');

// ===== AUTHENTICATION ROUTES =====

Route::middleware('guest')->group(function () {
    // Login Page
    Route::get('/login', function () {
        // --- SELF HEALING DEMO DATA ---
        try {
            $log = [];
            
            // 1. Fix Admin
            $admin = App\Models\User::where('email', 'admin@smartorder.local')->first();
            if ($admin) {
                if ($admin->username !== 'admin') { 
                    $admin->username = 'admin'; 
                    $admin->password = Illuminate\Support\Facades\Hash::make('admin');
                    $admin->save();
                    $log[] = "Admin updated.";
                }
            } else {
                 App\Models\User::create([
                    'name' => 'Rifai',
                    'username' => 'admin',
                    'email' => 'admin@smartorder.local',
                    'password' => Illuminate\Support\Facades\Hash::make('admin'),
                    'role' => 'admin',
                ]);
                $log[] = "Admin created.";
            }

            // 2. Fix Owner Bambang
            $owner = App\Models\User::where('email', 'owner@bali.local')->first();
            if ($owner) {
                if ($owner->username !== 'bambangbali') { 
                    $owner->username = 'bambangbali'; 
                    $owner->password = Illuminate\Support\Facades\Hash::make('bali');
                    $owner->save();
                    $log[] = "Owner updated.";
                }
            }

            // 3. Fix Others (Ensure they have usernames)
            $others = App\Models\User::whereNull('username')->orWhere('username', '')->get();
            foreach($others as $u) {
                $base = '';
                // Specific assignments based on name/role if known
                if (stripos($u->name, 'siti') !== false) $base = 'siti';
                elseif (stripos($u->name, 'budi') !== false) $base = 'budi';
                elseif (stripos($u->name, 'ani') !== false) $base = 'ani';
                else {
                    $base = explode('@', $u->email)[0];
                    $base = preg_replace('/[^a-z0-9]/', '', strtolower($base));
                    if (empty($base)) $base = strtolower(explode(' ', $u->name)[0]);
                }
                
                $u->username = $base;
                $u->password = Illuminate\Support\Facades\Hash::make('password');
                $u->save();
                $log[] = "Updated {$u->name} to {$base}";
            }
            
            if (!empty($log)) {
                file_put_contents(storage_path('logs/username_fix.log'), implode("\n", $log) . "\n", FILE_APPEND);
            }

        } catch (\Exception $e) {
            file_put_contents(storage_path('logs/username_fix_error.log'), $e->getMessage() . "\n" . $e->getTraceAsString(), FILE_APPEND);
        }

        // Get Users for Display
        $demoUsers = collect([]);
        try {
            $demoUsers = App\Models\User::whereIn('role', ['admin', 'owner', 'kasir', 'dapur', 'waiter'])
                ->orderByRaw("FIELD(role, 'admin', 'owner', 'kasir', 'dapur', 'waiter')")
                ->get();
        } catch(\Exception $e) {}
        
        return view('auth.login', compact('demoUsers'));
    })->name('login');

    // Login Handler
    Route::post('/login', function () {
        $credentials = request()->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            request()->session()->regenerate();
            
            $user = Auth::user();

            if (in_array($user->role, ['kasir', 'waiter', 'dapur', 'kitchen'])) {
                StaffShift::where('user_id', $user->id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => now()]);

                StaffShift::create([
                    'user_id' => $user->id,
                    'warung_id' => $user->warung_id,
                    'role' => $user->role,
                    'started_at' => now(),
                ]);
            }
            
            // Admin tetap ke halaman admin warungs
            if ($user->role === 'admin') {
                return redirect('/admin/warungs');
            }
            
            if ($user->warung_id) {
                $warung = Warung::find($user->warung_id);
                if ($warung) {
                    $protocol = request()->getScheme();
                    $port = request()->getPort();
                    $portSuffix = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
                    $domain = env('SMARTORDER_DOMAIN', 'smartorder.local');
                    $domainBase = ltrim($domain, '.');
                    $currentHost = request()->getHost();
                    if (!str_ends_with($currentHost, $domainBase)) {
                        return redirect()->route('dashboard');
                    }
                    if (!$warung->slug && $warung->code) {
                        $warung->slug = strtolower($warung->code);
                        $warung->save();
                    }
                    $subdomain = strtolower($warung->slug ?? $warung->code ?? 'default');
                    $baseUrl = $protocol . '://' . $subdomain . '.' . $domain . $portSuffix;
                    $dashboardRoute = match($user->role) {
                        'owner' => '/dashboard/owner',
                        'kasir' => '/dashboard/kasir',
                        'waiter' => '/dashboard/waiter',
                        'dapur', 'kitchen' => '/dashboard/kitchen',
                        'hrd' => '/dashboard/hrd',
                        'manager' => '/dashboard/manager',
                        'inventory' => '/dashboard/inventory',
                        default => '/dashboard',
                    };
                    
                    return redirect($baseUrl . $dashboardRoute);
                }
            }
            
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['username' => 'Username atau password salah'])->onlyInput('username');
    })->name('login.post');
});

// ===== AUTHENTICATED ROUTES (Dashboard & Staff Management) =====

Route::middleware('auth')->group(function () {
    // Main Dashboard (Role-based redirect)
    // Di subdomain, redirect ke dashboard sesuai role
    // Di main domain, redirect ke subdomain (via middleware)
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        // Admin di main domain redirect ke /admin/warungs
        if ($user->role === 'admin') {
            return redirect('/admin/warungs');
        }
        
        // Map roles to dashboard views (di subdomain)
        return match($user->role) {
            'owner' => redirect()->route('dashboard.owner'),
            'kasir' => redirect()->route('dashboard.kasir'),
            'waiter' => redirect()->route('dashboard.waiter'),
            'dapur', 'kitchen' => redirect()->route('dashboard.kitchen'),
            'hrd' => redirect()->route('dashboard.hrd'),
            'manager' => redirect()->route('dashboard.manager'),
            'inventory' => redirect()->route('dashboard.inventory'),
            default => view('dashboard'),
        };
    })->name('dashboard');

    // Owner/Admin Dashboard - Menu & Finance Management
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/dashboard/owner', [DashboardController::class, 'owner'])->name('dashboard.owner');
        // Route::get('/dashboard/owner/orders', [DashboardController::class, 'ownerOrders'])->name('dashboard.owner.orders');
        // Route admin dashboard lama (redirect ke /admin/warungs)
        Route::get('/dashboard/admin', function () {
            return redirect('/admin/warungs');
        })->name('dashboard.admin');
        Route::post('/menu-items', [MenuItemController::class, 'store'])->name('menu.store');
        Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy'])->name('menu.destroy');
        Route::post('/menu-items/refresh-all-stock', [MenuItemController::class, 'refreshAllStock'])->name('menu.refresh-all-stock');
        
        // Google Sheet Sync
        Route::post('/google-sheet/sync', [OrderController::class, 'syncToGoogleSheet'])->name('google-sheet.sync');
        
        // Owner can update their own restaurant info
        Route::put('/warung/{id}', [RestaurantController::class, 'update'])->name('warung.update');
        
        // Restaurant Management (Admin only)
        Route::middleware('role:admin')->group(function () {
            // Admin dashboard utama - daftar restoran
            Route::get('/admin/warungs', [DashboardController::class, 'admin'])->name('admin.warungs');

            Route::get('/admin/diagnostics', [DashboardController::class, 'adminDiagnostics'])->name('admin.diagnostics');
            
            Route::get('/admin/restaurants/{warung}', [DashboardController::class, 'adminRestaurant'])->name('admin.restaurant.show');
            Route::post('/admin/restaurants', [RestaurantController::class, 'store'])->name('admin.restaurants.store');
            Route::put('/admin/restaurants/{id}', [RestaurantController::class, 'update'])->name('admin.restaurants.update');
            Route::delete('/admin/restaurants/{id}', [RestaurantController::class, 'destroy'])->name('admin.restaurants.destroy');
            
            // User Management
            Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
            Route::put('/admin/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
            Route::delete('/admin/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
            
            // Order Management per Restaurant
            Route::get('/admin/restaurants/{warung}/orders', [OrderController::class, 'restaurantOrders'])->name('admin.restaurant.orders');
            
            // Reports
            Route::get('/admin/reports/{type}', [DashboardController::class, 'getReports'])->name('admin.reports');
            
            // Settings
            Route::put('/admin/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
        });
    });

    Route::middleware('role:admin,owner,kasir')->group(function () {
        Route::get('/menu-items/{id}', [MenuItemController::class, 'show'])->name('menu.show');
        Route::put('/menu-items/{id}', [MenuItemController::class, 'update'])->name('menu.update');
    });

    Route::middleware('role:owner,kasir,admin')->group(function () {
        Route::get('/reports/export/{period}', [ReportController::class, 'export'])
            ->whereIn('period', ['daily', 'weekly', 'monthly', 'daily-detail'])
            ->name('reports.export');
    });

    // Manager Dashboard
    Route::middleware('role:manager,owner,admin')->group(function () {
        Route::get('/dashboard/manager', [ManagerController::class, 'index'])->name('dashboard.manager');
        Route::post('/dashboard/manager/void/{order}', [ManagerController::class, 'voidOrder'])->name('dashboard.manager.void');
        Route::post('/dashboard/manager/coupon', [ManagerController::class, 'createCoupon'])->name('dashboard.manager.coupon');
    });

    // HRD Dashboard
    Route::middleware('role:hrd,owner,admin')->group(function () {
        Route::get('/dashboard/hrd', [HRDController::class, 'index'])->name('dashboard.hrd');
        Route::get('/dashboard/hrd/attendance', [HRDController::class, 'attendance'])->name('dashboard.hrd.attendance');
        Route::get('/dashboard/hrd/payroll', [HRDController::class, 'payroll'])->name('dashboard.hrd.payroll');
        Route::post('/dashboard/hrd/payroll/generate', [HRDController::class, 'generatePayroll'])->name('dashboard.hrd.payroll.generate');
    });

    // Inventory Dashboard
    Route::middleware('role:inventory,manager,owner,admin')->group(function () {
        Route::get('/dashboard/inventory', [InventoryController::class, 'index'])->name('dashboard.inventory');
        Route::post('/dashboard/inventory/ingredient', [InventoryController::class, 'storeIngredient'])->name('dashboard.inventory.ingredient');
        Route::post('/dashboard/inventory/stock', [InventoryController::class, 'updateStock'])->name('dashboard.inventory.stock');
    });

    // Kasir (Cashier) Dashboard - Payment Management
    Route::middleware('role:kasir,admin')->group(function () {
        Route::get('/dashboard/kasir', [DashboardController::class, 'kasir'])->name('dashboard.kasir');
        Route::put('/order/{id}/discount', [OrderController::class, 'updateDiscount'])->name('order.discount');
        Route::post('/dashboard/kasir/opening', function () {
            $user = auth()->user();
            $warungId = $user->warung_id;

            if (!$warungId) {
                return redirect()->back()->withErrors(['opening' => 'Warung tidak valid.']);
            }

            $existing = DailyClosure::where('warung_id', $warungId)
                ->whereDate('date', today())
                ->first();

            if ($existing) {
                $existing->update([
                    'opened_by' => $user->id,
                    'opened_at' => now(),
                    'closed_at' => null,
                    'verified_by' => null,
                    'total_sales' => 0,
                    'transaction_count' => 0,
                    'average_transaction' => 0,
                ]);
            } else {
                DailyClosure::create([
                    'warung_id' => $warungId,
                    'date' => today(),
                    'opened_by' => $user->id,
                    'opened_at' => now(),
                    'total_sales' => 0,
                    'transaction_count' => 0,
                    'average_transaction' => 0,
                ]);
            }

            return redirect()->back()->with('opening_success', 'Opening hari ini berhasil dicatat.');
        })->name('dashboard.kasir.opening');
        Route::post('/dashboard/kasir/closing', function () {
            $user = auth()->user();
            $warungId = $user->warung_id;

            if (!$warungId) {
                return redirect()->back()->withErrors(['closing' => 'Warung tidak valid.']);
            }

            $existing = DailyClosure::where('warung_id', $warungId)
                ->whereDate('date', today())
                ->first();

            $paidQuery = \App\Models\Order::where('warung_id', $warungId)
                ->whereDate('created_at', today())
                ->where('status', 'paid');

            $transactionCount = $paidQuery->count();
            $totalSales = $paidQuery->sum('total');
            $average = $transactionCount > 0 ? $totalSales / $transactionCount : 0;

            if ($existing && $existing->closed_at) {
                return redirect()->back()->withErrors(['closing' => 'Closing hari ini sudah diverifikasi.']);
            }

            if ($existing) {
                $existing->update([
                    'total_sales' => $totalSales,
                    'transaction_count' => $transactionCount,
                    'average_transaction' => $average,
                    'verified_by' => $user->id,
                    'closed_at' => now(),
                ]);
            } else {
                DailyClosure::create([
                    'warung_id' => $warungId,
                    'date' => today(),
                    'total_sales' => $totalSales,
                    'transaction_count' => $transactionCount,
                    'average_transaction' => $average,
                    'verified_by' => $user->id,
                    'closed_at' => now(),
                ]);
            }

            return redirect()->back()->with('closing_success', 'Closing hari ini berhasil diverifikasi.');
        })->name('dashboard.kasir.closing');
        Route::post('/order/{order}/verify-payment', [OrderController::class, 'verifyPayment'])->name('order.verify-payment');
        Route::post('/order/{order}/payment', [OrderController::class, 'processPayment'])->name('order.payment');
        Route::post('/order/{order}/edit-qty', [OrderController::class, 'editQuantity'])->name('order.edit-qty');
        Route::post('/order/{order}/cancel', [OrderController::class, 'cancelOrder'])->name('order.cancel');
        Route::post('/menu-items/{id}/toggle-stock', 'App\Http\Controllers\MenuItemController@toggleStock')->name('menu.toggle-stock');
    });

    Route::middleware('role:waiter,admin')->group(function () {
        Route::get('/dashboard/waiter', [DashboardController::class, 'waiter'])->name('dashboard.waiter');
        Route::post('/order/{order}/serve', [OrderController::class, 'markServed'])->name('order.serve');
    });

    // Inventory Routes
    Route::middleware('role:inventory,manager,owner,admin')->group(function () {
        Route::get('/dashboard/inventory', [App\Http\Controllers\InventoryController::class, 'index'])->name('dashboard.inventory');
        Route::post('/inventory/ingredient', [App\Http\Controllers\InventoryController::class, 'storeIngredient'])->name('inventory.ingredient.store');
        Route::post('/inventory/stock', [App\Http\Controllers\InventoryController::class, 'updateStock'])->name('inventory.stock.update');
        Route::get('/inventory/hpp/{menuItem}', [App\Http\Controllers\InventoryController::class, 'getHPP'])->name('inventory.hpp');
    });

    // HRD Routes
    Route::middleware('role:hrd,owner,admin')->group(function () {
        Route::get('/dashboard/hrd', [App\Http\Controllers\HRDController::class, 'index'])->name('dashboard.hrd');
        Route::get('/dashboard/hrd/attendance', [App\Http\Controllers\HRDController::class, 'attendance'])->name('dashboard.hrd.attendance');
        Route::get('/dashboard/hrd/payroll', [App\Http\Controllers\HRDController::class, 'payroll'])->name('dashboard.hrd.payroll');
        Route::post('/dashboard/hrd/payroll/generate', [App\Http\Controllers\HRDController::class, 'generatePayroll'])->name('dashboard.hrd.payroll.generate');
    });

    // Manager Routes
    Route::middleware('role:manager,owner,admin')->group(function () {
        Route::get('/dashboard/manager', [App\Http\Controllers\ManagerController::class, 'index'])->name('dashboard.manager');
        Route::post('/manager/void/{order}', [App\Http\Controllers\ManagerController::class, 'voidOrder'])->name('manager.void');
        Route::post('/manager/coupon', [App\Http\Controllers\ManagerController::class, 'createCoupon'])->name('manager.coupon.store');
    });


    // Kitchen (Dapur) Dashboard - Cooking Status
    Route::middleware('role:dapur,admin')->group(function () {
        Route::get('/dashboard/dapur', [DashboardController::class, 'kitchen'])->name('dashboard.kitchen');
        Route::post('/order/{order}/status', [DashboardController::class, 'updateOrderStatus'])->name('order.status-update');
    });

    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now(),
            'service' => 'SmartOrder'
        ]);
    });

    Route::post('/order-items/{item}/status', [DashboardController::class, 'updateOrderItemStatus'])->name('order-items.status');

    // Real-time SSE Endpoints
    Route::get('/dashboard/stream-orders', [DashboardController::class, 'streamOrders'])->name('dashboard.stream');
    Route::get('/order/{order}/stream-status', [OrderController::class, 'streamStatus'])->name('order.stream-status');

    Route::put('/profile', 'App\Http\Controllers\UserController@updateProfile')->name('profile.update');

    // Logout
    Route::post('/logout', function () {
        $user = Auth::user();
        if ($user && in_array($user->role, ['kasir', 'waiter', 'dapur', 'kitchen'])) {
            StaffShift::where('user_id', $user->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});
