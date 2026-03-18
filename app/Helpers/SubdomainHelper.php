<?php

namespace App\Helpers;

use App\Models\Warung;
use Illuminate\Support\Facades\Config;

class SubdomainHelper
{
    /**
     * Get subdomain URL for a warung
     * 
     * @param Warung $warung
     * @param string $path
     * @return string
     */
    public static function getWarungUrl(Warung $warung, string $path = ''): string
    {
        $baseDomain = env('SMARTORDER_DOMAIN', 'smartapp.local');
        $domain = strtolower($warung->code) . '.' . $baseDomain;
        $baseUrl = 'http://' . $domain;
        
        return $path ? $baseUrl . $path : $baseUrl;
    }

    /**
     * Get customer menu URL for a warung
     * 
     * @param Warung $warung
     * @param int|null $tableId
     * @return string
     */
    public static function getMenuUrl(Warung $warung, ?int $tableId = null): string
    {
        $url = static::getWarungUrl($warung, '/');
        
        if ($tableId) {
            $url .= '?meja=' . $tableId;
        }
        
        return $url;
    }

    /**
     * Get QR Code URL for table (links to menu)
     * 
     * @param Warung $warung
     * @param int $tableId
     * @return string
     */
    public static function getTableQRUrl(Warung $warung, int $tableId): string
    {
        return static::getMenuUrl($warung, $tableId);
    }

    public static function generateMenuToken(Warung $warung, ?int $tableId = null): string
    {
        $key = Config::get('app.key');
        $data = strtoupper($warung->code) . '|' . ($tableId ?? '0');
        return hash_hmac('sha256', $data, $key);
    }

    public static function validateMenuToken(Warung $warung, ?int $tableId, ?string $token): bool
    {
        if (!$token) {
            return false;
        }
        $expected = static::generateMenuToken($warung, $tableId);
        return hash_equals($expected, $token);
    }
}
