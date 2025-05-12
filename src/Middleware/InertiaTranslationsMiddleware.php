<?php

namespace Thettler\InertiaLocalization\Middleware;

use Closure;
use Illuminate\Http\Request;

class InertiaTranslationsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
