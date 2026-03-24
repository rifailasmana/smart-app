<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountReceivable extends Model
{
    protected $table = 'accounts_receivables';

    protected $fillable = [
        'warung_id',
        'order_id',
        'table_id',
        'table_number',
        'customer_name',
        'order_code',
        'subtotal',
        'admin_fee',
        'discount',
        'total',
        'status',
        'revenue_recognized_at',
        'paid_at',
        'cashier_id',
        'cashier_name',
        'items_snapshot',
        'meta',
    ];

    protected $casts = [
        'items_snapshot' => 'array',
        'meta' => 'array',
        'revenue_recognized_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}

