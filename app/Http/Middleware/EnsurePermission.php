<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * @param  list<string>  $permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'message' => __('api.auth.unauthorized'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($permissions === []) {
            return $next($request);
        }

        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'message' => __('api.auth.forbidden_permission'),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
