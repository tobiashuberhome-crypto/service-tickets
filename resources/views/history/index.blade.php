@extends('layouts.app')

@php
    $serialNumber = $serialNumber ?? '';
    $serialHistory = $serialHistory ?? null;
@endphp

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
            <div>
                <label for="serial_number">Seriennummer</label>
                <input id="serial_number" name="serial_number" value="{{ $serialNumber }}" placeholder="optional fuer Ticket-Historie">
            </div>
            <div class="button-row" style="align-items: end;">
                <button class="btn" type="submit">Auswerten</button>
                <a class="btn secondary" href="{{ route('history.index') }}">Alle anzeigen</a>
            </div>
        </div>
    </form>

    <div style="height: 18px;"></div>

    @if ($serialNumber !== '')
        <div class="panel panel-body" style="margin-bottom: 18px;">
            <h2>Ticket-Historie zur Seriennummer {{ $serialNumber }}</h2>
            @if (($serialHistory['history']['count'] ?? 0) > 0)
                <p class="muted">
                    {{ $serialHistory['history']['count'] }} Ticket(s) gefunden.
                    @if (!empty($serialHistory['history']['last_acceptance_date']))
                        Letzte Annahme: {{ $serialHistory['history']['last_acceptance_date'] }}.
                    @endif
                </p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Status</th>
                                <th>Annahmedatum</th>
                                <th>Maschine</th>
                                <th>Kunde</th>
                                <th>Ansprechpartner</th>
                                <th>Erstellt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (($serialHistory['history']['tickets'] ?? []) as $historyTicket)
                                <tr>
                                    <td><a href="{{ $historyTicket['url'] }}">{{ $historyTicket['ticket_number'] }}</a></td>
                                    <td>{{ $historyTicket['status_label'] }}</td>
                                    <td>{{ $historyTicket['acceptance_date'] ?: '-' }}</td>
                                    <td>{{ $historyTicket['machine_label'] ?: '-' }}</td>
                                    <td>{{ $historyTicket['customer_name'] ?: '-' }}</td>
                                    <td>{{ $historyTicket['contact_name'] ?: '-' }}</td>
                                    <td>{{ $historyTicket['created_at'] ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="muted">Zu dieser Seriennummer wurden keine Tickets gefunden.</p>
            @endif
        </div>
    @endif

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
