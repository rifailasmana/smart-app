<?php

namespace App\Http\Controllers;

use App\Models\Warung;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class RestaurantController extends Controller
{
    /**
     * Store a newly created restaurant (warung) in storage.
     * Only admin can access this method.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warungs,name',
            'subdomain' => 'required|string|max:255|unique:warungs,slug',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        try {
            // Auto-generate slug from subdomain if empty
            if (empty($validated['slug'])) {
                $validated['slug'] = $validated['subdomain'];
            }

            $restaurant = Warung::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'subscription_tier' => 'starter',
                'monthly_price' => 150000.00,
                'subscription_expires_at' => now()->addDays(30),
                'status' => $validated['status'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant created successfully!',
                'data' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'slug' => $restaurant->slug,
                    'subdomain' => $restaurant->slug . '.' . env('SMARTORDER_DOMAIN', 'smartapp.local'),
                    'status' => $restaurant->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create restaurant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified restaurant.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $restaurant = Warung::findOrFail($id);
        
        // Owner hanya bisa update resto sendiri, admin bisa update semua
        if ($user->role !== 'admin' && $restaurant->id !== $user->warung_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validation rules
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warungs,name,' . $restaurant->id,
            'code' => 'nullable|string|min:3|max:10|regex:/^[A-Z0-9]+$/|unique:warungs,code,' . $restaurant->id,
            'description' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'nullable|string|max:500',
            'opening_hours' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'max_discount_percent' => 'nullable|numeric|min:0|max:100',
            'require_owner_auth_for_discount' => 'nullable|boolean',
            'google_sheets_enabled' => 'nullable|boolean',
            'google_sheets_spreadsheet_id' => 'nullable|string|max:255',
            'google_sheets_sheet_name' => 'nullable|string|max:100',
            'enable_system_clock' => 'nullable|boolean',
            'system_clock_format' => ['nullable', Rule::in(['24h', '12h'])],
            'subscription_tier' => 'nullable|in:starter,professional,enterprise',
            'monthly_price' => 'nullable|numeric|min:0|max:10000000',
            'status' => 'nullable|in:active,inactive,suspended',
            'features' => 'nullable|array',
        ]);

        try {
            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($restaurant->logo && Storage::disk('public')->exists($restaurant->logo)) {
                    Storage::disk('public')->delete($restaurant->logo);
                }
                $validated['logo'] = $request->file('logo')->store('warungs', 'public');
            } else {
                unset($validated['logo']);
            }

            if ($request->has('max_discount_percent')) {
                $validated['require_owner_auth_for_discount'] = $request->boolean('require_owner_auth_for_discount');
            }

            if ($request->has('google_sheets_enabled')) {
                $validated['google_sheets_enabled'] = $request->boolean('google_sheets_enabled');
            } else {
                $validated['google_sheets_enabled'] = false;
            }

            if ($request->has('enable_system_clock')) {
                $validated['enable_system_clock'] = $request->boolean('enable_system_clock');
            } else {
                $validated['enable_system_clock'] = false;
            }

            // Update slug if name changed
            if (isset($validated['name']) && $validated['name'] !== $restaurant->name) {
                $slug = Str::slug($validated['name']);
                
                // Ensure slug is unique
                $originalSlug = $slug;
                $counter = 1;
                while (Warung::where('slug', $slug)->where('id', '!=', $restaurant->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                $validated['slug'] = $slug;
            }

            $restaurant->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant updated successfully!',
                'data' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'code' => $restaurant->code,
                    'slug' => $restaurant->slug,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update restaurant: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified restaurant.
     */
    public function destroy(Warung $restaurant)
    {
        try {
            // Check if restaurant has orders
            if ($restaurant->orders()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete restaurant with existing orders.',
                ], 400);
            }

            // Check if restaurant has users
            if ($restaurant->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete restaurant with existing users.',
                ], 400);
            }

            $restaurant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Restaurant deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete restaurant: ' . $e->getMessage(),
            ], 500);
        }
    }
}
