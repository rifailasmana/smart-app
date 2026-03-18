<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * 
     * Route customer di subdomain harus tetap publik (tidak redirect ke login).
     * Hanya route yang memerlukan auth yang redirect ke login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Route customer di subdomain tidak perlu redirect ke login
        $host = $request->getHost();
        $path = $request->path();
        
        // Jika di subdomain dan akses route customer (menu, order, order-status), biarkan publik
        if (strpos($host, '.') !== false && strpos($host, 'localhost') !== false || strpos($host, 'smartorder') !== false) {
            $customerRoutes = ['menu', 'order', 'order-status'];
            foreach ($customerRoutes as $route) {
                if (str_starts_with($path, $route)) {
                    return null; // Don't redirect, allow public access
                }
            }
        }
        
        if (! $request->expectsJson()) {
            return '/login';
        }
    }
}
