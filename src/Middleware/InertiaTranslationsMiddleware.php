<?php

namespace Thettler\InertiaLocalization\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class InertiaTranslationsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Session::has(config('inertia-localization.current_locale_session_key'))) {
            App::setLocale(Session::get(config('inertia-localization.current_locale_session_key')));
        }

        Inertia::share(
            config('inertia-localization.js.current_locale_key'),
            function () {
                return session(config('inertia-localization.current_locale_session_key'), config('app.locale'));
            }
        );

        return $next($request);
    }
}
