<?php

use App\Http\Middleware\CheckInternalApiToken;
use App\Http\Middleware\EnsureBasicAuth;
use App\Http\Middleware\EnsureAdminAuth;
use App\Http\Middleware\EnsureCustomerPortalAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'integrations/easyappointments/bookings',
        ]);

        $middleware->web(append: [
            EnsureBasicAuth::class,
        ]);

        $middleware->alias([
            'customer.portal' => EnsureCustomerPortalAuth::class,
            'admin.auth' => EnsureAdminAuth::class,
            'internal.api' => CheckInternalApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
