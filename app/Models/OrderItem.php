<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'menu_item_id', 'menu_name', 'qty', 'price', 'note', 'status'];

    protected $touches = ['order'];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
