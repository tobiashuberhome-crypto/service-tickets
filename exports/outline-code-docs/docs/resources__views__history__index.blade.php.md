# Datei: resources\views\history\index.blade.php

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `resources\views\history\index.blade.php`
- **Stand:** 2026-06-27 13:25:18
- **Typ:** blade

## Code

```blade
@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1>Historie</h1>
            <p class="muted">Auswertungen zu verbrauchten Ersatzteilen und Maschinen-Typen.</p>
        </div>
    </div>

    <form class="panel panel-body" method="get" action="{{ route('history.index') }}">
        <div class="form-row">
            <div>
                <label for="from">Von</label>
                <input id="from" type="date" name="from" value="{{ $from }}">
            </div>
            <div>
                <label for="to">Bis</label>
                <input id="to" type="date" name="to" value="{{ $to }}">
            </div>
            <div class="button-row" style="align-items: end;">
                <button class="btn" type="submit">Auswerten</button>
                <a class="btn secondary" href="{{ route('history.index') }}">Alle anzeigen</a>
            </div>
        </div>
    </form>

    <div style="height: 18px;"></div>

    <div class="grid grid-3 history-grid">
        <div class="panel panel-body">
            <h2>Umschlagshaeufigkeit Ersatzteile</h2>
            <p class="muted">Berechnung: Summe verbrauchter Menge aus Ticketpositionen.</p>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Bezeichnung</th>
                        <th>Menge</th>
                        <th>Positionen</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($partsTurnover as $row)
                        <tr>
                            <td>{{ $row->part_ref_snapshot }}</td>
                            <td>{{ $row->label_snapshot }}</td>
                            <td>{{ number_format((float) $row->consumed_quantity, 2, ',', '.') }}</td>
                            <td>{{ $row->position_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Keine Daten vorhanden.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel panel-body">
            <h2>Maschinen-Typ Service</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Typ</th><th>Tickets</th></tr></thead>
                    <tbody>
                    @forelse ($serviceMachines as $row)
                        <tr><td>{{ $row->machine_type }}</td><td>{{ $row->ticket_count }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">Keine Daten vorhanden.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel panel-body">
            <h2>Maschinen-Typ Reparatur</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Typ</th><th>Tickets</th></tr></thead>
                    <tbody>
                    @forelse ($repairMachines as $row)
                        <tr><td>{{ $row->machine_type }}</td><td>{{ $row->ticket_count }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">Keine Daten vorhanden.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

```
