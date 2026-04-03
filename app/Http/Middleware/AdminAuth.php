<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login first');
        }

        $user = Auth::user();
        $isAdmin = in_array($user?->role, ['super_admin', 'tenant_admin'], true);
        $isActive = (bool) ($user?->is_active ?? true);

        if (!$isAdmin || !$isActive) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Admin access required');
        }

        return $next($request);
    }
}
