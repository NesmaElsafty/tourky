<?php

namespace App\Http\Middleware;

use App\Support\ApiLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromRequestHeader
{
    /**
     * Set app locale from lang / Accept-Language before auth (guest routes).
     */
    public function handle(Request $request, Closure $next): Response
    {
        ApiLocale::apply(ApiLocale::fromRequest($request));

        return $next($request);
    }
}
