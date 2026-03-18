<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warung extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'slug', 'description', 'logo', 'address', 'opening_hours', 'contact_email', 'subscription_tier', 'monthly_price', 'subscription_expires_at', 'status', 'features', 'phone', 'whatsapp_notification', 'max_discount_percent', 'max_discount_amount', 'require_owner_auth_for_discount', 'google_sheets_enabled', 'google_sheets_spreadsheet_id', 'google_sheets_sheet_name', 'google_sheets_last_synced_at', 'enable_system_clock', 'system_clock_format'];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'subscription_expires_at' => 'datetime',
        'whatsapp_notification' => 'boolean',
        'max_discount_percent' => 'integer',
        'max_discount_amount' => 'integer',
        'require_owner_auth_for_discount' => 'boolean',
        'google_sheets_enabled' => 'boolean',
        'google_sheets_last_synced_at' => 'datetime',
        'enable_system_clock' => 'boolean',
        'features' => 'array',
    ];

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Get the full subdomain URL for the restaurant
     */
    public function getSubdomainAttribute()
    {
        return $this->slug . '.' . env('SMARTORDER_DOMAIN', 'smartapp.local');
    }

    /**
     * Scope to get active restaurants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if subscription is active
     */
    public function isSubscriptionActive()
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }
}
