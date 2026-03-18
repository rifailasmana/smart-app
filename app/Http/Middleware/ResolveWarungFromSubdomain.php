<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Warung;
use Symfony\Component\HttpFoundation\Response;

class ResolveWarungFromSubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get subdomain from request
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // Expected format: warung-code.smartorder.local or warung-code.smartorder.com
        // parts[0] = warung code, parts[1] = smartorder, parts[2] = local/com
        
        if (count($parts) >= 3) {
            $subdomainCode = strtoupper($parts[0]);
            
            // Skip if it's www or admin or other system subdomains
            if (!in_array($subdomainCode, ['WWW', 'ADMIN', 'API', 'MAIL', 'SMARTORDER'])) {
                // Find warung by code
                $warung = Warung::where('code', $subdomainCode)
                    ->orWhere('code', strtolower($subdomainCode))
                    ->first();
                
                if ($warung) {
                    // Store warung in request context
                    $request->attributes->set('warung', $warung);
                    $request->attributes->set('warung_id', $warung->id);
                } else {
                    // Warung not found - redirect to landing
                    return redirect('/');
                }
            }
        }

        return $next($request);
    }
}
