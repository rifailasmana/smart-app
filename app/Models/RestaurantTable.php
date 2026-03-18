<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    use HasFactory;

    protected $fillable = ['warung_id', 'name', 'seats', 'status'];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}
