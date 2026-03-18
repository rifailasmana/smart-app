<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Recipe;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $warungId = auth()->user()->warung_id;
        $ingredients = Ingredient::where('warung_id', $warungId)->get();
        $suppliers = Supplier::where('warung_id', $warungId)->get();
        
        return view('dashboard.inventory', compact('ingredients', 'suppliers'));
    }

    public function storeIngredient(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'unit' => 'required|string',
            'min_stock' => 'required|numeric|min:0',
        ]);

        Ingredient::create([
            'warung_id' => auth()->user()->warung_id,
            'name' => $request->name,
            'unit' => $request->unit,
            'min_stock' => $request->min_stock,
        ]);

        return back()->with('success', 'Bahan baku berhasil ditambahkan');
    }

    public function updateStock(Request $request)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'type' => 'required|in:incoming,adjustment,waste',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required_if:type,incoming|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $ingredient = Ingredient::lockForUpdate()->find($request->ingredient_id);
            
            if ($request->type === 'incoming') {
                $ingredient->stock += $request->quantity;
                $ingredient->last_price = $request->price;
                // Update average price: (old_stock * old_avg + new_qty * new_price) / total_stock
                $totalVal = ($ingredient->stock - $request->quantity) * $ingredient->avg_price + ($request->quantity * $request->price);
                $ingredient->avg_price = $ingredient->stock > 0 ? $totalVal / $ingredient->stock : $request->price;
            } elseif ($request->type === 'waste' || $request->type === 'usage') {
                $ingredient->stock -= $request->quantity;
            } else {
                // Adjustment
                $ingredient->stock = $request->quantity;
            }

            $ingredient->save();

            StockLog::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'type' => $request->type,
                'quantity' => $request->quantity,
                'price' => $request->price,
                'notes' => $request->notes,
            ]);
        });

        return back()->with('success', 'Stok berhasil diperbarui');
    }

    public function getHPP($menuItemId)
    {
        $recipes = Recipe::with('ingredient')->where('menu_item_id', $menuItemId)->get();
        $hpp = $recipes->sum(function($recipe) {
            return $recipe->quantity * $recipe->ingredient->avg_price;
        });

        return response()->json(['hpp' => $hpp, 'details' => $recipes]);
    }
}
