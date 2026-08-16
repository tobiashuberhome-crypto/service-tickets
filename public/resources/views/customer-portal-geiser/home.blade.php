@extends('layouts.customer-portal-geiser')

@section('content')
    <div class="page-header">
        <div>
            <h1>Il Coccolino-Serviceportal</h1>
            <p class="muted">Tickets fuer den fest hinterlegten Dolibarr-Kunden 9 erfassen, mit eigener Maschinenakte pro Seriennummer.</p>
        </div>
        <a class="btn" href="{{ route('geiser-portal.login') }}">Zum Login</a>
    </div>

    <div class="grid grid-2">
        <section class="panel panel-body stack">
            <h2>So funktioniert es</h2>
            <p class="muted">Die Kundenzuordnung laeuft fest ueber den hinterlegten Dolibarr-Kunden. Im Portal koennen Sie nur die zusaetzlichen Kontakt-, Maschinen- und Annahmedaten pflegen. Diese Angaben bleiben ausschliesslich in der Ticket-Datenbank.</p>
            <ul class="stack" style="padding-left: 18px; margin: 0;">
                <li>Firma und Kundennummer sind fest vorgegeben.</li>
                <li>Seriennummern laden vorhandene Daten automatisch nach.</li>
                <li>Leistungspositionen bleiben intern und werden hier bewusst nicht angezeigt.</li>
            </ul>
        </section>

        <aside class="panel panel-body stack">
            <h2>Bereits freigeschaltet?</h2>
            @if ($account)
                <p class="muted">Sie sind bereits angemeldet fuer {{ $account->company_name }}.</p>
                <a class="btn" href="{{ route('geiser-portal.dashboard') }}">Zu meinen Tickets</a>
            @else
                <p class="muted">Melden Sie sich mit E-Mail und Passwort an oder nutzen Sie alternativ einen Magic Link.</p>
                <a class="btn" href="{{ route('geiser-portal.login') }}">Jetzt anmelden</a>
            @endif
        </aside>
    </div>
@endsection
