<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => __('api.auth.unauthorized'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (($user->type ?? '') !== 'client') {
            return response()->json([
                'message' => __('api.role.unauthorized_client'),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
