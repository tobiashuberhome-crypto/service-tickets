# Datei: resources\views\layouts\customer-portal-geiser.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\layouts\customer-portal-geiser.blade.php`
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
    <title>Geiser-Serviceportal - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('geiser-portal.home') }}">Geiser-Serviceportal</a>
        <nav class="nav">
            <a href="{{ route('geiser-portal.home') }}" @class(['active' => request()->routeIs('geiser-portal.home')])>Start</a>
            <a href="{{ route('geiser-portal.login') }}" @class(['active' => request()->routeIs('geiser-portal.login')])>Login</a>
            @if (session('geiser_customer_portal_account_id'))
                <a href="{{ route('geiser-portal.dashboard') }}" @class(['active' => request()->routeIs('geiser-portal.dashboard')])>Meine Tickets</a>
                <form method="post" action="{{ route('geiser-portal.logout') }}" style="display:inline">
                    @csrf
                    <button class="nav-button" type="submit">Abmelden</button>
                </form>
            @endif
        </nav>
    </header>

    <main class="page narrow-page">
        @include('partials.alerts')
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>

```
