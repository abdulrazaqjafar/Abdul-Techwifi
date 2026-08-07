<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', $request->cookie('locale', config('app.locale', 'en')));

        if (in_array($locale, ['en', 'sw'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
