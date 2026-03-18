<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    /**
     * Store a newly created menu item
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:1000',
            'harga_promo' => 'nullable|numeric|min:0',
            'promo_aktif' => 'nullable|boolean',
            'category' => 'required|in:makanan,minuman,dessert',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'warung_id' => 'nullable|exists:warungs,id',
        ]);

        if ($user->role === 'admin') {
            $warungId = $validated['warung_id'] ?? null;
        } else {
            $warungId = $user->warung_id;
        }

        if (!$warungId) {
            return response()->json(['error' => 'Restaurant context not found'], 403);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu_items', 'public');
        }

        MenuItem::create([
            'warung_id' => $warungId,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'harga_promo' => $validated['harga_promo'] ?? null,
            'promo_aktif' => $validated['promo_aktif'] ?? false,
            'category' => $validated['category'],
            'image' => $imagePath,
            'active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Menu item ditambahkan']);
    }

    /**
     * Update menu item
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        
        // Admin can update any menu, owner/kasir only their own warung
        if ($user->role === 'admin') {
            $menuItem = MenuItem::findOrFail($id);
        } else {
            $menuItem = MenuItem::where('warung_id', $user->warung_id)->findOrFail($id);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:1000',
            'harga_promo' => 'nullable|numeric|min:0',
            'promo_aktif' => 'nullable|boolean',
            'category' => 'required|in:makanan,minuman,dessert',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
                Storage::disk('public')->delete($menuItem->image);
            }
            $validated['image'] = $request->file('image')->store('menu_items', 'public');
        } else {
            // Keep existing image if no new image uploaded
            unset($validated['image']);
        }

        $menuItem->update($validated);

        return response()->json(['success' => true, 'message' => 'Menu item diperbarui']);
    }

    /**
     * Delete menu item
     */
    public function destroy($id)
    {
        $user = auth()->user();
        
        // Admin can delete any menu, owner/kasir only their own warung
        if ($user->role === 'admin') {
            $menuItem = MenuItem::findOrFail($id);
        } else {
            $menuItem = MenuItem::where('warung_id', $user->warung_id)->findOrFail($id);
        }
        
        // Delete image if exists
        if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
            Storage::disk('public')->delete($menuItem->image);
        }
        
        $menuItem->delete();

        return response()->json(['success' => true, 'message' => 'Menu item dihapus']);
    }

    /**
     * Get menu item by ID (for editing)
     */
    public function show($id)
    {
        $user = auth()->user();
        
        // Admin can view any menu, owner/kasir only their own warung
        if ($user->role === 'admin') {
            $menuItem = MenuItem::findOrFail($id);
        } else {
            $menuItem = MenuItem::where('warung_id', $user->warung_id)->findOrFail($id);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'description' => $menuItem->description,
                'price' => $menuItem->price,
                'harga_promo' => $menuItem->harga_promo,
                'promo_aktif' => $menuItem->promo_aktif,
                'category' => $menuItem->category,
                'image' => $menuItem->image ? asset('storage/' . $menuItem->image) : null,
                'active' => $menuItem->active,
            ]
        ]);
    }

    /**
     * Toggle menu stock (untuk kasir, owner, admin set out of stock)
     */
    public function toggleStock(Request $request, $id)
    {
        $user = auth()->user();
        
        if (!in_array($user->role, ['kasir', 'admin', 'owner'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($user->role === 'admin') {
            $menuItem = MenuItem::findOrFail($id);
        } else {
            $menuItem = MenuItem::where('warung_id', $user->warung_id)
                ->findOrFail($id);
        }
        
        $menuItem->update(['active' => !$menuItem->active]);
        
        return response()->json([
            'success' => true,
            'message' => $menuItem->active ? 'Menu diaktifkan' : 'Menu di-set out of stock',
            'active' => $menuItem->active
        ]);
    }

    public function refreshAllStock(Request $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->role === 'admin') {
            $warungId = $request->input('warung_id');
        } else {
            $warungId = $user->warung_id;
        }

        if (!$warungId) {
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        $updated = MenuItem::where('warung_id', $warungId)
            ->where('active', false)
            ->update(['active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Stok semua menu berhasil di-refresh',
            'updated' => $updated,
        ]);
    }
}
