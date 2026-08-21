<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Cek Role (Opsional): Redirect ke dashboard sesuai role
                $user = Auth::user();
                if ($user->role === 'super_admin') {
                    return redirect('/admin/dashboard'); // Dashboard Adm
                } elseif ($user->role === 'user') {
                    return redirect('/user/dashboard'); // Dashboard User
                }

                return redirect('/dashboard'); // Dashboard Default
            }
        }

        return $next($request);
    }
}
