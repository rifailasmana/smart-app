<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'warung_id',
        'code',
        'type',
        'value',
        'is_used',
        'category_restriction',
        'used_at',
        'expired_at',
    ];

    protected $casts = [
        'is_used' => 'integer',
        'value' => 'decimal:2',
        'used_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }
}
