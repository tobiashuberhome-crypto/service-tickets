# Datei: app\Http\Middleware\EnsureBasicAuth.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Middleware\EnsureBasicAuth.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->is('kundenportal') ||
            $request->is('kundenportal/*') ||
            $request->is('admin/login') ||
            $request->is('login')
        ) {
            return $next($request);
        }

        if ($request->session()->has('admin_user_id')) {
            return $next($request);
        }

        $user = config('services.basic_auth.user');
        $password = config('services.basic_auth.password');

        if (blank($user) || blank($password)) {
            return $next($request);
        }

        $givenUser = (string) $request->getUser();
        $givenPassword = (string) $request->getPassword();

        if (hash_equals((string) $user, $givenUser) && hash_equals((string) $password, $givenPassword)) {
            return $next($request);
        }

        return response('Authentication required', 401, [
            'WWW-Authenticate' => 'Basic realm="Service Tickets"',
        ]);
    }
}

```
