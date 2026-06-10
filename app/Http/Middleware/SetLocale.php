<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language');

        // Check for specific custom header 'X-Locale' or 'locale' if you prefer
        if ($request->hasHeader('X-Locale')) {
            $locale = $request->header('X-Locale');
        }

        // If the header has multiple languages (e.g. en-US,en;q=0.9,ar;q=0.8), we can just take the primary language
        if ($locale) {
            $locale = substr($locale, 0, 2);
        }

        // Check if the requested language is supported
        $supportedLocales = ['en', 'ar'];

        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
        } else {
            App::setLocale(config('app.locale'));
        }

        return $next($request);
    }
}
