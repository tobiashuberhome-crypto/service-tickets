# Datei: resources\views\layouts\app.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\layouts\app.blade.php`
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
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('tickets.index') }}">Service Tickets</a>
        <nav class="nav">
            <a href="{{ route('tickets.index') }}" @class(['active' => request()->routeIs('tickets.*')])>Tickets</a>
            <a href="{{ route('customer-portal-requests.index') }}" @class(['active' => request()->routeIs('customer-portal-requests.*')])>Kundenanfragen</a>
            <a href="{{ route('spare-parts.index') }}" @class(['active' => request()->routeIs('spare-parts.*')])>Ersatzteile</a>
            <a href="{{ route('spare-part-categories.index') }}" @class(['active' => request()->routeIs('spare-part-categories.*')])>Kategorien</a>
            <a href="{{ route('warehouse.index') }}" @class(['active' => request()->routeIs('warehouse.*')])>Lagerverwaltung</a>
            <a href="{{ route('history.index') }}" @class(['active' => request()->routeIs('history.*')])>Historie</a>
            <a href="{{ route('machine-documents.index') }}" @class(['active' => request()->routeIs('machine-documents.*')])>PDFs</a>
            <a href="{{ route('service-defaults.index') }}" @class(['active' => request()->routeIs('service-defaults.*')])>Service</a>
            <form method="post" action="{{ route('admin.logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn secondary" style="margin-left: 10px;">Abmelden</button>
            </form>
        </nav>
    </header>

    <main class="page">
        @include('partials.alerts')
        @yield('content')
    </main>
</div>

<script src="{{ asset('js/scanner.js') }}"></script>
@stack('scripts')
</body>
</html>

```
