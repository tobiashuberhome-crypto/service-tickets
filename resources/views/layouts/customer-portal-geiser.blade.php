<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Il Coccolino-Serviceportal - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('geiser-portal.home') }}">
            <img class="brand-logo" src="{{ asset('img/logo-cibena.png') }}" alt="Cibena Logo">
            <span class="brand-name">Il Coccolino-Serviceportal</span>
        </a>
        <nav class="nav">
            <a href="{{ route('geiser-portal.home') }}" @class(['active' => request()->routeIs('geiser-portal.home')])>Start</a>
            <a href="{{ route('geiser-portal.login') }}" @class(['active' => request()->routeIs('geiser-portal.login')])>Login</a>
            @if (session('geiser_customer_portal_account_id'))
                <a href="{{ route('geiser-portal.dashboard') }}" @class(['active' => request()->routeIs('geiser-portal.dashboard')])>Meine Tickets</a>
                <a href="{{ route('geiser-portal.history') }}" @class(['active' => request()->routeIs('geiser-portal.history')])>Historie</a>
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
