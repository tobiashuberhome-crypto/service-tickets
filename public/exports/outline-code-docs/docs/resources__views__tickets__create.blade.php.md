# Datei: resources\views\tickets\create.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\tickets\create.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Neues Ticket</h1>
            <p class="muted">Beim Speichern wird der Dolibarr-Auftrag als Entwurf angelegt.</p>
        </div>
        <a class="btn secondary" href="{{ route('tickets.index') }}">Zurueck</a>
    </div>

    <form method="post" action="{{ route('tickets.store') }}" class="ticket-layout">
        @csrf
        <div class="panel panel-body">
            @include('tickets.partials.form', ['ticket' => $ticket])
            <div class="button-row">
                <button class="btn" type="submit">Ticket speichern</button>
                <a class="btn secondary" href="{{ route('tickets.index') }}">Abbrechen</a>
            </div>
        </div>

        <aside class="panel panel-body">
            <h2>Ersatzteile</h2>
            <p class="muted">Ersatzteile koennen nach dem ersten Speichern zugeordnet werden, weil dann Kunde, Maschine und Dolibarr-Auftrag feststehen.</p>
        </aside>
    </form>
@endsection

@include('tickets.partials.form-scripts')

```
