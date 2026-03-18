<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Owner',
            'email' => 'owner@owner.com',
            'password' => Hash::make('owner'),
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Kasir',
            'email' => 'kasir@kasir.com',
            'password' => Hash::make('kasir'),
            'role' => 'kasir',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Kitchen',
            'email' => 'kitchen@kitchen.com',
            'password' => Hash::make('kitchen'),
            'role' => 'kitchen',
            'email_verified_at' => now(),
        ]);
    }
}
