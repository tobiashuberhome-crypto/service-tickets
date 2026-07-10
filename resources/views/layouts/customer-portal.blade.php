<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kundenportal - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('customer-portal.home') }}">Kundenportal</a>
        <nav class="nav">
            <a href="{{ route('customer-portal.home') }}" @class(['active' => request()->routeIs('customer-portal.home')])>Start</a>
            <a href="{{ route('customer-portal.login') }}" @class(['active' => request()->routeIs('customer-portal.login')])>Magic Link</a>
            @if (session('customer_portal_account_id'))
                <a href="{{ route('customer-portal.dashboard') }}" @class(['active' => request()->routeIs('customer-portal.dashboard')])>Meine Tickets</a>
                <form method="post" action="{{ route('customer-portal.logout') }}" style="display:inline">
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
</body>
</html>
