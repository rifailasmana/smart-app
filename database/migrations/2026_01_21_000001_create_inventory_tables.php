<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->onDelete('cascade');
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // Ingredients
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warung_id')->constrained('warungs')->onDelete('cascade');
            $table->string('name');
            $table->string('unit'); // kg, gr, liter, pcs, etc.
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('last_price', 12, 2)->default(0);
            $table->decimal('avg_price', 12, 2)->default(0);
            $table->timestamps();
        });

        // Recipes (Link MenuItem to Ingredients)
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->decimal('quantity', 12, 4); // amount of ingredient per 1 menu item
            $table->timestamps();
        });

        // Stock Logs (Incoming, Usage, Adjustment)
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', ['incoming', 'usage', 'adjustment', 'waste']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('price', 12, 2)->nullable(); // price per unit for incoming
            $table->string('reference_type')->nullable(); // e.g. 'order', 'purchase'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('suppliers');
    }
};
