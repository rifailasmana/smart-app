<?php

namespace App\Http\Controllers;

use App\Events\TableChanged;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableManagementController extends Controller
{
    /**
     * Move an active order to a new table.
     */
    public function moveTable(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'new_table_id' => 'required|exists:restaurant_tables,id',
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $newTable = RestaurantTable::findOrFail($validated['new_table_id']);

        // Ensure the new table belongs to the same warung
        if ($order->warung_id !== $newTable->warung_id) {
            return response()->json(['success' => false, 'message' => 'Meja tujuan tidak valid.'], 400);
        }

        // You might want to check if the new table is occupied, but for now, we'll allow it.

        $oldTableName = $order->table->name;
        $order->table_id = $newTable->id;
        $order->save();

        broadcast(new TableChanged($order->fresh()))->toOthers();

        // Optional: Add a log or notification about the move

        return response()->json([
            'success' => true,
            'message' => "Pesanan {$order->code} berhasil dipindahkan dari meja {$oldTableName} ke {$newTable->name}."
        ]);
    }

    /**
     * Merge multiple source orders into a single target order.
     */
    public function mergeTables(Request $request)
    {
        $validated = $request->validate([
            'target_order_id' => 'required|exists:orders,id',
            'source_order_ids' => 'required|array',
            'source_order_ids.*' => 'exists:orders,id',
        ]);

        $targetOrder = Order::findOrFail($validated['target_order_id']);
        $sourceOrders = Order::whereIn('id', $validated['source_order_ids'])->get();

        DB::beginTransaction();
        try {
            foreach ($sourceOrders as $sourceOrder) {
                if ($sourceOrder->id === $targetOrder->id) continue;

                // Re-assign order items to the target order
                $sourceOrder->items()->update(['order_id' => $targetOrder->id]);

                // Recalculate totals for the target order
                $targetOrder->subtotal += $sourceOrder->subtotal;
                $targetOrder->admin_fee += $sourceOrder->admin_fee;
                $targetOrder->total += $sourceOrder->total;

                // Delete the now-empty source order
                $sourceOrder->delete();
            }

            $targetOrder->save();

            broadcast(new TableChanged($targetOrder->fresh()))->toOthers();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meja berhasil digabungkan ke pesanan ' . $targetOrder->code
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menggabungkan meja: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Force reset a table's status to available.
     */
    public function resetTable(RestaurantTable $table)
    {
        $table->update(['status' => 'available']);

        // Check if there are any active orders for this table and void them or move them
        Order::where('table_id', $table->id)
            ->whereNotIn('stage', ['DONE', 'VOID'])
            ->update(['table_id' => null]); // Move to null (Take Away) instead of voiding

        broadcast(new TableChanged($table))->toOthers();

        return response()->json(['success' => true]);
    }
}
