<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nik',
        'base_salary',
        'allowance',
        'join_date',
        'bank_name',
        'bank_account',
        'performance_notes',
        'health_certificate_expiry',
        'emergency_contact',
        'uniform_details',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
