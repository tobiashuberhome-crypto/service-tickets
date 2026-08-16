# Datei: resources\views\spare-parts\create.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\spare-parts\create.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Neues Ersatzteil</h1>
            <p class="muted">Das Ersatzteil bleibt in der Ticket-DB und wird beim Abschluss als freie Dolibarr-Position uebergeben.</p>
        </div>
        <a class="btn secondary" href="{{ route('spare-parts.index') }}">Zurueck</a>
    </div>

    <form method="post" action="{{ route('spare-parts.store') }}" class="panel panel-body stack">
        @csrf
        @include('spare-parts.partials.form')
        <div class="button-row">
            <button class="btn" type="submit">Speichern</button>
            <a class="btn secondary" href="{{ route('spare-parts.index') }}">Abbrechen</a>
        </div>
    </form>
@endsection

```
