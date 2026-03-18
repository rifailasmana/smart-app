<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index($menuItemId)
    {
        $menuItem = MenuItem::findOrFail($menuItemId);
        $warungId = auth()->user()->warung_id;
        
        $recipes = Recipe::with('ingredient')->where('menu_item_id', $menuItemId)->get();
        $ingredients = Ingredient::where('warung_id', $warungId)->get();
        
        return view('dashboard.inventory.recipes', compact('menuItem', 'recipes', 'ingredients'));
    }

    public function store(Request $request, $menuItemId)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        Recipe::updateOrCreate(
            ['menu_item_id' => $menuItemId, 'ingredient_id' => $request->ingredient_id],
            ['quantity' => $request->quantity]
        );

        return back()->with('success', 'Bahan resep berhasil ditambahkan/diperbarui');
    }

    public function destroy($id)
    {
        $recipe = Recipe::findOrFail($id);
        $recipe->delete();
        
        return back()->with('success', 'Bahan resep berhasil dihapus');
    }

    /**
     * Auto-deduct stock for an order
     */
    public static function deductStockForOrder($order)
    {
        DB::transaction(function() use ($order) {
            foreach ($order->items as $item) {
                // Find menu item ID if not present (order_items might only have menu_name)
                // In this app, order_items has menu_item_id
                $menuItem = MenuItem::find($item->menu_item_id);
                if (!$menuItem) continue;

                $recipes = Recipe::where('menu_item_id', $menuItem->id)->get();
                foreach ($recipes as $recipe) {
                    $totalDeduction = $recipe->quantity * $item->qty;
                    
                    $ingredient = Ingredient::lockForUpdate()->find($recipe->ingredient_id);
                    if ($ingredient) {
                        $ingredient->stock -= $totalDeduction;
                        $ingredient->save();

                        \App\Models\StockLog::create([
                            'ingredient_id' => $ingredient->id,
                            'user_id' => auth()->id() ?? 1, // Fallback to system/admin if no auth (e.g. from customer callback)
                            'type' => 'usage',
                            'quantity' => $totalDeduction,
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'notes' => "Pemakaian untuk pesanan #{$order->code}",
                        ]);
                    }
                }
            }
        });
    }
}
