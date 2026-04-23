<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCaptain;
use App\Http\Middleware\EnsureUserIsClient;
use App\Http\Middleware\SetLocaleFromAuthenticatedUser;
use App\Http\Middleware\SetLocaleFromRequestHeader;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$isApiRequest = static function (Request $request): bool {
    $path = ltrim($request->getPathInfo(), '/');

    return str_starts_with($path, 'api/') || $path === 'api';
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) use ($isApiRequest): void {
        $middleware->redirectGuestsTo(function (Request $request) use ($isApiRequest): ?string {
            if ($isApiRequest($request)) {
                return null;
            }

            return Route::has('login')
                ? route('login')
                : null;
        });

        $middleware->api(prepend: [
            SetLocaleFromRequestHeader::class,
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'locale.user' => SetLocaleFromAuthenticatedUser::class,
            'type.admin' => EnsureUserIsAdmin::class,
            'type.captain' => EnsureUserIsCaptain::class,
            'type.client' => EnsureUserIsClient::class,
            'permission' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($isApiRequest): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) use ($isApiRequest): bool {
            return $isApiRequest($request) || $request->expectsJson();
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            return response()->json(['message' => __('api.auth.unauthorized')], 401);
        });
    })->create();
