<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StockLog;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Reduce stock based on order items and recipes
     */
    public static function reduceStockForOrder(Order $order)
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // Find menu item by name (since OrderItem only stores name sometimes)
                $menuItem = \App\Models\MenuItem::where('warung_id', $order->warung_id)
                    ->where('name', $item->menu_name)
                    ->first();

                if (!$menuItem) continue;

                foreach ($menuItem->recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    $usageQty = $recipe->quantity * $item->qty;

                    // Reduce stock
                    $ingredient->decrement('stock', $usageQty);

                    // Log usage
                    StockLog::create([
                        'ingredient_id' => $ingredient->id,
                        'user_id' => auth()->id() ?? 1, // Fallback to system user if no auth
                        'type' => 'usage',
                        'quantity' => $usageQty,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'notes' => "Usage for order #{$order->code}: {$item->qty}x {$menuItem->name}",
                    ]);
                }
            }
        });
    }

    /**
     * Update ingredient average price and stock for incoming goods
     */
    public static function addStockIncoming(Ingredient $ingredient, float $quantity, float $price, int $supplierId = null, string $notes = null)
    {
        DB::transaction(function () use ($ingredient, $quantity, $price, $supplierId, $notes) {
            $oldStock = $ingredient->stock;
            $oldAvgPrice = $ingredient->avg_price;

            // Update average price: (old_stock * old_avg + new_qty * new_price) / total_stock
            $totalVal = ($oldStock * $oldAvgPrice) + ($quantity * $price);
            $newStock = $oldStock + $quantity;
            
            $ingredient->avg_price = $newStock > 0 ? $totalVal / $newStock : $price;
            $ingredient->last_price = $price;
            $ingredient->stock = $newStock;
            $ingredient->save();

            // Log incoming
            StockLog::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'supplier_id' => $supplierId,
                'type' => 'incoming',
                'quantity' => $quantity,
                'price' => $price,
                'notes' => $notes,
            ]);
        });
    }
}
