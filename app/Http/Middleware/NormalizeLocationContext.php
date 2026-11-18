<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeLocationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasRole('super_admin') && $user->isLocationScopedUser()) {
            $request->session()->forget('selected_location_id');
        }

        return $next($request);
    }
}
