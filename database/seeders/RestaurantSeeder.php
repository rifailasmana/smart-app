<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warung;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example restaurant creation
        $restaurants = [
            [
                'name' => 'Restoran Bali Indah',
                'code' => 'BALI',
                'slug' => 'restoran-bali-indah',
                'description' => 'Traditional Indonesian cuisine with modern twist',
                'subscription_tier' => 'professional',
                'monthly_price' => 250000.00,
                'subscription_expires_at' => now()->addDays(30),
                'status' => 'active',
            ],
            [
                'name' => 'Jakarta Food Hub',
                'code' => 'JKT',
                'slug' => 'jakarta-food-hub',
                'description' => 'Urban dining experience with fusion menu',
                'subscription_tier' => 'starter',
                'monthly_price' => 150000.00,
                'subscription_expires_at' => now()->addDays(30),
                'status' => 'active',
            ],
            [
                'name' => 'Surabaya Kitchen',
                'code' => 'SBY',
                'slug' => 'surabaya-kitchen',
                'description' => 'Authentic East Javanese specialties',
                'subscription_tier' => 'enterprise',
                'monthly_price' => 500000.00,
                'subscription_expires_at' => now()->addDays(30),
                'status' => 'active',
            ],
        ];

        foreach ($restaurants as $restaurant) {
            Warung::create($restaurant);
            echo "Created restaurant: {$restaurant['name']} ({$restaurant['code']})\n";
            echo "Subdomain: {$restaurant['slug']}.smartorder.local\n";
            echo "Subscription: {$restaurant['subscription_tier']} - Rp " . number_format($restaurant['monthly_price'], 0) . "/month\n\n";
        }

        $this->command->info('Restaurant seeder completed successfully!');
    }
}
