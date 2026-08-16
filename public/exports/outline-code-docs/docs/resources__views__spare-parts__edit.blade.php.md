# Datei: resources\views\spare-parts\edit.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\spare-parts\edit.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $sparePart->part_ref }}</h1>
            <p class="muted">{{ $sparePart->label }}</p>
        </div>
        <a class="btn secondary" href="{{ route('spare-parts.index') }}">Zurueck</a>
    </div>

    <form method="post" action="{{ route('spare-parts.update', $sparePart) }}" class="panel panel-body stack">
        @csrf
        @method('PUT')
        @include('spare-parts.partials.form')
        <div class="button-row">
            <button class="btn" type="submit">Speichern</button>
            <button class="btn danger" type="submit" form="delete-spare-part">Loeschen</button>
        </div>
    </form>

    <form id="delete-spare-part" method="post" action="{{ route('spare-parts.destroy', $sparePart) }}">
        @csrf
        @method('DELETE')
    </form>
@endsection

```
