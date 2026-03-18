<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Create new user for restaurant
     * Form hanya: nama dan posisi (kasir/waiter/dapur)
     * Generate email: {nama_slug}@{slug_resto}.local
     * Generate password: 8 karakter random
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Auto-generate email if not present
        if (!$request->has('email') && $request->has('username')) {
            $request->merge(['email' => $request->username . '@smartorder.local']);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:owner,kasir,waiter,dapur',
            'whatsapp' => 'nullable|string|max:20',
            'warung_id' => 'nullable|exists:warungs,id', // Hanya untuk admin
        ]);

        try {
            // Tentukan warung_id
            if ($user->role === 'admin') {
                // Admin harus pilih restaurant
                if (empty($validated['warung_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Admin harus memilih restaurant',
                    ], 422);
                }
                $warungId = $validated['warung_id'];
            } else {
                // Owner menggunakan restaurant sendiri
                $warungId = $user->warung_id;
            }

            $newUser = User::create([
                'warung_id' => $warungId,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'whatsapp' => $validated['whatsapp'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'data' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'username' => $newUser->username,
                    'email' => $newUser->email,
                    'role' => $newUser->role,
                    'warung' => $newUser->warung->name ?? '-',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update existing user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:owner,kasir,waiter,dapur',
            'whatsapp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        try {
            // Update password if provided
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'warung' => $user->warung->name ?? '-',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        try {
            $userName = $user->name;
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => "User {$userName} deleted successfully!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users for specific warung (for admin)
     */
    public function warungUsers(Warung $warung)
    {
        $users = User::where('warung_id', $warung->id)
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        try {
            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }
}
