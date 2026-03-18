<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    /**
     * Split an order into multiple bills based on selected items.
     */
    public function splitBill(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'bills' => 'required|array',
            'bills.*.items' => 'required|array',
            'bills.*.items.*.order_item_id' => 'required|exists:order_items,id',
            'bills.*.items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        DB::beginTransaction();
        try {
            foreach ($validated['bills'] as $billData) {
                $billTotal = 0;
                $billItems = [];

                foreach ($billData['items'] as $itemData) {
                    $orderItem = OrderItem::findOrFail($itemData['order_item_id']);
                    // Ensure the item belongs to the correct order
                    if ($orderItem->order_id !== $order->id) {
                        throw new \Exception("Item tidak sesuai dengan pesanan.");
                    }
                    // Ensure requested quantity is not more than available
                    if ($itemData['quantity'] > $orderItem->qty) {
                        throw new \Exception("Jumlah split melebihi jumlah pesanan item.");
                    }

                    $billTotal += $orderItem->price * $itemData['quantity'];
                    $billItems[] = $itemData;
                }

                $bill = Bill::create([
                    'order_id' => $order->id,
                    'bill_code' => 'B-' . uniqid(),
                    'total' => $billTotal,
                    'status' => 'unpaid',
                ]);

                foreach ($billItems as $item) {
                    BillItem::create([
                        'bill_id' => $bill->id,
                        'order_item_id' => $item['order_item_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pesanan berhasil di-split.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal split pesanan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Apply a coupon to an order.
     */
    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'coupon_code' => 'required|string',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $coupon = Coupon::where('code', $validated['coupon_code'])->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Kupon tidak valid.'], 404);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Kupon sudah kedaluwarsa.'], 400);
        }

        if ($coupon->uses >= $coupon->max_uses) {
            return response()->json(['success' => false, 'message' => 'Kupon sudah habis digunakan.'], 400);
        }

        if ($coupon->valid_for_category !== $order->category) {
            return response()->json(['success' => false, 'message' => "Kupon tidak berlaku untuk kategori pesanan '{$order->category}'."], 400);
        }

        // Apply discount
        $discountAmount = ($order->subtotal * $coupon->discount_percent) / 100;
        $order->total = max(0, $order->total - $discountAmount);
        $order->coupon_id = $coupon->id; // Assuming you add a coupon_id to orders table
        $order->save();

        $coupon->increment('uses');

        return response()->json([
            'success' => true, 
            'message' => "Kupon '{$coupon->code}' berhasil digunakan.",
            'new_total' => $order->total
        ]);
    }
}
