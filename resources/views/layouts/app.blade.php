<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script>document.documentElement.classList.add('js');</script>
    @include('partials.pwa')
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('tickets.index') }}">Service Tickets</a>
        <button type="button" class="btn secondary nav-toggle" data-mobile-toggle="#main-nav" aria-expanded="false" aria-controls="main-nav">Menü</button>
        <nav class="nav" id="main-nav">
            <a href="{{ route('heute.index') }}" @class(['active' => request()->routeIs('heute.*')])>Heute</a>
            <a href="{{ route('tickets.index') }}" @class(['active' => request()->routeIs('tickets.*')])>Tickets</a>
            <a href="{{ route('customer-portal-requests.index') }}" @class(['active' => request()->routeIs('customer-portal-requests.*')])>Kundenanfragen</a>
            <a href="{{ route('monthly-invoices.index') }}" @class(['active' => request()->routeIs('monthly-invoices.*')])>Monatsrechnungen</a>
            <a href="{{ route('spare-parts.index') }}" @class(['active' => request()->routeIs('spare-parts.*')])>Ersatzteile</a>
            <a href="{{ route('spare-part-categories.index') }}" @class(['active' => request()->routeIs('spare-part-categories.*')])>Kategorien</a>
            <a href="{{ route('warehouse.index') }}" @class(['active' => request()->routeIs('warehouse.*')])>Lagerverwaltung</a>
            <a href="{{ route('history.index') }}" @class(['active' => request()->routeIs('history.*')])>Historie</a>
            <a href="{{ route('machine-documents.index') }}" @class(['active' => request()->routeIs('machine-documents.*')])>PDFs</a>
            <a href="{{ route('service-defaults.index') }}" @class(['active' => request()->routeIs('service-defaults.*')])>Service</a>
            <a href="{{ route('portal-accounts.index') }}" @class(['active' => request()->routeIs('portal-accounts.*')])>Portale</a>
            <a href="{{ route('interne-tickets.index') }}" @class(['active' => request()->routeIs('interne-tickets.*')])>Intern</a>
            <a href="{{ route('trainings-app.index') }}" @class(['active' => request()->routeIs('trainings-app.*')])>Trainings-App</a>
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

<script>
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-mobile-toggle]');
    if (!button) return;
    const target = document.querySelector(button.dataset.mobileToggle);
    if (!target) return;
    const open = target.classList.toggle('open');
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
});
</script>
<script src="{{ asset('js/scanner.js') }}"></script>
@stack('scripts')
</body>
</html>
