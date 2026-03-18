<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'warung_id',
        'table_id',
        'customer_name',
        'code',
        'status',
        'subtotal',
        'admin_fee',
        'diskon_manual',
        'total',
        'payment_method',
        'payment_channel',
        'customer_phone',
        'queue_number',
        'alasan_diskon',
        'notes',
        'kasir_id',
        'waiter_id',
        'kitchen_id',
        'stage',
        'submitted_to_cashier_at',
        'paid_at',
        'sent_to_kitchen_at',
        'kitchen_done_at',
        'guest_category',
        'order_type',
        'reservation_name',
        'reservation_code',
        'merged_table_ids',
        'is_split_bill',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'diskon_manual' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function kitchen()
    {
        return $this->belongsTo(User::class, 'kitchen_id');
    }
}
