<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Recipe;
use App\Models\MenuItem;
use App\Models\RestockRequest;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function index()
    {
        $warungId = auth()->user()->warung_id;
        
        // Stats for Dashboard
        $stats = [
            'total_items' => Ingredient::where('warung_id', $warungId)->count(),
            'low_stock' => Ingredient::where('warung_id', $warungId)->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0)->count(),
            'out_of_stock' => Ingredient::where('warung_id', $warungId)->where('stock', '<=', 0)->count(),
            'incoming_today' => StockLog::whereHas('ingredient', fn($q) => $q->where('warung_id', $warungId))
                ->where('type', 'incoming')->whereDate('created_at', today())->count(),
            'usage_today' => StockLog::whereHas('ingredient', fn($q) => $q->where('warung_id', $warungId))
                ->where('type', 'usage')->whereDate('created_at', today())->count(),
        ];

        $ingredients = Ingredient::where('warung_id', $warungId)->orderBy('name')->get();
        $suppliers = Supplier::where('warung_id', $warungId)->get();
        $menuItems = MenuItem::where('warung_id', $warungId)->with('recipes.ingredient')->get();
        $recentLogs = StockLog::with(['ingredient', 'user'])
            ->whereHas('ingredient', fn($q) => $q->where('warung_id', $warungId))
            ->orderBy('created_at', 'desc')->limit(10)->get();

        // Data for Stock Overview (Chart)
        $stockMovement = StockLog::whereHas('ingredient', fn($q) => $q->where('warung_id', $warungId))
            ->whereIn('type', ['incoming', 'usage'])
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN type = 'incoming' THEN quantity ELSE 0 END) as incoming"),
                DB::raw("SUM(CASE WHEN type = 'usage' THEN quantity ELSE 0 END) as total_usage")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Data for Usage Tab
        $usageLogs = StockLog::with(['ingredient', 'user'])
            ->whereHas('ingredient', fn($q) => $q->where('warung_id', $warungId))
            ->where('type', 'usage')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'usage_page');

        // Data for Alert Tab
        $alertIngredients = Ingredient::where('warung_id', $warungId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc')
            ->get();
        
        return view('dashboard.inventory', compact(
            'ingredients', 
            'suppliers', 
            'menuItems', 
            'stats', 
            'recentLogs',
            'stockMovement',
            'usageLogs',
            'alertIngredients'
        ));
    }

    /**
     * Store a new ingredient
     */
    public function storeIngredient(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category' => 'nullable|string',
            'unit' => 'required|string',
            'min_stock' => 'required|numeric|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
        ]);

        $ingredient = Ingredient::create([
            'warung_id' => auth()->user()->warung_id,
            'name' => $request->name,
            'category' => $request->category,
            'unit' => $request->unit,
            'stock' => $request->initial_stock ?? 0,
            'min_stock' => $request->min_stock,
        ]);

        if ($ingredient->stock > 0) {
            StockLog::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'type' => 'adjustment',
                'quantity' => $ingredient->stock,
                'notes' => 'Initial stock on creation',
            ]);
        }

        return back()->with('success', 'Bahan baku berhasil ditambahkan');
    }

    /**
     * Record incoming stock and update prices
     */
    public function storeIncoming(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        
        StockService::addStockIncoming(
            $ingredient, 
            $validated['quantity'], 
            $validated['price'], 
            $validated['supplier_id'], 
            $validated['notes']
        );

        return back()->with('success', 'Stok masuk berhasil dicatat dan HPP diperbarui');
    }

    /**
     * Manual adjustment
     */
    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'type' => 'required|in:plus,minus',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|in:rusak,hilang,selisih,lainnya',
            'notes' => 'nullable|string',
        ]);

        $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
        $qty = $validated['type'] === 'plus' ? $validated['quantity'] : -$validated['quantity'];

        $ingredient->increment('stock', $qty);

        StockLog::create([
            'ingredient_id' => $ingredient->id,
            'user_id' => auth()->id(),
            'type' => $qty > 0 ? 'adjustment' : 'waste',
            'quantity' => abs($qty),
            'notes' => "Adjustment ({$validated['reason']}): " . $validated['notes'],
        ]);

        return back()->with('success', 'Koreksi stok berhasil disimpan');
    }

    /**
     * Get detailed history/mutation
     */
    public function history(Request $request)
    {
        $warungId = auth()->user()->warung_id;
        $logs = StockLog::with(['ingredient', 'user', 'supplier'])
            ->whereHas('ingredient', fn($q) => $q->where('warung_id', $warungId))
            ->when($request->ingredient_id, fn($q) => $q->where('ingredient_id', $request->ingredient_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('dashboard.inventory-history', compact('logs'));
    }

    /**
     * Restock requests management
     */
    public function restockRequests()
    {
        $warungId = auth()->user()->warung_id;
        $requests = RestockRequest::with(['ingredient', 'user', 'approvedBy'])
            ->where('warung_id', $warungId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $ingredients = Ingredient::where('warung_id', $warungId)->get();

        return view('dashboard.inventory-requests', compact('requests', 'ingredients'));
    }

    public function storeRestockRequest(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        RestockRequest::create([
            'warung_id' => auth()->user()->warung_id,
            'user_id' => auth()->id(),
            'ingredient_id' => $validated['ingredient_id'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Permintaan restock berhasil dikirim');
    }

    public function updateRequestStatus(Request $request, RestockRequest $restockRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,done',
        ]);

        $updateData = [
            'status' => $validated['status'],
        ];

        if ($validated['status'] === 'approved') {
            $updateData['approved_by'] = auth()->id();
            $updateData['approved_at'] = now();
        }

        $restockRequest->update($updateData);

        return back()->with('success', 'Status permintaan diperbarui');
    }

    /**
     * Get HPP Calculation details for API/Modal
     */
    public function getHPP($menuItemId)
    {
        $menuItem = MenuItem::with('recipes.ingredient')->findOrFail($menuItemId);
        $hpp = $menuItem->recipes->sum(function($recipe) {
            return $recipe->quantity * $recipe->ingredient->avg_price;
        });

        return response()->json([
            'menu_name' => $menuItem->name,
            'price' => $menuItem->price,
            'hpp' => $hpp,
            'margin' => $menuItem->price > 0 ? (($menuItem->price - $hpp) / $menuItem->price) * 100 : 0,
            'details' => $menuItem->recipes->map(fn($r) => [
                'ingredient' => $r->ingredient->name,
                'qty' => $r->quantity,
                'unit' => $r->ingredient->unit,
                'avg_price' => $r->ingredient->avg_price,
                'subtotal' => $r->quantity * $r->ingredient->avg_price,
            ])
        ]);
    }
}
