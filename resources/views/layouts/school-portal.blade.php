<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Schul-Serviceportal - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .machine-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .machine-card { border: 1px solid var(--line); border-radius: 12px; padding: 1rem; background: #fff; box-shadow: var(--shadow); }
        .machine-card h3 { margin: 0 0 .25rem; font-size: 1rem; }
        .machine-card .machine-serial { font-size:.8rem; color:var(--muted); margin-bottom:.5rem; }
        .badge { display:inline-block; padding:.2rem .6rem; border-radius:999px; font-size:.75rem; font-weight:600; }
        .badge-ok { background:#dcfce7; color:#166534; }
        .badge-ticket { background:#fee2e2; color:#991b1b; }
        .badge-unknown { background:var(--surface-soft); color:var(--accent); border: 1px solid var(--line); }
        .room-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap:1rem; }
        .room-card { border:1px solid var(--line); border-radius:12px; background:#fff; padding:1rem; display:flex; flex-direction:column; gap:.75rem; box-shadow: var(--shadow); }
        .room-card h3 { margin:0; font-size:1.05rem; }
        .room-card .room-meta { margin:0; font-size:.85rem; color:var(--muted); }
        .stat-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:1rem; margin-bottom:1.5rem; }
        .stat-card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:1rem; text-align:center; box-shadow: var(--shadow); }
        .stat-card .stat-value { font-size:2rem; font-weight:700; color:var(--accent); }
        .stat-card .stat-label { font-size:.8rem; color:var(--muted); }
    </style>
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('school-portal.home') }}">
            <img class="brand-logo" src="{{ asset('img/logo-cibena.png') }}" alt="Cibena Logo">
            <span class="brand-name">Schul-Serviceportal</span>
        </a>
        <nav class="nav">
            <a href="{{ route('school-portal.home') }}" @class(['active' => request()->routeIs('school-portal.home')])>Start</a>
            @if (session('school_portal_account_id'))
                <a href="{{ route('school-portal.dashboard') }}" @class(['active' => request()->routeIs('school-portal.dashboard')])>Uebersicht</a>
                <a href="{{ route('school-portal.users.index') }}" @class(['active' => request()->routeIs('school-portal.users.*')])>Benutzer</a>
                <form method="post" action="{{ route('school-portal.logout') }}" style="display:inline">
                    @csrf
                    <button class="nav-button" type="submit">Abmelden</button>
                </form>
            @else
                <a href="{{ route('school-portal.login') }}" @class(['active' => request()->routeIs('school-portal.login')])>Login</a>
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