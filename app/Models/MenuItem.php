<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'warung_id',
        'name',
        'description',
        'price',
        'harga_promo',
        'promo_aktif',
        'category',
        'image',
        'active',
    ];

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function getHppAttribute()
    {
        return $this->recipes->sum(function($recipe) {
            return $recipe->quantity * $recipe->ingredient->avg_price;
        });
    }

    public function getProfitAttribute()
    {
        return $this->price - $this->hpp;
    }

    protected $casts = [
        'price' => 'decimal:2',
        'harga_promo' => 'decimal:2',
        'promo_aktif' => 'boolean',
        'active' => 'boolean',
    ];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }
}
