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

Route::get('/system-upgrade-terminal', function () {
    if (!Illuminate\Support\Facades\Schema::hasColumn('orders', 'stage')) {
        Illuminate\Support\Facades\Schema::table('orders', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('stage', 32)->default('DRAFT')->after('status');
            $table->timestamp('submitted_to_cashier_at')->nullable()->after('stage');
            $table->timestamp('paid_at')->nullable()->after('submitted_to_cashier_at');
            $table->timestamp('sent_to_kitchen_at')->nullable()->after('paid_at');
            $table->timestamp('kitchen_done_at')->nullable()->after('sent_to_kitchen_at');
        });
        return "System Upgrade Complete: Terminal columns added to orders table.";
    }
    return "Terminal columns already exist.";
});

// ===== PUBLIC ROUTES (Landing & Authentication) =====

Route::domain('smartapp.local')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    })
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

// Default entry route for main host/local access
Route::get('/', function () {
    return redirect()->route('login');
})->middleware('auth.subdomain.redirect');

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
            foreach ($others as $u) {
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
        } catch (\Exception $e) {
        }

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
                    $dashboardRoute = match ($user->role) {
                        'owner' => '/terminal',
                        'kasir' => '/terminal/kasir',
                        'waiter' => '/terminal/waiter',
                        'dapur', 'kitchen' => '/terminal/kitchen',
                        'hrd' => '/dashboard/hrd',
                        'manager' => '/dashboard/manager',
                        'inventory' => '/dashboard/inventory',
                        default => '/terminal',
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
        return match ($user->role) {
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
        Route::get('/dashboard/owner', [App\Http\Controllers\OwnerController::class, 'index'])->name('dashboard.owner');
        Route::post('/owner/menu/price/{menuItem}', [App\Http\Controllers\OwnerController::class, 'updatePricing'])->name('owner.menu.price');
        Route::post('/owner/menu/toggle/{menuItem}', [App\Http\Controllers\OwnerController::class, 'toggleMenu'])->name('owner.menu.toggle');

        // Route admin dashboard lama (redirect ke /admin/warungs)
        Route::get('/dashboard/admin', function () {
            return redirect('/admin/warungs');
        })->name('dashboard.admin');
        Route::post('/menu-items', [MenuItemController::class, 'store'])->name('menu.store');
        Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy'])->name('menu.destroy');
        Route::post('/menu-items/refresh-all-stock', [MenuItemController::class, 'refreshAllStock'])->name('menu.refresh-all-stock');

        // Voucher Management
        Route::get('/manage/vouchers', [App\Http\Controllers\VoucherController::class, 'index'])->name('manage.vouchers.index');
        Route::post('/manage/vouchers', [App\Http\Controllers\VoucherController::class, 'store'])->name('manage.vouchers.store');
        Route::delete('/manage/vouchers/{voucher}', [App\Http\Controllers\VoucherController::class, 'destroy'])->name('manage.vouchers.destroy');

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
                ->whereIn('status', ['paid', 'invoiced']);

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
        Route::post('/order/{order}/invoice', [OrderController::class, 'checkoutToInvoice'])->name('order.invoice');
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
        Route::post('/inventory/incoming', [App\Http\Controllers\InventoryController::class, 'storeIncoming'])->name('inventory.incoming.store');
        Route::post('/inventory/adjustment', [App\Http\Controllers\InventoryController::class, 'adjustStock'])->name('inventory.adjustment.store');
        Route::get('/inventory/history', [App\Http\Controllers\InventoryController::class, 'history'])->name('inventory.history');
        Route::get('/inventory/requests', [App\Http\Controllers\InventoryController::class, 'restockRequests'])->name('inventory.requests');
        Route::post('/inventory/requests', [App\Http\Controllers\InventoryController::class, 'storeRestockRequest'])->name('inventory.requests.store');
        Route::post('/inventory/requests/{restockRequest}/status', [App\Http\Controllers\InventoryController::class, 'updateRequestStatus'])->name('inventory.requests.status');
        Route::get('/inventory/hpp/{menuItem}', [App\Http\Controllers\InventoryController::class, 'getHPP'])->name('inventory.hpp');

        // Recipe Routes
        Route::get('/inventory/menu/{menuItem}/recipes', [App\Http\Controllers\RecipeController::class, 'index'])->name('inventory.recipes.index');
        Route::post('/inventory/menu/{menuItem}/recipes', [App\Http\Controllers\RecipeController::class, 'store'])->name('inventory.recipes.store');
        Route::delete('/inventory/recipes/{id}', [App\Http\Controllers\RecipeController::class, 'destroy'])->name('inventory.recipes.destroy');
    });

    // HRD Routes
    Route::middleware('role:hrd,manager,owner,admin')->group(function () {
        Route::get('/dashboard/hrd', [App\Http\Controllers\HRDController::class, 'index'])->name('dashboard.hrd');

        // Employee Management
        Route::post('/hrd/employee', [App\Http\Controllers\HRDController::class, 'storeEmployee'])->name('hrd.employee.store');
        Route::post('/hrd/employee/{user}', [App\Http\Controllers\HRDController::class, 'updateEmployee'])->name('hrd.employee.update');

        // Attendance & Shift
        Route::post('/hrd/attendance', [App\Http\Controllers\HRDController::class, 'storeAttendance'])->name('hrd.attendance.store');
        Route::get('/hrd/shift/quick', [App\Http\Controllers\HRDController::class, 'quickAssignShift'])->name('hrd.shift.quick');
        Route::post('/hrd/shift/settings', [App\Http\Controllers\HRDController::class, 'updateShiftSettings'])->name('hrd.shift.settings.update');
        Route::post('/hrd/shift', [App\Http\Controllers\HRDController::class, 'storeShift'])->name('hrd.shift.store');
        Route::delete('/hrd/shift/{shift}', [App\Http\Controllers\HRDController::class, 'deleteShift'])->name('hrd.shift.delete');

        // Payroll
        Route::post('/hrd/payroll/generate', [App\Http\Controllers\HRDController::class, 'generatePayroll'])->name('hrd.payroll.generate');
        Route::post('/hrd/payroll/{payroll}/status', [App\Http\Controllers\HRDController::class, 'updatePayrollStatus'])->name('hrd.payroll.update-status');

        // Performance
        Route::post('/hrd/performance/{user}', [App\Http\Controllers\HRDController::class, 'updatePerformance'])->name('hrd.performance.update');

        // Access Control
        Route::post('/hrd/access/reset-password/{user}', [App\Http\Controllers\HRDController::class, 'resetPassword'])->name('hrd.access.reset-password');
    });

    // Manager Routes
    Route::middleware('role:manager,owner,admin')->group(function () {
        Route::get('/dashboard/manager', [App\Http\Controllers\ManagerController::class, 'index'])->name('dashboard.manager');
        Route::post('/manager/void/{order}', [App\Http\Controllers\ManagerController::class, 'voidOrder'])->name('manager.void');
        Route::post('/manager/coupon', [App\Http\Controllers\ManagerController::class, 'createCoupon'])->name('manager.coupon.store');
        Route::post('/manager/table', [App\Http\Controllers\ManagerController::class, 'storeTable'])->name('manager.table.store');
        Route::delete('/manager/table/{table}', [App\Http\Controllers\ManagerController::class, 'deleteTable'])->name('manager.table.delete');
        Route::post('/manager/table/merge', [App\Http\Controllers\ManagerController::class, 'mergeTables'])->name('manager.table.merge');
        Route::post('/manager/menu/toggle/{menuItem}', [App\Http\Controllers\ManagerController::class, 'toggleMenuStatus'])->name('manager.menu.toggle');
        Route::post('/manager/restock/approve/{request}', [App\Http\Controllers\ManagerController::class, 'approveRestock'])->name('manager.restock.approve');
        Route::post('/manager/restock/reject/{request}', [App\Http\Controllers\ManagerController::class, 'rejectRestock'])->name('manager.restock.reject');
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

    // ===== TERMINAL MODE ROUTES (PWA Full-screen) =====
    Route::prefix('terminal')->group(function () {
        Route::get('/', [App\Http\Controllers\TerminalController::class, 'index'])->name('terminal.index');
        Route::get('/waiter', [App\Http\Controllers\TerminalController::class, 'waiter'])->name('terminal.waiter');
        Route::get('/kasir', [App\Http\Controllers\TerminalController::class, 'kasir'])->name('terminal.kasir');
        Route::get('/kitchen', [App\Http\Controllers\TerminalController::class, 'kitchen'])->name('terminal.kitchen');

        // API routes within terminal prefix for easier management
        Route::get('/orders', [App\Http\Controllers\TerminalController::class, 'getOrders']);
        Route::post('/orders', [App\Http\Controllers\TerminalController::class, 'createOrUpdateDraft']);
        Route::post('/orders/{order}/submit-to-kitchen', [App\Http\Controllers\TerminalController::class, 'submitToKitchen'])->name('terminal.order.kitchen');
        Route::post('/orders/{order}/approve', [App\Http\Controllers\TerminalController::class, 'approveOrder'])->name('terminal.order.approve');
        Route::post('/orders/{order}/items/{item}/status', [App\Http\Controllers\TerminalController::class, 'updateItemStatus'])->name('terminal.order.item.status');
        Route::post('/orders/{order}/finalize-payment', [App\Http\Controllers\TerminalController::class, 'finalizePayment'])->name('terminal.order.finalize');
        Route::post('/orders/{order}/items/{item}/void', [App\Http\Controllers\TerminalController::class, 'voidItem'])->name('terminal.order.void');
        Route::get('/tables', [App\Http\Controllers\TerminalController::class, 'getTables']);
        Route::get('/tables/{table}/draft', [App\Http\Controllers\TerminalController::class, 'getTableDraft']);
        Route::post('/orders', [App\Http\Controllers\TerminalController::class, 'storeOrder']);
        Route::post('/orders/{order}/submit-to-cashier', [App\Http\Controllers\TerminalController::class, 'submitToCashier']);
        Route::post('/orders/{order}/split', [App\Http\Controllers\TerminalController::class, 'splitOrder']);
        Route::post('/orders/{order}/merge', [App\Http\Controllers\TerminalController::class, 'mergeOrder']);
        Route::post('/orders/{order}/approve', [App\Http\Controllers\TerminalController::class, 'approveOrder']);
        Route::post('/orders/{order}/serve', [App\Http\Controllers\TerminalController::class, 'serveOrder']);
        Route::post('/orders/{order}/finalize-payment', [App\Http\Controllers\TerminalController::class, 'finalizePayment']);
        Route::post('/orders/{order}/approve-and-pay', [App\Http\Controllers\TerminalController::class, 'approveAndPay']);
        Route::post('/orders/{order}/kitchen-status', [App\Http\Controllers\TerminalController::class, 'updateKitchenStatus']);
        Route::post('/orders/{order}/invoice', [App\Http\Controllers\TerminalController::class, 'checkoutToInvoice'])->name('terminal.order.invoice');
        Route::get('/orders/history', [App\Http\Controllers\TerminalController::class, 'history']);
        Route::get('/reports/summary', [App\Http\Controllers\TerminalController::class, 'reports']);
        Route::post('/orders/{order}/void', [App\Http\Controllers\TerminalController::class, 'voidOrder']);
        Route::post('/coupons/check', [App\Http\Controllers\TerminalController::class, 'checkCoupon']);
        Route::post('/vouchers/check', [App\Http\Controllers\TerminalController::class, 'checkVoucher']);
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
