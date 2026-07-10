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
        .machine-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; background: #fff; }
        .machine-card h3 { margin: 0 0 .25rem; font-size: 1rem; }
        .machine-card .machine-serial { font-size:.8rem; color:#64748b; margin-bottom:.5rem; }
        .badge { display:inline-block; padding:.2rem .6rem; border-radius:999px; font-size:.75rem; font-weight:600; }
        .badge-ok { background:#dcfce7; color:#166534; }
        .badge-ticket { background:#fee2e2; color:#991b1b; }
        .badge-unknown { background:#f1f5f9; color:#475569; }
        .room-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap:1rem; }
        .room-card { border:1px solid #e2e8f0; border-radius:10px; background:#fff; padding:1rem; display:flex; flex-direction:column; gap:.75rem; }
        .room-card h3 { margin:0; font-size:1.05rem; }
        .room-card .room-meta { margin:0; font-size:.85rem; color:#64748b; }
        .stat-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:1rem; margin-bottom:1.5rem; }
        .stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:1rem; text-align:center; }
        .stat-card .stat-value { font-size:2rem; font-weight:700; color:#1e293b; }
        .stat-card .stat-label { font-size:.8rem; color:#64748b; }
    </style>
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('school-portal.home') }}">Schul-Serviceportal</a>
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