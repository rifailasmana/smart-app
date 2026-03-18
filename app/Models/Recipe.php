<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = ['menu_item_id', 'ingredient_id', 'quantity'];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
