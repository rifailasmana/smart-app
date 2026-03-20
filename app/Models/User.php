<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'warung_id',
        'whatsapp',
    ];

    const ROLE_OWNER = 'owner';
    const ROLE_HRD = 'hrd';
    const ROLE_MANAGER = 'manager';
    const ROLE_CASHIER = 'kasir';
    const ROLE_WAITER = 'waiter';
    const ROLE_KITCHEN = 'kitchen';
    const ROLE_INVENTORY = 'inventory';
    const ROLE_ADMIN = 'admin';

    public function isOwner() { return $this->role === self::ROLE_OWNER || $this->role === self::ROLE_ADMIN; }
    public function isHRD() { return $this->role === self::ROLE_HRD || $this->isOwner(); }
    public function isManager() { return $this->role === self::ROLE_MANAGER || $this->isOwner(); }
    public function isCashier() { return $this->role === self::ROLE_CASHIER || $this->isManager(); }
    public function isWaiter() { return $this->role === self::ROLE_WAITER || $this->isManager(); }
    public function isKitchen() { return $this->role === self::ROLE_KITCHEN || $this->isManager(); }
    public function isInventory() { return $this->role === self::ROLE_INVENTORY || $this->isManager(); }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function warung()
    {
        return $this->belongsTo(Warung::class);
    }

    public function employeeDetail()
    {
        return $this->hasOne(EmployeeDetail::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
