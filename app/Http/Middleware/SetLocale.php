<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = config('app.locale');

        // Session has highest priority (user just switched locale)
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        }
        // Fall back to the authenticated user's saved locale and sync it to the session
        elseif (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
            Session::put('locale', $locale);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
