# Datei: app\Http\Middleware\AdminAuthenticate.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Middleware\AdminAuthenticate.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->get('admin_logged_in') === true) {
            return $next($request);
        }

        return redirect()->route('admin.login');
    }
}

```
