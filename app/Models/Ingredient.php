<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['warung_id', 'name', 'unit', 'stock', 'min_stock', 'last_price', 'avg_price'];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function stockLogs()
    {
        return $this->hasMany(StockLog::class);
    }
}
