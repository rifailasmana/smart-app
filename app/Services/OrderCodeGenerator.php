<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Warung;
use Carbon\Carbon;

class OrderCodeGenerator
{
    /**
     * Generate unique order code for warung
     * Format: {warung_code}-{DAY}{DATE}-{3digit_sequence}
     * Example: BALI-MON0915-001
     */
    public static function generate(Warung $warung): string
    {
        $today = Carbon::now();
        $dayAbbr = strtoupper($today->format('D')); // MON, TUE, etc
        $dateStr = $today->format('md'); // 0915 (month + day)

        // Get today's sequence number
        $todayOrders = Order::where('warung_id', $warung->id)
            ->whereDate('created_at', $today)
            ->count();

        $sequence = str_pad($todayOrders + 1, 3, '0', STR_PAD_LEFT);

        return "{$warung->code}-{$dayAbbr}{$dateStr}-{$sequence}";
    }

    /**
     * Check if code already exists
     */
    public static function exists(string $code, Warung $warung): bool
    {
        return Order::where('code', $code)
            ->where('warung_id', $warung->id)
            ->exists();
    }
}
