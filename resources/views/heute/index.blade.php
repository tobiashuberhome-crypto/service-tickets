@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Heute</h1>
            <p class="muted">Stand {{ now()->format('d.m.Y, H:i') }} Uhr. Was jetzt Aufmerksamkeit braucht.</p>
        </div>
        <div class="button-row">
            <a class="btn" href="{{ route('tickets.create') }}">Neues Ticket</a>
            <a class="btn secondary" href="{{ route('tickets.index') }}">Alle Tickets</a>
        </div>
    </div>

    <div class="today-stats">
        <a class="today-stat" href="{{ route('tickets.index') }}">
            <strong>{{ $counts['open'] }}</strong><span>Offen / in Bearbeitung</span>
        </a>
        <a class="today-stat" href="{{ route('tickets.index') }}">
            <strong>{{ $counts['handed'] }}</strong><span>Bei CG / bei TH</span>
        </a>
        <a class="today-stat {{ $counts['due'] > 0 ? 'today-stat-warn' : '' }}" href="#faellig">
            <strong>{{ $counts['due'] }}</strong><span>Fällig / überfällig</span>
        </a>
        <a class="today-stat {{ $counts['uninvoiced'] > 0 ? 'today-stat-warn' : '' }}" href="#ohne-rechnung">
            <strong>{{ $counts['uninvoiced'] }}</strong><span>Fertig ohne Monatsrechnung</span>
        </a>
        <a class="today-stat {{ $counts['syncErrors'] > 0 ? 'today-stat-danger' : '' }}" href="#sync-fehler">
            <strong>{{ $counts['syncErrors'] }}</strong><span>Sync-Fehler</span>
        </a>
        <a class="today-stat {{ $counts['requests'] > 0 ? 'today-stat-warn' : '' }}" href="{{ route('customer-portal-requests.index') }}">
            <strong>{{ $counts['requests'] }}</strong><span>Neue Kundenanfragen</span>
        </a>
        <a class="today-stat" href="{{ route('interne-tickets.index') }}">
            <strong>{{ $counts['internal'] }}</strong><span>Interne Tickets offen</span>
        </a>
    </div>

    <section class="panel panel-body today-section" id="faellig">
        <h2>Fällig oder überfällig</h2>
        <p class="muted">Frist in den nächsten 3 Tagen oder schon vorbei, noch nicht erledigt.</p>
        @include('heute.partials.ticket-list', ['tickets' => $dueTickets, 'total' => $counts['due'], 'emptyText' => 'Nichts fällig.', 'showDue' => true])
    </section>

    <section class="panel panel-body today-section" id="ohne-rechnung">
        <h2>Fertig, aber noch auf keiner Monatsrechnung</h2>
        <p class="muted">Geiser- und Cibena-Tickets mit Status erledigt oder geliefert. THSS-Tickets zählen hier nicht.</p>
        @include('heute.partials.ticket-list', ['tickets' => $uninvoicedTickets, 'total' => $counts['uninvoiced'], 'emptyText' => 'Alles abgerechnet.'])
    </section>

    <section class="panel panel-body today-section" id="sync-fehler">
        <h2>Sync-Fehler (Dolibarr)</h2>
        @include('heute.partials.ticket-list', ['tickets' => $syncErrorTickets, 'total' => $counts['syncErrors'], 'emptyText' => 'Keine Fehler.', 'showRetry' => true])
    </section>

    <section class="panel panel-body today-section">
        <h2>Neue Kundenanfragen</h2>
        @forelse ($newRequests as $customerRequest)
            <a class="today-item" href="{{ route('customer-portal-requests.show', $customerRequest) }}">
                <div>
                    <strong>{{ $customerRequest->company_name ?: $customerRequest->contact_name }}</strong>
                    <div class="muted">{{ $customerRequest->created_at?->format('d.m.Y') }}</div>
                </div>
                <span class="badge">{{ $customerRequest->statusLabel() }}</span>
            </a>
        @empty
            <p class="muted">Keine neuen Anfragen.</p>
        @endforelse
        @if ($counts['requests'] > $newRequests->count())
            <p class="muted">… und {{ $counts['requests'] - $newRequests->count() }} weitere.</p>
        @endif
    </section>
@endsection
