<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Jika user tidak authenticated, redirect ke login di host aktif
        if (!auth()->check()) {
            return redirect('/login');
        }

        // Jika roles tidak dispesifikasi, allow
        if (empty($roles)) {
            return $next($request);
        }

        // Check jika user role ada di allowed roles
        if (in_array(auth()->user()->role, $roles)) {
            return $next($request);
        }

        // Jika tidak authorized, return 403
        abort(403, 'Unauthorized access');
    }
}
