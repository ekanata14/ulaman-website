<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        // Support pipe-separated roles: e.g. role:super_admin|user
        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles = array_merge($allowedRoles, explode('|', $role));
        }

        if (empty($allowedRoles) || in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        // Redirect the user to their own dashboard rather than showing a 403
        $targetRoute = match ($userRole) {
            'super_admin' => 'admin.dashboard',
            'user' => 'user.dashboard',
            default => null,
        };

        // Prevent redirect loops
        if ($targetRoute && $request->routeIs($targetRoute)) {
            abort(403, 'Unauthorized.');
        }

        if ($targetRoute) {
            return redirect()->route($targetRoute);
        }

        abort(403);
    }
}
