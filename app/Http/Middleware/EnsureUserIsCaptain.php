<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCaptain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->type !== 'captain') {
            return response()->json([
                'message' => 'Unauthorized for captain role.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
