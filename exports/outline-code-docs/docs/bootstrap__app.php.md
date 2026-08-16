# Datei: bootstrap\app.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `bootstrap\app.php`
- **Stand:** 2026-06-27 13:25:20
- **Typ:** php

## Code

```php
<?php

use App\Http\Middleware\EnsureBasicAuth;
use App\Http\Middleware\EnsureAdminAuth;
use App\Http\Middleware\EnsureCustomerPortalAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureBasicAuth::class,
        ]);

        $middleware->alias([
            'customer.portal' => EnsureCustomerPortalAuth::class,
            'admin.auth' => EnsureAdminAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

```
