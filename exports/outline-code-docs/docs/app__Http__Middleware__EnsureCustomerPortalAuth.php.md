# Datei: app\Http\Middleware\EnsureCustomerPortalAuth.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Middleware\EnsureCustomerPortalAuth.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerPortalAuth
{
    public function handle(Request $request, Closure $next, string $sessionKey = 'customer_portal_account_id'): Response
    {
        if (! $request->session()->has($sessionKey)) {
            $loginRoute = $sessionKey === 'geiser_customer_portal_account_id'
                ? 'geiser-portal.login'
                : 'customer-portal.login';

            return redirect()->route($loginRoute)->with('warning', 'Bitte melden Sie sich ueber einen Magic Link an.');
        }

        return $next($request);
    }
}

```
