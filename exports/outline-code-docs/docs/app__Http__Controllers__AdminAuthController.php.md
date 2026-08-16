# Datei: app\Http\Controllers\AdminAuthController.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `app\Http\Controllers\AdminAuthController.php`
- **Stand:** 2026-06-27 13:25:19
- **Typ:** php

## Code

```php
<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = AdminUser::query()
            ->where('username', $data['username'])
            ->where('is_active', true)
            ->first();

        if ($admin && Hash::check($data['password'], $admin->password)) {
            $request->session()->put('admin_user_id', $admin->id);
            $request->session()->regenerate();

            $admin->forceFill([
                'last_login_at' => now(),
            ])->save();

            return redirect()->intended(route('tickets.index'));
        }

        return back()->withErrors(['username' => 'UngÃ¼ltige Zugangsdaten'])->withInput();
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Abgemeldet');
    }
}


```
