<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\SetLocaleFromRequestHeader::class,
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'locale.user' => \App\Http\Middleware\SetLocaleFromAuthenticatedUser::class,
            'type.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'type.captain' => \App\Http\Middleware\EnsureUserIsCaptain::class,
            'type.client' => \App\Http\Middleware\EnsureUserIsClient::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
