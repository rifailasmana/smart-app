<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'warung_id',
        'date',
        'opened_by',
        'opened_at',
        'total_sales',
        'transaction_count',
        'average_transaction',
        'verified_by',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'total_sales' => 'decimal:2',
        'average_transaction' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
