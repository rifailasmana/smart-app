<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Coupon;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function index()
    {
        $warungId = auth()->user()->warung_id;
        $orders = Order::where('warung_id', $warungId)->orderBy('created_at', 'desc')->limit(10)->get();
        $menuItems = MenuItem::where('warung_id', $warungId)->get();
        
        return view('dashboard.manager', compact('orders', 'menuItems'));
    }

    public function voidOrder(Request $request, Order $order)
    {
        // Manager approval needed for void
        $order->update(['status' => 'cancelled']);
        
        // Log action for audit
        // AuditLog::create([...]);

        return back()->with('success', 'Order voided by Manager');
    }

    public function createCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code',
            'discount_percent' => 'required|numeric|min:1|max:100',
            'valid_for_category' => 'required|in:Regular,Reservation,Majar Priority,Majar Signature',
            'expires_at' => 'nullable|date',
        ]);

        Coupon::create([
            'code' => $request->code,
            'discount_percent' => $request->discount_percent,
            'valid_for_category' => $request->valid_for_category,
            'expires_at' => $request->expires_at,
            'max_uses' => 1,
        ]);

        return back()->with('success', 'Kupon berhasil dibuat');
    }
}
