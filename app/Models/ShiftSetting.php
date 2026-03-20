<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSetting extends Model
{
    protected $fillable = ['warung_id', 'type', 'start_time', 'end_time'];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }
}
