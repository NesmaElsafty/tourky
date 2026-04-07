<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->type !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized for admin role.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
