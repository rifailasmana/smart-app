<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Voucher;
use App\Models\RestockRequest;
use App\Models\ApprovalRequest;
use App\Models\OrderItemVoid;
use App\Models\Ingredient;
use App\Models\AccountReceivable;
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
        // Fetch recent void records for manager review / audit with filtering and pagination
        $pendingApprovals = [];

        $voidQuery = OrderItemVoid::with(['order', 'orderItem', 'menuItem', 'user'])
            ->whereHas('order', function ($oq) use ($warungId) {
                $oq->where('warung_id', $warungId);
            });

        if ($q = $request->get('q')) {
            $voidQuery->where(function ($w) use ($q) {
                $w->where('reason', 'like', "%{$q}%")
                    ->orWhereHas('menuItem', function ($m) use ($q) {
                        $m->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('order', function ($o) use ($q) {
                        $o->where('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%");
                    });
            });
        }

        if ($from = $request->get('from')) {
            $voidQuery->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $voidQuery->whereDate('created_at', '<=', $to);
        }

        if ($by = $request->get('by')) {
            $voidQuery->where('voided_by', $by);
        }

        $voids = $voidQuery->orderBy('created_at', 'desc')
            ->paginate(25)
            ->appends($request->query());

        // 👥 5. STAFF MONITORING
        $staffOnShift = User::where('warung_id', $warungId)
            ->whereIn('role', ['kasir', 'waiter', 'kitchen', 'dapur'])
            ->get(); // In a real app, check shift status

        // 📦 6. INVENTORY CONTROL (LIGHT)
        $ingredients = Ingredient::where('warung_id', $warungId)->get();

        // 🎟️ 7. DISCOUNT / VOUCHER CONTROL
        $vouchers = Voucher::where('warung_id', $warungId)->get();

        // 🔄 8. ORDER MONITORING
        $allActiveOrders = Order::with(['items', 'table'])
            ->where('warung_id', $warungId)
            ->whereIn('status', ['pending', 'preparing', 'ready', 'served'])
            ->orderBy('created_at', 'asc')
            ->get();

        // 🧾 9. OUTSTANDING INVOICES
        $outstandingInvoices = AccountReceivable::where('warung_id', $warungId)
            ->where('status', 'outstanding')
            ->orderBy('created_at', 'desc')
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
            'pendingApprovals',
            'voids',
            'staffOnShift',
            'ingredients',
            'vouchers',
            'allActiveOrders',
            'outstandingInvoices'
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
            'value' => 'required|numeric|min:1|max:100',
        ]);

        $code = 'MAJAR-' . $request->value . '-' . strtoupper(\Illuminate\Support\Str::random(4));

        // Ensure uniqueness
        while (\App\Models\Voucher::where('code', $code)->exists()) {
            $code = 'MAJAR-' . $request->value . '-' . strtoupper(\Illuminate\Support\Str::random(4));
        }

        \App\Models\Voucher::create([
            'warung_id' => auth()->user()->warung_id,
            'code' => $code,
            'type' => 'percentage',
            'value' => $request->value,
            'is_used' => 0,
            'expires_at' => now()->addHour()
        ]);

        return back()->with('success', 'Voucher Instan Berhasil Dibuat: ' . $code);
    }

    // Approval CRUD handlers
    public function approvals(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $approvals = ApprovalRequest::where('warung_id', $warungId)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('dashboard.manager_approvals', compact('approvals'));
    }

    public function createApproval(Request $request)
    {
        return view('dashboard.manager_approvals_create');
    }

    public function storeApproval(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'payload' => 'nullable|array',
            'reason' => 'nullable|string',
        ]);

        ApprovalRequest::create([
            'warung_id' => auth()->user()->warung_id,
            'type' => $data['type'],
            'payload' => $data['payload'] ?? null,
            'requested_by' => auth()->id(),
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('manager.approvals.index')->with('success', 'Approval request dibuat');
    }

    public function editApproval(ApprovalRequest $approval)
    {
        return view('dashboard.manager_approvals_edit', compact('approval'));
    }

    public function updateApproval(Request $request, ApprovalRequest $approval)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'payload' => 'nullable|array',
            'reason' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $approval->update([
            'type' => $data['type'],
            'payload' => $data['payload'] ?? $approval->payload,
            'reason' => $data['reason'] ?? $approval->reason,
            'status' => $data['status'],
        ]);

        return redirect()->route('manager.approvals.index')->with('success', 'Approval request diperbarui');
    }

    public function destroyApproval(ApprovalRequest $approval)
    {
        $approval->delete();
        return redirect()->route('manager.approvals.index')->with('success', 'Approval request dihapus');
    }

    public function processApproval(Request $request, ApprovalRequest $approval)
    {
        $action = $request->get('action'); // approve or reject
        if ($action === 'approve') {
            $approval->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
            ]);
            return redirect()->route('manager.approvals.index')->with('success', 'Request disetujui');
        }

        if ($action === 'reject') {
            $approval->update([
                'status' => 'rejected',
                'processed_by' => auth()->id(),
                'reason' => $request->get('reason') ?? $approval->reason,
            ]);
            return redirect()->route('manager.approvals.index')->with('success', 'Request ditolak');
        }

        return redirect()->back()->with('error', 'Aksi tidak diketahui');
    }
}
