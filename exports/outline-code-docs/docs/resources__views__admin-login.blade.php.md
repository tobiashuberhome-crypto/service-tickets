# Datei: resources\views\admin-login.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\admin-login.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <main class="page" style="max-width: 540px; margin: 60px auto;">
        @include('partials.alerts')

        <div class="panel panel-body">
            <h1 style="margin-top: 0;">Admin Anmeldung</h1>
            <p class="muted">Bitte mit Admin-Benutzer aus der Datenbank anmelden.</p>

            <form method="post" action="{{ route('admin.login.post') }}">
                @csrf
                <div>
                    <label for="username">Benutzername</label>
                    <input id="username" name="username" value="{{ old('username') }}" required>
                </div>
                <div style="margin-top: 12px;">
                    <label for="password">Passwort</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div style="margin-top: 16px;">
                    <button class="btn" type="submit">Anmelden</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>


```
