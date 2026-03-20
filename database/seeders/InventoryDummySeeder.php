<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\StockLog;
use App\Models\RestockRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryDummySeeder extends Seeder
{
    public function run(): void
    {
        $warung = Warung::first();
        if (!$warung) return;

        $user = User::where('role', 'inventory')->first() ?? User::first();
        $manager = User::where('role', 'manager')->first() ?? $user;

        // 1. Create Some Suppliers if not exists
        $suppliers = [
            ['name' => 'Pasar Induk Sayur', 'phone' => '08123456789'],
            ['name' => 'PT. Daging Segar', 'phone' => '08987654321'],
            ['name' => 'Toko Sembako Jaya', 'phone' => '08554433221'],
        ];

        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['name' => $s['name'], 'warung_id' => $warung->id], $s);
        }
        $supplierIds = Supplier::where('warung_id', $warung->id)->pluck('id')->toArray();

        // 2. Create Ingredients with various stock levels
        $ingredients = [
            ['name' => 'Daging Sapi Sirloin', 'category' => 'Daging', 'unit' => 'kg', 'stock' => 5.5, 'min_stock' => 10, 'last_price' => 120000, 'avg_price' => 118000],
            ['name' => 'Beras Premium', 'category' => 'Sembako', 'unit' => 'kg', 'stock' => 50, 'min_stock' => 20, 'last_price' => 15000, 'avg_price' => 14500],
            ['name' => 'Minyak Goreng', 'category' => 'Sembako', 'unit' => 'liter', 'stock' => 2, 'min_stock' => 5, 'last_price' => 18000, 'avg_price' => 17500],
            ['name' => 'Ayam Broiler', 'category' => 'Daging', 'unit' => 'kg', 'stock' => 0, 'min_stock' => 15, 'last_price' => 35000, 'avg_price' => 34000],
            ['name' => 'Telur Ayam', 'category' => 'Sembako', 'unit' => 'kg', 'stock' => 12, 'min_stock' => 5, 'last_price' => 28000, 'avg_price' => 27000],
            ['name' => 'Bawang Merah', 'category' => 'Bumbu', 'unit' => 'kg', 'stock' => 3.2, 'min_stock' => 2, 'last_price' => 40000, 'avg_price' => 38000],
            ['name' => 'Bawang Putih', 'category' => 'Bumbu', 'unit' => 'kg', 'stock' => 1.5, 'min_stock' => 2, 'last_price' => 35000, 'avg_price' => 33000],
            ['name' => 'Cabai Rawit', 'category' => 'Bumbu', 'unit' => 'kg', 'stock' => 0.5, 'min_stock' => 1, 'last_price' => 60000, 'avg_price' => 55000],
        ];

        foreach ($ingredients as $i) {
            $ing = Ingredient::updateOrCreate(
                ['name' => $i['name'], 'warung_id' => $warung->id],
                $i
            );

            // 3. Create dummy logs for each
            // Incoming
            StockLog::create([
                'ingredient_id' => $ing->id,
                'user_id' => $user->id,
                'supplier_id' => $supplierIds[array_rand($supplierIds)],
                'type' => 'incoming',
                'quantity' => rand(10, 50),
                'price' => $i['last_price'],
                'notes' => 'Stok mingguan rutin',
                'created_at' => now()->subDays(rand(1, 5)),
            ]);

            // Usage
            if ($ing->stock > 0) {
                StockLog::create([
                    'ingredient_id' => $ing->id,
                    'user_id' => $user->id,
                    'type' => 'usage',
                    'quantity' => rand(1, 5),
                    'notes' => 'Pemakaian operasional harian',
                    'created_at' => now()->subHours(rand(1, 12)),
                ]);
            }
        }

        // 4. Create Restock Requests
        $lowStockIngredients = Ingredient::where('warung_id', $warung->id)
            ->whereColumn('stock', '<=', 'min_stock')
            ->get();

        foreach ($lowStockIngredients as $ing) {
            RestockRequest::create([
                'warung_id' => $warung->id,
                'user_id' => $manager->id,
                'ingredient_id' => $ing->id,
                'quantity' => $ing->min_stock * 2,
                'notes' => 'Stok sudah di bawah batas minimum, mohon segera restock.',
                'status' => 'pending',
            ]);
        }

        // Add one approved request
        if ($lowStockIngredients->count() > 0) {
            RestockRequest::create([
                'warung_id' => $warung->id,
                'user_id' => $manager->id,
                'ingredient_id' => $lowStockIngredients->first()->id,
                'quantity' => 20,
                'notes' => 'Urgent untuk event besok',
                'status' => 'approved',
                'approved_by' => $manager->id,
                'approved_at' => now(),
            ]);
        }
    }
}
