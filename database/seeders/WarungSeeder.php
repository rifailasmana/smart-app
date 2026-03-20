<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warung;
use App\Models\RestaurantTable;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class WarungSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warung = Warung::updateOrCreate(
            ['code' => 'MAJAR'],
            [
                'name' => 'Majar Signature',
                'slug' => 'majar-signature',
                'description' => 'Fine Dining & Traditional Nusantara Cuisine',
                'subscription_tier' => 'professional',
                'monthly_price' => 500000,
                'features' => json_encode(['pos', 'inventory', 'hrd', 'reports', 'qr_order']),
            ]
        );

        // Create Tables
        $tables = [
            ['name' => 'MS-01', 'seats' => 2],
            ['name' => 'MS-02', 'seats' => 4],
            ['name' => 'MS-03', 'seats' => 4],
            ['name' => 'VIP-01', 'seats' => 8],
            ['name' => 'OUT-01', 'seats' => 2],
        ];

        foreach ($tables as $table) {
            RestaurantTable::updateOrCreate(
                [
                    'warung_id' => $warung->id,
                    'name' => $table['name'],
                ],
                [
                    'seats' => $table['seats'],
                    'status' => 'available',
                ]
            );
        }

        // Create Menu Items
        $menuItems = [
            // Signature
            ['name' => 'Majar Signature Steak', 'category' => 'makanan', 'price' => 155000, 'description' => 'Premium Wagyu with special Majar sauce'],
            ['name' => 'Bebek Majar Crispy', 'category' => 'makanan', 'price' => 85000, 'description' => 'Crispy duck with 3 types of sambal'],
            
            // Makanan
            ['name' => 'Nasi Goreng Majar', 'category' => 'makanan', 'price' => 45000, 'description' => 'Signature fried rice with seafood'],
            ['name' => 'Sate Ayam Premium', 'category' => 'makanan', 'price' => 55000, 'description' => 'Tender chicken satay with peanut sauce'],
            
            // Minuman
            ['name' => 'Majar Iced Tea', 'category' => 'minuman', 'price' => 15000, 'description' => 'Special blend iced tea'],
            ['name' => 'Orange Signature', 'category' => 'minuman', 'price' => 25000, 'description' => 'Fresh orange juice with mint'],
        ];

        foreach ($menuItems as $item) {
            MenuItem::updateOrCreate(
                [
                    'warung_id' => $warung->id,
                    'name' => $item['name'],
                ],
                [
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'category' => $item['category'],
                    'active' => true,
                ]
            );
        }

        // Create Users with requested usernames and password '123456'
        $users = [
            ['name' => 'Majar Owner', 'username' => 'owner', 'role' => 'owner'],
            ['name' => 'Majar HRD', 'username' => 'hrd', 'role' => 'hrd'],
            ['name' => 'Majar Manager', 'username' => 'manager', 'role' => 'manager'],
            ['name' => 'Majar Cashier 1', 'username' => 'cashier', 'role' => 'kasir'],
            ['name' => 'Majar Cashier 2', 'username' => 'cashier2', 'role' => 'kasir'],
            ['name' => 'Majar Waiter 1', 'username' => 'waiter', 'role' => 'waiter'],
            ['name' => 'Majar Waiter 2', 'username' => 'waiter2', 'role' => 'waiter'],
            ['name' => 'Majar Kitchen 1', 'username' => 'kitchen', 'role' => 'kitchen'],
            ['name' => 'Majar Kitchen 2', 'username' => 'kitchen2', 'role' => 'kitchen'],
            ['name' => 'Majar Inventory', 'username' => 'inventory', 'role' => 'inventory'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'name' => $u['name'],
                    'email' => $u['username'] . '@majar.local',
                    'password' => Hash::make('123456'),
                    'role' => $u['role'],
                    'warung_id' => $warung->id,
                ]
            );
        }

        // Admin (Super)
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin System',
                'email' => 'admin@smartorder.local',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'warung_id' => null,
            ]
        );
    }
}
