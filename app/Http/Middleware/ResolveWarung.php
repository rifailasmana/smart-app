<?php

namespace App\Http\Middleware;

use App\Models\Warung;
use Closure;
use Illuminate\Http\Request;

class ResolveWarung
{
    public function handle(Request $request, Closure $next)
    {
        $subdomain = $request->route('warung_code');
        
        if (!$subdomain) {
            return redirect(env('APP_URL', 'http://smartapp.local'));
        }
        
        $warung = Warung::where('slug', $subdomain)
            ->orWhere('code', $subdomain)
            ->first();
            
        if (!$warung) {
            abort(404, 'Restaurant not found');
        }
        
        view()->share('warung', $warung);
        $request->attributes->set('warung', $warung);
        
        return $next($request);
    }
}
