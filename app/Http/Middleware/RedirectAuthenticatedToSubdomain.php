<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Warung;

class RedirectAuthenticatedToSubdomain
{
    /**
     * Handle an incoming request.
     * Jika user sudah login dan akses main domain (bukan subdomain), redirect ke subdomain restorannya
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || $request->is('login') || $request->is('login/*')) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user->role === 'admin') {
            return $next($request);
        }

        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) >= 3) {
            return $next($request);
        }

        if ($user->warung_id) {
            $warung = Warung::find($user->warung_id);
            if ($warung) {
                $protocol = $request->getScheme();
                $port = $request->getPort();
                $portSuffix = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
                $domain = env('SMARTORDER_DOMAIN', 'smartapp.local');
                if (!$warung->slug && $warung->code) {
                    $warung->slug = strtolower($warung->code);
                    $warung->save();
                }
                $subdomain = strtolower($warung->slug ?? $warung->code ?? 'default');
                $baseUrl = $protocol . '://' . $subdomain . '.' . $domain . $portSuffix;
                $dashboardRoute = match($user->role) {
                    'owner' => '/dashboard/owner',
                    'kasir' => '/dashboard/kasir',
                    'waiter' => '/dashboard/waiter',
                    'dapur' => '/dashboard/kitchen',
                    default => '/dashboard',
                };

                return redirect($baseUrl . $dashboardRoute);
            }
        }

        return $next($request);
    }
}
