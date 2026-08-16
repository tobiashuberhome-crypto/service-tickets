<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cibena-Serviceportal - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('cibena-portal.home') }}">
            <img class="brand-logo" src="{{ asset('img/logo-cibena.png') }}" alt="Cibena Logo">
            <span class="brand-name">Cibena-Serviceportal</span>
        </a>
        <nav class="nav">
            @if (session('cibena_customer_portal_account_id'))
                <a href="{{ route('cibena-portal.dashboard') }}" @class(['active' => request()->routeIs('cibena-portal.dashboard')])>Meine Tickets</a>
                <a href="{{ route('cibena-portal.history') }}" @class(['active' => request()->routeIs('cibena-portal.history')])>Historie</a>
                <form method="post" action="{{ route('cibena-portal.logout') }}" style="display:inline">
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
