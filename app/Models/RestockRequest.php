<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockRequest extends Model
{
    protected $fillable = [
        'warung_id',
        'user_id',
        'ingredient_id',
        'quantity',
        'notes',
        'status',
        'approved_by',
        'approved_at',
    ];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
