@extends('layouts.school-portal')

@section('content')
    <div class="page-header">
        <div>
            <h1>Schul-Serviceportal</h1>
            <p class="muted">Räume verwalten, Maschinen dokumentieren, Service direkt melden.</p>
        </div>
        @if ($account)
            <a class="btn" href="{{ route('school-portal.dashboard') }}">Zur Übersicht</a>
        @else
            <a class="btn" href="{{ route('school-portal.login') }}">Login anfordern</a>
        @endif
    </div>

    <div class="grid grid-2">
        <section class="panel panel-body stack">
            <h2>So funktioniert es</h2>
            <ul class="stack" style="padding-left:18px; margin:0;">
                <li>Jede Schule sieht ihre Räume und Nähmaschinen auf einen Blick.</li>
                <li>Per Klick auf eine Maschine können Sie ein Ticket erstellen.</li>
                <li>Alle Service- und Reparaturverläufe bleiben übersichtlich dokumentiert.</li>
                <li>Wir melden uns nach jeder Meldung zur weiteren Abstimmung.</li>
            </ul>
        </section>

        <aside class="panel panel-body stack">
            <h2>Bereits freigeschaltet?</h2>
            @if ($account)
                <p class="muted">Sie sind angemeldet als {{ $account->company_name }}.</p>
                <a class="btn" href="{{ route('school-portal.dashboard') }}">Zur Übersicht</a>
            @else
                <p class="muted">Fordern Sie mit Ihrer hinterlegten E-Mail-Adresse einen Login-Link an.</p>
                <a class="btn" href="{{ route('school-portal.login') }}">Magic Link anfordern</a>
            @endif
        </aside>
    </div>
@endsection
