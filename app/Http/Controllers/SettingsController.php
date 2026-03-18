<?php

namespace App\Http\Controllers;

use App\Models\Warung;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Update restaurant settings
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        
        // Only owner can update their restaurant settings
        if ($user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Only restaurant owner can update settings',
            ], 403);
        }

        $warung = $user->warung;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp_notification' => 'boolean',
        ]);

        try {
            $warung->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Restaurant settings updated successfully!',
                'data' => [
                    'name' => $warung->name,
                    'phone' => $warung->phone,
                    'whatsapp_notification' => $warung->whatsapp_notification,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current settings
     */
    public function show()
    {
        $user = auth()->user();
        
        if ($user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Only restaurant owner can view settings',
            ], 403);
        }

        $warung = $user->warung;

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $warung->name,
                'phone' => $warung->phone,
                'whatsapp_notification' => $warung->whatsapp_notification ?? false,
            ]
        ]);
    }
}
