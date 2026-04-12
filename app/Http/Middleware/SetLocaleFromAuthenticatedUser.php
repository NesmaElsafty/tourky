<?php

namespace App\Http\Middleware;

use App\Support\ApiLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromAuthenticatedUser
{
    /**
     * After Sanctum: prefer the user's stored language when it is en|ar.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            ApiLocale::applyFromUserLanguage($user);
        }

        return $next($request);
    }
}
