<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['warung_id', 'name', 'contact_person', 'phone', 'address'];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }
}
