<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    protected $fillable = ['ingredient_id', 'user_id', 'supplier_id', 'type', 'quantity', 'price', 'reference_type', 'reference_id', 'notes'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
