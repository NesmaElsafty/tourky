<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCaptain;
use App\Http\Middleware\EnsureUserIsClient;
use App\Http\Middleware\SetLocaleFromAuthenticatedUser;
use App\Http\Middleware\SetLocaleFromRequestHeader;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $model = class_basename((string) $e->getModel());
            $messageKey = match ($model) {
                'Term' => 'api.terms.not_found',
                'Role' => 'api.roles.not_found',
                'Time' => 'api.times.not_found',
                'Point' => 'api.points.not_found',
                'RouteTime' => 'api.route_times.not_found',
                'Car' => 'api.cars.not_found',
                'Notification' => 'api.notifications.not_found',
                'Route' => 'api.routes.not_found',
                'Trip' => 'api.trips.not_found',
                'Ticket' => 'api.tickets.not_found',
                'Reservation' => 'api.reservations.not_found',
                'User' => 'api.users.not_found',
                'Transaction' => 'api.transactions.not_found',
                default => 'api.general.not_found',
            };

            return response()->json([
                'status' => 'error',
                'message' => __($messageKey),
            ], 404);
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) use ($isApiRequest) {
            if (! $isApiRequest($request)) {
                return null;
            }

            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                $model = class_basename((string) $previous->getModel());
                $messageKey = match ($model) {
                    'Term' => 'api.terms.not_found',
                    'Role' => 'api.roles.not_found',
                    'Time' => 'api.times.not_found',
                    'Point' => 'api.points.not_found',
                    'RouteTime' => 'api.route_times.not_found',
                    'Car' => 'api.cars.not_found',
                    'Notification' => 'api.notifications.not_found',
                    'Route' => 'api.routes.not_found',
                    'Trip' => 'api.trips.not_found',
                    'Ticket' => 'api.tickets.not_found',
                    'Reservation' => 'api.reservations.not_found',
                    'User' => 'api.users.not_found',
                    'Transaction' => 'api.transactions.not_found',
                    default => 'api.general.not_found',
                };

                return response()->json([
                    'status' => 'error',
                    'message' => __($messageKey),
                ], 404);
            }

            return response()->json([
                'status' => 'error',
                'message' => __('api.general.not_found'),
            ], 404);
        });
    })->create();
