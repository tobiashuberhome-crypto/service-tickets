# Datei: app\Http\Middleware\EnsureAdminAuth.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Middleware\EnsureAdminAuth.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('admin_user_id')) {
            return $next($request);
        }

        return redirect()->route('admin.login');
    }
}


```
